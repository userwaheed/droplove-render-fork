<?php
/**
 * discover.php — Browse active profiles with filters.
 *
 * Filters: category, gender, age range, city/country, sort.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_login();

$me  = current_user();
$pdo = db();

$cat   = isset($_GET['category']) && array_key_exists($_GET['category'], CATEGORIES) ? $_GET['category'] : 'all';
$gen   = in_array($_GET['gender'] ?? '', ['male', 'female', 'other'], true) ? $_GET['gender'] : 'all';
$sort  = in_array($_GET['sort'] ?? '', ['newest', 'online', 'likes', 'age'], true) ? ($_GET['sort'] ?? 'newest') : 'newest';
$min   = max(18, (int)($_GET['min_age'] ?? 18));
$max   = max($min, (int)($_GET['max_age'] ?? 99));
$loc   = trim($_GET['location'] ?? '');
$per   = 24;
$page  = max(1, (int)($_GET['page'] ?? 1));

$where  = ['u.id <> :me', 'u.is_active = 1', 'u.banned = 0'];
$bind   = [':me' => (int)$me['id']];

if ($cat !== 'all')       { $where[] = 'u.category = :cat';  $bind[':cat'] = $cat; }
if ($gen !== 'all')       { $where[] = 'u.gender = :gen';    $bind[':gen'] = $gen; }
    if ($loc !== '')          { $op = LIKE_OP; $where[] = "(u.city {$op} :loc ESCAPE '\\' OR u.country {$op} :loc ESCAPE '\\' OR u.zip {$op} :loc ESCAPE '\\')";                            $locEsc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $loc);
                            $bind[':loc'] = "%$locEsc%"; }

// Age filter via birth_date arithmetic (SQLite date functions).
$oldest   = date('Y-m-d', strtotime("-$max years"));
$youngest = date('Y-m-d', strtotime("-$min years"));
$where[] = 'u.birth_date BETWEEN :dob_lo AND :dob_hi';
$bind[':dob_lo'] = $oldest;
$bind[':dob_hi'] = $youngest;

$orderBy = [
    'newest' => 'u.created_at DESC',
    'online' => '(u.online_last IS NOT NULL) DESC, u.online_last DESC',
    'age'    => 'u.birth_date DESC',
    'likes'  => '(SELECT COUNT(*) FROM likes l WHERE l.user_id = u.id) DESC',
][$sort];

$whereSql = implode(' AND ', $where);

$s = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSql");
$s->execute($bind);
$pg = paginate((int)$s->fetchColumn(), $per, $page);

$bind[':per'] = $pg['per'];
$bind[':off'] = $pg['offset'];

$s = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM likes l WHERE l.user_id = u.id)          AS likes_count,
           (SELECT COUNT(*) FROM likes l WHERE l.target_id = u.id)        AS likes_in,
           (SELECT 1 FROM likes l WHERE l.user_id = :me AND l.target_id = u.id) AS i_liked
    FROM users u
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT :per OFFSET :off");
$s->execute($bind);
$profiles = $s->fetchAll();

require __DIR__ . '/views/discover.php';
