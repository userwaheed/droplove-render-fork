<?php
/**
 * Debug page — shows PHP errors, SSL status, and database connection diagnostics.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Show PHP SSL support
echo "OpenSSL loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PostgreSQL libpq version: " . pg_libversion() . "\n";
echo "PDO PGSQL SSL support: " . (defined('PDO::PGSQL_ATTR_SSL_MODE') ? 'YES' : 'NO') . "\n";
echo "SSL method: " . (function_exists('openssl_get_ssl_constant_string') ? 'available' : 'not available') . "\n";

// Show DB_DSN info (without password)
$dsn_display = DB_DSN;
if (is_postgres()) {
    $dsn_display = preg_replace('/:\/\/([^:]+):([^@]+)@/', '://$1:***@', $dsn_display);
}
echo "DSN (masked): " . htmlspecialchars($dsn_display) . "\n";
echo "DB Storage: " . DB_STORAGE . "\n";
echo "LIKE_OP: " . LIKE_OP . "\n";
echo "is_postgres: " . (is_postgres() ? 'true' : 'false') . "\n";

// Try a direct pg_connect test
if (is_postgres()) {
    echo "\nTrying pg_connect with postgresql:// URI...\n";
    $conn = @pg_connect(DB_DSN);
    if ($conn) {
        echo "pg_connect: SUCCESS\n";
        pg_close($conn);
    } else {
        $err = pg_last_error();
        echo "pg_connect: FAILED - " . ($err ? $err : 'no error message') . "\n";
    }
    
    // Show the actual DSN we're building
    $url = DB_DSN;
    $url = preg_replace('/\?.*$/', '', $url);
    $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
    if (preg_match($pattern, $url, $m)) {
        echo "Parsed DSN: pgsql:host=" . $m[3] . ";port=" . $m[4] . ";dbname=" . $m[5] . ";user=" . $m[1] . ";password=***;sslmode=require\n";
    } else {
        echo "Regex did not match!\n";
    }
}

// Try db() connection
echo "\nTrying PDO connection...\n";
try {
    $pdo = db();
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1 AND banned = 0')->fetchColumn();
    echo "Users count: " . $count . "\n";
} catch (Throwable $e) {
    echo "PDO error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
