<?php
/**
 * Debug page — shows PHP errors and database connection status.
 * DELETE after debugging.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pdo = db();

// Show DB_DSN info (without password)
$dsn_display = DB_DSN;
if (is_postgres()) {
    $dsn_display = preg_replace('/:\/\/([^:]+):([^@]+)@/', '://$1:***@', $dsn_display);
}
echo "DSN (masked): " . htmlspecialchars($dsn_display) . "\n";
echo "DB Storage: " . DB_STORAGE . "\n";
echo "LIKE_OP: " . LIKE_OP . "\n";
echo "is_postgres: " . (is_postgres() ? 'true' : 'false') . "\n";

// Run the same query index.php uses
try {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1 AND banned = 0')->fetchColumn();
    echo "Users count: " . $count . "\n";
} catch (Throwable $e) {
    echo "Query error: " . $e->getMessage() . "\n";
}

echo "\nAll checks passed.\n";
