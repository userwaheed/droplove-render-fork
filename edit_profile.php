<?php
/**
 * edit_profile.php — Edit account, settings, photos and bio.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_login();

$me  = current_user();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $username   = trim($_POST['username'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $gender     = $_POST['gender'] ?? 'other';
        $category   = $_POST['category'] ?? $me['category'];
        $orientation = $_POST['orientation'] ?? $me['orientation'];
        $birth_date = $_POST['birth_date'] ?? $me['birth_date'];
        $city       = trim($_POST['city'] ?? '');
        $country    = trim($_POST['country'] ?? '');
        $zip        = trim($_POST['zip'] ?? '');
        $bio        = trim($_POST['bio'] ?? '');
        $interests  = trim($_POST['interests'] ?? '');
        $looking    = trim($_POST['looking_for'] ?? '');
        $body_type  = trim($_POST['body_type'] ?? '');
        $height_cm  = (int)($_POST['height_cm'] ?? 0);
        $visibility = in_array($_POST['visibility'] ?? '', ['members', 'everyone'], true) ? $_POST['visibility'] : 'members';
        $password   = $_POST['new_password'] ?? '';

        if (mb_strlen($username) < 3 || mb_strlen($username) > 30)
            $errors['username'] = 'Username must be 3-30 characters.';
        elseif (!preg_match('/^[A-Za-z0-9_]+$/', $username))
            $errors['username'] = 'Username may only use letters, numbers and underscores.';
        elseif ($username !== $me['username']) {
            $s = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $s->execute([$username, $me['id']]);
            if ($s->fetch()) $errors['username'] = 'That username is taken.';
        }

        if (!$first_name) $errors['first_name'] = 'First name is required.';

        if (!in_array($gender, ['male', 'female', 'other'], true))
            $errors['gender'] = 'Invalid gender.';

        if (!array_key_exists($category, CATEGORIES))
            $errors['category'] = 'Please choose a category.';

        $age = user_age($birth_date);
        if ($age === null || $age < 18)
            $errors['birth_date'] = 'You must be 18 or older.';

        if ($password !== '') {
            $current = $_POST['current_password'] ?? '';
            if (!password_verify($current, $me['password_hash'])) {
                $errors['current_password'] = 'Current password is incorrect.';
            }
            if (!valid_password($password)) $errors['new_password'] = 'New password needs 8+ chars with at least one uppercase, one lowercase, and one number.';
        }

        if (mb_strlen($bio) > 2000)          $errors['bio'] = 'Bio must be under 2000 characters.';
        if (mb_strlen($interests) > 1000)    $errors['interests'] = 'Interests must be under 1000 characters.';
        if (mb_strlen($looking) > 1000)      $errors['looking'] = 'Too long.';
        if ($height_cm < 0 || $height_cm > 250) $height_cm = null;

        if (empty($errors)) {
            $pdo->prepare("
                UPDATE users SET username=?, first_name=?, last_name=?, gender=?, category=?, orientation=?,
                       birth_date=?, city=?, country=?, zip=?, bio=?, interests=?, looking_for=?,
                       body_type=?, height_cm=?, visibility=?, updated_at=NOW()
                WHERE id=?")
            ->execute([
                $username, $first_name, $last_name !== '' ? $last_name : null, $gender, $category,
                $orientation, $birth_date, $city !== '' ? $city : null, $country !== '' ? $country : null,
                $zip !== '' ? $zip : null, $bio, $interests, $looking,
                $body_type !== '' ? $body_type : null, $height_cm, $visibility, $me['id'],
            ]);

            if ($password !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $me['id']]);
            }

            unset($_SESSION['user']);
            flash('success', 'Profile updated.');
            redirect('/edit_profile.php?tab=about');
        }
    } elseif ($action === 'avatar') {
        try {
            $name = handle_image_upload($_FILES['avatar'] ?? [], 'avatar');
            if ($name) {
                delete_upload($me['avatar']);
                $pdo->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$name, $me['id']]);
                unset($_SESSION['user']);
                flash('success', 'Profile photo updated.');
            }
        } catch (RuntimeException $ex) {
            $errors['avatar'] = $ex->getMessage();
        }
        redirect('/edit_profile.php?tab=photos');
    } elseif ($action === 'cover') {
        try {
            $name = handle_image_upload($_FILES['cover'] ?? [], 'cover');
            if ($name) {
                delete_upload($me['cover']);
                $pdo->prepare("UPDATE users SET cover = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$name, $me['id']]);
                unset($_SESSION['user']);
                flash('success', 'Cover photo updated.');
            }
        } catch (RuntimeException $ex) {
            $errors['cover'] = $ex->getMessage();
        }
        redirect('/edit_profile.php?tab=photos');
    } elseif ($action === 'photo_add') {
        try {
            $name = handle_image_upload($_FILES['photo'] ?? [], 'photo');
            if ($name) {
                $pdo->prepare('INSERT INTO profile_photos (user_id, photo) VALUES (?, ?)')
                    ->execute([$me['id'], $name]);
                flash('success', 'Photo added.');
            }
        } catch (RuntimeException $ex) {
            $errors['photo'] = $ex->getMessage();
        }
        redirect('/edit_profile.php?tab=photos');
    } elseif ($action === 'photo_del') {
        $photoId = (int)($_POST['photo_id'] ?? 0);
        $s = $pdo->prepare('SELECT photo FROM profile_photos WHERE id = ? AND user_id = ?');
        $s->execute([$photoId, $me['id']]);
        if ($row = $s->fetch()) {
            delete_upload($row['photo']);
            $pdo->prepare('DELETE FROM profile_photos WHERE id = ? AND user_id = ?')
                ->execute([$photoId, $me['id']]);
            flash('info', 'Photo removed.');
        }
        redirect('/edit_profile.php?tab=photos');
    } elseif ($action === 'delete_account') {
        if (($_POST['confirm'] ?? '') !== $me['username']) {
            $errors['delete'] = 'Type your username to confirm deletion.';
        } else {
            $photoStmt = $pdo->prepare('SELECT photo FROM profile_photos WHERE user_id = ?');
            $photoStmt->execute([$me['id']]);
            foreach ($photoStmt as $r) {
                delete_upload($r['photo']);
            }
            delete_upload($me['avatar']);
            delete_upload($me['cover']);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$me['id']]);
            logout();
            redirect('/');
        }
    }
}

$me = current_user();
$tab = in_array($_GET['tab'] ?? '', ['about', 'photos', 'settings'], true) ? ($_GET['tab'] ?? 'about') : 'about';

$pdo2 = db();
$s = $pdo2->prepare('SELECT * FROM profile_photos WHERE user_id = ? ORDER BY position ASC');
$s->execute([$me['id']]);
$myPhotos = $s->fetchAll();

require __DIR__ . '/views/edit_profile.php';
