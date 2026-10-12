<?php
/**
 * pg_tunnel.php - PostgreSQL SSL tunnel
 *
 * Works around libpq SSL handshake failures to Render PostgreSQL.
 * PHP's stream_socket_enable_crypto completes TLS handshakes successfully,
 * but libpq (used by pdo_pgsql) fails.
 *
 * This tunnel:
 *   1. Listens on 127.0.0.1:5433 (plain TCP)
 *   2. Accepts client connections
 *   3. Tells client "N" (no SSL) — client connects without SSL
 *   4. Connects to real PG server, does SSL upgrade via stream_socket_enable_crypto
 *   5. Proxies data between client (plain) and PG server (TLS)
 */

$db_url = getenv('DATABASE_URL');
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$PG_HOST = $m[3] ?? 'localhost';
$PG_PORT = (int)($m[4] ?? 5432);
$PG_USER = $m[1] ?? '';
$PG_PASS = $m[2] ?? '';
$PG_DB  = $m[5] ?? '';

$L_HOST = '127.0.0.1';
$L_PORT = (int)($_ENV['PG_TUNNEL_PORT'] ?? 5433);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('error_log', '/var/www/html/data/tunnel.log');

echo "PG SSL Tunnel: {$L_HOST}:{$L_PORT} -> {$PG_HOST}:{$PG_PORT}\n";
flush();

$server = stream_socket_server("tcp://{$L_HOST}:{$L_PORT}", $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "FATAL: Cannot listen on {$L_HOST}:{$L_PORT}: $errstr ($errno)\n");
    exit(1);
}

// Fork a child for each connection (or run sequentially if no pcntl)
while (true) {
    $client = @stream_socket_accept($server, -1);
    if (!$client) {
        usleep(100000);
        continue;
    }
    
    if (function_exists('pcntl_fork')) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            error_log("Tunnel: fork failed");
            fclose($client);
        } elseif ($pid > 0) {
            fclose($client);
        } else {
            fclose($server);
            handleClient($client, $PG_HOST, $PG_PORT);
            exit(0);
        }
    } else {
        handleClient($client, $PG_HOST, $PG_PORT);
    }
}

function handleClient($client, $host, $port) {
    stream_set_blocking($client, true);
    error_log("Tunnel: client connected from " . stream_socket_get_name($client, true));

    // Step 1: Connect to PG server via plain TCP
    $server = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
    if (!$server) {
        error_log("Tunnel: PG connect failed: $errstr ($errno)");
        // Tell client SSL not available so it sends StartupMessage directly
        fwrite($client, "N");
        // But also handle the case where client doesn't send SSLRequest
        proxyData($client, null);
        return;
    }
    stream_set_blocking($server, true);

    // Step 2: Read client's first packet
    $client_data = readPacket($client);
    if ($client_data === false) {
        error_log("Tunnel: client disconnected before sending data");
        fclose($server);
        fclose($client);
        return;
    }

    // Check protocol version
    $proto = unpack("N", substr($client_data, 4, 4))[1];

    if ($proto === 80877103) {
        // Client wants SSL — tell client NO SSL (we handle TLS server-side)
        fwrite($client, "N");
        error_log("Tunnel: told client 'N' (no SSL)");
        
        // Read client's StartupMessage (sent after N response)
        $startup = readPacket($client);
        if ($startup === false) {
            error_log("Tunnel: client disconnected after N");
            fclose($server);
            fclose($client);
            return;
        }
        
        // Step 3: Send SSL request to PG server, upgrade to TLS
        $ssl_request = pack("NN", 8, 80877103);
        fwrite($server, $ssl_request);
        $resp = fread($server, 1);
        error_log("Tunnel: PG server SSL response: " . bin2hex($resp));

        if ($resp === 'S') {
            $crypto = @stream_socket_enable_crypto($server, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto) {
                error_log("Tunnel: PG server TLS upgrade SUCCESS");
            } else {
                error_log("Tunnel: PG server TLS upgrade FAILED: " . json_encode(error_get_last()));
                fwrite($client, "N");
                fclose($server);
                fclose($client);
                return;
            }
        }

        // Step 4: Forward the client's StartupMessage to PG server
        fwrite($server, $startup);
        
        // Step 5: Proxy data between client (plain) and server (TLS)
        proxyData($client, $server);
    } else {
        // Client sent StartupMessage directly (no SSL request)
        fwrite($server, $client_data);
        
        // Try SSL request to server
        $ssl_request = pack("NN", 8, 80877103);
        fwrite($server, $ssl_request);
        $resp = fread($server, 1);
        if ($resp === 'S') {
            $crypto = @stream_socket_enable_crypto($server, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                error_log("Tunnel: PG server TLS upgrade FAILED");
            }
        }
        
        proxyData($client, $server);
    }

    fclose($server);
    fclose($client);
    error_log("Tunnel: client handled and disconnected");
}

function readPacket($fp) {
    // PostgreSQL protocol: first 4 bytes = length (including these 4 bytes)
    $header = '';
    while (strlen($header) < 4) {
        $chunk = fread($fp, 4 - strlen($header));
        if ($chunk === false || feof($fp)) return false;
        $header .= $chunk;
    }
    $len = unpack("N", $header)[1];
    if ($len < 4) return false;
    
    $payload = '';
    while (strlen($payload) < $len - 4) {
        $chunk = fread($fp, $len - 4 - strlen($payload));
        if ($chunk === false || feof($fp)) return false;
        $payload .= $chunk;
    }
    return $header . $payload;
}

function proxyData($a, $b) {
    if ($b === null) {
        // Just close the client
        fclose($a);
        return;
    }
    stream_set_blocking($a, false);
    stream_set_blocking($b, false);
    
    $buf_ab = '';
    $buf_ba = '';
    $a_dead = false;
    $b_dead = false;
    
    while (!$a_dead || !$b_dead) {
        $read = [];
        $write = [];
        $error = [];
        
        if (!$a_dead) $read[] = $a;
        if (!$b_dead) $read[] = $b;
        if ($buf_ab !== '') $write[] = $b;
        if ($buf_ba !== '') $write[] = $a;
        
        $changed = @stream_select($read, $write, $error, 5);
        if ($changed === false || $changed === 0) {
            usleep(50000);
            continue;
        }
        
        if (in_array($a, $read)) {
            $data = fread($a, 16384);
            if ($data === false || feof($a)) { $a_dead = true; @fclose($a); }
            else $buf_ab .= $data;
        }
        if (in_array($b, $read)) {
            $data = fread($b, 16384);
            if ($data === false || feof($b)) { $b_dead = true; @fclose($b); }
            else $buf_ba .= $data;
        }
        if (in_array($b, $write) && $buf_ab !== '') {
            $w = fwrite($b, $buf_ab);
            if ($w === false) { $b_dead = true; @fclose($b); }
            else $buf_ab = substr($buf_ab, $w);
        }
        if (in_array($a, $write) && $buf_ba !== '') {
            $w = fwrite($a, $buf_ba);
            if ($w === false) { $a_dead = true; @fclose($a); }
            else $buf_ba = substr($buf_ba, $w);
        }
    }
    
    if (!$a_dead) @fclose($a);
    if (!$b_dead) @fclose($b);
}
