<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<html><body><pre>";
require_once __DIR__ . '/config.php';
echo "PHP: " . phpversion() . "
";
echo "DB_DSN: " . (defined('DB_DSN') ? DB_DSN : 'NOT DEFINED') . "
";
try {
    require_once __DIR__ . '/db.php';
    $pdo = db();
    echo "db(): SUCCESS
";
} catch (Throwable $e) {
    echo "db() FAILED: " . $e->getMessage() . "
";
}
echo "</pre></body></html>";
