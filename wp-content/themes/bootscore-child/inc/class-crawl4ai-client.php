<?php
/**
 * Crawl4AI Client for Financial Project
 *
 * Microservice client to interact with the Crawl4AI instance running on Coolify.
 *
 * @package Bootscore Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Financial_Crawl4AI_Client {

    /**
     * API Base URL
     *
     * @var string
     */
    protected $api_url;

    /**
     * API Bearer Token
     *
     * @var string
     */
    protected $api_token;

    /**
     * Constructor
     *
     * @param string|null $api_url
     * @param string|null $api_token
     */
    public function __construct($api_url = null, $api_token = null) {
        $this->api_url = rtrim($api_url ?: (defined('CRAWL4AI_API_URL') ? CRAWL4AI_API_URL : 'http://crawl4ai.51.222.83.114.sslip.io'), '/');
        $this->api_token = $api_token ?: (defined('CRAWL4AI_API_TOKEN') ? CRAWL4AI_API_TOKEN : '8Qz8RKv72nEBB$');
    }

    /**
     * Check if Crawl4AI microservice is healthy
     *
     * @return array ['success' => bool, 'version' => string, 'message' => string, 'latency_ms' => float]
     */
    public function check_health() {
        $t0 = microtime(true);
        $url = $this->api_url . '/health';

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        $latency = round((microtime(true) - $t0) * 1000, 1);

        if (is_wp_error($response)) {
            return array(
                'success'    => false,
                'version'    => 'unknown',
                'message'    => $response->get_error_message(),
                'latency_ms' => $latency,
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 200 && is_array($data) && isset($data['status']) && $data['status'] === 'ok') {
            return array(
                'success'    => true,
                'version'    => isset($data['version']) ? $data['version'] : '0.9.x',
                'message'    => 'Crawl4AI Service is healthy and reachable.',
                'latency_ms' => $latency,
            );
        }

        return array(
            'success'    => false,
            'version'    => 'unknown',
            'message'    => 'Unexpected status code ' . $code . ': ' . substr($body, 0, 120),
            'latency_ms' => $latency,
        );
    }

    /**
     * Crawl a single URL and extract clean Markdown
     *
     * @param string $target_url Target URL to crawl
     * @param string $filter     Filter type: 'fit' (default clean), 'raw', 'bm25', 'llm'
     * @param string $query      Optional query for BM25/LLM filters
     * @return array ['success' => bool, 'markdown' => string, 'url' => string, 'error' => string|null]
     */
    public function crawl_markdown($target_url, $filter = 'fit', $query = null) {
        $endpoint = $this->api_url . '/md';

        $payload = array(
            'url' => esc_url_raw($target_url),
            'f'   => in_array($filter, array('fit', 'raw', 'bm25', 'llm'), true) ? $filter : 'fit',
        );

        if (!empty($query)) {
            $payload['q'] = sanitize_text_field($query);
        }

        $response = wp_remote_post($endpoint, array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            return array(
                'success'  => false,
                'markdown' => '',
                'url'      => $target_url,
                'error'    => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 200 && is_array($data) && !empty($data['success'])) {
            return array(
                'success'  => true,
                'markdown' => isset($data['markdown']) ? $data['markdown'] : '',
                'url'      => isset($data['url']) ? $data['url'] : $target_url,
                'error'    => null,
            );
        }

        $error_msg = isset($data['detail']) ? (is_string($data['detail']) ? $data['detail'] : wp_json_encode($data['detail'])) : 'HTTP ' . $code . ': ' . substr($body, 0, 150);

        return array(
            'success'  => false,
            'markdown' => '',
            'url'      => $target_url,
            'error'    => $error_msg,
        );
    }

    /**
     * Crawl a URL and extract raw or rendered HTML
     *
     * @param string $target_url
     * @return array
     */
    public function crawl_html($target_url) {
        $endpoint = $this->api_url . '/html';

        $payload = array(
            'url' => esc_url_raw($target_url),
        );

        $response = wp_remote_post($endpoint, array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'html'    => '',
                'error'   => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 200 && is_array($data)) {
            return array(
                'success' => true,
                'html'    => isset($data['html']) ? $data['html'] : '',
                'error'   => null,
            );
        }

        return array(
            'success' => false,
            'html'    => '',
            'error'   => 'HTTP ' . $code . ': ' . substr($body, 0, 150),
        );
    }

    /**
     * Convert markdown to simple clean HTML for WordPress post content
     *
     * @param string $markdown
     * @return string
     */
    public function markdown_to_html($markdown) {
        $lines = explode("\n", $markdown);
        $html = '';
        $in_list = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                continue;
            }

            // Headers
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                $level = strlen($m[1]);
                $heading_text = esc_html($m[2]);
                $html .= "<h{$level}>{$heading_text}</h{$level}>\n";
                continue;
            }

            // Bullet points
            if (preg_match('/^[-*•]\s+(.+)$/', $trimmed, $m)) {
                if (!$in_list) {
                    $html .= "<ul>\n";
                    $in_list = true;
                }
                $item_text = esc_html($m[1]);
                $html .= "<li>{$item_text}</li>\n";
                continue;
            }

            // Regular paragraph
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }

            // Convert simple bold and links
            $p = esc_html($trimmed);
            $p = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $p);
            $p = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s\)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $p);

            $html .= "<p>{$p}</p>\n";
        }

        if ($in_list) {
            $html .= "</ul>\n";
        }

        return $html;
    }

    /**
     * Create WordPress Post from crawled markdown content
     *
     * @param string $source_url
     * @param string $title
     * @param string $markdown
     * @param string $post_status 'draft' or 'publish'
     * @param array  $categories Category IDs or names
     * @return int|WP_Error Post ID on success
     */
    public function import_as_post($source_url, $title, $markdown, $post_status = 'draft', $categories = array()) {
        $html_content = $this->markdown_to_html($markdown);

        // Append source citation
        $html_content .= "\n\n<hr><p class=\"text-muted small\"><em>Source: <a href=\"" . esc_url($source_url) . "\" target=\"_blank\" rel=\"noopener nofollow\">" . esc_html($source_url) . "</a> (Crawled via Crawl4AI)</em></p>";

        $post_data = array(
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($html_content),
            'post_status'  => in_array($post_status, array('draft', 'publish', 'pending'), true) ? $post_status : 'draft',
            'post_author'  => get_current_user_id() ?: 1,
            'post_type'    => 'post',
        );

        $post_id = wp_insert_post($post_data, true);

        if (!is_wp_error($post_id)) {
            // Save source meta
            update_post_meta($post_id, '_crawl4ai_source_url', esc_url_raw($source_url));
            update_post_meta($post_id, '_crawl4ai_crawled_at', current_time('mysql'));

            if (!empty($categories)) {
                wp_set_post_categories($post_id, $categories);
            }
        }

        return $post_id;
    }
}
