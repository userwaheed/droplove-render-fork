<?php
/**
 * Privacy Policy view.
 */
$title = 'Privacy Policy';
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding:3rem 0 4rem">
    <span class="eyebrow">Legal</span>
    <h1 style="font-size:2.4rem">Privacy Policy</h1>
    <p class="text-muted">Last updated: <?= e(date('F Y')) ?> · <?= e(APP_NAME) ?></p>

    <div class="prose">
      <h2>What we collect</h2>
      <p>We collect only what you give us: email, username, name, birth date, gender, orientation,
      location, bio, interests, and any photos you upload. We never request bank or ID details.</p>

      <h2>How we use it</h2>
      <ul>
        <li>To operate the service: matching, discover feeds, messaging.</li>
        <li>To secure the platform: password hashing, rate limiting, abuse detection.</li>
        <li>To review safety reports you submit.</li>
      </ul>
      <p>We do not sell your data. We do not run targeted advertising. We do not profile you for third parties.</p>

      <h2>Cookies and sessions</h2>
      <p>We use one session cookie to keep you signed in, and remember your language preference.
      We do not use third-party analytics or tracking pixels.</p>

      <h2>Your rights</h2>
      <ul>
        <li><strong>Access:</strong> view all your data on your profile and settings pages.</li>
        <li><strong>Correction:</strong> edit your profile at any time.</li>
        <li><strong>Deletion:</strong> delete your account permanently from settings.</li>
      </ul>

      <h2>Data retention</h2>
      <p>Data is retained while your account exists. On account deletion, your personal data and uploaded
      files are removed permanently. Deleted data cannot be recovered.</p>

      <h2>Security</h2>
      <p>Passwords are hashed with a modern algorithm. Transfers use HTTPS. Uploaded files are re-encoded
      and stored outside the web root, served only through a validated endpoint.</p>

      <h2>Contact</h2>
      <p><a href="/contact.php">Contact us</a> with any privacy questions or data requests.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
