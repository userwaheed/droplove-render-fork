FROM php:8.3-apache

# Install PHP extensions the app needs.
# libpng-dev libjpeg-dev libwebp-dev libgif-dev are required for GD
# (used by helpers.php: detect_nudity() / handle_image_upload()).
#
# pkg-config is needed so configure can locate the codec libs.
# Do NOT add --with-freetype — freetype2-dev fails on Debian.
#
# libssl-dev and ca-certificates ensure OpenSSL can do TLS handshakes.
# We also install postgresql-client for debugging.
#
# OpenSSL security level fix: OpenSSL 3.5.x defaults to SECLEVEL=2,
# which rejects cipher suites used by Render's PostgreSQL. We set
# OPENSSL_CONF to a custom config with @SECLEVEL=0 to allow all ciphers.
RUN apt-get update && apt-get install -y --no-install-recommends \
    pkg-config \
    libicu-dev \
    libsqlite3-dev \
    libpq-dev \
    libssl-dev \
    ca-certificates \
    postgresql-client \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libgif-dev \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite intl mysqli \
    && docker-php-ext-enable pdo pdo_pgsql pdo_sqlite intl mysqli \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Custom OpenSSL config that sets security level to 0,
# allowing all cipher suites (workaround for OpenSSL 3.5.x + Render PG SSL).
RUN printf "openssl_conf = openssl_init\n\n[openssl_init]\nssl_conf = ssl_sect\n\n[ssl_sect]\nsystem_default = system_default_sect\n\n[system_default_sect]\nMinProtocol = TLSv1\nCipherString = DEFAULT:@SECLEVEL=0\n" > /usr/local/ssl/openssl.cnf

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Ensure SSL is used for PostgreSQL connections (Render requires SSL)
ENV PGSSLMODE=require

# Custom OpenSSL config to allow all cipher suites (SECLEVEL=0)
# This fixes the 'SSL connection has been closed unexpectedly' error with OpenSSL 3.5.x
ENV OPENSSL_CONF=/usr/local/ssl/openssl.cnf

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
