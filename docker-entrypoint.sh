#!/bin/bash
set -e
mkdir -p /var/www/html/data /var/www/html/data/uploads
if [ "${PG_USE_TUNNEL}" = "1" ]; then
    php /var/www/html/pg_tunnel.php &
    sleep 3
fi
exec "$@"
