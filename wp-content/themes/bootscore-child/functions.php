<?php
/**
 * Bootscore Child Theme Functions - Meridian Global Finance
 *
 * @package Bootscore Child
 * @version 6.0.0
 */

defined('ABSPATH') || exit;

// Theme setup
add_action('after_setup_theme', 'meridian_child_theme_setup');
function meridian_child_theme_setup() {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Navigation', 'bootscore-child'),
        'secondary' => __('Secondary Navigation', 'bootscore-child'),
    ));
}

// Enqueue styles & scripts
add_action('wp_enqueue_scripts', 'meridian_child_enqueue_assets');
function meridian_child_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'meridian-google-fonts',
        'https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700;8..60,900&family=Libre+Franklin:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Bootstrap 5.3.3 CSS
    wp_enqueue_style(
        'bootstrap-5',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        array(),
        '5.3.3'
    );

    // Meridian CSS
    $meridian_ver = file_exists(get_stylesheet_directory() . '/assets/css/meridian.css')
        ? filemtime(get_stylesheet_directory() . '/assets/css/meridian.css')
        : '1.0.0';
    wp_enqueue_style(
        'meridian-theme-style',
        get_stylesheet_directory_uri() . '/assets/css/meridian.css',
        array('bootstrap-5'),
        $meridian_ver
    );

    // Bootstrap JS
    wp_enqueue_script(
        'bootstrap-5-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.3',
        true
    );
}

/**
 * Custom helper for Ad slots
 */
function meridian_ad_slot($width = 728, $height = 90, $sticky = false) {
    $sticky_class = $sticky ? 'ad-sticky' : '';
    return '<div class="' . esc_attr($sticky_class) . '"><div class="ad" style="max-width:' . intval($width) . 'px;aspect-ratio:' . intval($width) . '/' . intval($height) . '">
        <span class="lbl">Advertisement</span><span class="sz">' . intval($width) . ' × ' . intval($height) . ($sticky ? ' · sticky' : '') . '</span></div></div>';
}

/**
 * Custom helper for Section Headings
 */
function meridian_section_head($title, $link_url = '') {
    $output = '<div class="section-head">';
    if ($link_url) {
        $output .= '<a class="font-serif" href="' . esc_url($link_url) . '" style="font-size:18px;font-weight:700;color:var(--ink);">' . esc_html($title) . '</a>';
        $output .= '<a class="see-all" href="' . esc_url($link_url) . '">See all</a>';
    } else {
        $output .= '<h2>' . esc_html($title) . '</h2>';
    }
    $output .= '</div>';
    return $output;
}

/**
 * Crawl4AI Integration
 */
require_once get_stylesheet_directory() . '/inc/class-crawl4ai-client.php';
if (is_admin()) {
    require_once get_stylesheet_directory() . '/inc/admin-crawl4ai.php';
}
