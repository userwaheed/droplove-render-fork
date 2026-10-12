<?php
/**
 * messages.php — Conversations and thread view.
 *
 * GET  /messages.php              — inbox (needs a mutual like)
 * GET  /messages.php?with=ID      — open thread, marks messages read
 * POST /messages.php?with=ID      — send a message
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_login();

$me   = current_user();
$pdo  = db();
$errors = [];

$withId = (int)($_GET['with'] ?? 0);

/** Two members can talk once they've liked each other (mutual like). */
function can_message(PDO $pdo, int $a, int $b): bool
{
    $s = $pdo->prepare(
        'SELECT COUNT(*) FROM likes WHERE (user_id = ? AND target_id = ?) OR (user_id = ? AND target_id = ?)');
    $s->execute([$a, $b, $b, $a]);
    return (int)$s->fetchColumn() === 2;
}

/* ── POST: send ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $withId) {
    csrf_verify();
    $body = trim($_POST['body'] ?? '');

    $s = $pdo->prepare('SELECT id FROM users WHERE id = ? AND banned = 0 AND is_active = 1 LIMIT 1');
    $s->execute([$withId]);
    $target = $s->fetch();

    if (!$target) {
        $errors['body'] = 'Cannot message this member.';
    } elseif ($withId === $me['id']) {
        $errors['body'] = 'You cannot message yourself.';
    } elseif (!can_message($pdo, $me['id'], $withId)) {
        $errors['body'] = 'You can only message members you have liked or who liked you. Visit their profile and like them first.';
    } elseif (mb_strlen($body) < 1) {
        $errors['body'] = 'Message cannot be empty.';
    } elseif (mb_strlen($body) > 2000) {
        $errors['body'] = 'Message must be 2000 characters or fewer.';
    } else {
        $pdo->prepare('INSERT INTO messages (from_id, to_id, body) VALUES (?, ?, ?)')
            ->execute([$me['id'], $withId, mb_substr($body, 0, 2000)]);
        redirect('/messages.php?with=' . $withId);
    }
}

/* ── GET: inbox ── */
$inbox = [];
if (!$withId) {
    $inbox = $pdo->prepare('
        SELECT m.*, u.id AS peer_id, u.username AS peer_username, u.first_name AS peer_name, u.avatar AS peer_avatar, u.category AS peer_category
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.from_id = ? THEN m.to_id ELSE m.from_id END
        WHERE (m.from_id = ? OR m.to_id = ?)
        ORDER BY m.created_at DESC LIMIT 100');
    $inbox->execute([$me['id'], $me['id'], $me['id']]);
    $inbox = $inbox->fetchAll();

    // Mark incoming as read.
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE to_id = ? AND is_read = 0')->execute([$me['id']]);

    // Group by peer.
    $grouped = [];
    foreach ($inbox as $m) {
        $grouped[$m['peer_id']]['peer_id']       = $m['peer_id'];
        $grouped[$m['peer_id']]['peer_username'] = $m['peer_username'];
        $grouped[$m['peer_id']]['peer_name']     = $m['peer_name'];
        $grouped[$m['peer_id']]['peer_avatar']   = $m['peer_avatar'];
        $grouped[$m['peer_id']]['peer_category'] = $m['peer_category'];
        $grouped[$m['peer_id']]['last']          = $m['body'];
        $grouped[$m['peer_id']]['time']          = $m['created_at'];
        if ($m['to_id'] === $me['id'] && (int)$m['is_read'] === 0) {
            $grouped[$m['peer_id']]['unread'] = 1;
        }
    }
    // Newest conversation first.
    uasort($grouped, fn($a, $b) => strcmp($b['time'] ?? '', $a['time'] ?? ''));
    $inbox = array_values($grouped);
}

/* ── GET: thread ── */
$peer = null;
$thread = [];
if ($withId) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ? AND banned = 0 LIMIT 1');
    $s->execute([$withId]);
    $peer = $s->fetch();

    if ($peer) {
        if ($withId === $me['id'] || !can_message($pdo, $me['id'], $withId)) {
            $peer = null;
        }
    }

    if ($peer) {
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE to_id = ? AND from_id = ? AND is_read = 0')
            ->execute([$me['id'], $withId]);

        $thread = $pdo->prepare('
            SELECT * FROM messages
            WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)
            ORDER BY created_at ASC LIMIT 300');
        $thread->execute([$me['id'], $withId, $withId, $me['id']]);
        $thread = $thread->fetchAll();
    }
}

require __DIR__ . '/views/messages.php';
