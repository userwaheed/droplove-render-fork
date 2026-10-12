<?php
// OpenSSL TLS connection test with different TLS versions
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_url = getenv('DATABASE_URL');
$url = preg_replace('/\?.*$/', '', $db_url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
preg_match($pattern, $url, $m);
$host = $m[3] ?? 'NONE';
$port = $m[4] ?? 'NONE';
$user = $m[1] ?? 'NONE';
$pass = $m[2] ?? 'NONE';
$db = $m[5] ?? 'NONE';

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";

// Test TLS 1.2 only
echo "\n-- TLS 1.2 only --\n";
$ctx = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
]]);
$fp = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
echo $fp ? "TLS 1.2 SUCCESS\n" : "TLS 1.2 FAILED: $errstr ($errno)\n";
if ($fp) fclose($fp);

// Test TLS 1.3
echo "\n-- TLS 1.3 --\n";
$ctx3 = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
]]);
$fp3 = @stream_socket_client("ssl://$host:$port", $errno3, $errstr3, 15, STREAM_CLIENT_CONNECT, $ctx3);
echo $fp3 ? "TLS 1.3 SUCCESS\n" : "TLS 1.3 FAILED: $errstr3 ($errno3)\n";
if ($fp3) fclose($fp3);

// Test TLS 1.1
echo "\n-- TLS 1.1 --\n";
$ctx1 = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
]]);
$fp1 = @stream_socket_client("ssl://$host:$port", $errno1, $errstr1, 15, STREAM_CLIENT_CONNECT, $ctx1);
echo $fp1 ? "TLS 1.1 SUCCESS\n" : "TLS 1.1 FAILED: $errstr1 ($errno1)\n";
if ($fp1) fclose($fp1);

// Try openssl CLI directly
echo "\n-- openssl s_client -connect $host:$port -servername $host (TLS 1.2) --\n";
$output = shell_exec("openssl s_client -connect $host:$port -servername $host -tls1_2 < /dev/null 2>&1");
echo substr($output, 0, 2000) . "\n";

echo "\n-- openssl s_client -connect $host:$port -servername $host (TLS 1.3) --\n";
$output2 = shell_exec("openssl s_client -connect $host:$port -servername $host -tls1_3 < /dev/null 2>&1");
echo substr($output2, 0, 2000) . "\n";

echo "\n-- openssl s_client -connect $host:$port (auto) --\n";
$output3 = shell_exec("openssl s_client -connect $host:$port < /dev/null 2>&1");
echo substr($output3, 0, 2000) . "\n";
