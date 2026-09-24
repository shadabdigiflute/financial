<?php
/**
 * WP-Admin Crawl4AI Interface & AJAX Handlers
 *
 * @package Bootscore Child
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu for Crawl4AI
 */
add_action('admin_menu', 'financial_crawl4ai_admin_menu');
function financial_crawl4ai_admin_menu() {
    add_menu_page(
        __('Crawl4AI Market Crawler', 'bootscore-child'),
        __('Market Crawler', 'bootscore-child'),
        'manage_options',
        'financial-crawler',
        'financial_crawl4ai_render_admin_page',
        'dashicons-rss',
        30
    );
}

/**
 * Render Admin Page
 */
function financial_crawl4ai_render_admin_page() {
    $client = new Financial_Crawl4AI_Client();
    $health = $client->check_health();

    $api_url = defined('CRAWL4AI_API_URL') ? CRAWL4AI_API_URL : 'http://crawl4ai.51.222.83.114.sslip.io';
    ?>
    <div class="wrap" style="max-width: 1100px;">
        <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <span class="dashicons dashicons-rss" style="font-size:32px;width:32px;height:32px;color:#0d6efd;"></span>
            <?php esc_html_e('Financial Crawl4AI Market Crawler', 'bootscore-child'); ?>
        </h1>

        <!-- System Status Banner -->
        <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));gap:15px;margin-bottom:25px;">
            <div style="background:#fff;padding:15px 20px;border-radius:8px;border-left:4px solid <?php echo $health['success'] ? '#198754' : '#dc3545'; ?>;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:12px;color:#6c757d;text-transform:uppercase;font-weight:600;"><?php esc_html_e('Service Status', 'bootscore-child'); ?></div>
                <div style="font-size:18px;font-weight:700;margin-top:4px;color:<?php echo $health['success'] ? '#198754' : '#dc3545'; ?>;">
                    <?php echo $health['success'] ? '● Connected & Ready' : '● Offline / Error'; ?>
                </div>
                <div style="font-size:11px;color:#888;margin-top:2px;">
                    <?php echo esc_html($health['message']); ?>
                </div>
            </div>

            <div style="background:#fff;padding:15px 20px;border-radius:8px;border-left:4px solid #0d6efd;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:12px;color:#6c757d;text-transform:uppercase;font-weight:600;"><?php esc_html_e('Crawl4AI Version', 'bootscore-child'); ?></div>
                <div style="font-size:18px;font-weight:700;margin-top:4px;color:#212529;">
                    v<?php echo esc_html($health['version']); ?>
                </div>
                <div style="font-size:11px;color:#888;margin-top:2px;">
                    Latency: <?php echo esc_html($health['latency_ms']); ?> ms
                </div>
            </div>

            <div style="background:#fff;padding:15px 20px;border-radius:8px;border-left:4px solid #6f42c1;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:12px;color:#6c757d;text-transform:uppercase;font-weight:600;"><?php esc_html_e('Coolify Endpoint', 'bootscore-child'); ?></div>
                <div style="font-size:13px;font-weight:600;margin-top:6px;word-break:break-all;color:#495057;">
                    <code><?php echo esc_html($api_url); ?></code>
                </div>
                <div style="font-size:11px;color:#888;margin-top:2px;">
                    Shared with Alhamd microservice
                </div>
            </div>
        </div>

        <!-- Crawler Workspace -->
        <div style="background:#fff;padding:24px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.08);margin-bottom:25px;">
            <h2 style="margin-top:0;font-size:18px;border-bottom:1px solid #eee;padding-bottom:10px;">
                <?php esc_html_e('Execute Real-Time Web Crawl', 'bootscore-child'); ?>
            </h2>
            <p style="color:#6c757d;font-size:13px;">
                <?php esc_html_e('Enter any financial news URL, stock analysis, or market research page to crawl and extract clean, LLM-ready markdown.', 'bootscore-child'); ?>
            </p>

            <form id="crawl4ai-form" onsubmit="return false;">
                <div style="display:flex;gap:12px;margin-bottom:15px;flex-wrap:wrap;">
                    <input type="url" id="crawl-url" class="regular-text" style="flex:1;min-width:320px;padding:8px 12px;font-size:14px;border-radius:4px;" placeholder="https://www.reuters.com/markets/... or any URL" required>
                    <select id="crawl-filter" style="padding:8px 12px;border-radius:4px;">
                        <option value="fit" selected>Filter: Fit (Clean Content)</option>
                        <option value="raw">Filter: Raw (Complete Content)</option>
                        <option value="bm25">Filter: BM25 (Keyword Weighted)</option>
                    </select>
                    <button type="button" id="btn-crawl" class="button button-primary" style="padding:4px 20px;font-size:14px;display:flex;align-items:center;gap:6px;">
                        <span class="dashicons dashicons-search" style="margin-top:3px;"></span>
                        <?php esc_html_e('Crawl URL', 'bootscore-child'); ?>
                    </button>
                </div>
            </form>

            <div id="crawl-loading" style="display:none;padding:20px;text-align:center;background:#f8f9fa;border-radius:6px;border:1px dashed #ced4da;">
                <span class="spinner is-active" style="float:none;margin-right:8px;"></span>
                <strong style="color:#0d6efd;font-size:14px;"><?php esc_html_e('Crawling page via Crawl4AI headless engine... Please wait 5-15s.', 'bootscore-child'); ?></strong>
            </div>

            <div id="crawl-error" style="display:none;padding:12px 16px;background:#f8d7da;color:#842029;border-radius:6px;margin-top:15px;"></div>

            <!-- Result Box -->
            <div id="crawl-result" style="display:none;margin-top:20px;border-top:1px solid #dee2e6;padding-top:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h3 id="result-title" style="margin:0 0 4px 0;font-size:18px;"></h3>
                        <span id="result-stats" style="font-size:12px;color:#6c757d;"></span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" id="btn-import-draft" class="button button-secondary" style="color:#198754;border-color:#198754;font-weight:600;">
                            <span class="dashicons dashicons-yes" style="vertical-align:text-bottom;"></span>
                            <?php esc_html_e('Save as WordPress Draft Post', 'bootscore-child'); ?>
                        </button>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <button type="button" class="button button-small tab-btn active" data-tab="preview-tab">Formatted Preview</button>
                    <button type="button" class="button button-small tab-btn" data-tab="raw-tab">Raw Markdown</button>
                </div>

                <div id="preview-tab" class="tab-content" style="background:#fdfdfd;padding:20px;border:1px solid #e2e8f0;border-radius:6px;max-height:450px;overflow-y:auto;line-height:1.7;"></div>

                <div id="raw-tab" class="tab-content" style="display:none;">
                    <textarea id="raw-markdown" readonly style="width:100%;height:350px;font-family:monospace;font-size:12px;padding:12px;border-radius:6px;background:#1e1e1e;color:#f8f9fa;"></textarea>
                </div>
            </div>
        </div>

        <!-- Documentation Card -->
        <div style="background:#fff;padding:20px 24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <h3 style="margin-top:0;font-size:15px;color:#495057;">
                <?php esc_html_e('Integration & Architecture Details', 'bootscore-child'); ?>
            </h3>
            <ul style="color:#6c757d;font-size:13px;line-height:1.8;margin-bottom:0;padding-left:20px;">
                <li><strong>Microservice Host:</strong> Coolify Docker container <code>crawl4ai</code> on <code>51.222.83.114:11235</code></li>
                <li><strong>Shared Usage:</strong> Works across both <code>financial</code> and <code>alhamd</code> projects seamlessly with zero cross-talk.</li>
                <li><strong>PHP Client:</strong> Available across theme and plugins via <code>$client = new Financial_Crawl4AI_Client();</code></li>
                <li><strong>CLI Pipeline:</strong> Standalone CLI script available in <code>pipeline/crawl_financial_data.php</code></li>
            </ul>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var currentCrawledMarkdown = '';
        var currentCrawledUrl = '';

        // Tab switching
        $('.tab-btn').on('click', function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').hide();
            $('#' + $(this).data('tab')).show();
        });

        // Trigger Crawl
        $('#btn-crawl').on('click', function() {
            var url = $('#crawl-url').val().trim();
            var filter = $('#crawl-filter').val();

            if (!url) {
                alert('Please enter a valid URL.');
                return;
            }

            $('#crawl-error').hide();
            $('#crawl-result').hide();
            $('#crawl-loading').show();
            $('#btn-crawl').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'financial_crawl4ai_do_crawl',
                    nonce: '<?php echo wp_create_nonce('financial_crawl4ai_nonce'); ?>',
                    url: url,
                    filter: filter
                },
                success: function(res) {
                    $('#crawl-loading').hide();
                    $('#btn-crawl').prop('disabled', false);

                    if (res.success && res.data) {
                        currentCrawledMarkdown = res.data.markdown;
                        currentCrawledUrl = res.data.url;

                        var words = currentCrawledMarkdown.split(/\s+/).filter(Boolean).length;
                        $('#result-title').text(res.data.suggested_title || 'Crawled Content');
                        $('#result-stats').text('Source: ' + currentCrawledUrl + ' | Words: ~' + words + ' | Status: 200 OK');

                        $('#raw-markdown').val(currentCrawledMarkdown);
                        $('#preview-tab').html(res.data.html_preview);
                        $('#crawl-result').slideDown();
                    } else {
                        $('#crawl-error').text(res.data && res.data.error ? res.data.error : 'Failed to crawl URL.').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#crawl-loading').hide();
                    $('#btn-crawl').prop('disabled', false);
                    $('#crawl-error').text('AJAX Request Error: ' + error).show();
                }
            });
        });

        // Trigger Save Draft
        $('#btn-import-draft').on('click', function() {
            if (!currentCrawledMarkdown) return;

            var title = $('#result-title').text();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving draft post...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'financial_crawl4ai_import_draft',
                    nonce: '<?php echo wp_create_nonce('financial_crawl4ai_nonce'); ?>',
                    url: currentCrawledUrl,
                    title: title,
                    markdown: currentCrawledMarkdown
                },
                success: function(res) {
                    if (res.success && res.data.post_id) {
                        $btn.text('Draft Created! (ID: #' + res.data.post_id + ')').css({'color':'#0d6efd', 'border-color':'#0d6efd'});
                        if (res.data.edit_url) {
                            window.open(res.data.edit_url, '_blank');
                        }
                    } else {
                        alert('Could not save draft: ' + (res.data.error || 'Unknown error'));
                        $btn.prop('disabled', false).text('Save as WordPress Draft Post');
                    }
                },
                error: function() {
                    alert('Server error while saving draft.');
                    $btn.prop('disabled', false).text('Save as WordPress Draft Post');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX Handler: Execute Crawl
 */
add_action('wp_ajax_financial_crawl4ai_do_crawl', 'financial_crawl4ai_ajax_do_crawl');
function financial_crawl4ai_ajax_do_crawl() {
    check_ajax_referer('financial_crawl4ai_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied.'));
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'fit';

    if (empty($url)) {
        wp_send_json_error(array('error' => 'URL cannot be empty.'));
    }

    $client = new Financial_Crawl4AI_Client();
    $crawl = $client->crawl_markdown($url, $filter);

    if (!$crawl['success']) {
        wp_send_json_error(array('error' => $crawl['error']));
    }

    // Extract suggested title from first heading or URL
    $suggested_title = '';
    if (preg_match('/^#\s+(.+)$/m', $crawl['markdown'], $m)) {
        $suggested_title = trim($m[1]);
    } else {
        $path = parse_url($url, PHP_URL_PATH);
        $suggested_title = ucwords(str_replace(array('-', '_', '/'), ' ', trim($path, '/')));
    }

    $html_preview = $client->markdown_to_html($crawl['markdown']);

    wp_send_json_success(array(
        'url'             => $crawl['url'],
        'markdown'        => $crawl['markdown'],
        'suggested_title' => $suggested_title ?: 'Financial Market Report',
        'html_preview'    => $html_preview,
    ));
}

/**
 * AJAX Handler: Import Crawled Content as Draft Post
 */
add_action('wp_ajax_financial_crawl4ai_import_draft', 'financial_crawl4ai_ajax_import_draft');
function financial_crawl4ai_ajax_import_draft() {
    check_ajax_referer('financial_crawl4ai_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied.'));
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : 'Market Intelligence';
    $markdown = isset($_POST['markdown']) ? wp_unslash($_POST['markdown']) : '';

    if (empty($markdown)) {
        wp_send_json_error(array('error' => 'No content to import.'));
    }

    $client = new Financial_Crawl4AI_Client();
    $post_id = $client->import_as_post($url, $title, $markdown, 'draft');

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('error' => $post_id->get_error_message()));
    }

    wp_send_json_success(array(
        'post_id'  => $post_id,
        'edit_url' => get_edit_post_link($post_id, 'raw'),
    ));
}
