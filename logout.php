<?php
/**
 * logout.php — End the session.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

csrf_verify();
logout();
flash('info', 'You have been signed out.');
redirect('/');
