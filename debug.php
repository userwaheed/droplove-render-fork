<?php
// Test with psql and openssl starttls
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_url = getenv('DATABASE_URL');
echo "DATABASE_URL: " . substr($db_url, 0, 30) . "***\n";

// Use psql to test the connection
echo "\n-- psql connection test --\n";
putenv('PGPASSWORD=' . getenv('DB_PASSWORD') ?: '');
$output = shell_exec("psql " . escapeshellarg($db_url) . " -c 'SELECT 1' 2>&1");
echo "psql result: " . substr($output ?: 'NULL', 0, 1000) . "\n";

// Try openssl s_client with starttls postgres
echo "\n-- openssl s_client with -starttls postgres --\n";
$output2 = shell_exec("openssl s_client -starttls postgres -connect " . getenv('PGHOST') . ":5432 -servername " . getenv('PGHOST') . " < /dev/null 2>&1");
echo substr($output2 ?: 'NULL', 0, 1500) . "\n";

// Also try with PGPASSWORD set from the URL
preg_match('/^postgresql:\/\/(.+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$host = $m[3];
$port = $m[4];
echo "\n-- openssl s_client -starttls postgres to $host:$port --\n";
$output3 = shell_exec("openssl s_client -starttls postgres -connect $host:$port < /dev/null 2>&1");
echo substr($output3 ?: 'NULL', 0, 1500) . "\n";
