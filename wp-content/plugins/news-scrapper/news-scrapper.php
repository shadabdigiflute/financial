<?php
/**
 * Plugin Name: News Scrapper
 * Plugin URI:  https://github.com/shadabdigiflute/financial
 * Description: Automated news scraping, AI rewriting, and publishing pipeline powered by Crawl4AI and Google Gemini Lite.
 * Version:     1.0.0
 * Author:      Financial Development Team
 * Author URI:  https://github.com/shadabdigiflute
 * License:     GPLv2 or later
 * Text Domain: news-scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEWS_SCRAPPER_VERSION', '1.1.0');
define('NEWS_SCRAPPER_FILE', __FILE__);
define('NEWS_SCRAPPER_PATH', plugin_dir_path(__FILE__));
define('NEWS_SCRAPPER_URL', plugin_dir_url(__FILE__));
define('NEWS_SCRAPPER_UPLOADS_DIR', wp_upload_dir()['basedir'] . '/news-scraper');

// Include core classes
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-db.php';
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-crawler.php';
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-gemini.php';
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-publisher.php';
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-importer.php';
require_once NEWS_SCRAPPER_PATH . 'includes/class-news-scraper-cron.php';

/**
 * Plugin Activation
 */
register_activation_hook(__FILE__, 'news_scrapper_activate');
function news_scrapper_activate() {
    News_Scraper_DB::create_tables();
    News_Scraper_Cron::schedule_crons();

    // Create storage directories
    $upload_dir = NEWS_SCRAPPER_UPLOADS_DIR;
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir . '/listings');
        wp_mkdir_p($upload_dir . '/articles');
    }

    // Default settings if empty
    if (!get_option('news_scrapper_crawl4ai_url')) {
        update_option('news_scrapper_crawl4ai_url', defined('CRAWL4AI_API_URL') ? CRAWL4AI_API_URL : 'http://crawl4ai.51.222.83.114.sslip.io');
    }
    if (!get_option('news_scrapper_crawl4ai_token')) {
        update_option('news_scrapper_crawl4ai_token', defined('CRAWL4AI_API_TOKEN') ? CRAWL4AI_API_TOKEN : '8Qz8RKv72nEBB$');
    }
    if (!get_option('news_scrapper_gemini_model')) {
        update_option('news_scrapper_gemini_model', 'gemini-2.0-flash-lite');
    }
}

/**
 * Plugin Deactivation
 */
register_deactivation_hook(__FILE__, 'news_scrapper_deactivate');
function news_scrapper_deactivate() {
    News_Scraper_Cron::unschedule_crons();
}

/**
 * Initialize Admin UI
 */
if (is_admin()) {
    require_once NEWS_SCRAPPER_PATH . 'admin/class-news-scraper-admin.php';
    new News_Scraper_Admin();
}

/**
 * Self-healing DB & Cron check on init for container environments
 */
add_action('init', 'news_scrapper_check_db');
function news_scrapper_check_db() {
    $current_version = get_option('news_scrapper_db_version');
    if ($current_version !== '1.1.0') {
        News_Scraper_DB::create_tables();
        News_Scraper_Cron::schedule_crons();
        update_option('news_scrapper_db_version', '1.1.0');
    }
}
