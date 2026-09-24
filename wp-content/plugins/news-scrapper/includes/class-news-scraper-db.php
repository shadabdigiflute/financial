<?php
/**
 * Database schema and CRUD helpers
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_DB {

    /**
     * Get feeds table name
     */
    public static function feeds_table() {
        global $wpdb;
        return $wpdb->prefix . 'news_scraper_feeds';
    }

    /**
     * Get queue table name
     */
    public static function queue_table() {
        global $wpdb;
        return $wpdb->prefix . 'news_scraper_queue';
    }

    /**
     * Get logs table name
     */
    public static function logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'news_scraper_logs';
    }

    /**
     * Create tables on activation
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $feeds_table = self::feeds_table();
        $queue_table = self::queue_table();
        $logs_table  = self::logs_table();

        $sql_feeds = "CREATE TABLE $feeds_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feed_name VARCHAR(255) NOT NULL,
            source_url TEXT NOT NULL,
            category_ids TEXT NOT NULL,
            pagination_depth INT(11) NOT NULL DEFAULT 3,
            post_status VARCHAR(20) NOT NULL DEFAULT 'draft',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            cron_interval INT(11) NOT NULL DEFAULT 28800,
            last_run_at DATETIME DEFAULT NULL,
            next_run_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_queue = "CREATE TABLE $queue_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feed_id BIGINT(20) UNSIGNED NOT NULL,
            article_url TEXT NOT NULL,
            article_hash VARCHAR(64) NOT NULL,
            title_raw TEXT DEFAULT NULL,
            author VARCHAR(255) DEFAULT NULL,
            published_date DATETIME DEFAULT NULL,
            datapoints_json LONGTEXT DEFAULT NULL,
            md_file_path TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'discovered',
            post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY feed_article (feed_id, article_hash),
            KEY article_hash (article_hash),
            KEY feed_id (feed_id),
            KEY status (status)
        ) $charset_collate;";

        $sql_logs = "CREATE TABLE $logs_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feed_id BIGINT(20) UNSIGNED DEFAULT NULL,
            action VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            items_processed INT(11) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'success',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY feed_id (feed_id)
        ) $charset_collate;";

        dbDelta($sql_feeds);
        dbDelta($sql_queue);
        dbDelta($sql_logs);
    }

    /**
     * Get all feeds
     */
    public static function get_feeds($limit = 100, $offset = 0) {
        global $wpdb;
        $table = self::feeds_table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
    }

    /**
     * Get feed by ID
     */
    public static function get_feed($id) {
        global $wpdb;
        $table = self::feeds_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    /**
     * Insert or update feed
     */
    public static function save_feed($data, $id = null) {
        global $wpdb;
        $table = self::feeds_table();

        $record = array(
            'feed_name'        => sanitize_text_field($data['feed_name']),
            'source_url'       => esc_url_raw($data['source_url']),
            'category_ids'     => is_array($data['category_ids']) ? wp_json_encode(array_map('intval', $data['category_ids'])) : $data['category_ids'],
            'pagination_depth' => isset($data['pagination_depth']) ? max(1, intval($data['pagination_depth'])) : 3,
            'post_status'      => isset($data['post_status']) && in_array($data['post_status'], array('publish', 'draft'), true) ? $data['post_status'] : 'draft',
            'status'           => isset($data['status']) && $data['status'] === 'paused' ? 'paused' : 'active',
            'cron_interval'    => isset($data['cron_interval']) ? max(3600, intval($data['cron_interval'])) : 28800, // 8 hours
        );

        if ($id) {
            $wpdb->update($table, $record, array('id' => intval($id)));
            return intval($id);
        } else {
            $record['created_at']  = current_time('mysql');
            $record['next_run_at'] = current_time('mysql');
            $wpdb->insert($table, $record);
            return $wpdb->insert_id;
        }
    }

    /**
     * Delete feed
     */
    public static function delete_feed($id) {
        global $wpdb;
        $wpdb->delete(self::feeds_table(), array('id' => intval($id)));
        $wpdb->delete(self::queue_table(), array('feed_id' => intval($id)));
    }

    /**
     * Insert article into queue (with deduplication)
     */
    public static function enqueue_article($feed_id, $article_url, $title_raw = '', $extra = array()) {
        global $wpdb;
        $table = self::queue_table();
        $hash = hash('sha256', esc_url_raw($article_url));

        // Check if exists for this feed
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE feed_id = %d AND article_hash = %s", $feed_id, $hash));
        if ($exists) {
            return false;
        }

        $record = array(
            'feed_id'      => intval($feed_id),
            'article_url'  => esc_url_raw($article_url),
            'article_hash' => $hash,
            'title_raw'    => sanitize_text_field($title_raw),
            'author'       => !empty($extra['author']) ? sanitize_text_field($extra['author']) : null,
            'status'       => 'discovered',
            'created_at'   => current_time('mysql'),
            'updated_at'   => current_time('mysql'),
        );

        if (!empty($extra['published_date'])) {
            $record['published_date'] = $extra['published_date'];
        }

        $inserted = $wpdb->insert($table, $record);
        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Get queue items by status
     */
    public static function get_queue_items($status = null, $limit = 50) {
        global $wpdb;
        $table = self::queue_table();
        if ($status) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE status = %s ORDER BY id ASC LIMIT %d", $status, $limit), ARRAY_A);
        }
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
    }

    /**
     * Update queue item status
     */
    public static function update_queue_item($id, $fields) {
        global $wpdb;
        $table = self::queue_table();
        $fields['updated_at'] = current_time('mysql');
        return $wpdb->update($table, $fields, array('id' => intval($id)));
    }

    /**
     * Add log entry
     */
    public static function log($feed_id, $action, $message, $items = 0, $status = 'success') {
        global $wpdb;
        $table = self::logs_table();
        $wpdb->insert($table, array(
            'feed_id'         => $feed_id ? intval($feed_id) : null,
            'action'          => sanitize_text_field($action),
            'message'         => sanitize_textarea_field($message),
            'items_processed' => intval($items),
            'status'          => $status,
            'created_at'      => current_time('mysql'),
        ));
    }

    /**
     * Get summary counts
     */
    public static function get_stats() {
        global $wpdb;
        $feeds_t = self::feeds_table();
        $queue_t = self::queue_table();

        $active_feeds = (int) $wpdb->get_var("SELECT COUNT(*) FROM $feeds_t WHERE status = 'active'");
        $total_feeds  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $feeds_t");
        $discovered   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_t WHERE status = 'discovered'");
        $processing   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_t WHERE status = 'processing'");
        $posted       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_t WHERE status = 'posted'");
        $failed       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_t WHERE status = 'failed'");

        return compact('active_feeds', 'total_feeds', 'discovered', 'processing', 'posted', 'failed');
    }

    /**
     * Flush scraped data: Truncate queue, delete generated posts, delete .md archive files
     *
     * @param bool $delete_posts  Whether to delete posts created by scraper
     * @param bool $delete_feeds  Whether to delete configured feeds
     * @return array Summary of flushed items
     */
    public static function flush_all_data($delete_posts = true, $delete_feeds = false) {
        global $wpdb;
        $queue_table = self::queue_table();
        $feeds_table = self::feeds_table();
        $logs_table  = self::logs_table();

        // 1. Delete generated WordPress posts if requested
        $deleted_posts_count = 0;
        if ($delete_posts) {
            $post_ids = $wpdb->get_col("
                SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_news_scraper_feed_id', '_news_scraper_original_url', '_news_scraper_hash')
            ");

            if (!empty($post_ids)) {
                foreach ($post_ids as $pid) {
                    wp_delete_post(intval($pid), true); // Force bypass trash
                    $deleted_posts_count++;
                }
            }
        }

        // 2. Count and truncate queue table
        $flushed_queue_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_table");
        $wpdb->query("TRUNCATE TABLE $queue_table");

        // 3. Clear logs table
        $wpdb->query("TRUNCATE TABLE $logs_table");

        // 4. Optionally clear feeds table
        $flushed_feeds_count = 0;
        if ($delete_feeds) {
            $flushed_feeds_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $feeds_table");
            $wpdb->query("TRUNCATE TABLE $feeds_table");
        }

        // 5. Clean scraped markdown archive files on disk
        $deleted_files_count = 0;
        $uploads_base = defined('NEWS_SCRAPPER_UPLOADS_DIR') ? NEWS_SCRAPPER_UPLOADS_DIR : (wp_upload_dir()['basedir'] . '/news-scraper');
        foreach (array('/listings', '/articles') as $sub) {
            $dir = $uploads_base . $sub;
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                            $deleted_files_count++;
                        }
                    }
                }
            }
        }

        return array(
            'queue_items'    => $flushed_queue_count,
            'posts_deleted'  => $deleted_posts_count,
            'files_deleted'  => $deleted_files_count,
            'feeds_deleted'  => $flushed_feeds_count,
        );
    }
}
