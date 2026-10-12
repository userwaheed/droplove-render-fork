<?php
/**
 * Debug page — shows database connection status.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

try {
    $pdo = db();
    echo "\nPDO connection: SUCCESS\n";
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "Users count: " . $count . "\n";
} catch (Throwable $e) {
    echo "\nPDO connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\nDone.\n";
