<?php
/**
 * login.php — Sign in.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (is_locked_out()) {
        $errors['email'] = 'Too many failed attempts. Please try again in 15 minutes.';
    } elseif (!$email || !$password) {
        if (!$email)    $errors['email']    = 'Email is required.';
        if (!$password) $errors['password'] = 'Password is required.';
    } elseif (is_string($password) && mb_strlen($password) > 4096) {
        $errors['password'] = 'Password is too long.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        $ok = $user && password_verify($password, $user['password_hash']);
        record_attempt($email, (bool)$ok);

        if ($ok) {
            if ((int)$user['banned'] === 1) {
                $errors['email'] = 'This account has been suspended. Contact support.';
            } else {
                clear_attempts($email);
                login_user((int)$user['id']);

                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $s = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $s->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }

                if (!empty($_SESSION['redirect'])) {
                    $dest = $_SESSION['redirect'];
                    unset($_SESSION['redirect']);
                    redirect($dest);
                }
                redirect('/dashboard.php');
            }
        } else {
            $errors['email'] = 'Invalid email or password.';
        }
    }
}

require __DIR__ . '/views/login.php';
