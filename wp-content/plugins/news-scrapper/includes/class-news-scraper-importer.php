<?php
/**
 * Bulk CSV Importer with Recursive Category Creator
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Importer {

    /**
     * Parse and import feeds from CSV file
     *
     * Expected CSV format:
     * Feed Name, Target URL, Categories, Pagination Depth, Post Status
     * Example Categories: "Markets > Forex > Central Banks; Global Economy > Trade"
     *
     * @param string $file_path
     * @return array
     */
    public function import_csv($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return array('success' => false, 'message' => 'Unable to read uploaded CSV file.');
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => 'Failed to open file stream.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return array('success' => false, 'message' => 'CSV file is empty.');
        }

        // Normalize header
        $header_map = array();
        foreach ($header as $index => $col) {
            $col_clean = strtolower(trim(str_replace(array(' ', '_', '-'), '', $col)));
            $header_map[$col_clean] = $index;
        }

        $imported_count = 0;
        $errors = array();
        $row_num = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $row_num++;
            if (empty(array_filter($row))) {
                continue;
            }

            // Extract values
            $feed_name = $this->get_col_val($row, $header_map, array('feedname', 'name', 'title'), 'Feed ' . ($row_num - 1));
            $target_url = $this->get_col_val($row, $header_map, array('targeturl', 'url', 'sourceurl', 'link'), '');
            $cat_string = $this->get_col_val($row, $header_map, array('categories', 'category', 'cats'), '');
            $depth = intval($this->get_col_val($row, $header_map, array('paginationdepth', 'depth', 'pages'), 3));
            $post_status = $this->get_col_val($row, $header_map, array('poststatus', 'status'), 'draft');

            if (empty($target_url) || !filter_var($target_url, FILTER_VALIDATE_URL)) {
                $errors[] = "Row $row_num skipped: Invalid URL '$target_url'.";
                continue;
            }

            // Parse & recursively create categories
            $category_ids = $this->resolve_or_create_categories($cat_string);

            $feed_data = array(
                'feed_name'        => sanitize_text_field($feed_name),
                'source_url'       => esc_url_raw($target_url),
                'category_ids'     => $category_ids,
                'pagination_depth' => max(1, $depth),
                'post_status'      => in_array($post_status, array('publish', 'draft'), true) ? $post_status : 'draft',
                'status'           => 'active',
            );

            $feed_id = News_Scraper_DB::save_feed($feed_data);
            if ($feed_id) {
                $imported_count++;
            }
        }

        fclose($handle);

        return array(
            'success'  => true,
            'imported' => $imported_count,
            'errors'   => $errors,
        );
    }

    /**
     * Resolve column value by candidate aliases
     */
    protected function get_col_val($row, $header_map, $aliases, $default = '') {
        foreach ($aliases as $alias) {
            if (isset($header_map[$alias]) && isset($row[$header_map[$alias]])) {
                $val = trim($row[$header_map[$alias]]);
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return $default;
    }

    /**
     * Recursively resolve or create categories with parent-child relationships
     *
     * Example input: "Markets > Forex > Central Banks; Global Economy > Trade"
     *
     * @param string $category_paths_string
     * @return array Array of term IDs
     */
    public function resolve_or_create_categories($category_paths_string) {
        if (empty($category_paths_string)) {
            // Default to Uncategorized (1) if empty
            return array(1);
        }

        $term_ids = array();
        // Multiple category paths separated by semicolon or comma (if not part of hierarchy)
        $paths = explode(';', $category_paths_string);

        foreach ($paths as $path) {
            $path = trim($path);
            if (empty($path)) {
                continue;
            }

            // Split hierarchy by '>' or '/'
            $parts = preg_split('/\s*[>\/]\s*/', $path);
            $parent_id = 0;

            foreach ($parts as $part_name) {
                $part_name = trim($part_name);
                if (empty($part_name)) {
                    continue;
                }

                // Check if category exists under this parent
                $term = term_exists($part_name, 'category', $parent_id);

                if ($term) {
                    $term_id = is_array($term) ? $term['term_id'] : $term;
                } else {
                    // Create new category at this depth
                    $new_term = wp_insert_term($part_name, 'category', array(
                        'parent' => $parent_id,
                    ));

                    if (!is_wp_error($new_term) && isset($new_term['term_id'])) {
                        $term_id = $new_term['term_id'];
                    } else {
                        // Fallback to term by slug or name if already exists globally
                        $existing = get_term_by('name', $part_name, 'category');
                        $term_id = $existing ? $existing->term_id : 1;
                    }
                }

                $parent_id = $term_id;
                $term_ids[] = $term_id;
            }
        }

        return !empty($term_ids) ? array_unique($term_ids) : array(1);
    }
}
