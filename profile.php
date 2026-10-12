<?php
/**
 * profile.php — Public profile page: photos, bio, likes, report, message.
 *
 * GET  /profile.php?id=123   — view
 * POST /profile.php?id=123   — like, report, or activate
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$id = (int)($_GET['id'] ?? 0);

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND banned = 0 LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    require __DIR__ . '/views/404.php';
    exit;
}

$me = current_user();

// Inactive users (is_active=0) are only visible to themselves or admins.
// But allow POST actions (like activate) to proceed for the profile owner.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ((int)$user['is_active'] === 0) {
        if (!$me || ((int)$me['id'] !== (int)$user['id'] && (int)($me['is_admin'] ?? 0) !== 1)) {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
            exit;
        }
    }
}
$photos = $pdo->prepare('SELECT * FROM profile_photos WHERE user_id = ? ORDER BY is_cover DESC, position ASC LIMIT 20');
$photos->execute([$user['id']]);
$photos = $photos->fetchAll();

$s = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE target_id = ?');
$s->execute([$user['id']]);
$user['likes_in'] = (int)$s->fetchColumn();
$likedCount = $user['likes_in'];

$iLiked = false;
if ($me) {
    $s = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = ? AND target_id = ?');
    $s->execute([$me['id'], $user['id']]);
    $iLiked = (bool)$s->fetchColumn();
}

// Non-members can only see basic details unless the owner chose "everyone".
$canSeeBio = ($user['visibility'] === 'everyone') || ($me && $me['id'] === $user['id']);

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me) {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'like') {
        if ($me['id'] === $user['id']) {
            flash('error', 'You cannot like your own profile.');
        } else {
        if (is_postgres()) {
            $s = $pdo->prepare('INSERT INTO likes (user_id, target_id) VALUES (?, ?) ON CONFLICT (user_id, target_id) DO NOTHING');
        } else {
            $s = $pdo->prepare('INSERT OR IGNORE INTO likes (user_id, target_id) VALUES (?, ?)');
        }
            $s->execute([$me['id'], $user['id']]);
            flash('success', 'Added to your likes!');
        }
    } elseif ($action === 'unlike') {
        $s = $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND target_id = ?');
        $s->execute([$me['id'], $user['id']]);
        flash('info', 'Removed from your likes.');
    } elseif ($action === 'report') {
        $reason = trim($_POST['reason'] ?? '') ?: 'Spam or inappropriate content';
        if ($me['id'] === $user['id']) {
            flash('error', 'You cannot report your own profile.');
        } else {
            $s = $pdo->prepare('INSERT INTO reports (reporter, target_id, reason) VALUES (?, ?, ?)');
            $s->execute([$me['id'], $user['id'], mb_substr($reason, 0, 500)]);
            flash('success', 'Thank you. Our moderators will review this report.');
        }
    } elseif ($action === 'activate') {
        if ($me['id'] === $user['id']) {
            $pdo->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?")
                ->execute([$me['id']]);
            flash('success', 'Your profile is now live in discover.');
        }
    } elseif ($action === 'deactivate') {
        $pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$me['id']]);
        flash('info', 'Your profile is hidden from discover. You can reactivate anytime.');
    }
    redirect('/profile.php?id=' . $id);
}

require __DIR__ . '/views/profile.php';
