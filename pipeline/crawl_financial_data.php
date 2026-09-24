<?php
/**
 * Standalone Financial News & Market Data Crawler CLI
 *
 * Uses the Crawl4AI microservice running on Coolify to scrape any target URL
 * and output clean markdown / JSON data.
 *
 * Usage:
 *   php pipeline/crawl_financial_data.php <URL> [options]
 *
 * Options:
 *   --filter=<fit|raw|bm25|llm>   Filter strategy (default: fit)
 *   --output=<dir>                Custom output directory (default: pipeline/data)
 *   --import-wp                   Import result directly as a WordPress draft post
 *
 * Examples:
 *   php pipeline/crawl_financial_data.php https://finance.yahoo.com/news/
 *   php pipeline/crawl_financial_data.php https://www.bloomberg.com/markets --import-wp
 */

$api_url = getenv('CRAWL4AI_API_URL') ?: 'http://crawl4ai.51.222.83.114.sslip.io';
$api_token = getenv('CRAWL4AI_API_TOKEN') ?: '8Qz8RKv72nEBB$';

// Parse command line arguments
$target_url = null;
$filter = 'fit';
$output_dir = __DIR__ . '/data';
$import_wp = false;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (strpos($arg, '--filter=') === 0) {
        $filter = substr($arg, 9);
    } elseif (strpos($arg, '--output=') === 0) {
        $output_dir = substr($arg, 9);
    } elseif ($arg === '--import-wp') {
        $import_wp = true;
    } elseif (strpos($arg, 'http') === 0) {
        $target_url = $arg;
    }
}

if (!$target_url) {
    echo "=========================================================\n";
    echo "  Financial Market Data Crawler CLI (Powered by Crawl4AI)\n";
    echo "=========================================================\n";
    echo "Usage:\n";
    echo "  php pipeline/crawl_financial_data.php <URL> [--filter=fit] [--import-wp]\n\n";
    echo "Crawl4AI Endpoint: {$api_url}\n";
    exit(1);
}

echo "\n[1/3] Connecting to Crawl4AI at: {$api_url}\n";
echo "      Target URL: {$target_url}\n";
echo "      Filter: {$filter}\n";

$ch = curl_init(rtrim($api_url, '/') . '/md');
$payload = json_encode([
    'url' => $target_url,
    'f'   => $filter
]);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $api_token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$elapsed = round(microtime(true) - $start_time, 2);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($curl_err || $http_code !== 200) {
    echo "\n[ERROR] Request failed! (HTTP {$http_code})\n";
    echo "        " . ($curl_err ?: substr($response, 0, 200)) . "\n";
    exit(1);
}

$data = json_decode($response, true);
if (!isset($data['markdown'])) {
    echo "\n[ERROR] Invalid response format from Crawl4AI:\n{$response}\n";
    exit(1);
}

$markdown = $data['markdown'];
$word_count = str_word_count(strip_tags($markdown));

echo "\n[2/3] Crawled successfully in {$elapsed}s! (~{$word_count} words extracted)\n";

// Save file
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

$url_parts = parse_url($target_url);
$slug = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', ($url_parts['host'] ?? 'site') . '-' . ($url_parts['path'] ?? 'page')), '-');
$file_path = $output_dir . '/' . $slug . '.md';

file_put_contents($file_path, $markdown);
echo "      Saved markdown file: {$file_path}\n";

// Optional WP Import
if ($import_wp) {
    echo "\n[3/3] Importing to WordPress...\n";
    $wp_load = dirname(__DIR__) . '/wp-load.php';
    if (file_exists($wp_load)) {
        define('WP_USE_THEMES', false);
        require_once $wp_load;

        if (class_exists('Financial_Crawl4AI_Client')) {
            $client = new Financial_Crawl4AI_Client();

            // Extract title
            $title = 'Market Intelligence: ' . ($url_parts['host'] ?? 'News');
            if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
                $title = trim($m[1]);
            }

            $post_id = $client->import_as_post($target_url, $title, $markdown, 'draft');
            if (!is_wp_error($post_id)) {
                echo "      [SUCCESS] Created WordPress Draft Post #{$post_id}: '{$title}'\n";
            } else {
                echo "      [ERROR] Could not import: " . $post_id->get_error_message() . "\n";
            }
        } else {
            echo "      [ERROR] Financial_Crawl4AI_Client class not found.\n";
        }
    } else {
        echo "      [WARNING] wp-load.php not found at {$wp_load}, skipped WP import.\n";
    }
} else {
    echo "\n[3/3] Done! (Pass --import-wp if you want to import directly into WordPress)\n";
}
echo "\n";
