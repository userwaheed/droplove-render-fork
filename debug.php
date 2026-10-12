<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "OPENSSL_CONF: [" . getenv('OPENSSL_CONF') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";

// Check if the OpenSSL config file exists and show it
$conf_path = getenv('OPENSSL_CONF') ?: '/usr/local/ssl/openssl.cnf';
echo "OpenSSL conf file exists: " . (file_exists($conf_path) ? 'YES' : 'NO') . "\n";
if (file_exists($conf_path)) {
    echo "OpenSSL conf content:\n";
    echo file_get_contents($conf_path) . "\n";
}

// Check OpenSSL default security level
echo "OpenSSL default SSL context options: " . json_encode(stream_context_get_options(stream_context_create(['ssl' => []]))) . "\n";

// Try with OPENSSL_CONF explicitly set via putenv
putenv('OPENSSL_CONF=' . $conf_path);
echo "OPENSSL_CONF after putenv: [" . getenv('OPENSSL_CONF') . "]\n";

// Also set in $_ENV
$_ENV['OPENSSL_CONF'] = $conf_path;

// Test via psql with SSL
echo "\n-- psql SSL test --\n";
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m2);
putenv('PGPASSWORD=' . $m2[2]);
$cmd = "psql \"host={$m2[3]} port={$m2[4]} dbname={$m2[5]} user={$m2[1]} sslmode=require\" -c 'SELECT 1 as result' 2>&1";
echo shell_exec($cmd) . "\n";

// Test with psql and different sslmode
echo "\n-- psql with sslmode=prefer --\n";
$cmd2 = "psql \"host={$m2[3]} port={$m2[4]} dbname={$m2[5]} user={$m2[1]} sslmode=prefer\" -c 'SELECT 1 as result' 2>&1";
echo shell_exec($cmd2) . "\n";

// Test with OPENSSL_CONF set explicitly
echo "\n-- psql with OPENSSL_CONF set explicitly --\n";
$cmd3 = "OPENSSL_CONF=/usr/local/ssl/openssl.cnf psql \"host={$m2[3]} port={$m2[4]} dbname={$m2[5]} user={$m2[1]} sslmode=require\" -c 'SELECT 1 as result' 2>&1";
echo shell_exec($cmd3) . "\n";

// Test with PGSSLMODE
echo "\n-- psql with PGSSLMODE env --\n";
putenv('PGSSLMODE=require');
$cmd4 = "PGSSLMODE=require OPENSSL_CONF=/usr/local/ssl/openssl.cnf psql \"host={$m2[3]} port={$m2[4]} dbname={$m2[5]} user={$m2[1]}\" -c 'SELECT 1 as result' 2>&1";
echo shell_exec($cmd4) . "\n";

// Test openssl s_client to see what ciphers/protocols work
echo "\n-- openssl s_client test --\n";
$cmd5 = "echo 'Q' | timeout 10 openssl s_client -connect {$m2[3]}:{$m2[4]} 2>&1 | head -30";
echo shell_exec($cmd5) . "\n";

// Test PHP stream_socket_enable_crypto
echo "\n-- PHP stream_socket_enable_crypto test --\n";
$fp = @fsockopen($m2[3], (int)$m2[4], $errno, $errstr, 10);
if ($fp) {
    fwrite($fp, pack("NN", 8, 80877103));
    $resp = fread($fp, 1);
    echo "PG SSL response: " . bin2hex($resp) . " (" . ($resp === "S" ? "SSL supported" : "not supported") . ")\n";
    if ($resp === "S") {
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        echo "PHP TLS handshake: " . ($crypto ? "SUCCESS" : "FAILED: " . $crypto) . "\n";
        if ($crypto) {
            fwrite($fp, pack("NNn", 8, 196608, 3)); // StartupMessage version 3.0
            $resp2 = fread($fp, 1000);
            echo "After startup: " . bin2hex(substr($resp2, 0, 20)) . " (" . substr($resp2, 0, 50) . ")\n";
        }
    }
    fclose($fp);
} else {
    echo "fsockopen failed: $errstr ($errno)\n";
}
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
