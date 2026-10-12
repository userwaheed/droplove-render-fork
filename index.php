<?php
/**
 * index.php — Landing page.
 *
 * Sections: hero, categories, how it works, gallery, testimonials,
 * safety, languages, CTA, footer.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$pdo = db();

$stats = [];
foreach ([
    'members'  => 'SELECT COUNT(*) FROM users WHERE is_active = 1 AND banned = 0',
    'likes'    => 'SELECT COUNT(*) FROM likes',
    'messages' => 'SELECT COUNT(*) FROM messages',
] as $k => $sql) {
    $stats[$k] = (int)$pdo->query($sql)->fetchColumn();
}
// Real numbers only — fake floors undermine trust the first time a new
// member signs up and finds the actual population (see website review H1).
// The empty-state story is handled by copy, not by invented counters.
$countries = (int)$pdo->query(
    'SELECT COUNT(DISTINCT country) FROM users WHERE country IS NOT NULL AND country <> \'\''
)->fetchColumn();

require __DIR__ . '/views/index.php';
