<?php
/**
 * Must-Use Plugin Loader for News Scrapper
 * Ensures the News Scrapper plugin is always loaded and active in all environments.
 *
 * @package Financial
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(WP_PLUGIN_DIR . '/news-scrapper/news-scrapper.php')) {
    require_once WP_PLUGIN_DIR . '/news-scrapper/news-scrapper.php';
}
