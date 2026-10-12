<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "PG_USE_TUNNEL: " . getenv('PG_USE_TUNNEL') . "\n";

// Check tunnel
$tfp = @fsockopen("127.0.0.1", 5433, $errno, $errstr, 2);
echo "Tunnel on 5433: " . ($tfp ? "LISTENING" : "NOT LISTENING ($errstr)") . "\n";
if ($tfp) fclose($tfp);

// Show tunnel log
$tlog = '/var/www/html/data/tunnel.log';
if (file_exists($tlog)) {
    echo "\n-- Tunnel log --\n";
    echo file_get_contents($tlog) . "\n";
}

echo "\n-- Syntax check --\n";
echo shell_exec('php -l /var/www/html/pg_tunnel.php 2>&1') . "\n";

// Test PDO through tunnel
echo "\n-- db() test --\n";
require_once __DIR__ . '/db.php';
try {
    $pdo = db();
    echo "db(): SUCCESS\n";
    echo "Users: " . (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "db() FAILED: " . $e->getMessage() . "\n";
}
