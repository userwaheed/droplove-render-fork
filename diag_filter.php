<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');

echo "GD loaded: " . (extension_loaded('gd') ? 'YES' : 'NO') . "\n";
echo "imagefilter: " . (function_exists('imagefilter') ? 'YES' : 'NO') . "\n";
echo "imagerotate: " . (function_exists('imagerotate') ? 'YES' : 'NO') . "\n";
echo "imagecreatefromjpeg: " . (function_exists('imagecreatefromjpeg') ? 'YES' : 'NO') . "\n";
echo "uploads table:\n";
try {
    $pdo = db();
    $rows = $pdo->query("SELECT filename, mime, size FROM uploads")->fetchAll();
    echo "uploads rows: " . count($rows) . "\n";
    foreach ($rows as $r) {
        echo "  " . $r['filename'] . " | " . $r['mime'] . " | " . $r['size'] . " bytes\n";
    }
} catch (Throwable $e) {
    echo "ERROR reading uploads: " . $e->getMessage() . "\n";
}

// Test the filter function on an existing upload
try {
    $pdo = db();
    $row = $pdo->query("SELECT filename FROM uploads LIMIT 1")->fetch();
    if ($row) {
        echo "\nTesting apply_image_filters on: " . $row['filename'] . "\n";
        $result = apply_image_filters($row['filename'], [
            'brightness' => 150, 'contrast' => 100, 'saturation' => 100,
            'blur' => 0, 'grayscale' => 100, 'hue' => 0, 'rotate' => 0,
        ]);
        echo "Result: " . ($result ? $result : 'NULL (failed)') . "\n";
    } else {
        echo "\nNo uploads to test.\n";
    }
} catch (Throwable $e) {
    echo "\nEXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Direct GD test
try {
    $im = imagecreatetruecolor(50, 50);
    imagefill($im, 0, 0, 0xFF0000);
    imagefilter($im, IMG_FILTER_BRIGHTNESS, 100);
    imagefilter($im, IMG_FILTER_GRAYSCALE, 100);
    ob_start();
    imagejpeg($im, null, 80);
    $out = ob_get_clean();
    imagedestroy($im);
    echo "\nDirect GD test: OK, " . strlen($out) . " bytes output\n";
} catch (Throwable $e) {
    echo "\nDirect GD test FAILED: " . $e->getMessage() . "\n";
}
