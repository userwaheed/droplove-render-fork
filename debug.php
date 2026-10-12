<?php
echo "<html><body><pre>";
echo "PHP: " . phpversion() . "
";
echo "Extensions: " . implode(', ', array_filter(get_loaded_extensions(), function($e){ return stripos($e,'pdo')!==false || stripos($e,'pgsql')!==false || stripos($e,'openssl')!==false; })) . "
";
echo "PG_USE_TUNNEL: " . getenv('PG_USE_TUNNEL') . "
";
echo "PGSSLMODE: " . getenv('PGSSLMODE') . "
";
echo "Database: " . (defined('DB_DSN') ? (strpos(DB_DSN,'postgresql://')===0 ? substr(DB_DSN,0,50).'...' : 'SQLite') : 'not set') . "
";
echo "</pre></body></html>";
