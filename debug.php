<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pgsql: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PGSSLMODE env: " . getenv('PGSSLMODE') . "\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

// Parse the DSN
$url = preg_replace('/\?.*$/', '', DB_DSN);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);
$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];

// Test pg_connect with error display ON
echo "\nTest 1: pg_connect with URI (PGSSLMODE=require)\n";
putenv('PGSSLMODE=require');
set_error_handler(function($errno, $errstr) { echo "  PHP warning: $errstr\n"; });
$conn = pg_connect(DB_DSN);
restore_error_handler();
if ($conn) {
    echo "  SUCCESS\n";
    pg_close($conn);
} else {
    echo "  FAILED\n";
}

// Test pg_connect with conninfo and sslmode=require
echo "\nTest 2: pg_connect with conninfo sslmode=require\n";
$conninfo = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn2 = pg_connect($conninfo);
if ($conn2) {
    echo "  SUCCESS\n";
    pg_close($conn2);
} else {
    echo "  FAILED\n";
}

// Test pg_connect with sslmode=prefer
echo "\nTest 3: pg_connect with conninfo sslmode=prefer\n";
$conninfo3 = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=prefer";
$conn3 = pg_connect($conninfo3);
if ($conn3) {
    echo "  SUCCESS\n";
    pg_close($conn3);
} else {
    echo "  FAILED\n";
}

// Test pg_connect with sslmode=disable
echo "\nTest 4: pg_connect with conninfo sslmode=disable\n";
$conninfo4 = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=disable";
$conn4 = pg_connect($conninfo4);
if ($conn4) {
    echo "  SUCCESS (non-SSL works!)\n";
    pg_close($conn4);
} else {
    echo "  FAILED\n";
}

// Test PDO
echo "\nTest 5: PDO with DSN (no sslmode)\n";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$pass";
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo "  SUCCESS\n";
} catch (Throwable $e) {
    echo "  FAIL: " . $e->getMessage() . "\n";
}
