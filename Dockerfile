FROM php:8.3-apache

# Install PHP extensions the app needs.
# libpng-dev libjpeg-dev libwebp-dev libgif-dev are required for GD
# (used by helpers.php: detect_nudity() / handle_image_upload()).
#
# pkg-config is needed so configure can locate the codec libs.
# Do NOT add --with-freetype — freetype2-dev fails on Debian.
#
# libssl-dev and ca-certificates ensure OpenSSL can do TLS handshakes.
RUN apt-get update && apt-get install -y --no-install-recommends \
    pkg-config \
    libicu-dev \
    libsqlite3-dev \
    libpq-dev \
    libssl-dev \
    ca-certificates \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libgif-dev \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite intl mysqli \
    && docker-php-ext-enable pdo pdo_pgsql pdo_sqlite intl mysqli \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && php -r 'echo "PG: ", (int)extension_loaded("pdo_pgsql"), " JPEG: ", (int)function_exists("imagecreatefromjpeg"), "\n"; exit(extension_loaded("pdo_pgsql") && function_exists("imagecreatefromjpeg") ? 0 : 1);' \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Ensure SSL is used for PostgreSQL connections for direct connections
ENV PGSSLMODE=require

# When PG_USE_TUNNEL=1, PHP connects to a local SSL tunnel (127.0.0.1:5433)
# that terminates TLS to the PostgreSQL server. This is enabled automatically
# because libpq has a known SSL handshake failure on OpenSSL 3.5.x + Render PG.
ENV PG_USE_TUNNEL=1 \
    PG_TUNNEL_HOST=127.0.0.1 \
    PG_TUNNEL_PORT=5433

# Clear default Apache docroot
RUN rm -rf /var/www/html/*

# Copy PHP app into the docroot.
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data/uploads \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

# Global PHP settings
RUN printf "display_errors = Off\nlog_errors = On\nerror_log = /var/www/html/data/php_errors.log\nupload_max_filesize = 20M\npost_max_size = 25M\nmax_execution_time = 60\nmemory_limit = 256M\nopenssl.cafile=/etc/ssl/certs/ca-certificates.crt\n" > /usr/local/etc/php/conf.d/app.ini

# Apache: allow .htaccess overrides at the docroot
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80

# Entrypoint starts the SSL tunnel then Apache.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
