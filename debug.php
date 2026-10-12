<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/(.+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$host = $m[3]; $port = $m[4]; $db = $m[5]; $user = $m[1]; $pass = $m[2];

echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";

// Check libpq version from psql
echo "\n-- psql --version --\n";
echo shell_exec("psql --version 2>&1") . "\n";

// Check which libpq psql links against
echo "\n-- ldd psql (OpenSSL/libpq linkage) --\n";
echo shell_exec("ldd $(which psql) | grep -E ssl|pq|postgres 2>&1") . "\n";

// Check which libpq php links against
echo "\n-- ldd apache2 (or php) --\n";
$output = shell_exec("ldd /usr/local/bin/php 2>/dev/null | grep -E ssl|pq|postgres");
echo $output . "\n";

// Check libpq version
echo "\n-- pg_config --version --\n";
echo shell_exec("pg_config --version 2>&1") . "\n";

// Check the actual libpq shared library
echo "\n-- dpkg -l libpq* --\n";
echo shell_exec("dpkg -l 'libpq*' 2>&1") . "\n";

// Check openssl version
echo "\n-- openssl version --\n";
echo shell_exec("openssl version 2>&1") . "\n";

// Check ldd on the PHP module
echo "\n-- Find pdo_pgsql.so and check linkage --\n";
$so = shell_exec("find /usr/local/lib/php/extensions -name pdo_pgsql.so 2>/dev/null | head -1");
if ($so) {
    echo "pdo_pgsql.so: $so\n";
    echo shell_exec("ldd $so | grep -E ssl|pq 2>&1") . "\n";
}

// Now let me try the raw PostgreSQL SSL request approach
echo "\n-- Manual PostgreSQL SSL request test --\n";
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
    // Send SSL request: length=8, protocol=80877103 (8 bytes)
    $ssl_request = pack("NN", 8, 80877103);
    fwrite($fp, $ssl_request);
    $response = fread($fp, 1);
    fclose($fp);
    echo "Server response: " . bin2hex($response) . " (ASCII: $response)\n";
    echo "  S = SSL supported, N = SSL not supported\n";
} else {
    echo "Failed to connect: $errstr ($errno)\n";
}
