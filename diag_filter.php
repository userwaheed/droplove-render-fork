<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');

$pdo = db();
// Build a colorful test image
$im = imagecreatetruecolor(100, 100);
$red = imagecolorallocate($im, 255, 0, 0);
imagefill($im, 0, 0, $red);
ob_start(); imagepng($im, null, 5); $pngData = ob_get_clean(); imagedestroy($im);

$testName = 'diag_' . bin2hex(random_bytes(4)) . '.png';
$stmt = $pdo->prepare('INSERT OR IGNORE INTO uploads (filename, mime, data, size) VALUES (?,?,?,?)');
$stmt->execute([$testName, 'image/png', $pngData, strlen($pngData)]);
echo "Inserted $testName (" . strlen($pngData) . " bytes)\n";

// Verify it's stored (within same request, data IS present)
$stmt2 = $pdo->prepare("SELECT size FROM uploads WHERE filename=?"); $stmt2->execute([$testName]);
$sz = $stmt2->fetchColumn();
echo "Stored size: " . ($sz ?: 'MISSING') . "\n";

echo "--- apply_image_filters (grayscale=100, brightness=150) ---\n";
try {
    $result = apply_image_filters($testName, [
        'brightness'=>150,'contrast'=>100,'saturation'=>100,
        'blur'=>0,'grayscale'=>100,'hue'=>0,'rotate'=>0,
    ]);
    echo "RESULT: " . ($result ?: 'NULL') . "\n";
} catch (Throwable $e) {
    echo "EXCEPTION [" . get_class($e) . "]: " . $e->getMessage() . "\n";
    echo "  at " . basename($e->getFile()) . ":" . $e->getLine() . "\n";
}

// Check if edited file got stored
$stmt3 = $pdo->query("SELECT filename,size FROM uploads WHERE filename LIKE 'edited_%'");
$ed = $stmt3->fetchAll();
echo "edited_ files after: " . count($ed) . "\n";
foreach ($ed as $ef) echo "  " . $ef['filename'] . " size=" . $ef['size'] . "\n";

// Full uploads table listing
echo "ALL uploads:\n";
foreach ($pdo->query("SELECT filename,size FROM uploads") as $r) {
    echo "  " . $r['filename'] . " = " . $r['size'] . " bytes\n";
}
