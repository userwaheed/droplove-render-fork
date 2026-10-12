<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "OPENSSL_CONF: [" . getenv('OPENSSL_CONF') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";
echo "DATABASE_URL: [" . (getenv('DATABASE_URL') ? 'SET (redacted)' : 'NOT SET') . "]\n";

// Check if the OpenSSL config file exists and show it
$conf_path = getenv('OPENSSL_CONF') ?: '/usr/local/ssl/openssl.cnf';
echo "OpenSSL conf file exists: " . (file_exists($conf_path) ? 'YES' : 'NO') . "\n";
if (file_exists($conf_path)) {
    echo "OpenSSL conf content:\n---\n" . file_get_contents($conf_path) . "\n---\n";
}

// Try with OPENSSL_CONF explicitly set via putenv
putenv('OPENSSL_CONF=' . $conf_path);
$_ENV['OPENSSL_CONF'] = $conf_path;

// Get DATABASE_URL early
$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);

echo "\n-- psql SSL test (sslmode=require) --\n";
putenv('PGPASSWORD=' . $m[2]);
echo shell_exec("psql \"host={$m[3]} port={$m[4]} dbname={$m[5]} user={$m[1]} sslmode=require\" -c 'SELECT 1 as result' 2>&1") . "\n";

echo "\n-- psql (sslmode=prefer) --\n";
echo shell_exec("psql \"host={$m[3]} port={$m[4]} dbname={$m[5]} user={$m[1]} sslmode=prefer\" -c 'SELECT 1 as result' 2>&1") . "\n";

echo "\n-- psql with OPENSSL_CONF (sslmode=require) --\n";
echo shell_exec("OPENSSL_CONF=/usr/local/ssl/openssl.cnf psql \"host={$m[3]} port={$m[4]} dbname={$m[5]} user={$m[1]} sslmode=require\" -c 'SELECT 1 as result' 2>&1") . "\n";

echo "\n-- openssl s_client test --\n";
echo shell_exec("echo 'Q' | timeout 10 openssl s_client -connect {$m[3]}:{$m[4]} 2>&1 | head -20") . "\n";

echo "\n-- openssl s_client -starttls postgres test --\n";
putenv('PGPASSWORD=' . $m[2]);
echo shell_exec("echo 'Q' | timeout 10 psql \"host={$m[3]} port={$m[4]} dbname={$m[5]} user={$m[1]} sslmode=require\" -c '\\q' 2>&1; echo '---'; echo 'Q' | timeout 10 openssl s_client -starttls postgres -connect {$m[3]}:{$m[4]} -debug 2>&1 | head -30") . "\n";

echo "\n-- PHP stream_socket_enable_crypto test --\n";
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

echo "\n-- PDO direct connection (sslmode=require) --\n";
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $m[3], $m[4], $m[5], $m[1], $m[2]);
putenv('PGSSLMODE=require');
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "PDO: SUCCESS\n";
    echo "Version: " . substr($pdo->query('SELECT version()')->fetchColumn(), 0, 80) . "\n";
    echo "Users: " . (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "PDO FAILED: " . $e->getMessage() . "\n";
}
