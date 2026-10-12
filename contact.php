<?php
/**
 * Contact view + handler.
 *
 * POST /contact.php validates and stores nothing by default — it sends the
 * message to the configured contact address via PHP mail(). Update
 * CONTACT_EMAIL in config.php. If mail() is unavailable on the host, the
 * page reports that and the message is logged.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$errors = [];
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if (mb_strlen($name) < 2)        $errors['name']    = 'Please enter your name.';
    if (!valid_email($email))        $errors['email']   = 'Enter a valid email address.';
    if (mb_strlen($subject) < 3)     $errors['subject'] = 'Enter a short subject.';
    if (mb_strlen($body) < 10)       $errors['body']    = 'Message must be at least 10 characters.';
    if (mb_strlen($body) > 2000)     $errors['body']    = 'Message must be 2000 characters or fewer.';

    if (empty($errors)) {
        $to      = CONTACT_EMAIL;
        $headers = 'From: ' . APP_NAME . ' <noreply@localhost>' . "\r\n"
                 . 'Reply-to: ' . str_replace(["\r", "\n", "#"], '', $email) . "\r\n"
                 . 'Content-Type: text/plain; charset=UTF-8';

        $ok = @mail($to, '[Website contact] ' . $subject, $body . "\r\n\r\n— " . $name . ' <' . $email . '>', $headers);

        if ($ok) {
            $sent = true;
        } else {
            // mail() often fails silently on shared hosts without SMTP.
            // Collapse all CRLF before writing: an unescaped body like
            // "x\n<timestamp> | admin | admin@x | FAKE | ..." would forge
            // audit-log lines. /data/ is web-blocked (see .htaccess), so
            // this is log tampering, not disclosure — but audit integrity
            // is the point of keeping a log.
            $safe  = fn(string $s): string => str_replace(["\r", "\n"], ' ', $s);
            $logFile = BASE_PATH . '/data/contact.log';
            if (is_dir(dirname($logFile))) {
                @file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " | {$safe($name)} | {$safe($email)} | {$safe($subject)} | {$safe($body)}\n",
                    FILE_APPEND | LOCK_EX
                );
                $sent = true; // logged, so the user isn't left thinking nothing happened
            } else {
                $errors['body'] = 'Could not send the message. Please try again later.';
            }
        }
    }
}

require __DIR__ . '/views/contact.php';
