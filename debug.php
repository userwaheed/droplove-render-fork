<?php
// Test database connection via tunnel
require_once __DIR__ . "/db.php";

echo "pdo_pgsql: " . (extension_loaded("pdo_pgsql") ? "YES" : "NO") . "\n";
echo "OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "PG_USE_TUNNEL: [" . getenv("PG_USE_TUNNEL") . "]\n";
echo "PGSSLMODE: [" . getenv("PGSSLMODE") . "]\n";

// Test via db() function (respects PG_USE_TUNNEL)
echo "\n-- Test: db() function (uses tunnel if PG_USE_TUNNEL=1) --\n";
try {
    $pdo = db();
    echo "db() SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) {
    echo "db() FAILED: " . $e->getMessage() . "\n";
}

// Test direct PDO to tunnel (PGSSLMODE=prefer)
echo "\n-- Test: direct PDO to tunnel (prefer SSL) --\n";
preg_match('/^postgresql:\/\/(.+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', DB_DSN, $m);
$dsn = "pgsql:host=127.0.0.1;port=5433;dbname={$m[5]};user={$m[1]};password={$m[2]}";
putenv("PGSSLMODE=prefer");
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "Direct tunnel PDO: SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) {
    echo "Direct tunnel PDO: FAILED - " . $e->getMessage() . "\n";
}

// Test direct PDO to tunnel (PGSSLMODE=disable)
echo "\n-- Test: direct PDO to tunnel (disable SSL) --\n";
try {
    putenv("PGSSLMODE=disable");
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]);
    echo "Direct tunnel PDO (disable): SUCCESS\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $count\n";
} catch (Throwable $e) {
    echo "Direct tunnel PDO (disable): FAILED - " . $e->getMessage() . "\n";
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
