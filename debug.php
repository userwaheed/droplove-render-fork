<?php
/**
 * Debug page — shows PHP errors, SSL status, and database connection diagnostics.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

// Show PHP SSL support
echo "OpenSSL loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "pdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "PDO PGSQL SSL support (attr): " . (defined('PDO::PGSQL_ATTR_SSL_MODE') ? 'YES' : 'NO') . "\n";

// Show DB_DSN info (without password)
$dsn_display = DB_DSN;
if (str_starts_with(DB_DSN, 'postgresql://')) {
    $dsn_display = preg_replace('/:\/\/([^:]+):([^@]+)@/', '://$1:***@', $dsn_display);
}
echo "DSN (masked): " . htmlspecialchars($dsn_display) . "\n";
echo "is_postgres: " . (str_starts_with(DB_DSN, 'postgresql://') ? 'true' : 'false') . "\n";

// Try pg_connect directly with URI (libpq supports postgresql:// URIs)
echo "\nTrying pg_connect with URI...\n";
$conn = @pg_connect(DB_DSN);
if ($conn) {
    echo "pg_connect: SUCCESS\n";
    echo "Server version: " . pg_options($conn) . "\n";
    pg_close($conn);
} else {
    $err = error_get_last();
    echo "pg_connect error: " . ($err ? $err['message'] : 'no error') . "\n";
}

// Show the actual DSN we'd build for PDO
$url = DB_DSN;
$url = preg_replace('/\?.*$/', '', $url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
if (preg_match($pattern, $url, $m)) {
    $pdo_dsn = "pgsql:host=" . $m[3] . ";port=" . $m[4] . ";dbname=" . $m[5] . ";user=" . $m[1] . ";password=***;sslmode=require";
    echo "PDO DSN (masked): " . $pdo_dsn . "\n";
    
    // Try without password to see if it's a connection issue or auth issue
    echo "\nTrying PDO without SSL...\n";
    try {
        $dsn_nossl = "pgsql:host=" . $m[3] . ";port=" . $m[4] . ";dbname=" . $m[5] . ";user=" . $m[1] . ";password=" . $m[2] . ";sslmode=prefer";
        $pdo_test = new PDO($dsn_nossl);
        echo "PDO (sslmode=prefer): SUCCESS\n";
    } catch (Throwable $e) {
        echo "PDO (sslmode=prefer): " . $e->getMessage() . "\n";
    }
    
    // Try with sslmode=allow
    echo "\nTrying PDO with sslmode=allow...\n";
    try {
        $dsn_allow = "pgsql:host=" . $m[3] . ";port=" . $m[4] . ";dbname=" . $m[5] . ";user=" . $m[1] . ";password=" . $m[2] . ";sslmode=allow";
        $pdo_test2 = new PDO($dsn_allow);
        echo "PDO (sslmode=allow): SUCCESS\n";
    } catch (Throwable $e) {
        echo "PDO (sslmode=allow): " . $e->getMessage() . "\n";
    }
} else {
    echo "Regex did not match!\n";
}

echo "\n---\n";
echo "PDO available drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
