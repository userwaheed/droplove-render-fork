<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');

echo "PHP: " . PHP_VERSION . "\n";
echo "GD: " . (extension_loaded('gd') ? 'YES' : 'NO') . "\n";
echo "DB_STORAGE: " . DB_STORAGE . "\n";
echo "data/ writable: " . (is_writable(__DIR__ . '/data') ? 'YES' : 'NO') . "\n";
$tf = @tempnam(sys_get_temp_dir(), 'wt_');
echo "tempnam: " . ($tf ? 'OK' : 'FAILED') . "\n";
if ($tf) { echo "tmp write: " . (@file_put_contents($tf,'x') !== false ? 'OK' : 'FAIL') . "\n"; @unlink($tf); }

echo "uploads rows before: " . db()->query("SELECT COUNT(*) FROM uploads")->fetchColumn() . "\n";
try { echo "users rows: " . db()->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n"; }
catch (Throwable $e) { echo "users: " . $e->getMessage() . "\n"; }

// Self-contained: create upload, apply filters, verify — one request, survives deploy wipe
try {
    $pdo = db();
    $im = imagecreatetruecolor(200, 200);
    for ($x=0;$x<200;$x++) for ($y=0;$y<200;$y++) imagesetpixel($im,$x,$y, (($x*255)/200), (($y*255)/200), 100);
    ob_start(); imagepng($im, null, 5); $pngData = ob_get_clean(); imagedestroy($im);

    $testName = 'diag_' . bin2hex(random_bytes(4)) . '.png';
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO uploads (filename, mime, data, size) VALUES (?,?,?,?)');
    $stmt->execute([$testName, 'image/png', $pngData, strlen($pngData)]);
    echo "\nInserted $testName (" . strlen($pngData) . " bytes)\n";

    $stmt2 = $pdo->prepare("SELECT size FROM uploads WHERE filename=?"); $stmt2->execute([$testName]);
    echo "Read back size: " . ($stmt2->fetchColumn(false) ?: 'MISSING') . "\n";

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

    $stmt3 = $pdo->prepare("SELECT filename,size FROM uploads WHERE filename LIKE 'edited_%'");
    $stmt3->execute([]);
    $ed = $stmt3->fetchAll();
    echo "edited_ files: " . count($ed) . "\n";
    foreach ($ed as $ef) echo "  " . $ef['filename'] . " size=" . $ef['size'] . "\n";
} catch (Throwable $e) {
    echo "SETUP EXCEPTION [" . get_class($e) . "]: " . $e->getMessage() . "\n";
    echo "  at " . basename($e->getFile()) . ":" . $e->getLine() . "\n";
}
