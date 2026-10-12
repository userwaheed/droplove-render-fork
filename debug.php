<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "OPENSSL_CONF: [" . (getenv('OPENSSL_CONF') ?: 'not set') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";

// Test: psql directly
echo "\n-- psql SSL test --\n";
$db_url = getenv('DATABASE_URL');
$cmd = "PGPASSWORD=" . escapeshellarg(substr($db_url, strpos($db_url, ':***') ? '***' : '')) . " psql $db_url -c 'SELECT 1' 2>&1";
// Actually, let me just use PGPASSWORD from the URL
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@/', $db_url, $m);
putenv('PGPASSWORD=' . $m[2]);
echo shell_exec("psql $db_url -c 'SELECT 1 as result' 2>&1") . "\n";

// Test: PDO direct connection
echo "\n-- PDO direct connection --\n";
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $m[3], $m[4], $m[5], $m[1], $m[2]);
putenv('PGSSLMODE=require');
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "PDO: SUCCESS\n";
    $ver = $pdo->query('SELECT version()')->fetchColumn();
    echo "Server: " . substr($ver, 0, 80) . "\n";
    echo "Users: " . (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "PDO: FAILED - " . $e->getMessage() . "\n";
}

// Test: PHP stream_socket_enable_crypto (proven to work)
echo "\n-- PHP SSL to PG server --\n";
$fp = @fsockopen($m[3], (int)$m[4], $errno, $errstr, 10);
if ($fp) {
    fwrite($fp, pack("NN", 8, 80877103));
    $resp = fread($fp, 1);
    echo "PG SSL response: " . bin2hex($resp) . " (" . ($resp === "S" ? "SSL supported" : "not supported") . ")\n";
    if ($resp === "S") {
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        echo "PHP TLS handshake: " . ($crypto ? "SUCCESS" : "FAILED") . "\n";
    }
    fclose($fp);
} else {
    echo "fsockopen failed: $errstr ($errno)\n";
}
