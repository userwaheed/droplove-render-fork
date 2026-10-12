<?php
// Minimal SSL debug script
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pgsql: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";

// Get the raw DATABASE_URL
$db_url = getenv('DATABASE_URL');
echo "DATABASE_URL (raw): " . substr($db_url, 0, 30) . "***\n";
echo "DATABASE_URL length: " . strlen($db_url) . "\n";

// Parse it
$url = preg_replace('/\?.*$/', '', $db_url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);
$host = $m[3] ?? 'NONE';
$port = $m[4] ?? 'NONE';
$db = $m[5] ?? 'NONE';
$user = $m[1] ?? 'NONE';
$pass = substr($m[2] ?? 'NONE', 0, 3) . '***';

echo "Host: $host\n";
echo "Port: $port\n";
echo "DB: $db\n";
echo "User: $user\n";

// Try pg_connect with full error reporting
echo "\n-- Attempting pg_connect with sslmode=require --\n";

putenv('PGSSLMODE=require');
set_time_limit(30);

// Use @ to suppress warnings, then check error_get_last
$conn = @pg_connect("host=$host port=$port dbname=$db user=$user password=$pass sslmode=require");
if ($conn) {
    echo "SUCCESS\n";
    $result = pg_query($conn, "SELECT 1");
    echo "Query result: " . pg_fetch_result($result, 0, 0) . "\n";
    pg_close($conn);
} else {
    $err = error_get_last();
    echo "FAILED: " . ($err['message'] ?? 'unknown error') . "\n";
    echo "pg_last_error: " . pg_last_error() . "\n";
    // Also try the PHP error log
    $log = '/var/www/html/data/php_errors.log';
    if (file_exists($log)) {
        echo "PHP errors (last 10 lines):\n";
        echo implode("\n", array_slice(file($log), -10));
    }
}

// Try with sslmode=disable to confirm server requires SSL
echo "\n-- Attempting pg_connect with sslmode=disable --\n";
$conn2 = @pg_connect("host=$host port=$port dbname=$db user=$user password=$pass sslmode=disable");
if ($conn2) {
    echo "SUCCESS (non-SSL works, so server does NOT require SSL)\n";
    pg_close($conn2);
} else {
    $err2 = error_get_last();
    echo "FAILED (as expected if server requires SSL): " . ($err2['message'] ?? 'unknown') . "\n";
}

// Try openssl connection test
echo "\n-- OpenSSL connection test to $host:$port --\n";
$context = stream_context_create([
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
        "capture_peer_cert" => true,
    ]
]);
$fp = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
if ($fp) {
    echo "OpenSSL TLS handshake SUCCESS\n";
    $params = stream_context_get_params($fp);
    if (isset($params['options']['ssl']['peer_certificate'])) {
        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        echo "Peer cert: " . ($cert['name'] ?? 'unknown') . "\n";
    }
    fclose($fp);
} else {
    echo "OpenSSL TLS handshake FAILED: $errstr ($errno)\n";
    // Try without SSL
    $fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, 10, STREAM_CLIENT_CONNECT);
    if ($fp2) {
        echo "TCP connection SUCCESS (so the server is reachable)\n";
        fclose($fp2);
    } else {
        echo "TCP connection FAILED: $errstr2 ($errno2)\n";
    }
}

// Check OpenSSL version
echo "\nOpenSSL version: " . OpenSSL::OPENSSL_VERSION_TEXT . "\n";
echo "OpenSSL version (const): " . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'N/A') . "\n";
