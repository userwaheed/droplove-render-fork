<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";

$url = DB_DSN;
$url = preg_replace('/\?.*$/', '', $url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);

$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];
$opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];

// Test 1: sslmode in DSN
echo "\nTest 1: sslmode=require in DSN\n";
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
    $pdo = null;
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

// Test 2: PGCONNECT env var with sslmode
echo "\nTest 2: PGCONNECT env var\n";
putenv('PGCONNECT=sslmode=require');
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
    $pdo = null;
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

// Test 3: sslmode=disable (to see if non-SSL works)
echo "\nTest 3: sslmode=disable\n";
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass sslmode=disable";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS (non-SSL works!)\n";
    $pdo = null;
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

// Test 4: sslmode=allow
echo "\nTest 4: sslmode=allow\n";
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass sslmode=allow";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
    $pdo = null;
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }
