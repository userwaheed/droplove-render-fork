<?php
/**
 * uploads.php - Serve uploaded images.
 * Supports both database storage (PostgreSQL/Render) and filesystem storage (local dev).
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/translations.php';

$file = basename($_GET['file'] ?? '');
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if ($file === '' || !in_array($ext, $allowed, true)) {
    http_response_code(404); exit;
}

$map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];

// Database storage
if (DB_STORAGE === 'db') {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT mime, data, size FROM uploads WHERE filename = ? LIMIT 1');
    $stmt->execute([$file]);
    $row = $stmt->fetch();
    if (!$row) {
        $fallback = BASE_PATH . '/assets/img/avatar-placeholder.svg';
        if (is_file($fallback)) {
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=86400');
            readfile($fallback);
            exit;
        }
        http_response_code(404); exit;
    }
    header('Content-Type: ' . $map[$ext]);
    header('Content-Length: ' . $row['size']);
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    echo $row['data'];
    exit;
}

// Legacy filesystem storage
$path = UPLOAD_DIR . $file;
if (!is_file($path)) {
    $fallback = BASE_PATH . '/assets/img/avatar-placeholder.svg';
    if (is_file($fallback)) {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400');
        readfile($fallback);
        exit;
    }
    http_response_code(404); exit;
}
header('Content-Type: ' . $map[$ext]);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
