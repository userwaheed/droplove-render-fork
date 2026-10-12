FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev \
    curl libicu-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pdo_sqlite gd zip opcache mysqli bcmath intl exif fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /usr/local/etc/php/apache2/conf/php.ini

ENV APP_ENV=production
ENV PG_USE_TUNNEL=0

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data/uploads \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

RUN printf 'display_errors = On\nlog_errors = On\nerror_log = /var/www/html/data/php_errors.log\nupload_max_filesize = 20M\npost_max_size = 25M\nmax_execution_time = 60\nmemory_limit = 256M\n' > /usr/local/etc/php/conf.d/app.ini

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]
