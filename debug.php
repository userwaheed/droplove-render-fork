<?php
// Minimal SSL debug script
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pgsql (libpq): " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";
echo "PGSSLMODE env: [" . getenv('PGSSLMODE') . "]\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

// Parse DATABASE_URL
$db_url = getenv('DATABASE_URL');
$url = preg_replace('/\?.*$/', '', $db_url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);
$host = $m[3] ?? 'NONE';
$port = $m[4] ?? 'NONE';
$db = $m[5] ?? 'NONE';
$user = $m[1] ?? 'NONE';

echo "\nHost: $host\n";
echo "Port: $port\n";
echo "DB: $db\n";
echo "User: $user\n";

// Test OpenSSL TLS connection directly
echo "\n-- OpenSSL stream_socket_client TLS test to $host:$port --\n";
$ctx = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'capture_peer_cert' => true,
    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
]]);
$fp = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
if ($fp) {
    echo "TLS handshake SUCCESS\n";
    $params = stream_context_get_params($fp);
    if (isset($params['options']['ssl']['peer_certificate'])) {
        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        echo "Peer cert subject: " . ($cert['name'] ?? 'unknown') . "\n";
        echo "Peer cert issuer: " . ($cert['issuer']['CN'] ?? 'unknown') . "\n";
    }
    fclose($fp);
} else {
    echo "TLS handshake FAILED: $errstr ($errno)\n";
    // Try TCP
    $fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, 10, STREAM_CLIENT_CONNECT);
    if ($fp2) {
        echo "TCP (no SSL) SUCCESS\n";
        fclose($fp2);
    } else {
        echo "TCP FAILED: $errstr2 ($errno2)\n";
    }
}

// Test PDO connection
echo "\n-- PDO connection test (sslmode=require) --\n";
try {
    $conninfo = "host=$host port=$port dbname=$db user=$user";
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;user=$user;password=" . $m[2];
    $pdo = new PDO($dsn, $user, $m[2], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 15,
    ]);
    echo "PDO SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users in DB: $count\n";
} catch (Throwable $e) {
    echo "PDO FAILED: " . $e->getMessage() . "\n";
}

// Read PHP error log
$log = '/var/www/html/data/php_errors.log';
if (file_exists($log)) {
    $lines = file($log);
    echo "\nPHP errors log (last 20 lines):\n";
    echo implode("", array_slice($lines, -20));
}
