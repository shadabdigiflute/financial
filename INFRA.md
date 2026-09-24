# Financial Project Infrastructure & Workspace Documentation

## 1. Workspace & Project Summary
- **Project Name**: Financial (Corporate & Financial Services WordPress Site)
- **Local Workspace Path**: `c:\xampp\htdocs\financial`
- **Active Theme**: `bootscore-child` (Parent: `bootscore`)
- **Core Technology Stack**:
  - WordPress 6.7
  - PHP 8.2 (Apache)
  - MariaDB 10.11 / MySQL
  - Bootstrap 5 via Bootscore framework
  - Docker & Docker Compose
- **Local Database**: MySQL database `financial` in XAMPP
- **Exported Seed Database**: `sql/init.sql` (53.4 KB)

---

## 2. WordPress Application & Admin Details
- **Site Title**: `Financial`
- **Live Website URL**: [http://financial.51.222.83.114.sslip.io/](http://financial.51.222.83.114.sslip.io/)
- **Live Admin Login URL**: [http://financial.51.222.83.114.sslip.io/wp-login.php](http://financial.51.222.83.114.sslip.io/wp-login.php) (or `/wp-admin/`)
- **Local Admin Login URL**: `http://localhost/financial/wp-login.php`
- **Admin Username**: `admin`
- **Admin Password**: `shadab@digiflute`
- **Admin Email**: `admin@example.com`
- **User Role**: `Administrator` (User ID: 1, Level: 10)
- **Login Verification**: Tested & Verified OK (HTTP 302 Redirect to `/wp-admin/`)
- **Active Theme**: `Bootscore Child` (`bootscore-child`)
- **Parent Framework Theme**: `Bootscore` (`bootscore`)
- **Table Prefix**: `wp_`

---

## 3. GitHub Repository & Version Control
- **Repository URL**: [https://github.com/shadabdigiflute/financial](https://github.com/shadabdigiflute/financial)
- **GitHub Owner**: `shadabdigiflute`
- **Default Branch**: `main`
- **Authentication**: GitHub Personal Access Token (PAT)
- **Local Git Tooling**: MinGit portable installed at `C:\Users\shada\AppData\Local\MinGit` and registered in environment `PATH`.

---

## 4. Coolify Server & Live Hosting Setup
- **Coolify Dashboard URL**: [http://51.222.83.114:8000/](http://51.222.83.114:8000/)
- **Host Server IP**: `51.222.83.114`
- **Coolify Server Host**: `localhost` (Server UUID: `hn09vvwmduyowh54bo05ks8d`)
- **Coolify Project**: `financial` (Project UUID: `yujczlojzjbrc0e81qeuuhxj`)
- **Environment**: `production` (Environment UUID: `mee9ujjs2b96x1vnzsfmveig`)
- **Application Resource**: `financial-app` (Resource UUID: `trjfn6bfcse3v1szbzd4tlad`)
- **Build Pack**: `dockercompose`
- **Compose Path**: `/docker-compose.yml`
- **Live Website URL**: [http://financial.51.222.83.114.sslip.io](http://financial.51.222.83.114.sslip.io)
- **Live HTTP Status**: `200 OK`

---

## 5. Continuous Deployment (CI/CD Webhook)
- **Webhook Status**: Active on GitHub (`Hook ID: 683780014`)
- **Payload URL**: `http://51.222.83.114:8000/webhooks/source/github/events`
- **Content Type**: `application/json`
- **Trigger Events**: `push` to `main` branch
- **Deployment Flow**:
  1. Developer modifies code in `c:\xampp\htdocs\financial`.
  2. Commit and push changes to `origin main`:
     ```powershell
     git add .
     git commit -m "Your update message"
     git push origin main
     ```
  3. GitHub sends a webhook POST to Coolify.
  4. Coolify triggers a zero-downtime rolling container rebuild and deploys to the live URL.

---

## 6. Container Architecture (`docker-compose.yml`)
Dual-container architecture managed by Docker Compose on Coolify:

### `web` Service (WordPress + PHP 8.2 Apache)
- **Dockerfile**: Base `wordpress:6.7-php8.2-apache` with `libzip`
- **Internal Port**: Exposes port `80` (routed dynamically via Traefik reverse proxy)
- **Volumes**:
  - `wp_uploads`: `/var/www/html/wp-content/uploads`
- **Environment Variables**:
  - `WORDPRESS_DB_HOST`: `db:3306`
  - `WORDPRESS_DB_NAME`: `financial`
  - `WORDPRESS_DB_USER`: `financial_user`
  - `WORDPRESS_DB_PASSWORD`: `financial_secure_password_2026`
  - `WORDPRESS_TABLE_PREFIX`: `wp_`

### `db` Service (MariaDB 10.11)
- **Image**: `mariadb:10.11`
- **Database Name**: `financial`
- **User**: `financial_user`
- **Volumes**:
  - `db_data`: `/var/lib/mysql`
  - `./sql`: `/docker-entrypoint-initdb.d`

---

## 7. Smart Multi-Environment Configuration
The `wp-config.php` file includes smart environment detection:
1. **Host-Based Switching**: Automatically switches database credentials and host between local XAMPP (`localhost`, `root`, blank password) and live container (`db:3306`, `financial_user`).
2. **Reverse Proxy SSL Support**: Transparently detects `HTTP_X_FORWARDED_PROTO` headers behind Coolify's Traefik reverse proxy to ensure proper HTTPS operation.
3. **Dynamic Site URL**: Uses the current request's `HTTP_HOST` so assets and navigation never break when switching between local and live URLs.
4. **Auto-Seeding & Password Sync**: Checks on container startup if the database tables exist; if empty, automatically imports `sql/init.sql`. Also guarantees that admin credentials remain synchronized and usable.

---

## 8. Crawl4AI Microservice Integration
The Financial project integrates with the shared Crawl4AI headless scraping microservice deployed on Coolify.

- **Service Name**: `crawl4ai` (Shared with Alhamd microservice)
- **Coolify Project UUID**: `3xxgbanazli508ro6ufvpdct`
- **Application UUID**: `ynr5vqgqa2divzxif7smrhiw`
- **Docker Image**: `unclecode/crawl4ai:latest`
- **Service Status**: `running:healthy` (v0.9.3)
- **Service Endpoint**: [http://crawl4ai.51.222.83.114.sslip.io](http://crawl4ai.51.222.83.114.sslip.io)
- **Direct Server Port**: `51.222.83.114:11235`
- **Authentication Token**: `8Qz8RKv72nEBB$`
- **WordPress Integration**:
  - Client Class: `wp-content/themes/bootscore-child/inc/class-crawl4ai-client.php`
  - Admin Interface: **WP Admin > Market Crawler** (`wp-content/themes/bootscore-child/inc/admin-crawl4ai.php`)
  - Standalone Pipeline CLI: `pipeline/crawl_financial_data.php`
- **CLI Example**:
  ```bash
  php pipeline/crawl_financial_data.php https://finance.yahoo.com/news --import-wp
  ```

---

## 9. News Scrapper Plugin: Architecture & Implementation Plan

### 9.1 Overview & Core Objectives
A standalone WordPress plugin (`news-scrapper`) providing an automated, enterprise-grade news scraping, AI rewriting, and publishing pipeline:
- **Scraping Engine**: Crawl4AI microservice running on Coolify (`http://crawl4ai.51.222.83.114.sslip.io`).
- **AI Rewriter**: Google Gemini Lite model (`gemini-2.0-flash-lite` or `gemini-1.5-flash`) for lightning-fast, factual rewriting at minimal cost.
- **Publishing & Storage**: Full markdown archival on disk (`wp-content/uploads/news-scraper/`), automatic WordPress post creation with featured image sideloading, hierarchical categories, and tags.
- **Automation**: Individual recurring cron execution every 4 hours (`every_4_hours`) per feed.

---

### 9.2 Database Architecture (Custom Dedicated Tables)
To avoid overloading `wp_options` and provide high-performance queue management:

1. **`wp_news_scraper_feeds`**:
   - `id` (BIGINT, PK, AI)
   - `feed_name` (VARCHAR 255)
   - `source_url` (TEXT)
   - `category_ids` (TEXT, JSON array of WP category IDs)
   - `pagination_depth` (INT, default 3)
   - `post_status` (VARCHAR 20, default 'draft')
   - `status` (VARCHAR 20: 'active', 'paused')
   - `cron_interval` (INT, default 14400 seconds / 4 hours)
   - `last_run_at` (DATETIME)
   - `next_run_at` (DATETIME)
   - `created_at` (DATETIME)

2. **`wp_news_scraper_queue`**:
   - `id` (BIGINT, PK, AI)
   - `feed_id` (BIGINT, FK)
   - `article_url` (TEXT)
   - `article_hash` (VARCHAR 64, UNIQUE index for deduplication)
   - `title_raw` (TEXT)
   - `md_file_path` (TEXT)
   - `status` (VARCHAR 20: 'discovered', 'scraped', 'rewritten', 'posted', 'failed')
   - `post_id` (BIGINT, null until created)
   - `error_message` (TEXT)
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)

3. **`wp_news_scraper_logs`**:
   - `id`, `feed_id`, `action`, `items_processed`, `duration_sec`, `status`, `log_time`.

---

### 9.3 Module Breakdown & Implementation Steps

#### Step 1: Plugin Scaffold & UI Foundation (`wp-content/plugins/news-scrapper/`)
- `news-scrapper.php`: Main bootstrap file, activation table installer, deactivation hooks.
- `includes/class-db.php`: Database schema installer and CRUD helpers.
- `includes/class-cron.php`: Registers custom 4-hour interval (`news_scraper_every_4_hours`) and schedules feed workers.
- `admin/css/admin.css` & `admin/js/admin.js`: Modern CSS design system (glassmorphism cards, badges, realtime progress bars).
- `admin/views/`:
  - `dashboard.php`: Live health metrics, active feeds counter, recent runs, queue status.
  - `feeds-list.php`: Table of all feeds with status badges, run now buttons, edit/delete.
  - `feed-form.php`: Feed configuration modal/page with nested hierarchical Category checkboxes.
  - `csv-import.php`: Drag-and-drop CSV importer with live preview.
  - `settings.php`: Crawl4AI endpoints, Gemini API Key, default post status.

#### Step 2: Hierarchical Category & CSV Importer
- **Category Tree UI**: Renders all WordPress categories and subcategories in an expandable tree view with checkboxes.
- **Recursive CSV Importer**:
  - Accepts CSV columns: `Feed Name`, `Target URL`, `Categories`, `Pagination Depth`, `Post Status`.
  - Column `Categories` format: e.g. `Markets > Forex > Central Banks; Global Economy > Trade`.
  - Parses delimiter `>` and dynamically checks `term_exists()`. If parent or child doesn't exist at any depth, creates terms recursively with `wp_insert_term(..., 'category', ['parent' => $parent_id])`.

#### Step 3: Crawl4AI Listing & Pagination Scraper
- `includes/class-scraper-listing.php`:
  - Fetches category/listing URL via Crawl4AI endpoint `/html` and `/md`.
  - Detects pagination pattern (e.g. `page/2/`, `?p=2`, next button anchors).
  - Iterates up to configured `pagination_depth`.
  - Discovers article links, normalizes absolute URLs, and inserts new records into `wp_news_scraper_queue` (ignoring already existing URLs via `article_hash`).
  - Saves listing snapshot markdown in `wp-content/uploads/news-scraper/feeds/<feed_id>/listings/listing_<timestamp>.md`.

#### Step 4: Deep Article A-to-Z Scraper
- `includes/class-scraper-article.php`:
  - Pulls queued articles in batch.
  - Calls Crawl4AI with `filter=fit` and extract options to get clean full markdown and images.
  - Extracts metadata: Main Headline, Lead paragraph, full body text, author, publish date, and featured image URL.
  - Saves individual article markdown to:
    `wp-content/uploads/news-scraper/feeds/<feed_id>/articles/<article_hash>.md`.
  - Guarantees 0% data loss by storing raw markdown alongside parsed structure.

#### Step 5: Gemini Lite AI Content Rewriter
- `includes/class-gemini-rewriter.php`:
  - Uses model: `gemini-2.0-flash-lite` (highest speed, lowest token cost).
  - Strict System Prompt:
    ```text
    You are a professional financial and news journalist.
    Rewrite the provided news article for engaging editorial flow, clean structure, and readability.
    STRICT CONSTRAINT: Do NOT invent, assume, or add any external information or facts.
    Strictly use only the information present in the scraped article.
    Generate a compelling headline, clean HTML body with subheadings (h2, h3) and paragraphs, and 3 to 6 high-relevance tags.
    Output JSON format: { "headline": "...", "content_html": "...", "tags": ["tag1", "tag2"] }
    ```
  - Fallback logic to protect against rate limits or temporary network issues.

#### Step 6: WordPress Publisher & Media Sideloading
- `includes/class-publisher.php`:
  - Downloads article featured image and inserts into WordPress Media Library using `media_sideload_image()`.
  - Assigns featured image ID to post (`set_post_thumbnail()`).
  - Calls `wp_insert_post()` with rewritten headline, body HTML, post status (`draft` or `publish`).
  - Sets hierarchical categories (`wp_set_post_categories()`).
  - Sets generated tags (`wp_set_post_tags()`).
  - Saves tracking metadata: `_source_url`, `_feed_id`, `_original_md_path`.
  - Updates queue status to `posted`.

#### Step 7: Automated 4-Hour Cron & Background Queue Runner
- `cron/queue-worker.php`:
  - Runs in non-blocking batches of 5-10 articles to ensure execution stays well under PHP execution limits.
  - Reschedules smoothly every 4 hours (`every_4_hours`).
  - Admin button "Run Feed Now" triggers AJAX background worker with real-time log streaming.

---

### 9.4 Verification & Quality Gates
1. Unit test category tree generation and CSV importer with multi-depth subcategories.
2. Verify Crawl4AI connection and listing pagination parsing on sample news sources.
3. Verify Gemini Lite API integration and strict factual preservation prompt.
4. Verify featured image sideloading and post creation.
5. Verify 4-hour WP-Cron schedule registration.
