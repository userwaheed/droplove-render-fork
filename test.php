<?php
echo "<html><body><pre>";
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "PHP Version: " . phpversion() . "
";
echo "PG_USE_TUNNEL: " . getenv('PG_USE_TUNNEL') . "
";
echo "PGSSLMODE: " . getenv('PGSSLMODE') . "
";

// Check if config.php loads
try {
    require_once __DIR__ . '/config.php';
    echo "config.php: LOADED OK
";
    echo "DB_DSN defined: " . (defined('DB_DSN') ? 'YES' : 'NO') . "
";
    if (defined('DB_DSN')) {
        echo "DB_DSN: " . substr(DB_DSN, 0, 60) . "...
";
    }
    // Try DB connection
    try {
        require_once __DIR__ . '/db.php';
        $pdo = db();
        echo "db(): SUCCESS
";
        $pdo->query("SELECT COUNT(*) FROM users");
        echo "Users table: OK
";
    } catch (Exception $e) {
        echo "db() FAILED: " . $e->getMessage() . "
";
    }
} catch (Exception $e) {
    echo "config.php FAILED: " . $e->getMessage() . "
";
}

echo "</pre></body></html>";
