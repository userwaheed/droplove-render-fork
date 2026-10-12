<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pgsql: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PGSSLMODE env: " . getenv('PGSSLMODE') . "\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

// Try pg_connect with the URI (libpq handles SSL natively)
echo "\nTest 1: pg_connect with postgresql:// URI + PGSSLMODE=require\n";
putenv('PGSSLMODE=require');
if (function_exists('pg_connect')) {
    $conn = @pg_connect(DB_DSN);
    if ($conn) {
        echo "SUCCESS\n";
        $result = pg_query($conn, "SELECT COUNT(*) as c FROM users");
        $row = pg_fetch_assoc($result);
        echo "Users: " . $row['c'] . "\n";
        pg_close($conn);
    } else {
        echo "pg_connect failed: " . (pg_last_error() ?: 'no error message') . "\n";
        
        // Try with explicit connection string
        echo "\nTest 2: pg_connect with conninfo string\n";
        $url = preg_replace('/\?.*$/', '', DB_DSN);
        $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
        if (preg_match($pattern, $url, $m)) {
            $conninfo = sprintf("host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
                $m[3], $m[4], $m[5], $m[1], $m[2]);
            $conn2 = @pg_connect($conninfo);
            if ($conn2) {
                echo "SUCCESS with conninfo!\n";
                pg_close($conn2);
            } else {
                echo "pg_connect conninfo failed: " . (pg_last_error() ?: 'no error') . "\n";
            }
        }
    }
} else {
    echo "pg_connect not available\n";
}

// Test PDO
echo "\nTest 3: PDO with pgsql: DSN + PGSSLMODE=require\n";
try {
    $url = preg_replace('/\?.*$/', '', DB_DSN);
    $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
    preg_match($pattern, $url, $m);
    $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s", $m[3], $m[4], $m[5], $m[1], $m[2]);
    $opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}
