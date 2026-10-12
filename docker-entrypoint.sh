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

# Start PostgreSQL SSL tunnel (works around libpq SSL handshake failure on
# OpenSSL 3.5.x when connecting to Render's managed PostgreSQL).
# The tunnel listens on 127.0.0.1:5433 and forwards to the PG server over TLS.
if [ "${PG_USE_TUNNEL:-0}" = "1" ]; then
    echo "[startup] Starting PostgreSQL SSL tunnel on 127.0.0.1:${PG_TUNNEL_PORT:-5433}"
    php /var/www/html/pg_tunnel.php &
    TUNNEL_PID=$!
    echo "[startup] Tunnel PID: $TUNNEL_PID"
    sleep 2
fi

echo "[startup] Starting Apache"
exec apache2-foreground
