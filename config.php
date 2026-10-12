<?php
/**
 * config.php — Central configuration.
 * Edit APP_NAME, CATEGORIES and LANGS before going live.
 */

declare(strict_types=1);

// ── App identity ──────────────────────────────────────────────────────────
define('APP_NAME',        'DrofLove.com');
define('APP_TAGLINE',     'Dating for singles, couples & the curious');
define('APP_VERSION',     '1.0.0');
define('APP_DEFAULT_LANG','en');

// ── Paths ──────────────────────────────────────────────────────────────────
define('BASE_PATH', __DIR__);
define('UPLOAD_DIR', BASE_PATH . '/data/uploads/'); // legacy; unused when DB storage is enabled

// ── Database ──────────────────────────────────────────────────────────────
// DATABASE_URL is set by Render (and any PaaS). Falls back to a local SQLite
// path for dev convenience. When the DSN starts with 'postgresql://', the
// app uses PDO PostgreSQL and stores uploads in the `uploads` table instead
// of on disk (Render free tier has no persistent volume).
define('DB_DSN', 'sqlite:' . __DIR__ . '/data/vk_match.db'); // FORCED SQLite - database was deleted on Render
define('DB_STORAGE', str_starts_with(DB_DSN, 'postgresql://') ? 'db' : 'file');
define('LIKE_OP', str_starts_with(DB_DSN, 'postgresql://') ? 'ILIKE' : 'LIKE');

// ── Contact ──────────────────────────────────────────────────────────────
define('CONTACT_EMAIL', 'powerplan786@gmail.com');

// ── Security limits ────────────────────────────────────────────────────────
define('MAX_AVATAR_MB', 8);
define('MAX_LOGIN_TRIES', 5);
define('LOCKOUT_SECONDS', 900);
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 days

// ── Categories / audience buckets ──────────────────────────────────────────
define('CATEGORIES', [
    'single_m' => ['label' => 'Singles — Male',      'icon' => '♂'],
    'single_f' => ['label' => 'Singles — Female',    'icon' => '♀'],
    'couple'   => ['label' => 'Couples',             'icon' => '⚧'],
    'mmf'      => ['label' => 'MMF (2 Men + 1 Woman)','icon' => '♂♂♀'],
    'ffm'      => ['label' => 'FFM (2 Women + 1 Man)','icon' => '♀♀♂'],
]);

// ── Allowed languages (code => [native name, RTL?]) ────────────────────────
define('LANGS', [
    'en' => ['English',   false], 'fr' => ['Français',  false],
    'es' => ['Español',   false], 'de' => ['Deutsch',   false],
    'it' => ['Italiano',  false], 'pt' => ['Português', false],
    'ru' => ['Русский',   false], 'hi' => ['हिन्दी',     false],
    'zh' => ['中文',        false], 'ja' => ['日本語',     false],
    'tr' => ['Türkçe',    false], 'ar' => ['العربية',   true ],
    'ur' => ['اردو',        true ], 'bn' => ['বাংলা',      false],
]);

// ── Allowed upload MIME types ──────────────────────────────────────────────
define('ALLOWED_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
]);

// HTTPS detection — flip to `true` manually on Hostinger if $_SERVER is unset.
define('IS_SSL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));

if (IS_SSL) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => true,
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}
session_start();
