<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<html><body><pre>";
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = db();
    echo "Database connected: OK\n";
    
    // Check if uploads table exists
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    // Check if uploads table exists
    if (in_array('uploads', $tables)) {
        echo "uploads table: EXISTS\n";
        $count = $pdo->query("SELECT COUNT(*) FROM uploads")->fetchColumn();
        echo "uploads count: " . $count . "\n";
    } else {
        echo "uploads table: DOES NOT EXIST\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "</pre></body></html>";
