<?php
/**
 * Crawl4AI Scraping Engine for Listings and Articles
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Crawler {

    protected $api_url;
    protected $api_token;

    public function __construct() {
        $this->api_url   = rtrim(get_option('news_scrapper_crawl4ai_url', defined('CRAWL4AI_API_URL') ? CRAWL4AI_API_URL : 'http://crawl4ai.51.222.83.114.sslip.io'), '/');
        $this->api_token = get_option('news_scrapper_crawl4ai_token', defined('CRAWL4AI_API_TOKEN') ? CRAWL4AI_API_TOKEN : '8Qz8RKv72nEBB$');
    }

    /**
     * Check if Crawl4AI microservice is active
     */
    public function ping() {
        $res = wp_remote_get($this->api_url . '/health', array(
            'timeout' => 5,
        ));

        if (is_wp_error($res)) {
            return array('success' => false, 'message' => $res->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($res);
        $body = json_decode(wp_remote_retrieve_body($res), true);

        if ($code === 200 && isset($body['status']) && $body['status'] === 'ok') {
            return array('success' => true, 'version' => $body['version'] ?? '0.9.x');
        }

        return array('success' => false, 'message' => 'Status ' . $code);
    }

    /**
     * Step 1: Scrape Category/Listing page and discover article links with pagination
     *
     * @param int    $feed_id
     * @param string $source_url
     * @param int    $max_pages
     * @return array
     */
    public function crawl_listing($feed_id, $source_url, $max_pages = 3) {
        $discovered_urls = array();
        $pages_crawled = 0;
        $current_url = $source_url;

        $upload_dir = NEWS_SCRAPPER_UPLOADS_DIR . '/listings';
        wp_mkdir_p($upload_dir);

        for ($page = 1; $page <= $max_pages; $page++) {
            if (empty($current_url)) {
                break;
            }

            // Call Crawl4AI for Markdown
            $response = wp_remote_post($this->api_url . '/md', array(
                'timeout' => 90,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ),
                'body'    => wp_json_encode(array(
                    'url' => esc_url_raw($current_url),
                    'f'   => 'raw',
                )),
            ));

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                break;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $markdown = $body['markdown'] ?? '';

            if (empty($markdown)) {
                break;
            }

            $pages_crawled++;

            // Save listing snapshot
            $file_name = sprintf('feed_%d_p%d_%s.md', $feed_id, $page, date('Ymd_His'));
            file_put_contents($upload_dir . '/' . $file_name, $markdown);

            // Extract links from markdown: [Title](URL)
            preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/i', $markdown, $matches, PREG_SET_ORDER);
            $base_domain = parse_url($source_url, PHP_URL_HOST);

            $page_articles = 0;
            foreach ($matches as $match) {
                $link_title = trim($match[1]);
                $link_url   = trim($match[2]);

                // Filter out non-article links (privacy, login, terms, home, etc.)
                if ($this->is_likely_article_url($link_url, $base_domain, $link_title)) {
                    $enqueued = News_Scraper_DB::enqueue_article($feed_id, $link_url, $link_title);
                    if ($enqueued) {
                        $discovered_urls[] = $link_url;
                        $page_articles++;
                    }
                }
            }

            // Check next page URL for pagination
            $next_url = $this->detect_next_page_url($markdown, $current_url, $page);
            if ($next_url && $next_url !== $current_url) {
                $current_url = $next_url;
            } else {
                break;
            }
        }

        return array(
            'success'         => true,
            'pages_crawled'   => $pages_crawled,
            'discovered_count'=> count($discovered_urls),
            'discovered_urls' => $discovered_urls,
        );
    }

    /**
     * Check if a URL looks like an actual news article
     */
    protected function is_likely_article_url($url, $base_domain, $title) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || strpos($host, $base_domain) === false) {
            return false;
        }

        // Must have reasonable title length
        if (strlen($title) < 15 || str_word_count($title) < 3) {
            return false;
        }

        // Exclude common navigation paths
        $exclude_patterns = array(
            '/about', '/contact', '/privacy', '/terms', '/login', '/signup', '/subscribe',
            '/tag/', '/category/', '/author/', '/page/', '/wp-content/', '/search',
            '#', 'mailto:', 'javascript:', 'tel:'
        );

        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        foreach ($exclude_patterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return false;
            }
        }

        // Must have slug-like ending or date in path
        if (preg_match('/\d{4}\/\d{2}\/|[a-z0-9\-]{8,}\/?$/i', $path)) {
            return true;
        }

        return (strlen($path) > 12);
    }

    /**
     * Detect next pagination URL
     */
    protected function detect_next_page_url($markdown, $current_url, $page_num) {
        $next_page = $page_num + 1;

        // Check for "Next" or page number in markdown links
        if (preg_match('/\[(?:next|older|›|»|page\s*' . $next_page . '|\b' . $next_page . '\b)\]\((https?:\/\/[^\)]+)\)/i', $markdown, $m)) {
            return $m[1];
        }

        // Check if query param exists (?page=N or &p=N)
        if (preg_match('/([?&]page=)(\d+)/i', $current_url)) {
            return preg_replace('/([?&]page=)(\d+)/i', '${1}' . $next_page, $current_url);
        }
        if (preg_match('/([?&]p=)(\d+)/i', $current_url)) {
            return preg_replace('/([?&]p=)(\d+)/i', '${1}' . $next_page, $current_url);
        }

        // Check path pagination (/page/2/)
        if (preg_match('/\/page\/\d+\/?/i', $current_url)) {
            return preg_replace('/\/page\/\d+\/?/i', '/page/' . $next_page . '/', $current_url);
        }

        return false;
    }

    /**
     * Step 2: Deep Scrape single article A to Z
     *
     * @param int    $queue_id
     * @param string $article_url
     * @param int    $feed_id
     * @return array
     */
    public function crawl_article($queue_id, $article_url, $feed_id) {
        $upload_dir = NEWS_SCRAPPER_UPLOADS_DIR . '/articles';
        wp_mkdir_p($upload_dir);

        // Fetch markdown with Crawl4AI
        $response = wp_remote_post($this->api_url . '/md', array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode(array(
                'url' => esc_url_raw($article_url),
                'f'   => 'fit',
            )),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($body['markdown'])) {
            return array('success' => false, 'error' => 'HTTP ' . $code . ': Empty content');
        }

        $markdown = $body['markdown'];

        // Save raw markdown file on disk
        $hash = hash('sha256', $article_url);
        $file_name = sprintf('feed_%d_%s.md', $feed_id, substr($hash, 0, 16));
        $file_path = $upload_dir . '/' . $file_name;
        file_put_contents($file_path, $markdown);

        // Extract metadata: headline, featured image, text
        $headline = '';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            $headline = trim($m[1]);
        }

        // Extract images: ![alt](url)
        $images = array();
        if (preg_match_all('/!\[(.*?)\]\((https?:\/\/[^\s\)]+)\)/i', $markdown, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $img_url = $match[2];
                // Exclude tiny icons, avatars, tracking pixels
                if (!preg_match('/avatar|icon|logo|pixel|banner|1x1/i', $img_url)) {
                    $images[] = $img_url;
                }
            }
        }

        $featured_image = !empty($images) ? $images[0] : null;

        // Update queue item
        News_Scraper_DB::update_queue_item($queue_id, array(
            'status'       => 'scraped',
            'title_raw'    => $headline,
            'md_file_path' => $file_path,
        ));

        return array(
            'success'        => true,
            'headline'       => $headline,
            'markdown'       => $markdown,
            'featured_image' => $featured_image,
            'all_images'     => $images,
            'file_path'      => $file_path,
        );
    }
}
