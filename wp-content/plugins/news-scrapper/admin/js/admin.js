/**
 * News Scrapper - Admin Dashboard Interactions
 */

jQuery(document).ready(function($) {

    // Tab Navigation
    $('.ns-tab-link').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('tab');

        $('.ns-tab-link').removeClass('active');
        $(this).addClass('active');

        $('.ns-panel').removeClass('active');
        $('#' + target).addClass('active');
    });

    // Open Add Feed Modal
    $('#ns-btn-add-feed').on('click', function() {
        $('#ns-feed-form')[0].reset();
        $('#ns-feed-id').val('');
        $('#ns-modal-title').text('Add New News Feed');
        $('#ns-modal-feed').css('display', 'flex');
    });

    // Close Modal
    $('.ns-modal-close, #ns-btn-cancel-feed').on('click', function() {
        $('#ns-modal-feed').hide();
    });

    // Save Feed Form
    $('#ns-feed-form').on('submit', function(e) {
        e.preventDefault();

        var selectedCats = [];
        $('.ns-cat-checkbox:checked').each(function() {
            selectedCats.push($(this).val());
        });

        if (selectedCats.length === 0) {
            alert('Please select at least one category.');
            return;
        }

        var data = {
            action: 'news_scraper_save_feed',
            nonce: newsScraperVars.nonce,
            id: $('#ns-feed-id').val(),
            feed_name: $('#ns-feed-name').val(),
            source_url: $('#ns-source-url').val(),
            pagination_depth: $('#ns-pagination-depth').val(),
            post_status: $('#ns-post-status').val(),
            category_ids: selectedCats
        };

        var $btn = $('#ns-btn-save-feed');
        $btn.prop('disabled', true).text('Saving...');

        $.post(newsScraperVars.ajaxurl, data, function(res) {
            $btn.prop('disabled', false).text('Save Feed');
            if (res.success) {
                $('#ns-modal-feed').hide();
                location.reload();
            } else {
                alert('Error: ' + (res.data || 'Could not save feed.'));
            }
        });
    });

    // Edit Feed
    $(document).on('click', '.ns-edit-feed', function() {
        var feedId = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_get_feed',
            nonce: newsScraperVars.nonce,
            id: feedId
        }, function(res) {
            $btn.prop('disabled', false);
            if (res.success && res.data) {
                var f = res.data;
                $('#ns-feed-id').val(f.id);
                $('#ns-feed-name').val(f.feed_name);
                $('#ns-source-url').val(f.source_url);
                $('#ns-pagination-depth').val(f.pagination_depth);
                $('#ns-post-status').val(f.post_status);

                $('.ns-cat-checkbox').prop('checked', false);
                var cats = JSON.parse(f.category_ids || '[]');
                cats.forEach(function(cid) {
                    $('.ns-cat-checkbox[value="' + cid + '"]').prop('checked', true);
                });

                $('#ns-modal-title').text('Edit News Feed #' + f.id);
                $('#ns-modal-feed').css('display', 'flex');
            }
        });
    });

    // Delete Feed
    $(document).on('click', '.ns-delete-feed', function() {
        if (!confirm('Are you sure you want to delete this feed and its queued articles?')) {
            return;
        }

        var feedId = $(this).data('id');
        var $row = $(this).closest('tr');

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_delete_feed',
            nonce: newsScraperVars.nonce,
            id: feedId
        }, function(res) {
            if (res.success) {
                $row.fadeOut(300, function() { $(this).remove(); });
            } else {
                alert('Could not delete feed.');
            }
        });
    });

    // Crawl Feed Listing (Tier 1 - 8hr cycle)
    $(document).on('click', '.ns-crawl-feed', function() {
        var feedId = $(this).data('id');
        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Crawling...');

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_crawl_feed',
            nonce: newsScraperVars.nonce,
            id: feedId
        }, function(res) {
            $btn.prop('disabled', false).html(originalText);
            if (res.success && res.data) {
                alert('Listing Crawled!\nDiscovered: ' + res.data.discovered_count + ' articles\nNew Enqueued: ' + res.data.new_enqueued + '\nTime: ' + res.data.duration_sec + 's\n\nQueued articles will be processed automatically by the 3-minute AI worker or you can click "Process Queue" to run now.');
                location.reload();
            } else {
                alert('Crawl failed: ' + (res.data || 'Unknown error'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalText);
            alert('Request timed out or encountered server error.');
        });
    });

    // Process Queue Batch (Tier 2 - 3min AI Worker)
    $(document).on('click', '#ns-btn-process-queue, .ns-process-queue-now', function() {
        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> AI Processing...');

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_process_queue',
            nonce: newsScraperVars.nonce,
            limit: 3
        }, function(res) {
            $btn.prop('disabled', false).html(originalText);
            if (res.success && res.data) {
                if (res.data.processed_count > 0) {
                    alert('Queue Worker Finished!\nProcessed & Published: ' + res.data.processed_count + ' articles in ' + res.data.duration_sec + 's\nPost IDs: ' + res.data.post_ids.join(', '));
                } else {
                    alert('Queue is empty! No discovered articles are waiting.');
                }
                location.reload();
            } else {
                alert('Queue processing failed: ' + (res.data || 'Unknown error'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalText);
            alert('Request timed out or encountered server error.');
        });
    });

    // Run Full Feed Cycle (Crawl + Process)
    $(document).on('click', '.ns-run-feed', function() {
        var feedId = $(this).data('id');
        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Running Full Cycle...');

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_run_feed',
            nonce: newsScraperVars.nonce,
            id: feedId
        }, function(res) {
            $btn.prop('disabled', false).html(originalText);
            if (res.success && res.data) {
                alert('Feed executed successfully!\nDiscovered: ' + res.data.discovered + ' URLs\nPublished: ' + res.data.published_count + ' posts in ' + res.data.duration_sec + 's');
                location.reload();
            } else {
                alert('Run failed: ' + (res.data || 'Unknown error'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalText);
            alert('Request timed out or encountered server error.');
        });
    });

    // CSV Form Upload
    $('#ns-csv-form').on('submit', function(e) {
        e.preventDefault();

        var fileInput = document.getElementById('ns-csv-file');
        if (!fileInput.files.length) {
            alert('Please select a CSV file.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'news_scraper_import_csv');
        formData.append('nonce', newsScraperVars.nonce);
        formData.append('csv_file', fileInput.files[0]);

        var $btn = $('#ns-btn-upload-csv');
        $btn.prop('disabled', true).text('Importing Feeds...');

        $.ajax({
            url: newsScraperVars.ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $btn.prop('disabled', false).text('Import CSV Feeds');
                if (res.success && res.data) {
                    alert('Successfully imported ' + res.data.imported + ' feeds!\n' + (res.data.errors.length ? res.data.errors.join('\n') : ''));
                    location.reload();
                } else {
                    alert('Import failed: ' + (res.data && res.data.message ? res.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Import CSV Feeds');
                alert('Error uploading file.');
            }
        });
    });

    // Save Settings
    $('#ns-settings-form').on('submit', function(e) {
        e.preventDefault();

        var data = {
            action: 'news_scraper_save_settings',
            nonce: newsScraperVars.nonce,
            crawl4ai_url: $('#ns-setting-crawl4ai-url').val(),
            crawl4ai_token: $('#ns-setting-crawl4ai-token').val(),
            gemini_key: $('#ns-setting-gemini-key').val(),
            gemini_model: $('#ns-setting-gemini-model').val()
        };

        var $btn = $('#ns-btn-save-settings');
        $btn.prop('disabled', true).text('Saving...');

        $.post(newsScraperVars.ajaxurl, data, function(res) {
            $btn.prop('disabled', false).text('Save Settings');
            if (res.success) {
                alert('Settings saved successfully.');
            } else {
                alert('Could not save settings.');
            }
        });
    });

    // Flush Scraped Data
    $(document).on('click', '.ns-btn-flush-data', function() {
        if (!confirm('Are you sure you want to flush all queued items, discovered links, scraped markdown archive files, and test posts?\n\n(Configured feeds will be preserved)')) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Flushing...');

        $.post(newsScraperVars.ajaxurl, {
            action: 'news_scraper_flush_data',
            nonce: newsScraperVars.nonce,
            delete_posts: 1,
            delete_feeds: 0
        }, function(res) {
            $btn.prop('disabled', false).html(originalText);
            if (res.success && res.data) {
                alert(res.data.message || 'Scraped data flushed successfully.');
                location.reload();
            } else {
                alert('Flush failed: ' + (res.data || 'Unknown error'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalText);
            alert('Request failed or server timeout.');
        });
    });
});
