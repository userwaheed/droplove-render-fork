#!/bin/sh
# Startup script — ensures the persistent volume is writable by Apache's
# www-data user before Apache starts. Does NOT create the SQLite DB file —
# db.php does that on first request so it can populate the schema.
set -e

# First boot: the volume is empty. Create the uploads subdir.
mkdir -p /var/www/html/data/uploads

# Always fix ownership — Fly mounts volumes with root:root 0755 by default.
chown -R www-data:www-data /var/www/html/data
chmod -R 775 /var/www/html/data

echo "[startup] Volume ready"

# Start PostgreSQL SSL tunnel (pg_tunnel.php) when PG_USE_TUNNEL=1.
# This works around libpq SSL handshake failures with OpenSSL 3.5.x when
# connecting to Render's managed PostgreSQL. PHP's stream_socket_enable_crypto
# handles TLS correctly, but libpq's OpenSSL does not. The tunnel terminates
# TLS server-side and presents plain TCP to libpq on localhost:5433.
if [ "${PG_USE_TUNNEL:-1}" = "1" ]; then
    echo "[startup] Starting PostgreSQL SSL tunnel on 127.0.0.1:${PG_TUNNEL_PORT:-5433}"
    php /var/www/html/pg_tunnel.php >> /var/www/html/data/tunnel.log 2>&1 &
    echo $! > /var/www/html/data/tunnel.pid
    sleep 2
    echo "[startup] Tunnel started"
fi

echo "[startup] Starting Apache"
exec apache2-foreground
