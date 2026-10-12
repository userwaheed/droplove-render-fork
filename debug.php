<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "OPENSSL_CONF: [" . getenv('OPENSSL_CONF') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";

// Try direct PDO connection
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', getenv('DATABASE_URL'), $m);
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $m[3], $m[4], $m[5], $m[1], $m[2]);
putenv('PGSSLMODE=require');
echo "\n-- PDO direct connection (sslmode=require) --\n";
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "PDO: SUCCESS\n";
    echo "Version: " . $pdo->query('SELECT version()')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "PDO FAILED: " . $e->getMessage() . "\n";
}

// Try with sslmode=prefer
echo "\n-- PDO direct connection (sslmode=prefer) --\n";
putenv('PGSSLMODE=prefer');
try {
    $pdo2 = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "PDO: SUCCESS\n";
} catch (Throwable $e) {
    echo "PDO FAILED: " . $e->getMessage() . "\n";
}
