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
        $new_enqueued_count = 0;
        $pages_crawled = 0;
        $current_url = trim($source_url);

        $upload_dir = NEWS_SCRAPPER_UPLOADS_DIR . '/listings';
        wp_mkdir_p($upload_dir);

        // Check if source URL is an RSS feed
        if ($this->is_rss_url($current_url)) {
            return $this->crawl_rss_listing($feed_id, $current_url, $upload_dir);
        }

        $base_host = parse_url($current_url, PHP_URL_HOST);

        for ($page = 1; $page <= $max_pages; $page++) {
            if (empty($current_url)) {
                break;
            }

            // Call Crawl4AI /crawl with full browser rendering
            $payload = array(
                'urls' => array($current_url),
                'crawler_config' => array(
                    'delay_before_return_html' => 1.5,
                ),
            );

            $response = wp_remote_post($this->api_url . '/crawl', array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ),
                'body'    => wp_json_encode($payload),
            ));

            if (is_wp_error($response)) {
                break;
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                break;
            }

            $resp_data = json_decode(wp_remote_retrieve_body($response), true);
            $result = $resp_data['results'][0] ?? null;

            if (!$result || empty($result['success'])) {
                break;
            }

            $pages_crawled++;

            // Extract Markdown content
            $raw_markdown = '';
            if (is_array($result['markdown'])) {
                $raw_markdown = !empty($result['markdown']['raw_markdown']) ? $result['markdown']['raw_markdown'] : ($result['markdown']['markdown_with_citations'] ?? '');
            } elseif (is_string($result['markdown'])) {
                $raw_markdown = $result['markdown'];
            }

            // Save listing snapshot .md file
            $file_name = sprintf('feed_%d_p%d_%s.md', $feed_id, $page, date('Ymd_His'));
            $file_header = sprintf(
                "# Listing Snapshot: Feed #%d (Page %d)\n- Source: %s\n- Crawled: %s\n- Crawler Engine: Crawl4AI (Chromium/Playwright)\n\n---\n\n",
                $feed_id,
                $page,
                $current_url,
                current_time('mysql')
            );
            file_put_contents($upload_dir . '/' . $file_name, $file_header . $raw_markdown);

            // 1. Discover links from structured Crawl4AI internal links
            $internal_links = $result['links']['internal'] ?? array();
            $page_candidates = array();

            foreach ($internal_links as $link_obj) {
                $href  = trim($link_obj['href'] ?? '');
                $title = trim($link_obj['text'] ?? ($link_obj['title'] ?? ''));

                if ($this->is_valid_article_url($href, $base_host, $title)) {
                    if (!isset($page_candidates[$href])) {
                        $page_candidates[$href] = $title;
                    }
                }
            }

            // 2. Discover links from Markdown syntax: [Title](URL) and Citations ⟨N⟩ URL: Text
            if (!empty($raw_markdown)) {
                if (preg_match_all('/\[([^\]]{10,250})\]\((https?:\/\/[^\s\)\#]+)\)/i', $raw_markdown, $m, PREG_SET_ORDER)) {
                    foreach ($m as $match) {
                        $href  = trim($match[2]);
                        $title = trim($match[1]);
                        if ($this->is_valid_article_url($href, $base_host, $title)) {
                            if (!isset($page_candidates[$href])) {
                                $page_candidates[$href] = $title;
                            }
                        }
                    }
                }

                // Crawl4AI citation pattern: ⟨123⟩ https://...: ![Title](...)
                if (preg_match_all('/⟨\d+⟩\s*(https?:\/\/[^\s:]+):\s*(?:!\[(.*?)\]\([^\)]+\)|([^\n]+))/i', $raw_markdown, $m, PREG_SET_ORDER)) {
                    foreach ($m as $match) {
                        $href  = trim($match[1]);
                        $title = trim(!empty($match[2]) ? $match[2] : ($match[3] ?? ''));
                        if ($this->is_valid_article_url($href, $base_host, $title)) {
                            if (!isset($page_candidates[$href])) {
                                $page_candidates[$href] = $title;
                            }
                        }
                    }
                }
            }

            // Enqueue all unique valid articles
            foreach ($page_candidates as $art_url => $art_title) {
                $discovered_urls[] = $art_url;
                $enqueued = News_Scraper_DB::enqueue_article($feed_id, $art_url, $art_title);
                if ($enqueued) {
                    $new_enqueued_count++;
                }
            }

            // Detect next page for pagination
            $next_url = $this->detect_pagination_url($internal_links, $raw_markdown, $current_url, $page);
            if ($next_url && $next_url !== $current_url) {
                $current_url = $next_url;
            } else {
                break;
            }
        }

        $discovered_urls = array_unique($discovered_urls);

        return array(
            'success'          => true,
            'pages_crawled'    => $pages_crawled,
            'discovered_count' => count($discovered_urls),
            'discovered_urls'  => $discovered_urls,
            'new_enqueued'     => $new_enqueued_count,
        );
    }

    /**
     * Check if a URL looks like an RSS / Atom feed
     */
    protected function is_rss_url($url) {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        return (bool) preg_match('/(\.xml|rss|feed|atom)(\/.*)?$/i', $path);
    }

    /**
     * Parse RSS/Atom feed directly if given as source URL
     */
    protected function crawl_rss_listing($feed_id, $source_url, $upload_dir) {
        $response = wp_remote_get($source_url, array('timeout' => 30));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array('success' => false, 'pages_crawled' => 0, 'discovered_count' => 0);
        }

        $xml_content = wp_remote_retrieve_body($response);
        $file_name = sprintf('feed_%d_rss_%s.md', $feed_id, date('Ymd_His'));
        file_put_contents($upload_dir . '/' . $file_name, "# RSS Feed Snapshot\n" . $source_url . "\n\n" . $xml_content);

        $discovered_urls = array();
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if ($xml) {
            $items = isset($xml->channel->item) ? $xml->channel->item : (isset($xml->entry) ? $xml->entry : array());
            foreach ($items as $item) {
                $link = (string) (isset($item->link['href']) ? $item->link['href'] : $item->link);
                $title = (string) $item->title;
                if (!empty($link) && filter_var($link, FILTER_VALIDATE_URL)) {
                    $enqueued = News_Scraper_DB::enqueue_article($feed_id, $link, $title);
                    if ($enqueued) {
                        $discovered_urls[] = $link;
                    }
                }
            }
        }

        return array(
            'success'          => true,
            'pages_crawled'    => 1,
            'discovered_count' => count($discovered_urls),
            'discovered_urls'  => $discovered_urls,
        );
    }

    /**
     * Determine if a URL is an actual news article
     */
    protected function is_valid_article_url($url, $base_host, $title) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        // Domain or subdomain match
        if (stripos($host, str_replace('www.', '', $base_host)) === false && stripos($base_host, str_replace('www.', '', $host)) === false) {
            return false;
        }

        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        // Exclude system, account, policy, and common noise paths
        $exclude_patterns = array(
            'about', 'contact', 'privacy', 'terms', 'cookie', 'disclaimer',
            'login', 'signup', 'register', 'signin', 'auth', 'subscribe',
            'account', 'profile', 'search', 'feedback', 'sitemap', 'licensing',
            'reprints', 'adinfo', 'plans', 'guce', 'consent', 'wp-admin',
            'wp-content', 'wp-json', 'feed', 'rss', 'watchlist', 'preferences',
            'newsletter', 'press-release'
        );

        foreach ($exclude_patterns as $pattern) {
            if (strpos($path, '/' . $pattern) !== false || strpos($path, $pattern . '/') !== false) {
                return false;
            }
        }

        // Exclude generic categories and tag indexes with no article slug
        if (preg_match('/\/(analysis|markets|investing|stocks|business|tech|politics|economy|category|topic|tag|section)\/?$/i', $path)) {
            return false;
        }

        // Extract last segment (slug)
        $trimmed_path = trim($path, '/');
        $segments = explode('/', $trimmed_path);
        $slug = end($segments);

        if (empty($slug)) {
            return false;
        }

        // Rule 1: Must have a date pattern: /YYYY/MM/ or /YYYY-MM-DD/ or -YYYY-MM-DD
        if (preg_match('/(\/\d{4}[\/\-]\d{2}|\d{4}-\d{2}-\d{2})/', $path)) {
            return (strlen($slug) > 8);
        }

        // Rule 2: Must have article indicator directory: /articles/ or /story/ or /report/
        if (preg_match('/(\/articles\/|\/story\/|\/report\/)/', $path)) {
            return (strlen($slug) > 12);
        }

        // Rule 3: Must end with .html or .htm with a long slug (> 15 chars)
        if (preg_match('/\.html?$/i', $slug) && strlen($slug) > 18) {
            return true;
        }

        // Rule 4: Must contain article ID: e.g. -12345678 or /12345678
        if (preg_match('/[-\/]\d{6,}\/?$/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Detect next pagination URL
     */
    protected function detect_pagination_url($internal_links, $markdown, $current_url, $page_num) {
        $next_page = $page_num + 1;

        // 1. Check structured internal links for next page number or "Next" label
        foreach ($internal_links as $l) {
            $href = $l['href'] ?? '';
            $text = strtolower(trim($l['text'] ?? ($l['title'] ?? '')));

            if (empty($href) || $href === $current_url) {
                continue;
            }

            // Matches "next", "older", "page 2", "2"
            if ($text === (string)$next_page || $text === 'next' || $text === 'next page' || $text === 'older' || $text === '›' || $text === '»') {
                return $href;
            }

            // Path ending in /page/2/ or /2/
            if (preg_match('/\/page\/' . $next_page . '\/?$/i', $href) || preg_match('/\/' . $next_page . '\/?$/i', $href)) {
                return $href;
            }
        }

        // 2. Check query parameter pagination: ?page=N or &p=N
        if (preg_match('/([?&]page=)(\d+)/i', $current_url)) {
            return preg_replace('/([?&]page=)(\d+)/i', '${1}' . $next_page, $current_url);
        }
        if (preg_match('/([?&]p=)(\d+)/i', $current_url)) {
            return preg_replace('/([?&]p=)(\d+)/i', '${1}' . $next_page, $current_url);
        }

        // 3. Check path pagination: /page/1/ -> /page/2/
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

        // Call Crawl4AI /crawl with full browser rendering
        $payload = array(
            'urls' => array($article_url),
            'crawler_config' => array(
                'delay_before_return_html' => 1.5,
            ),
        );

        $response = wp_remote_post($this->api_url . '/crawl', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $resp_data = json_decode(wp_remote_retrieve_body($response), true);
        $result = $resp_data['results'][0] ?? null;

        if ($code !== 200 || !$result || empty($result['success'])) {
            return array('success' => false, 'error' => 'HTTP ' . $code . ': Unable to crawl article');
        }

        // Extract raw markdown
        $raw_markdown = '';
        if (is_array($result['markdown'])) {
            $raw_markdown = !empty($result['markdown']['raw_markdown']) ? $result['markdown']['raw_markdown'] : ($result['markdown']['markdown_with_citations'] ?? '');
        } elseif (is_string($result['markdown'])) {
            $raw_markdown = $result['markdown'];
        }

        if (empty($raw_markdown)) {
            return array('success' => false, 'error' => 'Empty markdown content returned');
        }

        // Save COMPLETE raw markdown on disk for archive/audit
        $hash = hash('sha256', $article_url);
        $file_name = sprintf('feed_%d_%s.md', $feed_id, substr($hash, 0, 16));
        $file_path = $upload_dir . '/' . $file_name;
        file_put_contents($file_path, $raw_markdown);

        // Extract structured datapoints (Headline, Author, Date, Highlights, Clean Body, Images)
        $datapoints = $this->extract_article_datapoints($result, $raw_markdown, $article_url);

        // Extract content images
        $images = array();
        if (preg_match_all('/!\[(.*?)\]\((https?:\/\/[^\s\)]+)\)/i', $raw_markdown, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $img_url = $match[2];
                if (!preg_match('/avatar|icon|logo|pixel|banner|1x1|spacer|button/i', $img_url)) {
                    $images[] = $img_url;
                    if (empty($datapoints['featured_image'])) {
                        $datapoints['featured_image'] = $img_url;
                    }
                }
            }
        }

        // Update queue item in DB with structured datapoints
        $update_fields = array(
            'status'          => 'scraped',
            'title_raw'       => $datapoints['headline'],
            'author'          => $datapoints['author'],
            'datapoints_json' => wp_json_encode($datapoints),
            'md_file_path'    => $file_path,
        );
        if (!empty($datapoints['published_date'])) {
            $update_fields['published_date'] = $datapoints['published_date'];
        }
        News_Scraper_DB::update_queue_item($queue_id, $update_fields);

        return array(
            'success'          => true,
            'headline'         => $datapoints['headline'],
            'markdown'         => $datapoints['clean_body'],
            'raw_markdown'     => $raw_markdown,
            'featured_image'   => $datapoints['featured_image'],
            'all_images'       => $images,
            'file_path'        => $file_path,
            'metadata'         => $result['metadata'] ?? array(),
            'datapoints'       => $datapoints,
        );
    }

    /**
     * Extract genuine structured datapoints from HTML, metadata, and markdown
     */
    public static function extract_article_datapoints($result, $raw_markdown, $article_url) {
        $metadata = $result['metadata'] ?? array();
        $html = $result['html'] ?? '';

        // 1. Headline determination
        $headline = '';
        if (!empty($metadata['title']) && strtolower(trim($metadata['title'])) !== 'not found') {
            $headline = trim($metadata['title']);
        } elseif (!empty($metadata['og:title'])) {
            $headline = trim($metadata['og:title']);
        } elseif (preg_match('/^#\s+(.+)$/m', $raw_markdown, $m)) {
            $headline = trim($m[1]);
        }
        $headline = preg_replace('/\s*[-|–]\s*(CNBC|Yahoo Finance|Reuters|TechCrunch|Bloomberg|MarketWatch|BBC News|CNN|Forbes|Wall Street Journal).*$/i', '', $headline);

        // 2. Author determination
        $author = '';
        if (!empty($metadata['author'])) {
            $author = trim($metadata['author']);
        } elseif (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $author = trim($m[1]);
        } elseif (preg_match('/"@type"\s*:\s*"Person"\s*,\s*"name"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $author = trim($m[1]);
        } elseif (preg_match('/(?:^|\n)(?:By|Author:)\s+([A-Z][a-zA-Z\.\s]{2,35})(?:\n|$)/i', $raw_markdown, $m)) {
            $author = trim($m[1]);
        }

        // 3. Published Date determination
        $published_date = null;
        $raw_date_str = '';
        if (preg_match('/"(?:datePublished|uploadDate)"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $raw_date_str = $m[1];
        } elseif (preg_match('/<meta[^>]+property=["\']article:published_time["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $raw_date_str = $m[1];
        } elseif (preg_match('/Published\s+([A-Za-z]+,?\s+[A-Za-z]+\s+\d{1,2},?\s+\d{4}[^\n<]*)/i', $raw_markdown, $m)) {
            $raw_date_str = $m[1];
        }
        if (!empty($raw_date_str)) {
            $ts = strtotime($raw_date_str);
            if ($ts && $ts > 0) {
                $published_date = date('Y-m-d H:i:s', $ts);
            }
        }

        // 4. Key Highlights determination
        $highlights = array();
        if (preg_match('/(?:Key Points|Key Takeaways|Highlights|At a Glance)\s*\n((?:\s*[\*\-]\s+[^\n]+\n?)+)/i', $raw_markdown, $m)) {
            $bullet_lines = explode("\n", trim($m[1]));
            foreach ($bullet_lines as $bl) {
                $b_clean = trim(preg_replace('/^[\*\-]\s+/', '', trim($bl)));
                if (!empty($b_clean) && strlen($b_clean) > 15) {
                    $highlights[] = $b_clean;
                }
            }
        }
        if (empty($highlights) && !empty($metadata['description'])) {
            $highlights[] = trim($metadata['description']);
        }

        // 5. Featured Image determination
        $featured_image = '';
        if (!empty($metadata['og:image']) && filter_var($metadata['og:image'], FILTER_VALIDATE_URL)) {
            $featured_image = $metadata['og:image'];
        } elseif (!empty($metadata['twitter:image']) && filter_var($metadata['twitter:image'], FILTER_VALIDATE_URL)) {
            $featured_image = $metadata['twitter:image'];
        }

        // 6. Clean Editorial Body
        $clean_body = self::extract_clean_article_body($raw_markdown, $headline);

        // 7. Source Domain
        $source_domain = parse_url($article_url, PHP_URL_HOST);

        return array(
            'headline'       => $headline,
            'author'         => $author,
            'published_date' => $published_date,
            'highlights'     => $highlights,
            'clean_body'     => $clean_body,
            'featured_image' => $featured_image,
            'source_domain'  => $source_domain,
            'source_url'     => $article_url,
        );
    }

    /**
     * Extract pure editorial article body, stripping site navigation chrome and footer widgets
     *
     * @param string $raw_md
     * @param string $headline
     * @return string
     */
    public static function extract_clean_article_body($raw_md, $headline = '') {
        $lines = explode("\n", $raw_md);
        $in_article = false;
        $article_lines = array();

        // Footer termination keywords
        $footer_triggers = array(
            'subscribe to', 'licensing & reprints', 'news tips', 'sign up for', 'advertise with us',
            'terms of service', 'market data terms', 'all rights reserved', 'related tickers',
            'trending stories', 'more from', 'watch livestream', 'choose cnbc as your preferred',
            'read next', 'privacy policy', 'cookie notice', 'follow us on', 'about yahoo',
            'feedback', 'trending tickers', 'disclaimer:', 'copyright ©', '© 20'
        );

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($in_article) {
                    $article_lines[] = '';
                }
                continue;
            }

            // Start capturing when headline or H1 is reached
            if (!$in_article) {
                if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                    $in_article = true;
                    $article_lines[] = '# ' . (!empty($headline) ? $headline : $m[1]);
                    continue;
                }
                // Or if headline matches line
                if (!empty($headline) && stripos($trimmed, substr($headline, 0, 25)) !== false) {
                    $in_article = true;
                    $article_lines[] = '# ' . $headline;
                    continue;
                }
                continue;
            }

            // Check if we hit footer or disclaimer boundaries
            $lower = strtolower($trimmed);
            $hit_footer = false;
            foreach ($footer_triggers as $trigger) {
                if (strpos($lower, $trigger) !== false && strlen($trimmed) < 140) {
                    $hit_footer = true;
                    break;
                }
            }
            if ($hit_footer) {
                break;
            }

            // Filter out in-article navigation noise, share buttons and widgets
            if (preg_match('/^\[(?:Skip|Livestream|Watchlist|Sign In|Create Free Account|Join|Menu|Watch Now|Listen|Zoom In)/i', $trimmed)) {
                continue;
            }
            if (preg_match('/^(NOW|UP NEXT|VIDEO\d|watch now|Zoom In Icon|ShareShare|WATCH LIVE|Follow your favorite|CREATE FREE|In this article)/i', $trimmed)) {
                continue;
            }
            if (preg_match('/^\*\s*\[(?:Home|News|Markets|Business|Investing|Tech|Politics)/i', $trimmed)) {
                continue;
            }

            $article_lines[] = $line;
        }

        // Fallback: If strict headline start was not triggered, filter lines by paragraph length
        if (empty($article_lines) || count($article_lines) < 3) {
            $fallback_lines = array();
            if (!empty($headline)) {
                $fallback_lines[] = '# ' . $headline;
            }
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (preg_match('/^\[(?:Skip|Home|News|Markets|Terms|Privacy|Cookie)/i', $trimmed)) continue;
                if (preg_match('/^\*\s*\[/i', $trimmed)) continue;
                if (strlen($trimmed) > 35) {
                    $fallback_lines[] = $line;
                }
            }
            return implode("\n", $fallback_lines);
        }

        return implode("\n", $article_lines);
    }
}
