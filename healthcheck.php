<?php
/**
 * Healthcheck endpoint — no DB access, fast return.
 * Used by Fly.io machine health checks so deployment succeeds
 * even before the persistent volume / SQLite DB is ready.
 */
header('Content-Type: text/plain; charset=UTF-8');
echo "ok\n";
