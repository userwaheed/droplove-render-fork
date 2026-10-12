FROM php:8.3-apache

# Install system packages.
# libpq-dev is required for PostgreSQL support.
# libssl-dev and ca-certificates ensure OpenSSL can do TLS handshakes.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libsqlite3-dev \
    libpq-dev \
    libssl-dev \
    ca-certificates \
    libzip-dev \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite intl pdo_zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Custom OpenSSL config that sets security level to 0,
# allowing all cipher suites (workaround for OpenSSL 3.5.x + Render PostgreSQL SSL).
RUN printf "openssl_conf = openssl_init\n\n[openssl_init]\nssl_conf = ssl_sect\n\n[ssl_sect]\nsystem_default = system_default_sect\n\n[system_default_sect]\nMinProtocol = TLSv1\nCipherString = DEFAULT:@SECLEVEL=0\n" > /usr/local/ssl/openssl.cnf

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Ensure SSL is used for PostgreSQL connections (Render requires SSL)
ENV PGSSLMODE=require

# Custom OpenSSL config to allow all cipher suites (SECLEVEL=0)
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

# Entrypoint fixes permissions before Apache starts.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
