FROM wordpress:6.7-php8.2-apache

# Install additional extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . /var/www/html/

# Set proper permissions for Apache WordPress runtime
RUN chown -R www-data:www-data /var/www/html
