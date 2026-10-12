FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends     libpq-dev libsqlite3-dev libssl-dev ca-certificates     libzip-dev libpng-dev libjpeg-dev libwebp-dev     curl libicu-dev unzip     && docker-php-ext-install pdo pdo_pgsql pdo_sqlite gd zip opcache mysqli bcmath intl exif fileinfo     && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /usr/local/etc/php/apache2/conf/php.ini

ENV APP_ENV=production

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /var/www/html/data/uploads     && chown -R www-data:www-data /var/www/html/data     && chmod -R 775 /var/www/html/data

RUN echo 'display_errors = On' > /usr/local/etc/php/conf.d/app.ini &&     echo 'log_errors = On' >> /usr/local/etc/php/conf.d/app.ini &&     echo 'error_log = /var/www/html/data/php_errors.log' >> /usr/local/etc/php/conf.d/app.ini &&     echo 'upload_max_filesize = 20M' >> /usr/local/etc/php/conf.d/app.ini &&     echo 'post_max_size = 25M' >> /usr/local/etc/php/conf.d/app.ini &&     echo 'max_execution_time = 120' >> /usr/local/etc/php/conf.d/app.ini &&     echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/app.ini

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]
