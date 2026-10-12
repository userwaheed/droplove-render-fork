<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OPENSSL_CONF: [" . getenv('OPENSSL_CONF') . "]\n";
echo "PGSSLMODE: [" . getenv('PGSSLMODE') . "]\n";
echo "PG_USE_TUNNEL: [" . getenv('PG_USE_TUNNEL') . "]\n";

// Check if php CLI is available
echo "\n-- PHP CLI availability --\n";
echo "php CLI binary: " . PHP_BINARY . "\n";
echo "php CLI sapi: " . PHP_SAPI . "\n";

// Check if pcntl_fork is available (needed for forking)
echo "pcntl_fork: " . (function_exists('pcntl_fork') ? 'YES' : 'NO') . "\n";
echo "pcntl_signal: " . (function_exists('pcntl_signal') ? 'YES' : 'NO') . "\n";

// Check if pg_tunnel.php exists
echo "\npg_tunnel.php exists: " . (file_exists('/var/www/html/pg_tunnel.php') ? 'YES' : 'NO') . "\n";

// Check if php binary works
echo "php -v: " . shell_exec('php -v 2>&1') . "\n";

// Check if tunnel is listening on 5433
echo "\n-- Tunnel status --\n";
$tfp = @fsockopen("127.0.0.1", 5433, $errno, $errstr, 2);
echo "Tunnel on 127.0.0.1:5433: " . ($tfp ? "LISTENING" : "NOT LISTENING ($errstr)") . "\n";
if ($tfp) fclose($tfp);

// Check tunnel log
$tlog = '/var/www/html/data/tunnel.log';
echo "Tunnel log exists: " . (file_exists($tlog) ? 'YES' : 'NO') . "\n";
if (file_exists($tlog)) {
    echo "Tunnel log (last 20 lines):\n";
    $lines = explode("\n", file_get_contents($tlog));
    foreach (array_slice($lines, -20) as $line) echo "  " . $line . "\n";
}

// Test PDO through tunnel (like db() does when PG_USE_TUNNEL=1)
echo "\n-- PDO through tunnel (127.0.0.1:5433, sslmode=prefer) --\n";
$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$dsn = sprintf('pgsql:host=127.0.0.1;port=5433;dbname=%s;user=%s;password=%s', $m[5], $m[1], $m[2]);
putenv('PGSSLMODE=prefer');
try {
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 30]);
    echo "PDO through tunnel: SUCCESS\n";
    echo "Version: " . substr($pdo->query('SELECT version()')->fetchColumn(), 0, 80) . "\n";
    echo "Users: " . (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "PDO through tunnel FAILED: " . $e->getMessage() . "\n";
}

// Also try through db() function
echo "\n-- PDO through db() function --\n";
require_once __DIR__ . '/db.php';
try {
    $pdo2 = db();
    echo "db(): SUCCESS\n";
    echo "Users: " . (int)$pdo2->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "db() FAILED: " . $e->getMessage() . "\n";
}
