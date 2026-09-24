<?php
/**
 * Decoupled 2-Tier Cron Job and Background Queue Worker
 *
 * Tier 1: Source Listing Crawler (Runs Every 8 Hours)
 * Tier 2: AI Queue Worker & Publisher (Runs Every 3 Minutes)
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Cron {

    const LISTING_HOOK = 'news_scraper_listing_cron_hook';
    const QUEUE_HOOK   = 'news_scraper_queue_cron_hook';

    /**
     * Register cron intervals and action hooks
     */
    public static function init() {
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_intervals'));
        add_action(self::LISTING_HOOK, array(__CLASS__, 'run_listing_cron_cycle'));
        add_action(self::QUEUE_HOOK, array(__CLASS__, 'run_queue_worker_cycle'));
    }

    /**
     * Add custom cron intervals
     */
    public static function add_cron_intervals($schedules) {
        $schedules['every_8_hours'] = array(
            'interval' => 28800, // 8 hours in seconds
            'display'  => __('Every 8 Hours', 'news-scrapper'),
        );
        $schedules['every_3_minutes'] = array(
            'interval' => 180, // 3 minutes in seconds
            'display'  => __('Every 3 Minutes', 'news-scrapper'),
        );
        return $schedules;
    }

    /**
     * Schedule recurring cron events
     */
    public static function schedule_crons() {
        if (!wp_next_scheduled(self::LISTING_HOOK)) {
            wp_schedule_event(time(), 'every_8_hours', self::LISTING_HOOK);
        }
        if (!wp_next_scheduled(self::QUEUE_HOOK)) {
            wp_schedule_event(time() + 60, 'every_3_minutes', self::QUEUE_HOOK);
        }
    }

    /**
     * Unschedule recurring cron events
     */
    public static function unschedule_crons() {
        $listing_ts = wp_next_scheduled(self::LISTING_HOOK);
        if ($listing_ts) {
            wp_unschedule_event($listing_ts, self::LISTING_HOOK);
        }
        $queue_ts = wp_next_scheduled(self::QUEUE_HOOK);
        if ($queue_ts) {
            wp_unschedule_event($queue_ts, self::QUEUE_HOOK);
        }
    }

    /**
     * Tier 1: Check active feeds every 8 hours and discover new news
     */
    public static function run_listing_cron_cycle() {
        global $wpdb;
        $feeds_table = News_Scraper_DB::feeds_table();

        $now = current_time('mysql');
        $feeds = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $feeds_table WHERE status = 'active' AND (next_run_at IS NULL OR next_run_at <= %s) ORDER BY id ASC LIMIT 5", $now),
            ARRAY_A
        );

        foreach ($feeds as $feed) {
            self::crawl_feed_listing($feed['id']);

            // Schedule next run in 8 hours (28,800 seconds)
            $next_run = date('Y-m-d H:i:s', time() + 28800);
            $wpdb->update($feeds_table, array(
                'last_run_at' => current_time('mysql'),
                'next_run_at' => $next_run,
            ), array('id' => $feed['id']));
        }
    }

    /**
     * Tier 2: Process next batch of queued articles every 3 minutes
     */
    public static function run_queue_worker_cycle() {
        self::process_queue_batch(3);
    }

    /**
     * Crawl source URL of a single feed and enqueue discovered articles
     *
     * @param int $feed_id
     * @return array
     */
    public static function crawl_feed_listing($feed_id) {
        $feed = News_Scraper_DB::get_feed($feed_id);
        if (!$feed) {
            return array('success' => false, 'message' => 'Feed not found');
        }

        $crawler = new News_Scraper_Crawler();
        $start_time = microtime(true);

        $listing_result = $crawler->crawl_listing($feed['id'], $feed['source_url'], $feed['pagination_depth']);

        $elapsed = round(microtime(true) - $start_time, 2);

        News_Scraper_DB::log(
            $feed['id'],
            'listing_crawl',
            sprintf("Feed '%s': Discovered %d articles (%d new enqueued) in %ss. Next check in 8h.", $feed['feed_name'], $listing_result['discovered_count'] ?? 0, $listing_result['new_enqueued'] ?? 0, $elapsed),
            $listing_result['new_enqueued'] ?? 0,
            'success'
        );

        return array(
            'success'          => true,
            'pages_crawled'    => $listing_result['pages_crawled'] ?? 0,
            'discovered_count' => $listing_result['discovered_count'] ?? 0,
            'new_enqueued'     => $listing_result['new_enqueued'] ?? 0,
            'duration_sec'     => $elapsed,
        );
    }

    /**
     * Process a batch of queued articles: Deep Scrape -> Datapoint Extraction -> AI Rewrite -> Publish
     *
     * @param int $batch_size Default 3
     * @return array
     */
    public static function process_queue_batch($batch_size = 3) {
        global $wpdb;
        $queue_table = News_Scraper_DB::queue_table();

        // Lock next batch of discovered items
        $queued_items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $queue_table WHERE status = 'discovered' ORDER BY id ASC LIMIT %d", $batch_size),
            ARRAY_A
        );

        if (empty($queued_items)) {
            return array('success' => true, 'processed_count' => 0, 'post_ids' => array(), 'message' => 'Queue is empty');
        }

        $crawler   = new News_Scraper_Crawler();
        $gemini    = new News_Scraper_Gemini();
        $publisher = new News_Scraper_Publisher();

        $start_time = microtime(true);
        $processed_count = 0;
        $published_posts = array();

        foreach ($queued_items as $item) {
            // Mark as processing
            News_Scraper_DB::update_queue_item($item['id'], array('status' => 'processing'));

            $feed = News_Scraper_DB::get_feed($item['feed_id']);
            if (!$feed) {
                News_Scraper_DB::update_queue_item($item['id'], array(
                    'status'        => 'failed',
                    'error_message' => 'Associated feed #' . $item['feed_id'] . ' not found',
                ));
                continue;
            }

            // Step A: Deep Scrape & Extract Datapoints
            $scraped = $crawler->crawl_article($item['id'], $item['article_url'], $item['feed_id']);

            if (!$scraped['success']) {
                News_Scraper_DB::update_queue_item($item['id'], array(
                    'status'        => 'failed',
                    'error_message' => $scraped['error'] ?? 'Scrape failed',
                ));
                continue;
            }

            $datapoints = $scraped['datapoints'] ?? array();

            // Step B: Factual AI Rewrite (Zero-Hallucination)
            $rewritten = $gemini->rewrite_article($scraped['headline'], $scraped['markdown'], $datapoints);

            $final_headline = !empty($rewritten['headline']) ? $rewritten['headline'] : (!empty($scraped['headline']) ? $scraped['headline'] : (!empty($item['title_raw']) ? $item['title_raw'] : 'Financial Market Report'));

            $article_payload = array(
                'headline'       => $final_headline,
                'content_html'   => $rewritten['content_html'],
                'tags'           => $rewritten['tags'],
                'author'         => $datapoints['author'] ?? $item['author'],
                'published_date' => $datapoints['published_date'] ?? $item['published_date'],
                'source_url'     => $item['article_url'],
                'featured_image' => $scraped['featured_image'],
                'md_file_path'   => $scraped['file_path'],
            );

            // Step C: WordPress Post Publishing & Media Sideloading
            $post_id = $publisher->publish_post($item['id'], $article_payload, $feed);

            if (!is_wp_error($post_id)) {
                $processed_count++;
                $published_posts[] = $post_id;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);

        if ($processed_count > 0) {
            News_Scraper_DB::log(
                null,
                'queue_worker',
                sprintf("AI Queue Worker: Successfully processed & published %d articles in %ss.", $processed_count, $elapsed),
                $processed_count,
                'success'
            );
        }

        return array(
            'success'         => true,
            'processed_count' => $processed_count,
            'post_ids'        => $published_posts,
            'duration_sec'    => $elapsed,
        );
    }

    /**
     * Backward-compatible single feed runner
     */
    public static function run_single_feed($feed_id) {
        $listing = self::crawl_feed_listing($feed_id);
        $queue   = self::process_queue_batch(3);

        return array(
            'success'         => true,
            'discovered'      => $listing['discovered_count'] ?? 0,
            'new_enqueued'     => $listing['new_enqueued'] ?? 0,
            'published_count' => $queue['processed_count'] ?? 0,
            'post_ids'        => $queue['post_ids'] ?? array(),
            'duration_sec'    => ($listing['duration_sec'] ?? 0) + ($queue['duration_sec'] ?? 0),
        );
    }
}

News_Scraper_Cron::init();

