<?php
/**
 * admin.php — Moderation console for DrofLove.com.
 *
 * Access: membership flag users.is_admin = 1 (server-side enforced via
 * require_admin(), which also requires require_login()). Non-admins get
 * redirected to /login.php — admin data is NEVER rendered for them.
 *
 * Actions (all POST + CSRF-protected, all idempotent and id-scoped):
 *   - suspend/block  : bans a user (banned=1). Banned users cannot log in
 *                      or appear in discover/profile.
 *   - unsuspend/unblock : un-bans a user (banned=0).
 *   - deactivate     : hides a profile from discover (is_active=0).
 *   - activate       : brings a profile back into discover (is_active=1).
 *   - toggle_verified: flips verified badge.
 *   - toggle_admin   : flips is_admin (with last-admin guard).
 *   - delete         : hard-deletes a user; cascades photos/likes/messages/
 *                      reports via FK ON DELETE CASCADE, plus removes
 *                      the uploaded files (avatar, cover, gallery photos).
 *   - reset          : sets a fresh random password (shown once to the admin).
 *
 * DECISION: suspend vs. block
 *   The schema already has a `banned` column (INTEGER, default 0) that is
 *   respected by login.php, profile.php, discover.php and current_user().
 *   Banned users cannot log in or appear anywhere public. We reuse `banned`
 *   for both "suspend" and "block" — the existing column IS the blocked state.
 *   Adding a new `status` ENUM column would require schema changes across
 *   MySQL and SQLite and would duplicate what `banned` + `is_active` already
 *   express. The UI labels them distinctly. No migration needed beyond
 *   the `verified` column added in db.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

/* ── Bootstrap FIRST ──
 * ensure_admin_account() must run BEFORE require_admin(), otherwise a database
 * with zero admins can never be reached: require_admin() would redirect to
 * /login.php and the bootstrap would never execute. Verified lockout on a
 * clean deploy. The check is a single indexed COUNT, so it is cheap on the
 * hot path. */
ensure_admin_account(db());

require_admin();

$me  = current_user();
$pdo = db();

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Handle report resolution separately (different POST shape).
    if (isset($_POST['resolve_report'])) {
        $rid = (int)$_POST['resolve_report'];
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$rid]);
        flash('success', 'Report marked resolved.');
        redirect('/admin.php');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        flash('error', 'Invalid user ID.');
    } else {
        $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $s->execute([$id]);
        $target = $s->fetch();

        if (!$target) {
            flash('error', 'User not found.');
        } elseif ((int)$target['id'] === (int)$me['id']) {
            flash('error', 'You cannot modify your own account here.');
        } else {
            $isTargetAdmin = (int)$target['is_admin'] === 1;
            $isTargetVerified = (int)($target['verified'] ?? 0) === 1;

            switch ($action) {
                case 'suspend':
                case 'block':
                    $pdo->prepare("UPDATE users SET banned = 1, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$id]);
                    flash('success', $target['username'] . ' has been suspended/blocked.');
                    break;

                case 'unsuspend':
                case 'unblock':
                    $pdo->prepare("UPDATE users SET banned = 0, is_active = 1, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$id]);
                    flash('success', $target['username'] . ' has been restored.');
                    break;

                case 'deactivate':
                    $pdo->prepare("UPDATE users SET is_active = 0, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$id]);
                    flash('success', $target['username'] . ' is now hidden from discover.');
                    break;

                case 'activate':
                    $pdo->prepare("UPDATE users SET is_active = 1, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$id]);
                    flash('success', $target['username'] . ' is now visible in discover.');
                    break;

                case 'toggle_verified':
                    $newVal = $isTargetVerified ? 0 : 1;
                    $pdo->prepare("UPDATE users SET verified = ?, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$newVal, $id]);
                    flash('success', ($newVal ? $target['username'] . ' is now verified.'
                                              : $target['username'] . ' is no longer verified.'));
                    break;

                case 'toggle_admin':
                    if ($isTargetAdmin) {
                        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
                        if ($adminCount <= 1) {
                            flash('error', 'Cannot remove the last admin.');
                            break;
                        }
                    }
                    $newVal = $isTargetAdmin ? 0 : 1;
                    $pdo->prepare("UPDATE users SET is_admin = ?, updated_at = datetime('now') WHERE id = ?")
                        ->execute([$newVal, $id]);
                    flash('success', ($newVal ? $target['username'] . ' is now an admin.'
                                              : $target['username'] . ' is no longer an admin.'));
                    break;

                case 'reset':
                    $tmp = bin2hex(random_bytes(8));
                    $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = datetime('now') WHERE id = ?")
                        ->execute([password_hash($tmp, PASSWORD_DEFAULT), $id]);
                    $_SESSION['reset_pw'] = ['username' => $target['username'], 'password' => $tmp];
                    redirect('/admin.php?id=' . $id);
                    break;

                case 'delete':
                    if ($isTargetAdmin) {
                        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
                        if ($adminCount <= 1) {
                            flash('error', 'Cannot delete the last admin.');
                            break;
                        }
                    }

                    $ppStmt = $pdo->prepare('SELECT photo FROM profile_photos WHERE user_id = ?');
                    $ppStmt->execute([$id]);
                    while ($p = $ppStmt->fetch()) {
                        delete_upload($p['photo']);
                    }
                    delete_upload($target['avatar']);
                    delete_upload($target['cover']);

                    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                    flash('success', 'Deleted ' . $target['username'] . '.');
                    break;

                default:
                    flash('error', 'Unknown action.');
                    break;
            }
        }
    }
}

// PRG pattern: after any successful POST action, redirect to avoid double-submit.
// (The reset case already redirects above; this catches all other actions.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect('/admin.php');
}

/* ── GET: search / filter / pagination ── */
$search    = trim($_GET['search'] ?? '');
$status    = in_array($_GET['status'] ?? '', ['', 'all', 'active', 'suspended', 'inactive'], true)
           ? (($_GET['status'] ?? '') === '' ? 'all' : $_GET['status']) : 'all';
$catFilter = $_GET['cat'] ?? 'all';
if (!array_key_exists($catFilter, CATEGORIES) && $catFilter !== 'all') $catFilter = 'all';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

/* ── Detail view: /admin.php?id=N ── */
$detailId = (int)($_GET['id'] ?? 0);

$resetPw = isset($_SESSION['reset_pw']) ? $_SESSION['reset_pw'] : null;
unset($_SESSION['reset_pw']);

if ($detailId > 0) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $s->execute([$detailId]);
    $du = $s->fetch();

    if (!$du) {
        flash('error', 'User not found.');
        redirect('/admin.php');
    }

    $likesInStmt  = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE target_id = ?');
    $likesInStmt->execute([$du['id']]);
    $likesIn = (int)$likesInStmt->fetchColumn();

    $likesOutStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE user_id = ?');
    $likesOutStmt->execute([$du['id']]);
    $likesOut = (int)$likesOutStmt->fetchColumn();

    $msgStmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE from_id = ? OR to_id = ?');
    $msgStmt->execute([$du['id'], $du['id']]);
    $msgs = (int)$msgStmt->fetchColumn();

    $photoStmt = $pdo->prepare('SELECT * FROM profile_photos WHERE user_id = ? ORDER BY position ASC, id ASC');
    $photoStmt->execute([$du['id']]);
    $photos = $photoStmt->fetchAll();

    $uploadFiles = [];
    if (!empty($du['avatar']))  $uploadFiles[] = $du['avatar'];
    if (!empty($du['cover']))   $uploadFiles[] = $du['cover'];
    foreach ($photos as $p) {
        if (!empty($p['photo'])) $uploadFiles[] = $p['photo'];
    }

    require __DIR__ . '/views/admin_user.php';
    exit;
}

/* ── Summary stats ── */
$totalUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeUsers   = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1 AND banned = 0')->fetchColumn();
$suspended     = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE banned = 1')->fetchColumn();
$admins        = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
$totalLikes    = (int)$pdo->query('SELECT COUNT(*) FROM likes')->fetchColumn();
$totalMessages = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$newestSignups = $pdo->query('SELECT id, username, email, avatar, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

/* ── Build user list query with filters ── */
$where  = [];
$params = [];

if ($search !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
    $op = LIKE_OP;
    $where[] = "(username {$op} ? ESCAPE '\\' OR email {$op} ? ESCAPE '\\' OR first_name {$op} ? ESCAPE '\\' OR last_name {$op} ? ESCAPE '\\')";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if ($status === 'active') {
    $where[] = 'is_active = 1 AND banned = 0';
} elseif ($status === 'suspended') {
    $where[] = 'banned = 1';
} elseif ($status === 'inactive') {
    $where[] = 'is_active = 0 AND banned = 0';
}

if ($catFilter !== 'all') {
    $where[] = 'category = ?';
    $params[] = $catFilter;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pg = paginate($totalRows, $perPage, $page);

$listStmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM likes WHERE target_id = u.id) AS likes_in,
           (SELECT COUNT(*) FROM messages WHERE from_id = u.id OR to_id = u.id) AS msgs
    FROM users u
    $whereSql
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?");
$listParams = array_merge($params, [$pg['per'], (int)$pg['offset']]);
$listStmt->execute($listParams);
$users = $listStmt->fetchAll();

$reports = $pdo->query("
    SELECT r.*,
           (SELECT username FROM users WHERE id = r.reporter) AS reporter_name,
           (SELECT username FROM users WHERE id = r.target_id) AS target_name
    FROM reports r
    ORDER BY r.status = 'open' DESC, r.created_at DESC
    LIMIT 100
")->fetchAll();

$reportCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn();

$title = 'Admin';
require __DIR__ . '/views/admin.php';

/**
 * Ensure a real admin account exists for the site owner.
 * Runs idempotently: only creates the admin user if no admin exists.
 */
function ensure_admin_account(PDO $pdo): void
{
    $adminEmail = 'powerplan786@gmail.com';
    // Must satisfy valid_password() (8+ chars, upper, lower, digit) so the
    // owner can change it from /edit_profile.php afterwards. 'password' fails
    // that check, so the seeded account would have had no way to log in after
    // the first use and no way to change its own password.
    $defaultHash = password_hash('Admin1234', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE is_admin = 1');
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$adminEmail]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare('UPDATE users SET is_admin = 1, banned = 0, is_active = 1 WHERE id = ?')
            ->execute([(int)$existing['id']]);
        return;
    }

    $username = 'admin';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $username = 'drof_admin';
    }

    $pdo->prepare(
        "INSERT INTO users (email, password_hash, username, first_name, last_name,
                            gender, category, orientation, birth_date, bio, is_active, is_admin, verified, created_at)
         VALUES (?, ?, ?, ?, ?, 'other', 'single_m', 'flexible', '1990-01-01', ?, 1, 1, 1, datetime('now'))"
    )->execute([
        $adminEmail,
        $defaultHash,
        $username,
        'Admin',
        'User',
        'Site administrator.',
    ]);
}
