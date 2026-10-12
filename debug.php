<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");

echo "pdo_pgsql: " . (extension_loaded("pdo_pgsql") ? "YES" : "NO") . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "PG_USE_TUNNEL: [" . getenv("PG_USE_TUNNEL") . "]\n";

require_once __DIR__ . "/config.php";
$url = preg_replace("/\\?.*$/", "", DB_DSN);
preg_match('/^postgresql:\/\/(.+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $url, $m);
$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];

// Test 1: Direct PDO (sslmode=require) - should fail
echo "\n-- Test 1: Direct PDO (sslmode=require) --\n";
$dsn = "pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$pass";
try {
    putenv("PGSSLMODE=require");
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    echo "Direct PDO: SUCCESS\n";
} catch (Throwable $e) {
    echo "Direct PDO: FAILED - " . $e->getMessage() . "\n";
}

// Test 2: Tunnel PDO with PGSSLMODE=prefer (allows non-SSL fallback)
echo "\n-- Test 2: PDO via tunnel (PGSSLMODE=prefer) --\n";
$tunnel_dsn = "pgsql:host=127.0.0.1;port=5433;dbname=$db;user=$user;password=$pass";
try {
    putenv("PGSSLMODE=prefer");
    $pdo = new PDO($tunnel_dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    echo "Tunnel PDO: SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) {
    echo "Tunnel PDO: FAILED - " . $e->getMessage() . "\n";
}

// Test 3: Tunnel PDO with PGSSLMODE=disable
echo "\n-- Test 3: PDO via tunnel (PGSSLMODE=disable) --\n";
try {
    putenv("PGSSLMODE=disable");
    $pdo = new PDO($tunnel_dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    echo "Tunnel PDO (disable SSL): SUCCESS\n";
} catch (Throwable $e) {
    echo "Tunnel PDO (disable SSL): FAILED - " . $e->getMessage() . "\n";
}

// Test 4: PHP SSL to PG server (proves OpenSSL works)
echo "\n-- Test 4: PHP SSL to PG server --\n";
$fp = @fsockopen($host, (int)$port, $errno, $errstr, 10);
if ($fp) {
    fwrite($fp, pack("NN", 8, 80877103));
    $resp = fread($fp, 1);
    echo "PG SSL response: " . bin2hex($resp) . " (" . ($resp === "S" ? "SSL supported" : "SSL NOT supported") . ")\n";
    if ($resp === "S") {
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        echo "PHP TLS handshake: " . ($crypto ? "SUCCESS" : "FAILED") . "\n";
    }
    fclose($fp);
} else {
    echo "fsockopen failed: $errstr ($errno)\n";
}

// Check tunnel status
echo "\n-- Tunnel status --\n";
$tfp = @fsockopen("127.0.0.1", 5433, $errno2, $errstr2, 2);
echo $tfp ? "Tunnel is LISTENING" : "Tunnel NOT listening: $errstr2 ($errno2)";
echo "\n";
if ($tfp) fclose($tfp);

// Check tunnel log
$logfile = "/var/www/html/data/tunnel.log";
if (file_exists($logfile)) {
    echo "\n-- Tunnel log (last 20 lines) --\n";
    $lines = file($logfile);
    echo implode("", array_slice($lines, -20));
}
