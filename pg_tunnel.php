<?php
/**
 * pg_tunnel.php - PostgreSQL SSL tunnel
 *
 * Works around libpq SSL handshake failures to Render PostgreSQL.
 * PHP stream_socket_enable_crypto completes TLS handshakes successfully,
 * but libpq (used by pdo_pgsql) fails.
 *
 * Tunnel listens on 127.0.0.1:5433 (plain TCP) and forwards to
 * the real PG server over TLS, terminating SSL server-side.
 */

$db_url = getenv("DATABASE_URL");
preg_match('/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/', $db_url, $m);
$PG_HOST = $m[3] ?? 'localhost';
$PG_PORT = (int)($m[4] ?? 5432);

$L_HOST = '127.0.0.1';
$L_PORT = (int)(getenv('PG_TUNNEL_PORT') ?: 5433);

error_reporting(E_ALL);
ini_set("display_errors", "1");
ini_set('error_log', '/var/www/html/data/tunnel.log');
ini_set("memory_limit", "64M");
echo "PG SSL Tunnel: {$L_HOST}:{$L_PORT} -> {$PG_HOST}:{$PG_PORT}\n";
flush();

$server = stream_socket_server("tcp://{$L_HOST}:{$L_PORT}", $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "FATAL: Cannot listen on {$L_HOST}:{$L_PORT}: $errstr ($errno)\n");
    exit(1);
}

echo "Tunnel listening on {$L_HOST}:{$L_PORT}\n";
flush();

while (true) {
    $client = @stream_socket_accept($server, -1);
    if (!$client) {
        usleep(100000);
        continue;
    }
    
    if (function_exists("pcntl_fork")) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            error_log("Tunnel: fork failed");
            @if (is_resource($client)) @fclose($client);
        } elseif ($pid > 0) {
            @if (is_resource($client)) @fclose($client);
        } else {
            @if (is_resource($server)) @fclose($server);
            $r = handleClient($client, $PG_HOST, $PG_PORT);
            error_log("Tunnel: client handled, result=" . $r);
            exit(0);
        }
    } else {
        handleClient($client, $PG_HOST, $PG_PORT);
    }
}

function readPacket($fp, $timeout = 15) {
    // PostgreSQL protocol: first 4 bytes = length (including these 4 bytes)
    stream_set_timeout($fp, $timeout);
    $header = "";
    while (strlen($header) < 4) {
        $chunk = fread($fp, 4 - strlen($header));
        if ($chunk === false || feof($fp)) return false;
        if (strlen($chunk) == 0) return false;
        $header .= $chunk;
    }
    $len = unpack("N", $header)[1];
    if ($len < 4 || $len > 104857600) {
        // Sanity check: packet length must be 4-100MB
        error_log("Tunnel: invalid packet length: $len");
        return false;
    }
    
    $payload = "";
    while (strlen($payload) < $len - 4) {
        $need = $len - 4 - strlen($payload);
        $chunk = fread($fp, $need);
        if ($chunk === false || feof($fp)) return false;
        if (strlen($chunk) == 0) return false;
        $payload .= $chunk;
    }
    return $header . $payload;
}

function handleClient($client, $host, $port) {
    stream_set_blocking($client, true);
    error_log("Tunnel: client connected from " . stream_socket_get_name($client, true));

    // Step 1: Connect to PG server via plain TCP
    $server = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
    if (!$server) {
        error_log("Tunnel: PG connect failed: $errstr ($errno)");
        fwrite($client, "N");
        @if (is_resource($client)) @fclose($client);
        return "pg_connect_failed";
    }
    stream_set_blocking($server, true);

    // Step 2: Read client first packet (SSLRequest or StartupMessage)
    $client_data = readPacket($client);
    if ($client_data === false) {
        error_log("Tunnel: client disconnected before sending data");
        @if (is_resource($server)) @fclose($server);
        @if (is_resource($client)) @fclose($client);
        return "client_disconnected";
    }

    // Check protocol: SSLRequest = 80877103
    $proto = unpack("N", substr($client_data, 4, 4))[1];

    if ($proto === 80877103) {
        // Client wants SSL — tell client NO SSL (tunnel handles TLS server-side)
        fwrite($client, "N");
        error_log("Tunnel: told client N (no SSL)");
        
        // Read client StartupMessage (sent after N response)
        $startup = readPacket($client);
        if ($startup === false) {
            error_log("Tunnel: client disconnected after N");
            @if (is_resource($server)) @fclose($server);
            @if (is_resource($client)) @fclose($client);
            return "client_disconnected_after_n";
        }
        
        // Send SSL request to PG server, upgrade to TLS
        $ssl_request = pack("NN", 8, 80877103);
        fwrite($server, $ssl_request);
        $resp = fread($server, 1);
        error_log("Tunnel: PG server SSL response: " . bin2hex($resp));

        if ($resp === "S") {
            $crypto = @stream_socket_enable_crypto($server, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto) {
                error_log("Tunnel: PG server TLS upgrade SUCCESS");
            } else {
                error_log("Tunnel: PG server TLS upgrade FAILED: " . json_encode(error_get_last()));
                fwrite($client, "N");
                @if (is_resource($server)) @fclose($server);
                @if (is_resource($client)) @fclose($client);
                return "tls_failed";
            }
        }

        // Forward client StartupMessage to PG server
        fwrite($server, $startup);
        
        // Proxy data between client (plain) and server (TLS)
        proxyData($client, $server);
    } else {
        // Client sent StartupMessage directly (no SSL request)
        fwrite($server, $client_data);
        
        // Try SSL request to server
        $ssl_request = pack("NN", 8, 80877103);
        fwrite($server, $ssl_request);
        $resp = fread($server, 1);
        if ($resp === "S") {
            $crypto = @stream_socket_enable_crypto($server, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                error_log("Tunnel: PG server TLS upgrade FAILED (no SSL from client)");
            }
        }
        
        proxyData($client, $server);
    }

    @if (is_resource($server)) @fclose($server);
    @if (is_resource($client)) @fclose($client);
    error_log("Tunnel: client handled and disconnected");
    return "ok";
}

function proxyData($a, $b) {
    stream_set_timeout($a, 0);
    stream_set_timeout($b, 0);
    stream_set_blocking($a, false);
    stream_set_blocking($b, false);
    
    $buf_ab = "";
    $buf_ba = "";
    $a_dead = false;
    $b_dead = false;
    $bytes_a2b = 0;
    $bytes_b2a = 0;
    
    while (!$a_dead || !$b_dead) {
        $read = array();
        $write = array();
        $error = array();
        
        if (!$a_dead) $read[] = $a;
        if (!$b_dead) $read[] = $b;
        if ($buf_ab !== "") $write[] = $b;
        if ($buf_ba !== "") $write[] = $a;
        
        $changed = @stream_select($read, $write, $error, 120);
        if ($changed === false || $changed === 0) {
            error_log("Tunnel: select timeout/idle, a2b=" . $bytes_a2b . " b2a=" . $bytes_b2a);
            usleep(50000);
            continue;
        }
        
        if (in_array($a, $read, true)) {
            $data = fread($a, 16384);
            if ($data === false || feof($a)) {
                error_log("Tunnel: client EOF, a2b=" . $bytes_a2b . " b2a=" . $bytes_b2a);
                $a_dead = true;
                if (is_resource($a)) @fclose($a);
            } else {
                $bytes_a2b += strlen($data);
                $buf_ab .= $data;
                // Log first few bytes of each packet
                $ptype = strlen($data) > 0 ? bin2hex(substr($data, 0, min(1, strlen($data)))) : "";
                // error_log("Tunnel: client->" . $bytes_a2b . " bytes: " . bin2hex(substr($data, 0, 8)));
            }
        }
        if (in_array($b, $read, true)) {
            $data = fread($b, 16384);
            if ($data === false || feof($b)) {
                error_log("Tunnel: server EOF, a2b=" . $bytes_a2b . " b2a=" . $bytes_b2a);
                $b_dead = true;
                if (is_resource($b)) @fclose($b);
            } else {
                $bytes_b2a += strlen($data);
                $buf_ba .= $data;
                // Log first few bytes of server response
                error_log("Tunnel: server->" . $bytes_b2a . " bytes: " . bin2hex(substr($data, 0, min(20, strlen($data)))));
            }
        }
        if (in_array($b, $write, true) && $buf_ab !== "") {
            $w = fwrite($b, $buf_ab);
            if ($w === false) { $b_dead = true; if (is_resource($b)) @fclose($b); }
            else $buf_ab = substr($buf_ab, $w);
        }
        if (in_array($a, $write, true) && $buf_ba !== "") {
            $w = fwrite($a, $buf_ba);
            if ($w === false) { $a_dead = true; if (is_resource($a)) @fclose($a); }
            else $buf_ba = substr($buf_ba, $w);
        }
    }
    
    if (is_resource($a)) @fclose($a);
    if (is_resource($b)) @fclose($b);
}
