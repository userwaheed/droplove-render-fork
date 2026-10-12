<?php
/**
 * dashboard.php — Member home: recent matches, likes received, message count.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_login();

$me   = current_user();
$pdo  = db();

$stats = [
    'active' => 0,
    'likes_in'  => 0,
    'likes_out' => 0,
    'unread'    => 0,
    'members'   => 0,
];

$s = $pdo->prepare('SELECT COUNT(*) FROM users WHERE is_active = 1 AND banned = 0 AND id <> ?');
$s->execute([$me['id']]);
$stats['members'] = (int)$s->fetchColumn();

foreach ([
    ['likes_in',  'SELECT COUNT(*) FROM likes WHERE target_id = ?'],
    ['likes_out', 'SELECT COUNT(*) FROM likes WHERE user_id = ?'],
    ['unread',    'SELECT COUNT(*) FROM messages WHERE to_id = ? AND is_read = 0'],
] as [$key, $sql]) {
    $s = $pdo->prepare($sql);
    $s->execute([$me['id']]);
    $stats[$key] = (int)$s->fetchColumn();
}

$stats['active'] = $stats['members'];

/* Recent profiles I like (mutual-ish) — profiles I've liked, plus fresh ones. */
$liked = $pdo->prepare('
    SELECT u.* FROM likes l
    JOIN users u ON u.id = l.target_id
    WHERE l.user_id = ? AND u.is_active = 1 AND u.banned = 0
    ORDER BY l.created_at DESC LIMIT 12');
$liked->execute([$me['id']]);
$recentLiked = $liked->fetchAll();

$discovery = $pdo->prepare('
    SELECT u.*,
           (SELECT COUNT(*) FROM likes l2 WHERE l2.user_id = u.id) AS likes_count
    FROM users u
    WHERE u.id <> ? AND u.is_active = 1 AND u.banned = 0
      AND u.category = ?
      AND u.id NOT IN (SELECT target_id FROM likes WHERE user_id = ?)
      AND u.id NOT IN (SELECT user_id FROM likes WHERE target_id = ?)
    ORDER BY u.created_at DESC LIMIT 9');
$discovery->execute([$me['id'], $me['category'], $me['id'], $me['id']]);
$forYou = $discovery->fetchAll();

/* Latest messages preview. */
$threads = $pdo->prepare('
    SELECT * FROM messages
    WHERE (from_id = ? AND to_id IN (SELECT user_id FROM likes WHERE target_id = ?))
       OR (to_id = ? AND from_id IN (SELECT target_id FROM likes WHERE user_id = ?))
    ORDER BY created_at DESC LIMIT 6');
$threads->execute([$me['id'], $me['id'], $me['id'], $me['id']]);
$recentMsgs = $threads->fetchAll();

require __DIR__ . '/views/dashboard.php';
