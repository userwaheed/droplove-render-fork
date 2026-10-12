<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "OPENSSL_CONF: [" . getenv('OPENSSL_CONF') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";

// Try direct PDO connection to PostgreSQL
$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $m[3], $m[4], $m[5], $m[1], $m[2]);
putenv('PGSSLMODE=require');
echo "\n-- PDO direct connection (sslmode=require) --\n";
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "PDO: SUCCESS\n";
    echo "Version: " . substr($pdo->query('SELECT version()')->fetchColumn(), 0, 80) . "\n";
    echo "Users: " . (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "PDO FAILED: " . $e->getMessage() . "\n";
}
