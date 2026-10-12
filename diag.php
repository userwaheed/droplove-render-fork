<?php
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "Loaded extensions:\n";
$exts = get_loaded_extensions();
foreach ($exts as $ext) {
    echo "  - $ext\n";
}
echo "\nPDO drivers:\n";
if (extension_loaded('pdo')) {
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo "  - $driver\n";
    }
}
echo "\nConfig files scan dir: " . ini_get('scan_dir') . "\n";
echo "Extension dir: " . ini_get('extension_dir') . "\n";
