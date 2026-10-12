<?php
/**
 * router.php — PHP built-in server shim that mirrors .htaccess.
 *
 * The PHP CLI server does not read .htaccess, so every rewrite there has
 * to be duplicated here for local dev to behave like the Hostinger
 * (Apache) deploy target. If you change .htaccess, update this.
 *
 * Usage:
 *   php -S 127.0.0.1:8120 -t . router.php
 */

declare(strict_types=1);

$uri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$docroot = __DIR__;
$file    = $docroot . $uri;

// ── Blocks (mirror .htaccess RewriteRules). 404, not 403, so a
//    probe can't tell whether the file exists. ─────────────────
$blocked = [
    '#^/(views|data|uploads)(/|$)#i',
    '#^/\.dev(/|$)#i',
    '#(^|/)\.(git|gitignore|htaccess|env|ds_store)(/|$)#i',
    '#(^|/)\.(ini|log|sql|sqlite|db|bak|cache)(/|$)#i',
    '#(^|/)(config|db|helpers)\.php$#i',
];
if ($uri !== '/' && array_filter($blocked, fn($p) => preg_match($p, $uri))) {
    http_response_code(404);
    exit('Not Found');
}

// ── Static files: hand off to the CLI server. ──────────────────
if ($uri !== '/' && is_file($file)) {
    return false;
}

// ── Rewrite clean URLs → .php (Apache's index.php fallback). ──
if ($uri === '/' || $uri === '') {
    $uri = '/index.php';
} elseif (preg_match('#^/[a-z0-9_\-]+$#i', $uri) && is_file($docroot . $uri . '.php')) {
    $uri .= '.php';
} elseif (!preg_match('#\.php$#i', $uri)) {
    http_response_code(404);
    require $docroot . '/views/404.php';
    exit;
}

require $docroot . $uri;
