<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<html><body><pre>";

// Try to load the homepage and capture the error
ob_start();
try {
    // Simulate what index.php does
    require_once __DIR__ . '/config.php';
    echo "Config loaded OK
";
    
    // Try DB connection
    $opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
    if (str_starts_with(DB_DSN, 'postgresql://')) {
        echo "Using PostgreSQL
";
    } else {
        echo "Using SQLite: " . DB_DSN . "
";
        $dir = __DIR__ . '/data';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:' . $dir . '/vk_match.db', null, null, $opt);
        $pdo->exec('PRAGMA journal_mode=WAL');
        echo "SQLite connected OK
";
        
        // Check if tables exist
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables: " . implode(', ', $tables) . "
";
        
        if (in_array('users', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Users count: " . $count . "
";
        } else {
            echo "Users table does NOT exist
";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "
";
    echo "Stack: " . $e->getTraceAsString() . "
";
}

echo "</pre></body></html>";
