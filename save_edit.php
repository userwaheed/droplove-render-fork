<?php
/**
 * save_edit.php - Save an edited (filtered) image back to the database.
 *
 * The avatar/cover columns store a FILENAME that points to a row in the
 * `uploads` table (DB storage) or a file in UPLOAD_DIR (filesystem).
 * To persist a filter, we update the underlying uploads row in place
 * (same filename) so all existing references keep working.
 *
 * Accepts POST: action (avatar|cover|photo), photo_id (for gallery),
 *               image (base64 data URL), csrf.
 * Returns JSON: { ok: bool, error?: string }
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF check (inline so we can return JSON instead of dying)
$csrfToken = $_POST['csrf'] ?? '';
if (!is_string($csrfToken) || !hash_equals(csrf_token(), $csrfToken)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$me = current_user();
$action = $_POST['action'] ?? '';
$photoId = (int)($_POST['photo_id'] ?? 0);
$dataUrl = $_POST['image'] ?? '';

// Decode base64 data URL
if (!preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUrl, $m)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid image data']);
    exit;
}

$mime = 'image/' . strtolower($m[1]);
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowedMime, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unsupported image type']);
    exit;
}

$binary = base64_decode($m[2]);
if ($binary === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Failed to decode image']);
    exit;
}

// Size limit (5 MB)
$maxBytes = 5 * 1024 * 1024;
if (strlen($binary) > $maxBytes) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Image too large']);
    exit;
}

$pdo = db();

/**
 * Overwrite an existing upload by filename.
 * DB storage -> update the uploads row. Filesystem -> overwrite the file.
 */
function overwrite_upload(PDO $pdo, string $filename, string $binary, string $mime): void
{
    $fname = basename($filename);
    if (!$fname) {
        throw new RuntimeException('Empty filename');
    }
    if (DB_STORAGE === 'db') {
        $stmt = $pdo->prepare('UPDATE uploads SET data = ?, mime = ?, size = ? WHERE filename = ?');
        $stmt->execute([$binary, $mime, strlen($binary), $fname]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Upload row not found');
        }
    } else {
        $path = UPLOAD_DIR . $fname;
        if (!is_file($path)) {
            throw new RuntimeException('Upload file not found');
        }
        if (file_put_contents($path, $binary) === false) {
            throw new RuntimeException('Failed to write file');
        }
    }
}

try {
    switch ($action) {
        case 'avatar':
            if (empty($me['avatar'])) {
                throw new RuntimeException('No avatar to update');
            }
            overwrite_upload($pdo, $me['avatar'], $binary, $mime);
            break;

        case 'cover':
            if (empty($me['cover'])) {
                throw new RuntimeException('No cover to update');
            }
            overwrite_upload($pdo, $me['cover'], $binary, $mime);
            break;

        case 'photo':
            if ($photoId <= 0) {
                throw new RuntimeException('Missing photo_id');
            }
            $s = $pdo->prepare('SELECT photo FROM profile_photos WHERE id = ? AND user_id = ?');
            $s->execute([$photoId, (int)$me['id']]);
            $row = $s->fetch();
            if (!$row) {
                throw new RuntimeException('Photo not found');
            }
            overwrite_upload($pdo, $row['photo'], $binary, $mime);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid action']);
            exit;
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('save_edit error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Save failed']);
}
