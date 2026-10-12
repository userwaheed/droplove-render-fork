FROM php:8.3-apache

# Install PHP extensions the app needs.
# libpng-dev libjpeg-dev libwebp-dev libgif-dev are required for GD
# (used by helpers.php: detect_nudity() / handle_image_upload()).
#
# pkg-config is needed so configure can locate the codec libs.
# Do NOT add --with-freetype — freetype2-dev fails on Debian.
#
# Render PostgreSQL requires SSL. The Sury PHP repo provides php8.3-pgsql
# with proper libpq/OpenSSL linkage for working SSL connections.
RUN apt-get update && apt-get install -y --no-install-recommends \
    pkg-config \
    libicu-dev \
    libsqlite3-dev \
    libpq-dev \
    libssl-dev \
    ca-certificates \
    lsb-release \
    gnupg \
    curl \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libgif-dev \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list \
    && curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-php.gpg \
    && apt-get update && apt-get install -y --no-install-recommends php8.3-pgsql \
    && docker-php-ext-install pdo pdo_sqlite intl mysqli \
    && docker-php-ext-enable pdo pdo_sqlite intl mysqli \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && php -r 'echo "JPEG: ", (int)function_exists("imagecreatefromjpeg"), " WebP: ", (int)function_exists("imagecreatefromwebp"), " PG: ", (int)extension_loaded("pdo_pgsql"), "\n"; exit(function_exists("imagecreatefromjpeg") && extension_loaded("pdo_pgsql") ? 0 : 1);' \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Ensure SSL is used for PostgreSQL connections (Render requires SSL)
ENV PGSSLMODE=require

# Clear default Apache docroot
RUN rm -rf /var/www/html/*

# Copy PHP app into the docroot.
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data/uploads \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

# Global PHP settings
RUN { \
        echo "display_errors = Off"; \
        echo "log_errors = On"; \
        echo "error_log = /var/www/html/data/php_errors.log"; \
        echo "upload_max_filesize = 20M"; \
        echo "post_max_size = 25M"; \
        echo "max_execution_time = 60"; \
        echo "memory_limit = 256M"; \
        echo "openssl.cafile=/etc/ssl/certs/ca-certificates.crt"; \
    } > /usr/local/etc/php/conf.d/app.ini

# Apache: allow .htaccess overrides at the docroot
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80

# Entrypoint fixes permissions on the persistent volume before Apache starts.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
