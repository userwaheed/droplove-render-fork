FROM php:8.3-apache

# Install PHP extensions the app needs.
# libpng-dev libjpeg-dev libwebp-dev libgif-dev are required for GD
# (used by helpers.php: detect_nudity() / handle_image_upload()).
#
# On Debian trixie / PHP 8.3, `docker-php-ext-install gd` silently
# builds a "bundled" GD without JPEG/WebP codec support unless you
# explicitly tell configure to find them. Without --with-jpeg,
# imagecreatefromjpeg() is missing and image uploads 500 with
# "Call to undefined function imagecreatefromjpeg()".
#
# pkg-config is needed so configure can locate the codec libs.
# Do NOT add --with-freetype — freetype2-dev fails on Debian trixie.
RUN apt-get update && apt-get install -y --no-install-recommends \
    pkg-config \
    libicu-dev \
    libsqlite3-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libgif-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql intl mysqli \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && php -r 'echo "JPEG: ", (int)function_exists("imagecreatefromjpeg"), " WebP: ", (int)function_exists("imagecreatefromwebp"), " PDO: ", (int)extension_loaded("pdo_pgsql"), "\n"; exit(function_exists("imagecreatefromjpeg") && extension_loaded("pdo_pgsql") ? 0 : 1);' \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Clear default Apache docroot
RUN rm -rf /var/www/html/*

# Copy PHP app into the docroot. data/ is excluded via .dockerignore,
# so create it here — Fly's persistent volume will mount over it at runtime.
# /var/www/html/data/ will hold both the SQLite DB and the uploads/ subdir.
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data/uploads \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

# Global PHP settings
RUN printf "display_errors = Off\nlog_errors = On\nerror_log = /var/www/html/data/php_errors.log\nupload_max_filesize = 20M\npost_max_size = 25M\nmax_execution_time = 60\nmemory_limit = 256M\n" \
    > /usr/local/etc/php/conf.d/app.ini

# Apache: allow .htaccess overrides at the docroot
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Apache default site points at /var/www/html — keep it
EXPOSE 80

# Entrypoint fixes permissions on the persistent volume before Apache starts.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
