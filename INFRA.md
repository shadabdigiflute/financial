# Financial Project Infrastructure & Workspace Documentation

## 1. Project Overview
- **Project Name**: Financial (WordPress Corporate / Financial Services Theme)
- **Local Workspace Path**: `c:\xampp\htdocs\financial`
- **Primary Tech Stack**: WordPress 6.7, PHP 8.2, MariaDB 10.11 / MySQL, Bootscore Child Theme, Apache
- **Custom Theme**: `bootscore-child` (inheriting from `bootscore`)
- **HTML Templates**: Located in `financial-html-templates/`
- **Database Dump**: `sql/init.sql` (Auto-imported on container initialization)

---

## 2. GitHub Repository Configuration
- **Repository URL**: `https://github.com/shadabdigiflute/financial`
- **Repository Visibility**: Public / Private
- **Owner**: `shadabdigiflute`
- **Default Branch**: `main`
- **CI/CD Integration**: Webhook configured to trigger Coolify rolling deployment on every push to `main`

---

## 3. Coolify Server & Live Hosting
- **Coolify Dashboard URL**: `http://51.222.83.114:8000/`
- **Server IP**: `51.222.83.114`
- **Server Hostname**: `localhost` (Coolify Host UUID: `hn09vvwmduyowh54bo05ks8d`)
- **Coolify Project**: `financial`
- **Live Website URL**: `http://financial.51.222.83.114.sslip.io`
- **Traefik Reverse Proxy**: Automatically proxies traffic on port 80/443 to the WordPress `web` service container.

---

## 4. Container Architecture (Docker Compose)
The application runs as a containerized stack orchestrated by Docker Compose:

### Services:
1. **`web` (WordPress Application Service)**:
   - **Base Image / Build**: Custom build from `Dockerfile` (`wordpress:6.7-php8.2-apache` + `libzip`)
   - **Internal Port**: `80`
   - **Environment Variables**:
     - `WORDPRESS_DB_HOST`: `db:3306`
     - `WORDPRESS_DB_NAME`: `financial`
     - `WORDPRESS_DB_USER`: `financial_user`
     - `WORDPRESS_DB_PASSWORD`: `financial_secure_password_2026`
     - `WORDPRESS_TABLE_PREFIX`: `wp_`
   - **Volumes**:
     - `wp_uploads`: `/var/www/html/wp-content/uploads`

2. **`db` (Database Service)**:
   - **Image**: `mariadb:10.11`
   - **Environment Variables**:
     - `MYSQL_ROOT_PASSWORD`: `financial_root_password_2026`
     - `MYSQL_DATABASE`: `financial`
     - `MYSQL_USER`: `financial_user`
     - `MYSQL_PASSWORD`: `financial_secure_password_2026`
   - **Volumes**:
     - `db_data`: `/var/lib/mysql`
     - `./sql`: `/docker-entrypoint-initdb.d` (automatically seeds initial database on first launch)

---

## 5. Local Development vs. Production Mode
- **Local XAMPP**: Runs directly from `c:\xampp\htdocs\financial` accessing local MySQL on `localhost`.
- **Dynamic Site URLs**: Configured in `wp-config.php` via `HTTP_HOST` detection to eliminate hardcoded domain conflicts between local (`localhost/financial`) and production (`financial.51.222.83.114.sslip.io`).
- **SSL / Reverse Proxy Support**: Configured to recognize `HTTP_X_FORWARDED_PROTO` headers sent by Coolify's Traefik edge proxy.

---

## 6. Continuous Deployment Workflow
1. Developer edits code locally in `c:\xampp\htdocs\financial`.
2. Commit and push changes to `main` on GitHub:
   ```bash
   git add .
   git commit -m "Update site features"
   git push origin main
   ```
3. GitHub sends a webhook POST request to Coolify's deployment endpoint.
4. Coolify initiates a zero-downtime rolling container rebuild and deploys to the live server URL.
