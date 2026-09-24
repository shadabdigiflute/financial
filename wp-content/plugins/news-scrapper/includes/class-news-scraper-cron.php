<?php
/**
 * Cron Job and Background Queue Worker
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Cron {

    const HOOK_NAME = 'news_scraper_cron_hook';

    /**
     * Register custom 4-hour cron interval
     */
    public static function init() {
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_interval'));
        add_action(self::HOOK_NAME, array(__CLASS__, 'run_cron_cycle'));
    }

    /**
     * Add 4-hour interval
     */
    public static function add_cron_interval($schedules) {
        $schedules['every_4_hours'] = array(
            'interval' => 14400, // 4 hours in seconds
            'display'  => __('Every 4 Hours', 'news-scrapper'),
        );
        return $schedules;
    }

    /**
     * Schedule recurring event on activation
     */
    public static function schedule_crons() {
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'every_4_hours', self::HOOK_NAME);
        }
    }

    /**
     * Unschedule on deactivation
     */
    public static function unschedule_crons() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }

    /**
     * Main background worker executed every 4 hours
     */
    public static function run_cron_cycle() {
        global $wpdb;
        $feeds_table = News_Scraper_DB::feeds_table();

        // Get active feeds whose next_run_at <= now
        $now = current_time('mysql');
        $feeds = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $feeds_table WHERE status = 'active' AND (next_run_at IS NULL OR next_run_at <= %s) ORDER BY id ASC LIMIT 5", $now),
            ARRAY_A
        );

        foreach ($feeds as $feed) {
            self::run_single_feed($feed['id']);

            // Schedule next run in 4 hours
            $next_run = date('Y-m-d H:i:s', time() + 14400);
            $wpdb->update($feeds_table, array(
                'last_run_at' => current_time('mysql'),
                'next_run_at' => $next_run,
            ), array('id' => $feed['id']));
        }
    }

    /**
     * Run full pipeline for a single feed
     *
     * @param int $feed_id
     * @return array
     */
    public static function run_single_feed($feed_id) {
        $feed = News_Scraper_DB::get_feed($feed_id);
        if (!$feed) {
            return array('success' => false, 'message' => 'Feed not found');
        }

        $crawler   = new News_Scraper_Crawler();
        $gemini    = new News_Scraper_Gemini();
        $publisher = new News_Scraper_Publisher();

        $start_time = microtime(true);

        // Step 1: Listing Scraping & Pagination Discovery
        $listing_result = $crawler->crawl_listing($feed['id'], $feed['source_url'], $feed['pagination_depth']);

        // Step 2, 3, 4: Process Queued Discovered Articles for this feed (up to 8 per batch)
        global $wpdb;
        $queue_table = News_Scraper_DB::queue_table();
        $queued_items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $queue_table WHERE feed_id = %d AND status = 'discovered' ORDER BY id ASC LIMIT 8", $feed['id']),
            ARRAY_A
        );

        $processed_count = 0;
        $published_posts = array();

        foreach ($queued_items as $item) {
            // Step 2: Deep A to Z Scrape
            $scraped = $crawler->crawl_article($item['id'], $item['article_url'], $feed['id']);

            if (!$scraped['success']) {
                News_Scraper_DB::update_queue_item($item['id'], array(
                    'status'        => 'failed',
                    'error_message' => $scraped['error'] ?? 'Scrape failed',
                ));
                continue;
            }

            // Step 3: Gemini Lite AI Rewrite
            $rewritten = $gemini->rewrite_article($scraped['headline'], $scraped['markdown']);

            $article_payload = array(
                'headline'       => $rewritten['headline'],
                'content_html'   => $rewritten['content_html'],
                'tags'           => $rewritten['tags'],
                'source_url'     => $item['article_url'],
                'featured_image' => $scraped['featured_image'],
                'md_file_path'   => $scraped['file_path'],
            );

            // Step 4: WordPress Post Publishing
            $post_id = $publisher->publish_post($item['id'], $article_payload, $feed);

            if (!is_wp_error($post_id)) {
                $processed_count++;
                $published_posts[] = $post_id;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);

        News_Scraper_DB::log(
            $feed['id'],
            'cron_run',
            sprintf("Feed '%s': Discovered %d URLs, Published %d posts in %ss.", $feed['feed_name'], $listing_result['discovered_count'] ?? 0, $processed_count, $elapsed),
            $processed_count,
            'success'
        );

        return array(
            'success'         => true,
            'discovered'      => $listing_result['discovered_count'] ?? 0,
            'published_count' => $processed_count,
            'post_ids'        => $published_posts,
            'duration_sec'    => $elapsed,
        );
    }
}

News_Scraper_Cron::init();
