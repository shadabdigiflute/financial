# Financial Market Data Pipeline (Powered by Crawl4AI)

This directory contains standalone tools and pipelines for crawling, extracting, and importing financial news, stock reports, forex analysis, and market intelligence into the Financial WordPress site.

## Microservice Architecture
- **Service Name**: `crawl4ai` (Shared with Alhamd microservice on Coolify)
- **Coolify Endpoint**: `http://crawl4ai.51.222.83.114.sslip.io`
- **Internal / External Port**: `11235`
- **Authentication**: Bearer Token `8Qz8RKv72nEBB$`

---

## 1. CLI Usage

You can crawl any website or financial page from your command line:

```bash
# Basic crawl and save markdown to pipeline/data/
php pipeline/crawl_financial_data.php https://example.com

# Crawl and directly import as a draft article in WordPress
php pipeline/crawl_financial_data.php https://finance.yahoo.com/news/article --import-wp

# Specify content filter (fit, raw, bm25, llm)
php pipeline/crawl_financial_data.php https://www.bloomberg.com/markets --filter=fit --import-wp
```

---

## 2. WordPress Admin Interface

You can also crawl and preview articles interactively directly from the WordPress Admin:
- Navigate to: **WP Admin > Market Crawler**
- Enter any URL -> Click **Crawl URL**
- Review live formatted preview or raw markdown
- Click **Save as WordPress Draft Post** to create an editable article in one click.

---

## 3. Programmatic Usage in PHP / Theme

```php
$client = new Financial_Crawl4AI_Client();

// 1. Health check
$health = $client->check_health();

// 2. Crawl markdown
$result = $client->crawl_markdown('https://target-financial-url.com');
if ($result['success']) {
    $markdown = $result['markdown'];
    
    // 3. Optional: Import as WP post
    $post_id = $client->import_as_post('https://target-financial-url.com', 'Post Title', $markdown, 'draft');
}
```
