<?php
/**
 * Debug page — database connection diagnostics.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

echo "pdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "ssl: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

// Show DB_DSN info (without password)
$dsn_display = DB_DSN;
if (str_starts_with(DB_DSN, 'postgresql://')) {
    $dsn_display = preg_replace('/:\/\/([^:]+):([^@]+)@/', '://$1:***@', $dsn_display);
}
echo "DSN (masked): " . htmlspecialchars($dsn_display) . "\n";

// Parse and show PDO DSN
$url = DB_DSN;
$url = preg_replace('/\?.*$/', '', $url);
$pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/';
if (preg_match($pattern, $url, $m)) {
    echo "Parsed: host=" . $m[3] . " port=" . $m[4] . " db=" . $m[5] . " user=" . $m[1] . "\n";
    
    // Try with PGCONNECT env var
    putenv('PGCONNECT=sslmode=require');
    
    echo "\nTrying PDO connection...\n";
    try {
        $opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
        $dsn = "pgsql:host=" . $m[3] . ";port=" . $m[4] . ";dbname=" . $m[5] . ";user=" . $m[1] . ";password=" . $m[2];
        $pdo = new PDO($dsn, null, null, $opt);
        echo "PDO: SUCCESS\n";
        
        // Try a query
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo "Users count: " . $count . "\n";
    } catch (Throwable $e) {
        echo "PDO error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Regex did not match!\n";
}

echo "\n---\nDone.\n";
