FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev ca-certificates && update-ca-certificates && docker-php-ext-install pdo_pgsql && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN rm -rf /var/www/html/*
COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
