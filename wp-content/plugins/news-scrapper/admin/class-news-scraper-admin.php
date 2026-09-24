<?php
/**
 * Admin Controller and AJAX Handlers
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX handlers
        add_action('wp_ajax_news_scraper_save_feed', array($this, 'ajax_save_feed'));
        add_action('wp_ajax_news_scraper_get_feed', array($this, 'ajax_get_feed'));
        add_action('wp_ajax_news_scraper_delete_feed', array($this, 'ajax_delete_feed'));
        add_action('wp_ajax_news_scraper_run_feed', array($this, 'ajax_run_feed'));
        add_action('wp_ajax_news_scraper_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_news_scraper_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Register Admin Menu
     */
    public function register_menu() {
        add_menu_page(
            __('News Scrapper', 'news-scrapper'),
            __('News Scrapper', 'news-scrapper'),
            'manage_options',
            'news-scrapper',
            array($this, 'render_dashboard'),
            'dashicons-rss',
            26
        );
    }

    /**
     * Enqueue Admin Assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'news-scrapper') === false) {
            return;
        }

        wp_enqueue_style(
            'news-scraper-admin',
            NEWS_SCRAPPER_URL . 'admin/css/admin.css',
            array(),
            NEWS_SCRAPPER_VERSION
        );

        wp_enqueue_script(
            'news-scraper-admin',
            NEWS_SCRAPPER_URL . 'admin/js/admin.js',
            array('jquery'),
            NEWS_SCRAPPER_VERSION,
            true
        );

        wp_localize_script('news-scraper-admin', 'newsScraperVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('news_scraper_admin_nonce'),
        ));
    }

    /**
     * Render Dashboard
     */
    public function render_dashboard() {
        $stats = News_Scraper_DB::get_stats();
        $feeds = News_Scraper_DB::get_feeds();
        $queue = News_Scraper_DB::get_queue_items(null, 30);
        $logs  = News_Scraper_DB::get_stats();

        $crawler = new News_Scraper_Crawler();
        $health  = $crawler->ping();

        require_once NEWS_SCRAPPER_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Helper to render hierarchical category checkbox tree
     */
    public static function render_category_tree($parent_id = 0, $selected_ids = array()) {
        $cats = get_categories(array(
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (empty($cats)) {
            return;
        }

        echo '<ul>';
        foreach ($cats as $cat) {
            $checked = in_array($cat->term_id, $selected_ids) ? 'checked' : '';
            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" class="ns-cat-checkbox" name="categories[]" value="' . esc_attr($cat->term_id) . '" ' . $checked . '> ';
            echo esc_html($cat->name);
            echo '</label>';

            // Recursive call for subcategories
            self::render_category_tree($cat->term_id, $selected_ids);

            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * AJAX: Save Feed
     */
    public function ajax_save_feed() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
        $feed_id = News_Scraper_DB::save_feed($_POST, $id);

        wp_send_json_success(array('feed_id' => $feed_id));
    }

    /**
     * AJAX: Get Feed
     */
    public function ajax_get_feed() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $feed = News_Scraper_DB::get_feed($id);

        if ($feed) {
            wp_send_json_success($feed);
        } else {
            wp_send_json_error('Feed not found');
        }
    }

    /**
     * AJAX: Delete Feed
     */
    public function ajax_delete_feed() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        News_Scraper_DB::delete_feed($id);
        wp_send_json_success();
    }

    /**
     * AJAX: Run Feed Now
     */
    public function ajax_run_feed() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $result = News_Scraper_Cron::run_single_feed($id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? 'Run failed');
        }
    }

    /**
     * AJAX: Import CSV
     */
    public function ajax_import_csv() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }

        $importer = new News_Scraper_Importer();
        $result = $importer->import_csv($_FILES['csv_file']['tmp_name']);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Save Settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('news_scraper_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (isset($_POST['crawl4ai_url'])) {
            update_option('news_scrapper_crawl4ai_url', esc_url_raw($_POST['crawl4ai_url']));
        }
        if (isset($_POST['crawl4ai_token'])) {
            update_option('news_scrapper_crawl4ai_token', sanitize_text_field($_POST['crawl4ai_token']));
        }
        if (isset($_POST['gemini_key'])) {
            update_option('news_scrapper_gemini_api_key', sanitize_text_field($_POST['gemini_key']));
        }
        if (isset($_POST['gemini_model'])) {
            update_option('news_scrapper_gemini_model', sanitize_text_field($_POST['gemini_model']));
        }

        wp_send_json_success();
    }
}
