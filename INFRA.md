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
- **Admin Email**: `admin@example.com`
- **Live Admin Login URL**: [http://financial.51.222.83.114.sslip.io/wp-admin/](http://financial.51.222.83.114.sslip.io/wp-admin/)
- **Local Admin Login URL**: `http://localhost/financial/wp-admin/`
- **Default Admin Username**: `admin`
- **Admin Password**: `shadab@digiflute` (or configured via `WORDPRESS_ADMIN_PASSWORD`)
- **User Role**: `Administrator` (User ID: 1, Level: 10)
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
