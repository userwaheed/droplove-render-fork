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

exec apache2-foreground
