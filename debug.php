<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/(.+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "Host: $host, Port: $port\n";

// Test: PHP stream_socket_enable_crypto after PostgreSQL SSL request
echo "\n-- PHP: manual PostgreSQL SSL upgrade --\n";
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
    fwrite($fp, pack("NN", 8, 80877103));
    $resp = fread($fp, 1);
    echo "SSL request response: '$resp'\n";
    
    if ($resp === 'S') {
        $crypto_result = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($crypto_result) {
            echo "stream_socket_enable_crypto: SUCCESS\n";
        } else {
            echo "stream_socket_enable_crypto: FAILED\n";
            $err = error_get_last();
            echo "Error: " . ($err['message'] ?? 'none') . "\n";
        }
    }
    fclose($fp);
} else {
    echo "fsockopen failed: $errstr ($errno)\n";
}

// Check ldd on libpq
echo "\n-- libpq OpenSSL linkage --\n";
$libpq_path = trim(shell_exec("find /usr -name libpq.so.5 2>/dev/null | head -1"));
echo "libpq.so.5: $libpq_path\n";
echo shell_exec("ldd $libpq_path | grep -E 'ssl|crypto|tls' 2>&1") . "\n";

// Check shared OpenSSL libraries
echo "\n-- System OpenSSL libs --\n";
echo shell_exec("ls -la /lib/x86_64-linux-gnu/libssl* 2>&1") . "\n";
echo shell_exec("dpkg -l | grep libssl 2>&1") . "\n";

// Try psql with verbose output
echo "\n-- psql with PGSSLMODE=require --\n";
$cmd = "PGPASSWORD=$pass PGSSLMODE=require psql -h $host -p $port -d $db -U $user -c 'SELECT 1' 2>&1";
echo shell_exec($cmd) . "\n";
