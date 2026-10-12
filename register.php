<?php
/**
 * register.php — Create a profile.
 *
 * Supports singles (male / female), couples, MMF and FFM. Couples/poly
 * registrations use a shared account; the category drives discover filters.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$errors    = [];
$old       = ['email' => '', 'username' => '', 'first_name' => '', 'last_name' => '',
              'gender' => '', 'category' => '', 'orientation' => '', 'birth_date' => '',
              'city' => '', 'country' => '', 'bio' => '', 'looking_for' => '', 'body_type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Rate-limit registration (reuse the same lockout mechanism as login).
    if (is_locked_out()) {
        $errors['email'] = 'Too many attempts. Please wait ' . (LOCKOUT_SECONDS / 60) . ' minutes and try again.';
    }

    foreach ($old as $k => $_v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    $email      = $old['email'];
    $username   = $old['username'];
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['password_confirm'] ?? '';
    $tos        = !empty($_POST['tos']);
    $category   = $old['category'];
    $birth_date = $old['birth_date'];

    /* ── Validation ── */
    if (!valid_email($email))                              $errors['email']      = 'Enter a valid email address.';
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30)
                                                           $errors['username']   = 'Username must be 3-30 characters.';
    elseif (!preg_match('/^[A-Za-z0-9_]+$/', $username))  $errors['username']   = 'Username may only use letters, numbers and underscores.';
    if (!mb_strlen($old['first_name']))                   $errors['first_name'] = 'First name is required.';

    if (!valid_password($password))                          $errors['password']   = 'Password needs 8+ chars with at least one uppercase, one lowercase, and one number.';
    if ($password !== $confirm)                            $errors['password_confirm'] = 'Passwords do not match.';

    if (!array_key_exists($category, CATEGORIES))          $errors['category']   = 'Please choose a category.';

    if (!$birth_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date))
                                                           $errors['birth_date'] = 'Enter your birth date.';
    else {
        $age = user_age($birth_date);
        if ($age === null)                                 $errors['birth_date'] = 'That date is invalid.';
        elseif ($age < 18)                                 $errors['birth_date'] = 'You must be 18 or older to use ' . APP_NAME . '.';
        elseif ($age > 120)                                $errors['birth_date'] = 'Please enter a realistic birth date.';
    }

    if (!$tos)                                             $errors['tos']        = 'You must accept the Terms of Service.';

    if (empty($errors)) {
        $pdo = db();
        try {
            $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $dup->execute([$email, $username]);
            if ($dup->fetch()) {
                $errors['email'] = 'An account with that email or username already exists.';
            }
        } catch (PDOException) {}

        if (empty($errors)) {
            // Auto-align single_m / single_f with gender.
            if ($category === 'single_m')      $old['gender'] = 'male';
            if ($category === 'single_f')      $old['gender'] = 'female';
            if (!in_array($old['gender'], ['male', 'female', 'other'], true))
                $old['gender'] = 'other';

            // Categories with no own profile photo use their first name field.
            $stmt = $pdo->prepare('
                INSERT INTO users (email, password_hash, username, first_name, last_name,
                                   gender, category, orientation, birth_date, city, country, bio, looking_for, body_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $username,
                $old['first_name'],
                $old['last_name'],
                $old['gender'],
                $category,
                $old['orientation'],
                $birth_date,
                $old['city'],
                $old['country'],
                $old['bio'],
                $old['looking_for'],
                $old['body_type'],
            ]);

            $newId = (int)$pdo->lastInsertId();
            flash('success', 'Welcome to ' . APP_NAME . '! Your account is ready — go live from your dashboard.');
            login_user($newId);
            redirect('/dashboard.php');
        }
    }
}

require __DIR__ . '/views/register.php';
