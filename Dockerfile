FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev ca-certificates && update-ca-certificates && docker-php-ext-install pdo_pgsql && apt-get clean && rm -rf /var/lib/apt/lists/*

# Custom OpenSSL config: security level 0 allows all cipher suites.
# Fixes "SSL connection has been closed unexpectedly" with OpenSSL 3.5.x
# connecting to Render's managed PostgreSQL.
RUN mkdir -p /usr/local/ssl && printf 'openssl_conf = openssl_init\n\n[openssl_init]\nssl_conf = ssl_sect\n\n[ssl_sect]\nsystem_default = system_default_sect\n\n[system_default_sect]\nMinProtocol = TLSv1\nCipherString = DEFAULT:@SECLEVEL=0\n' > /usr/local/ssl/openssl.cnf && echo "OpenSSL config created" && cat /usr/local/ssl/openssl.cnf
RUN rm -rf /var/www/html/*
COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
