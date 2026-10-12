FROM php:8.3-apache

# Install PHP extensions the app needs.
# libpng-dev libjpeg-dev libwebp-dev libgif-dev are required for GD
# (used by helpers.php: detect_nudity() / handle_image_upload()).
#
# pkg-config is needed so configure can locate the codec libs.
# Do NOT add --with-freetype — freetype2-dev fails on Debian.
#
# PostgreSQL SSL: we add the official PostgreSQL apt repo to get
# a newer libpq5 with proven SSL/TLS support for Render PostgreSQL.
RUN set -eux;
    apt-get update;
    apt-get install -y --no-install-recommends
        pkg-config
        libicu-dev
        libsqlite3-dev
        ca-certificates
        curl
        gnupg
        lsb-release
        libpng-dev
        libjpeg-dev
        libwebp-dev
        libgif-dev
        openssl
    ;
    # Add PostgreSQL official apt repo
    install -d /usr/share/postgresql-common/pgdg/apt.postgresql.org
    curl -sSL https://www.postgresql.org/media/downloads/apt.postgresql.org/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql-acc.gpg
    echo "deb [signed-by=/etc/apt/trusted.gpg.d/postgresql-acc.gpg] https://apt.postgresql.org/pub/repos/apt $(. /etc/os-release && echo $VERSION_CODENAME)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    apt-get update;
    apt-get install -y --no-install-recommends libpq-dev libpq5 postgresql-client-16
    # Compile PHP extensions
    docker-php-ext-install pdo pdo_pgsql pdo_sqlite intl mysqli
    docker-php-ext-enable pdo pdo_pgsql pdo_sqlite intl mysqli
    # GD for image uploads
    docker-php-ext-configure gd --with-jpeg --with-webp
    docker-php-ext-install gd
    # Verify extensions loaded
    php -r 'echo "PG: ", (int)extension_loaded("pdo_pgsql"), " JPEG: ", (int)function_exists("imagecreatefromjpeg"), "\n"; exit(extension_loaded("pdo_pgsql") && function_exists("imagecreatefromjpeg") ? 0 : 1);'
    ; apt-get clean; rm -rf /var/lib/apt/lists/*

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
RUN printf "display_errors = Off\nlog_errors = On\nerror_log = /var/www/html/data/php_errors.log\nupload_max_filesize = 20M\npost_max_size = 25M\nmax_execution_time = 60\nmemory_limit = 256M\nopenssl.cafile=/etc/ssl/certs/ca-certificates.crt\n" > /usr/local/etc/php/conf.d/app.ini

# Apache: allow .htaccess overrides at the docroot
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80

# Entrypoint fixes permissions on the persistent volume before Apache starts.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
