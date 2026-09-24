<?php
/**
 * WordPress Post Publisher & Media Sideloading
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Publisher {

    /**
     * Publish article as WordPress post
     *
     * @param int   $queue_id
     * @param array $article_data  Scraped and rewritten article details
     * @param array $feed          Feed configuration
     * @return int|WP_Error Post ID on success
     */
    public function publish_post($queue_id, $article_data, $feed) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $headline     = $article_data['headline'] ?? 'Financial News';
        $content_html = $article_data['content_html'] ?? '';
        $tags         = $article_data['tags'] ?? array();
        $source_url   = $article_data['source_url'] ?? '';
        $post_status  = !empty($feed['post_status']) ? $feed['post_status'] : 'draft';

        // Decode category IDs from feed
        $category_ids = array();
        if (!empty($feed['category_ids'])) {
            $decoded = json_decode($feed['category_ids'], true);
            if (is_array($decoded)) {
                $category_ids = array_map('intval', $decoded);
            }
        }

        // Author byline header
        $byline_html = '';
        if (!empty($article_data['author'])) {
            $byline_html .= '<p class="news-scraper-byline"><small><strong>By ' . esc_html($article_data['author']) . '</strong>';
            if (!empty($article_data['published_date'])) {
                $byline_html .= ' &bull; <time datetime="' . esc_attr($article_data['published_date']) . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($article_data['published_date']))) . '</time>';
            }
            $byline_html .= '</small></p>';
        }

        // Add source citation at bottom
        if (!empty($source_url)) {
            $source_host = parse_url($source_url, PHP_URL_HOST);
            $content_html .= "\n\n<hr class=\"news-scraper-divider\"><p class=\"text-muted news-scraper-source\"><small><em>Originally published on <a href=\"" . esc_url($source_url) . "\" target=\"_blank\" rel=\"noopener nofollow\">" . esc_html($source_host ?: $source_url) . "</a></em></small></p>";
        }

        $final_content = $byline_html . $content_html;

        // Prepare post array
        $post_args = array(
            'post_title'    => sanitize_text_field($headline),
            'post_content'  => wp_kses_post($final_content),
            'post_status'   => in_array($post_status, array('publish', 'draft', 'pending'), true) ? $post_status : 'draft',
            'post_author'   => get_current_user_id() ?: 1,
            'post_type'     => 'post',
            'post_category' => $category_ids,
            'tags_input'    => $tags,
        );

        // Preserve genuine publication date if available
        if (!empty($article_data['published_date'])) {
            $post_args['post_date']     = $article_data['published_date'];
            $post_args['post_date_gmt'] = get_gmt_from_date($article_data['published_date']);
        }

        $post_id = wp_insert_post($post_args, true);

        if (is_wp_error($post_id)) {
            News_Scraper_DB::update_queue_item($queue_id, array(
                'status'        => 'failed',
                'error_message' => $post_id->get_error_message(),
            ));
            return $post_id;
        }

        // Save tracking metadata
        update_post_meta($post_id, '_news_scraper_feed_id', intval($feed['id']));
        update_post_meta($post_id, '_news_scraper_queue_id', intval($queue_id));
        update_post_meta($post_id, '_news_scraper_source_url', esc_url_raw($source_url));
        if (!empty($article_data['author'])) {
            update_post_meta($post_id, '_news_scraper_author', sanitize_text_field($article_data['author']));
        }
        if (!empty($article_data['md_file_path'])) {
            update_post_meta($post_id, '_news_scraper_md_file', sanitize_text_field($article_data['md_file_path']));
        }

        // Sideload Featured Image if available
        if (!empty($article_data['featured_image'])) {
            $this->sideload_featured_image($post_id, $article_data['featured_image'], $headline);
        }

        // Update queue item
        News_Scraper_DB::update_queue_item($queue_id, array(
            'status'  => 'posted',
            'post_id' => $post_id,
        ));

        return $post_id;
    }

    /**
     * Sideload remote image to WordPress Media Library and set as Post Thumbnail
     */
    protected function sideload_featured_image($post_id, $image_url, $description) {
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Download and attach image
        $attach_id = media_sideload_image($image_url, $post_id, sanitize_text_field($description), 'id');

        if (!is_wp_error($attach_id) && $attach_id > 0) {
            set_post_thumbnail($post_id, $attach_id);
            return $attach_id;
        }

        return false;
    }
}
