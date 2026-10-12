FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libssl-dev \
    ca-certificates \
    postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql \
    && update-ca-certificates

RUN printf "openssl_conf = openssl_init\n\n[openssl_init]\nssl_conf = ssl_sect\n\n[ssl_sect]\nsystem_default = system_default_sect\n\n[system_default_sect]\nMinProtocol = TLSv1\nCipherString = DEFAULT:@SECLEVEL=0\n" > /usr/local/ssl/openssl.cnf

ENV OPENSSL_CONF=/usr/local/ssl/openssl.cnf

CMD ["php", "-r", "echo extension_loaded('pdo_pgsql') ? 'pdo_pgsql=OK' : 'pdo_pgsql=MISSING'; echo '\n';"]
