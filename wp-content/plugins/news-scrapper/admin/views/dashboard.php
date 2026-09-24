<?php
/**
 * Master Admin Dashboard View
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

$crawl4ai_url   = get_option('news_scrapper_crawl4ai_url', defined('CRAWL4AI_API_URL') ? CRAWL4AI_API_URL : 'http://crawl4ai.51.222.83.114.sslip.io');
$crawl4ai_token = get_option('news_scrapper_crawl4ai_token', defined('CRAWL4AI_API_TOKEN') ? CRAWL4AI_API_TOKEN : '8Qz8RKv72nEBB$');
$gemini_key     = get_option('news_scrapper_gemini_api_key', defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
$gemini_model   = get_option('news_scrapper_gemini_model', 'gemini-2.0-flash-lite');
?>

<div class="wrap news-scraper-wrap">

    <!-- Top Header -->
    <div class="ns-header">
        <div class="ns-title-group">
            <div class="ns-icon-badge">
                <span class="dashicons dashicons-rss"></span>
            </div>
            <div>
                <h1><?php esc_html_e('News Scrapper', 'news-scrapper'); ?></h1>
                <p><?php esc_html_e('Enterprise Crawl4AI scraping & Gemini Lite AI rewriting pipeline', 'news-scrapper'); ?></p>
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;">
            <button type="button" id="ns-btn-process-queue" class="ns-btn" style="background:#059669;color:#fff;border-color:#059669;" title="Process pending articles in queue with AI">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Process Queue Now', 'news-scrapper'); ?>
            </button>
            <button type="button" id="ns-btn-add-feed" class="ns-btn ns-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add New Feed', 'news-scrapper'); ?>
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="ns-stats-grid">
        <div class="ns-stat-card blue">
            <div class="ns-stat-label"><?php esc_html_e('Active Feeds', 'news-scrapper'); ?></div>
            <div class="ns-stat-val"><?php echo intval($stats['active_feeds']); ?> / <?php echo intval($stats['total_feeds']); ?></div>
            <div class="ns-stat-sub"><?php esc_html_e('Every 8 Hours Listing Crawl', 'news-scrapper'); ?></div>
        </div>

        <div class="ns-stat-card green">
            <div class="ns-stat-label"><?php esc_html_e('Published Posts', 'news-scrapper'); ?></div>
            <div class="ns-stat-val"><?php echo intval($stats['posted']); ?></div>
            <div class="ns-stat-sub"><?php esc_html_e('AI Rewritten & Sideloaded', 'news-scrapper'); ?></div>
        </div>

        <div class="ns-stat-card amber">
            <div class="ns-stat-label"><?php esc_html_e('In Queue / Discovered', 'news-scrapper'); ?></div>
            <div class="ns-stat-val"><?php echo intval($stats['discovered']); ?></div>
            <div class="ns-stat-sub"><?php esc_html_e('AI Worker runs every 3-5 min', 'news-scrapper'); ?></div>
        </div>

        <div class="ns-stat-card purple">
            <div class="ns-stat-label"><?php esc_html_e('Crawl4AI Engine', 'news-scrapper'); ?></div>
            <div class="ns-stat-val" style="font-size:18px;display:flex;align-items:center;gap:6px;margin-top:10px;">
                <span style="color:<?php echo $health['success'] ? '#10b981' : '#ef4444'; ?>;">●</span>
                <?php echo $health['success'] ? 'Online (' . esc_html($health['version']) . ')' : 'Offline'; ?>
            </div>
            <div class="ns-stat-sub"><?php echo esc_html(parse_url($crawl4ai_url, PHP_URL_HOST) ?? 'Coolify Service'); ?></div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="ns-nav-tabs">
        <a href="#" class="ns-tab-link active" data-tab="tab-feeds">
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Feeds Manager', 'news-scrapper'); ?>
        </a>
        <a href="#" class="ns-tab-link" data-tab="tab-csv">
            <span class="dashicons dashicons-media-spreadsheet"></span>
            <?php esc_html_e('Bulk CSV Importer', 'news-scrapper'); ?>
        </a>
        <a href="#" class="ns-tab-link" data-tab="tab-queue">
            <span class="dashicons dashicons-hourglass"></span>
            <?php esc_html_e('Scraping Queue', 'news-scrapper'); ?>
        </a>
        <a href="#" class="ns-tab-link" data-tab="tab-settings">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Settings & API Keys', 'news-scrapper'); ?>
        </a>
    </div>

    <!-- PANEL 1: Feeds Manager -->
    <div id="tab-feeds" class="ns-panel active">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h2 style="margin:0;font-size:16px;"><?php esc_html_e('Active News Sources & Automated Feeds', 'news-scrapper'); ?></h2>
            <span style="font-size:12px;color:#64748b;"><?php esc_html_e('Tier 1: Checks source URLs every 8 hours. Tier 2: AI rewrites & publishes every 3-5 minutes.', 'news-scrapper'); ?></span>
        </div>

        <?php if (!empty($feeds)): ?>
            <table class="ns-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Feed Name</th>
                        <th>Source URL</th>
                        <th>Categories</th>
                        <th>Depth</th>
                        <th>Status</th>
                        <th>Post Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeds as $feed):
                        $cats = json_decode($feed['category_ids'], true);
                        $cat_names = array();
                        if (is_array($cats)) {
                            foreach ($cats as $cid) {
                                $c = get_category($cid);
                                if ($c && !is_wp_error($c)) {
                                    $cat_names[] = $c->name;
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($feed['id']); ?></strong></td>
                        <td><strong><?php echo esc_html($feed['feed_name']); ?></strong></td>
                        <td><a href="<?php echo esc_url($feed['source_url']); ?>" target="_blank" rel="noopener noreferrer" style="color:#2563eb;text-decoration:none;"><?php echo esc_html(substr($feed['source_url'], 0, 45) . (strlen($feed['source_url']) > 45 ? '...' : '')); ?></a></td>
                        <td><span style="font-size:12px;color:#475569;"><?php echo !empty($cat_names) ? esc_html(implode(', ', $cat_names)) : '—'; ?></span></td>
                        <td><?php echo esc_html($feed['pagination_depth']); ?> pgs</td>
                        <td>
                            <span class="ns-badge <?php echo esc_attr($feed['status']); ?>">
                                ● <?php echo ucfirst(esc_html($feed['status'])); ?>
                            </span>
                        </td>
                        <td><span class="ns-badge" style="background:#f1f5f9;color:#334155;"><?php echo ucfirst(esc_html($feed['post_status'])); ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="ns-btn ns-btn-primary ns-btn-sm ns-crawl-feed" data-id="<?php echo esc_attr($feed['id']); ?>" title="Crawl source URL and enqueue new articles (8-Hour Cycle)">
                                    <span class="dashicons dashicons-search" style="font-size:14px;width:14px;height:14px;"></span> Crawl (8h)
                                </button>
                                <button type="button" class="ns-btn ns-btn-secondary ns-btn-sm ns-run-feed" data-id="<?php echo esc_attr($feed['id']); ?>" title="Run Crawl & Rewrite immediately">
                                    <span class="dashicons dashicons-controls-play" style="font-size:14px;width:14px;height:14px;"></span> Full Run
                                </button>
                                <button type="button" class="ns-btn ns-btn-secondary ns-btn-sm ns-edit-feed" data-id="<?php echo esc_attr($feed['id']); ?>">
                                    Edit
                                </button>
                                <button type="button" class="ns-btn ns-btn-secondary ns-btn-sm ns-delete-feed" data-id="<?php echo esc_attr($feed['id']); ?>" style="color:#ef4444;">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center;padding:50px 20px;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;">
                <span class="dashicons dashicons-rss" style="font-size:48px;width:48px;height:48px;color:#94a3b8;margin-bottom:12px;"></span>
                <h3 style="margin:0 0 6px 0;"><?php esc_html_e('No Feeds Configured Yet', 'news-scrapper'); ?></h3>
                <p style="color:#64748b;margin:0 0 16px 0;font-size:13px;"><?php esc_html_e('Add your first news source URL manually or import via CSV.', 'news-scrapper'); ?></p>
                <button type="button" class="ns-btn ns-btn-primary" onclick="jQuery('#ns-btn-add-feed').click();">
                    <?php esc_html_e('+ Add New Feed', 'news-scrapper'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- PANEL 2: Bulk CSV Importer -->
    <div id="tab-csv" class="ns-panel">
        <h2 style="margin-top:0;font-size:16px;"><?php esc_html_e('Bulk Import News Feeds via CSV', 'news-scrapper'); ?></h2>
        <p style="color:#64748b;font-size:13px;">
            <?php esc_html_e('Upload a CSV file containing your news categories, URLs, and hierarchy. Any new categories or subcategories (parent > child) specified in the CSV will be created automatically at any depth level.', 'news-scrapper'); ?>
        </p>

        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 18px;margin-bottom:20px;">
            <strong style="color:#166534;font-size:13px;"><?php esc_html_e('Expected CSV Format Example:', 'news-scrapper'); ?></strong>
            <pre style="background:#fff;padding:10px 14px;border-radius:6px;border:1px solid #e2e8f0;margin:8px 0 0 0;font-size:12px;color:#1e293b;">Feed Name,Target URL,Categories,Pagination Depth,Post Status
Forex Central,https://www.reuters.com/markets/currencies,"Markets > Forex > Central Banks",3,draft
US Economy,https://www.cnbc.com/economy/,"Global Economy > US Markets",2,publish
Tech Stocks,https://techcrunch.com/category/startups/,"Technology > Venture Capital > Startups",4,draft</pre>
        </div>

        <form id="ns-csv-form" enctype="multipart/form-data">
            <div class="ns-dropzone" onclick="document.getElementById('ns-csv-file').click();">
                <span class="dashicons dashicons-upload"></span>
                <h3 style="margin:0 0 6px 0;font-size:16px;color:#1e293b;"><?php esc_html_e('Click or Drag & Drop CSV File Here', 'news-scrapper'); ?></h3>
                <p style="margin:0;font-size:13px;color:#64748b;"><?php esc_html_e('Supports UTF-8 CSV with unlimited category hierarchies', 'news-scrapper'); ?></p>
                <input type="file" id="ns-csv-file" name="csv_file" accept=".csv" style="display:none;" onchange="jQuery('#ns-csv-filename').text(this.files[0] ? this.files[0].name : '');">
                <div id="ns-csv-filename" style="margin-top:12px;font-weight:600;color:#2563eb;"></div>
            </div>

            <div style="margin-top:20px;text-align:right;">
                <button type="submit" id="ns-btn-upload-csv" class="ns-btn ns-btn-primary" style="padding:10px 24px;">
                    <span class="dashicons dashicons-database-import"></span>
                    <?php esc_html_e('Import CSV Feeds', 'news-scrapper'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- PANEL 3: Scraping Queue -->
    <div id="tab-queue" class="ns-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <div>
                <h2 style="margin:0;font-size:16px;"><?php esc_html_e('Discovered & Processed Article Queue', 'news-scrapper'); ?></h2>
                <p style="color:#64748b;font-size:13px;margin:4px 0 0 0;"><?php esc_html_e('Articles are enqueued by the 8-hour listing crawler and processed every 3-5 minutes by the Gemini AI worker.', 'news-scrapper'); ?></p>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="ns-btn ns-btn-secondary ns-btn-flush-data" style="color:#ef4444;border-color:#fca5a5;" title="Flush all queued items and generated posts">
                    <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Flush Scraped Data', 'news-scrapper'); ?>
                </button>
                <button type="button" class="ns-btn ns-btn-primary ns-process-queue-now" style="background:#059669;border-color:#059669;">
                    <span class="dashicons dashicons-update"></span> <?php esc_html_e('Process Queue Batch Now', 'news-scrapper'); ?>
                </button>
            </div>
        </div>

        <?php if (!empty($queue)): ?>
            <table class="ns-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Feed</th>
                        <th>Headline / URL</th>
                        <th>Author & Date</th>
                        <th>Status</th>
                        <th>Post Link</th>
                        <th>MD Backup</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue as $item): ?>
                    <tr>
                        <td>#<?php echo esc_html($item['id']); ?></td>
                        <td>Feed #<?php echo esc_html($item['feed_id']); ?></td>
                        <td>
                            <strong><?php echo esc_html($item['title_raw'] ?: 'Discovered Link'); ?></strong><br>
                            <a href="<?php echo esc_url($item['article_url']); ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px;color:#64748b;"><?php echo esc_html(substr($item['article_url'], 0, 60) . '...'); ?></a>
                        </td>
                        <td>
                            <?php if (!empty($item['author'])): ?>
                                <span style="font-size:11px;font-weight:600;color:#1e293b;"><?php echo esc_html($item['author']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($item['published_date'])): ?>
                                <span style="font-size:11px;color:#64748b;"><?php echo esc_html(substr($item['published_date'], 0, 16)); ?></span>
                            <?php else: ?>
                                <span style="font-size:11px;color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ns-badge <?php echo esc_attr($item['status']); ?>">
                                ● <?php echo ucfirst(esc_html($item['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($item['post_id'])): ?>
                                <a href="<?php echo esc_url(get_edit_post_link($item['post_id'])); ?>" target="_blank" style="color:#10b981;font-weight:600;text-decoration:none;">
                                    Post #<?php echo esc_html($item['post_id']); ?> ↗
                                </a>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['md_file_path']) && file_exists($item['md_file_path'])): ?>
                                <span style="font-size:11px;color:#475569;" title="<?php echo esc_attr($item['md_file_path']); ?>">
                                    ✓ Saved (.md)
                                </span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:#64748b;"><?php echo esc_html($item['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <?php esc_html_e('Queue is currently empty. Run any feed to discover articles.', 'news-scrapper'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PANEL 4: Settings & API Keys -->
    <div id="tab-settings" class="ns-panel">
        <h2 style="margin-top:0;font-size:16px;"><?php esc_html_e('Crawl4AI & Google Gemini Configuration', 'news-scrapper'); ?></h2>
        <p style="color:#64748b;font-size:13px;"><?php esc_html_e('Manage Crawl4AI endpoints and Google Gemini Lite API credentials.', 'news-scrapper'); ?></p>

        <form id="ns-settings-form" style="max-width:680px;">
            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Crawl4AI Endpoint URL</label>
                <input type="url" id="ns-setting-crawl4ai-url" class="regular-text" style="width:100%;padding:8px 12px;border-radius:6px;" value="<?php echo esc_attr($crawl4ai_url); ?>" required>
                <span style="font-size:11px;color:#64748b;">Live shared Coolify microservice URL.</span>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Crawl4AI Authorization Token</label>
                <input type="text" id="ns-setting-crawl4ai-token" class="regular-text" style="width:100%;padding:8px 12px;border-radius:6px;" value="<?php echo esc_attr($crawl4ai_token); ?>" required>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Google Gemini API Key</label>
                <input type="password" id="ns-setting-gemini-key" class="regular-text" style="width:100%;padding:8px 12px;border-radius:6px;" value="<?php echo esc_attr($gemini_key); ?>" placeholder="AIzaSy...">
                <span style="font-size:11px;color:#64748b;">Get free or low-cost API key from <a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>.</span>
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Gemini Lite Model</label>
                <select id="ns-setting-gemini-model" style="width:100%;padding:8px 12px;border-radius:6px;">
                    <option value="gemini-2.0-flash-lite" <?php selected($gemini_model, 'gemini-2.0-flash-lite'); ?>>Gemini 2.0 Flash Lite (Recommended - Ultra Fast & Low Cost)</option>
                    <option value="gemini-1.5-flash" <?php selected($gemini_model, 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash (Standard)</option>
                    <option value="gemini-1.5-flash-8b" <?php selected($gemini_model, 'gemini-1.5-flash-8b'); ?>>Gemini 1.5 Flash 8B (High Throughput)</option>
                </select>
            </div>

            <button type="submit" id="ns-btn-save-settings" class="ns-btn ns-btn-primary" style="padding:10px 24px;">
                <?php esc_html_e('Save Settings', 'news-scrapper'); ?>
            </button>
        </form>

        <hr style="margin:32px 0 24px 0;border:0;border-top:1px solid #e2e8f0;max-width:680px;">

        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:18px 22px;max-width:680px;">
            <h3 style="margin:0 0 6px 0;color:#991b1b;font-size:15px;"><?php esc_html_e('Danger Zone: Flush Scraped Data', 'news-scrapper'); ?></h3>
            <p style="color:#7f1d1d;font-size:13px;margin:0 0 14px 0;">
                <?php esc_html_e('Flush all queued items, discovered links, scraped markdown files, and automatically generated posts. Configured feeds remain saved so you can run fresh crawls anytime.', 'news-scrapper'); ?>
            </p>
            <button type="button" class="ns-btn ns-btn-flush-data" style="background:#dc2626;color:#fff;border-color:#dc2626;padding:8px 18px;">
                <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Flush All Scraped Data Now', 'news-scrapper'); ?>
            </button>
        </div>
    </div>

</div>

<!-- ADD / EDIT FEED MODAL -->
<div id="ns-modal-feed" class="ns-modal-overlay">
    <div class="ns-modal">
        <div class="ns-modal-header">
            <h2 id="ns-modal-title"><?php esc_html_e('Add New News Feed', 'news-scrapper'); ?></h2>
            <button type="button" class="ns-modal-close">&times;</button>
        </div>

        <form id="ns-feed-form">
            <input type="hidden" id="ns-feed-id" value="">

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Feed Name</label>
                <input type="text" id="ns-feed-name" class="regular-text" style="width:100%;padding:8px 12px;border-radius:6px;" placeholder="e.g. Reuters Currencies" required>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Target Listing or Category URL</label>
                <input type="url" id="ns-source-url" class="regular-text" style="width:100%;padding:8px 12px;border-radius:6px;" placeholder="https://www.reuters.com/markets/currencies/" required>
                <span style="font-size:11px;color:#64748b;">Crawl4AI will scrape this listing and discover articles with pagination.</span>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Target WordPress Categories (Parent & Child Hierarchy)</label>
                <div class="ns-category-tree">
                    <?php News_Scraper_Admin::render_category_tree(0, array()); ?>
                </div>
                <span style="font-size:11px;color:#64748b;">Select any parent or subcategories. Crawled posts will be assigned to all selected terms.</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
                <div>
                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Pagination Depth (Pages)</label>
                    <input type="number" id="ns-pagination-depth" value="3" min="1" max="20" style="width:100%;padding:8px 12px;border-radius:6px;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">Post Status</label>
                    <select id="ns-post-status" style="width:100%;padding:8px 12px;border-radius:6px;">
                        <option value="draft" selected>Draft (Recommended)</option>
                        <option value="publish">Publish Immediately</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" id="ns-btn-cancel-feed" class="ns-btn ns-btn-secondary"><?php esc_html_e('Cancel', 'news-scrapper'); ?></button>
                <button type="submit" id="ns-btn-save-feed" class="ns-btn ns-btn-primary"><?php esc_html_e('Save Feed', 'news-scrapper'); ?></button>
            </div>
        </form>
    </div>
</div>
