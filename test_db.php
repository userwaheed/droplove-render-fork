<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<html><body><pre>";

// Load config
try {
    require_once __DIR__ . '/config.php';
    echo "Config loaded OK\n";
    echo "DB_DSN: " . (defined('DB_DSN') ? DB_DSN : 'NOT DEFINED') . "\n";
} catch (Throwable $e) {
    echo "Config FAILED: " . $e->getMessage() . "\n";
}

// Try DB connection
try {
    require_once __DIR__ . '/db.php';
    $pdo = db();
    echo "db(): SUCCESS\n";
    
    // Check tables
    if (str_starts_with(DB_DSN, 'sqlite:')) {
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables: " . implode(', ', $tables) . "\n";
        if (in_array('users', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Users: " . $count . "\n";
        }
    } else {
        echo "Connected to PostgreSQL\n";
    }
} catch (Throwable $e) {
    echo "db() FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre></body></html>";