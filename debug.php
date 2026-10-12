<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PGSSLMODE env: " . getenv('PGSSLMODE') . "\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

$url = DB_DSN;
$url = preg_replace('/\?.*$/', '', $url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);
$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];

$opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];

// Test with PGSSLMODE env var (no sslmode in DSN)
putenv('PGSSLMODE=require');
echo "\nTest 1: PGSSLMODE=require, no sslmode in DSN\n";
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

// Test with sslmode=require in DSN
echo "\nTest 2: sslmode=require in DSN\n";
try {
    $dsn = "pgsql:host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }

// Test with semicolon format
echo "\nTest 3: semicolon DSN + PGSSLMODE\n";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$pass";
    $pdo = new PDO($dsn, null, null, $opt);
    echo "SUCCESS\n";
} catch (Throwable $e) { echo "FAIL: " . $e->getMessage() . "\n"; }
