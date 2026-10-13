<?php
/**
 * helpers.php — CSRF, escaping, flash messages, auth guards, validation,
 * hashing, uploads, pagination and i18n helpers.
 *
 * SQLite quoting note: every SQL string literal here uses single quotes
 * (datetime('now')) because SQLite reads "text" as an identifier.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/translations.php';

/* ────────────────────────────────── i18n ────────────────────────────────── */

function current_lang(): string
{
    $code = $_GET['lang'] ?? ($_SESSION['lang'] ?? APP_DEFAULT_LANG);
    if (!array_key_exists($code, LANGS)) {
        $code = APP_DEFAULT_LANG;
    }
    $_SESSION['lang'] = $code;
    return $code;
}

function lang_name(string $code): string
{
    return LANGS[$code][0] ?? $code;
}

function is_rtl(): bool
{
    return LANGS[current_lang()][1] ?? false;
}

/* ────────────────────────────────── Output ──────────────────────────────── */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function compact_number(float|int|string $n): string
{
    $n = (float)$n;
    return $n >= 1000000 ? (round($n / 1000000, 1) . 'M')
        : ($n >= 1000    ? (round($n / 1000, 1) . 'K')
        : (string)round($n));
}

/* ────────────────────────────── Flash messages ──────────────────────────── */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function get_flash(string $type): ?string
{
    $m = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $m;
}

/* ────────────────────────────────── CSRF ────────────────────────────────── */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        http_response_code(405);
        header('Allow: POST, PUT, PATCH, DELETE');
        die('Method not allowed. State-changing requests must use POST.');
    }
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        die('Invalid form token. Go back, refresh the page and try again.');
    }
}

/* ────────────────────────────────── Auth ────────────────────────────────── */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();

    if (!$u || (int)$u['banned'] === 1) {
        logout();
        return null;
    }

    /* The cached row is a display memo only. Every mutation path that touches
     * user data already clears it (edit_profile.php unsets it on each save,
     * login_user() and logout() unset it too), so it stays fresh outside of
     * admin actions — which is the one case that does NOT notify the user.
     *
     * banned is therefore re-read from the database on every request: there is
     * no sessions table, so this is the only way a suspend/delete takes effect
     * immediately. Without it, a banned user keeps a working session until
     * re-login or the 7-day cookie expiry. One extra indexed lookup, which is
     * negligible against the 10-20 queries a page already runs.
     *
     * The cached values are what the rest of the code reads for everything
     * else, so this does not regress performance. */
    if (isset($_SESSION['user'])) {
        $_SESSION['user']['banned'] = $u['banned'];
        $u = $_SESSION['user'];
    } else {
        $_SESSION['user'] = $u;
    }

    if (is_postgres()) {
        $stmt = db()->prepare("UPDATE users SET online_last = datetime('now') WHERE id = ?");
    } else {
        $stmt = db()->prepare("UPDATE users SET online_last = datetime('now') WHERE id = ?");
    }
    $stmt->execute([$u['id']]);
    return $u;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    unset($_SESSION['user']);
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!current_user()) {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if ((int)current_user()['is_admin'] !== 1) {
        http_response_code(403);
        exit('Forbidden.');
    }
}

/* ──────────────────────────── Login rate limiting ───────────────────────── */

function ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_locked_out(): bool
{
    if (is_postgres()) {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND success = 0 AND created_at > NOW() - (?::TEXT || ' seconds')::interval"
        );
        $stmt->execute([ip(), (string)LOCKOUT_SECONDS]);
    } else {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND success = 0 AND created_at > datetime('now', ?)"
        );
        $stmt->execute([ip(), '-' . LOCKOUT_SECONDS . ' seconds']);
    }
    return (int)$stmt->fetchColumn() >= MAX_LOGIN_TRIES;
}

function record_attempt(string $identifier, bool $success): void
{
    $pdo = db();
    // Periodically purge old login_attempts to prevent unbounded table growth.
    if (is_postgres()) {
        $pdo->exec("DELETE FROM login_attempts WHERE created_at < datetime('now', '-90 days')");
    } else {
        $pdo->exec("DELETE FROM login_attempts WHERE created_at < datetime('now','-90 days')");
    }
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (ip, identifier, success) VALUES (?, ?, ?)'
    );
    $stmt->execute([ip(), $identifier, $success ? 1 : 0]);
}

function clear_attempts(string $identifier): void
{
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE ip = ? AND identifier = ?');
    $stmt->execute([ip(), $identifier]);
}

/* ───────────────────────────────── Validation ───────────────────────────── */

function field_has_error(array $errors, string $key): string
{
    return !empty($errors[$key]) ? ' is-invalid' : '';
}

function field_error(array $errors, string $key): string
{
    return $errors[$key] ?? '';
}

function valid_email(string $email): bool
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/** Validate password: 8+ chars, upper, lower, digit. Used by register + change-password. */
function valid_password(string $password): bool
{
    return mb_strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

function user_age(?string $birth_date): ?int
{
    if (!$birth_date) {
        return null;
    }
    try {
        $d = new DateTime($birth_date);
    } catch (Exception) {
        return null;
    }
    return (int)$d->diff(new DateTime('today'))->y;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $i = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $i .= mb_substr($p, 0, 1, 'UTF-8');
    }
    return mb_strtoupper($i ?: '?', 'UTF-8');
}

/** Public profile URL — the app is served from a subdirectory too. */
function url(string $path): string
{
    return $path;
}

function avatar_url(?string $avatar, ?string $name = null): string
{
    // Route through uploads.php (never serve /uploads/ directly — see .htaccess).
    // Relative paths so the app works both at domain root and in a subdirectory.
    if ($avatar) {
        return 'uploads.php?file=' . urlencode(basename($avatar));
    }
    return 'assets/img/avatar-placeholder.svg';
}

/* ────────────────────────────────── Uploads ─────────────────────────────── */

/**
 * Detect potential nudity in an image using a skin-color ratio heuristic.
 *
 * Returns true if the image appears to contain nudity (too much skin tone
 * relative to the overall image). This is a first-line filter — no ML model
 * needed, works with stock GD.
 */
function detect_nudity(string $file_path, string $mime): bool
{
    // Skip GIFs (animate) — check first frame only
    $img = false;
    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($file_path); break;
        case 'image/png':  $img = @imagecreatefrompng($file_path);  break;
        case 'image/webp': $img = @imagecreatefromwebp($file_path); break;
        case 'image/gif':  $img = @imagecreatefromgif($file_path);  break;
    }
    if (!$img) return false;

    $w = imagesx($img);
    $h = imagesy($img);
    // Sample every Nth pixel to keep it fast on large images
    $step = max(1, (int)($w * $h / 50000));
    $skin = 0;
    $total = 0;
    for ($y = 0; $y < $h; $y += $step) {
        for ($x = 0; $x < $w; $x += $step) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $total++;
            // Skin tone heuristic: R > G > B, R in [95,255], G in [40,255], B in [20,230]
            if ($r > 95 && $r > $g && $r > $b &&
                $g > 40 && $b > 20 && $b < 230 &&
                ($r - $b) > 15) {
                $skin++;
            }
        }
    }
    imagedestroy($img);

    if ($total === 0) return false;
    $ratio = $skin / $total;
    // If more than 35% of sampled pixels are skin-toned, flag it
    return $ratio > 0.35;
}

function handle_image_upload(array $file, string $prefix = 'avatar'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . (int)$file['error'] . ').');
    }
    $size = (int)$file['size'];
    if ($size <= 0 || $size > MAX_AVATAR_MB * 1024 * 1024) {
        throw new RuntimeException('Image must be smaller than ' . MAX_AVATAR_MB . ' MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_TYPES[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP and GIF images are allowed.');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || ($info['mime'] ?? '') !== $mime) {
        throw new RuntimeException('File content does not match its declared type.');
    }
    if (detect_nudity($file['tmp_name'], $mime)) {
        throw new RuntimeException('Nudity or inappropriate content detected. Please upload a different photo.');
    }
    $ext = ALLOWED_TYPES[$mime];
    $src = null;
    switch ($ext) {
        case 'png':  $src = @imagecreatefrompng($file['tmp_name']);  break;
        case 'gif':  $src = @imagecreatefromgif($file['tmp_name']);  break;
        case 'jpg':  $src = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'webp': $src = @imagecreatefromwebp($file['tmp_name']); break;
    }
    if (!$src) {
        throw new RuntimeException('Could not process the image. Check that GD is enabled.');
    }
    $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
    switch ($ext) {
        case 'png':  imagepng($src, $tmpFile, 7);  break;
        case 'gif':  imagegif($src, $tmpFile);      break;
        case 'jpg':  imagejpeg($src, $tmpFile, 85); break;
        case 'webp': imagewebp($src, $tmpFile, 85); break;
    }
    imagedestroy($src);
    if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
        @unlink($tmpFile);
        throw new RuntimeException('Could not process the image. Check that GD is enabled.');
    }
    $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $data = file_get_contents($tmpFile);
    $encodedSize = strlen($data);
    @unlink($tmpFile);
    if ($encodedSize > MAX_AVATAR_MB * 1024 * 1024) {
        throw new RuntimeException('Image too large after processing.');
    }
    if (DB_STORAGE === 'db') {
        $pdo = db();
        if (is_postgres()) {
            $stmt = $pdo->prepare('INSERT INTO uploads (filename, mime, data, size) VALUES (?, ?, ?, ?) ON CONFLICT (filename) DO NOTHING');
        } else {
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO uploads (filename, mime, data, size) VALUES (?, ?, ?, ?)');
        }
        $stmt->execute([$name, $mime, $data, $encodedSize]);
    } else {
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true)) {
            throw new RuntimeException('Uploads folder is not writable.');
        }
        file_put_contents(UPLOAD_DIR . $name, $data);
    }
    return $name;
}

function delete_upload(?string $filename): void
{
    if (!$filename) return;
    $basename = basename($filename);
    if (DB_STORAGE === 'db') {
        $pdo = db();
        $pdo->prepare('DELETE FROM uploads WHERE filename = ?')->execute([$basename]);
        return;
    }
    $dirs = [UPLOAD_DIR, BASE_PATH . '/uploads/'];
    foreach ($dirs as $dir) {
        $p = $dir . $basename;
        if (is_file($p) && str_starts_with($p, $dir)) {
            @unlink($p);
        }
    }
}

/**
 * Read an existing uploaded image's raw bytes from storage (DB or file).
 * Returns null if the file cannot be found.
 */
function load_upload_data(string $filename): ?string
{
    $basename = basename($filename);
    if (DB_STORAGE === 'db') {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT data FROM uploads WHERE filename = ?');
        $stmt->execute([$basename]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : null;
    }
    $dirs = [UPLOAD_DIR, BASE_PATH . '/uploads/'];
    foreach ($dirs as $dir) {
        $p = $dir . $basename;
        if (is_file($p) && str_starts_with($p, $dir)) {
            return file_get_contents($p);
        }
    }
    return null;
}

/**
 * Get the MIME type of an existing uploaded image from storage.
 * Falls back to guessing from the filename extension.
 */
function load_upload_mime(string $filename): string
{
    $basename = basename($filename);
    if (DB_STORAGE === 'db') {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT mime FROM uploads WHERE filename = ?');
        $stmt->execute([$basename]);
        $row = $stmt->fetch();
        if ($row && !empty($row['mime'])) return $row['mime'];
    }
    $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    return [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
    ][$ext] ?? 'image/jpeg';
}

/**
 * Apply GD filters to an existing uploaded image and store the result as a new upload.
 * Returns the new filename, or null on failure.
 */
function apply_image_filters(string $filename, array $filters): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $data = load_upload_data($filename);
    if ($data === null || $data === false) {
        return null;
    }

    $tmpSrc = tempnam(sys_get_temp_dir(), 'src_');
    file_put_contents($tmpSrc, $data);

    $mime = load_upload_mime($filename);
    $src = null;
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($tmpSrc); break;
        case 'image/png':  $src = @imagecreatefrompng($tmpSrc);  break;
        case 'image/gif':  $src = @imagecreatefromgif($tmpSrc);  break;
        case 'image/webp': $src = @imagecreatefromwebp($tmpSrc); break;
    }
    if (!$src) {
        @unlink($tmpSrc);
        return null;
    }

    $brightness = (int)($filters['brightness'] ?? 100);
    $contrast   = (int)($filters['contrast'] ?? 100);
    $saturation = (int)($filters['saturation'] ?? 100);
    $blur       = (float)($filters['blur'] ?? 0);
    $grayscale  = (int)($filters['grayscale'] ?? 0);
    $hue        = (int)($filters['hue'] ?? 0);
    $rotate     = (int)($filters['rotate'] ?? 0);

    // Filters that operate on a GD image resource
    if ($brightness !== 100) {
        imagefilter($src, IMG_FILTER_BRIGHTNESS, ($brightness - 100) * 2.55);
    }
    if ($contrast !== 100) {
        imagefilter($src, IMG_FILTER_CONTRAST, ($contrast - 100) * 2.55);
    }
    if ($saturation !== 100) {
        imagefilter($src, IMG_FILTER_SATURATION, ($saturation - 100) * 2.55);
    }
    if ($grayscale > 0) {
        imagefilter($src, IMG_FILTER_GRAYSCALE, $grayscale);
    }
    if ($blur > 0) {
        imagefilter($src, IMG_FILTER_SMOOTH, (int)($blur * 10));
    }
    if ($hue !== 0) {
        imagefilter($src, IMG_FILTER_HUE, $hue);
    }

    // Rotation (90-degree steps)
    if ($rotate !== 0) {
        $rotated = imagerotate($src, $rotate, 0);
        if ($rotated !== false) {
            imagedestroy($src);
            $src = $rotated;
        }
    }

    // Save the filtered image
    $tmpOut = tempnam(sys_get_temp_dir(), 'out_');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($mime) {
        case 'image/png':  $ok = imagepng($src, $tmpOut, 7);  break;
        case 'image/gif':  $ok = imagegif($src, $tmpOut);      break;
        case 'image/webp': $ok = imagewebp($src, $tmpOut, 85); break;
        default:           $ok = imagejpeg($src, $tmpOut, 85); $mime = 'image/jpeg'; $ext = 'jpg'; break;
    }

    imagedestroy($src);
    @unlink($tmpSrc);

    if (!$ok || !file_exists($tmpOut) || filesize($tmpOut) === 0) {
        @unlink($tmpOut);
        return null;
    }

    $newName = 'edited_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $newData = file_get_contents($tmpOut);
    $newSize = strlen($newData);
    @unlink($tmpOut);

    if ($newSize > MAX_AVATAR_MB * 1024 * 1024) {
        return null;
    }

    if (DB_STORAGE === 'db') {
        $pdo = db();
        if (is_postgres()) {
            $stmt = $pdo->prepare('INSERT INTO uploads (filename, mime, data, size) VALUES (?, ?, ?, ?) ON CONFLICT (filename) DO NOTHING');
        } else {
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO uploads (filename, mime, data, size) VALUES (?, ?, ?, ?)');
        }
        $stmt->execute([$newName, $mime, $newData, $newSize]);
    } else {
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true)) {
            return null;
        }
        file_put_contents(UPLOAD_DIR . $newName, $newData);
    }

    return $newName;
}

/* ──────────────────────────────── Redirect ──────────────────────────────── */

/** Relative-safe location: preserves the current directory level. */
function redirect(string $url): never
{
    header('Location: ' . url($url));
    exit;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ──────────────────────────────── Pagination ────────────────────────────── */

function paginate(int $total, int $per = 24, int $page = 1): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page  = max(1, min($page, $pages));
    return ['total' => $total, 'per' => $per, 'page' => $page,
            'pages' => $pages, 'offset' => ($page - 1) * $per];
}

/** Build a query string from current GET params, optionally overriding some. */
function qs(array $extra = []): string
{
    $q = $_GET;
    unset($q['page']);
    $q = array_merge($q, $extra);
    return '?' . http_build_query($q);
}
