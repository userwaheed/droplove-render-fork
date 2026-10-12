<?php
/**
 * Terms of Service — static legal page.
 */
$title = 'Terms of Service';
$doc = 'Terms of Service';
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding:3rem 0 4rem">
    <span class="eyebrow">Legal</span>
    <h1 style="font-size:2.4rem">Terms of Service</h1>
    <p class="text-muted">Last updated: <?= e(date('F Y')) ?> · <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></p>

    <div class="prose">
      <h2>1. Eligibility</h2>
      <p>You must be at least <strong>18 years old</strong> to use <?= e(APP_NAME) ?>. You represent and warrant that all
      information you provide is accurate and current. If you are under 18, you may not create an account.</p>

      <h2>2. Your account</h2>
      <ul>
        <li>You are responsible for maintaining the confidentiality of your credentials.</li>
        <li>Never share your password or give access to anyone else.</li>
        <li>Notify us immediately of any unauthorised use of your account.</li>
        <li>Profiles must not contain misleading information or impersonate others.</li>
      </ul>

      <h2>3. Acceptable use</h2>
      <p>You agree not to use the service to:</p>
      <ul>
        <li>Harass, threaten, stalk or bully other members.</li>
        <li>Send spam, unsolicited commercial messages, or deceptive content.</li>
        <li>Post or solicit pornographic material, illegal substances, or weapons.</li>
        <li>Collect personal data about other members for any purpose.</li>
        <li>Sell, license or sublicense access to the service.</li>
        <li>Attempt to breach security, scrape at scale, or disrupt the platform.</li>
      </ul>

      <h2>4. Content and privacy</h2>
      <p>By creating a profile you grant us permission to host and display your photos and text for the purpose of
      operating the service. Your profile is visible according to the visibility setting you choose. You retain ownership
      of your content and may delete it at any time.</p>

      <h2>5. Safety and reporting</h2>
      <p>Reports are reviewed in good faith. We may suspend or terminate accounts that breach these terms, and we may
      remove content that violates the law or these terms. We cannot guarantee that messages are private — never share
      sensitive personal, financial or identity information with other members.</p>

      <h2>6. Termination</h2>
      <p>We reserve the right to suspend or terminate your account at any time, with or without cause, and with or
      without notice. You may delete your account at any time from profile settings. Upon deletion your personal data
      is removed permanently.</p>

      <h2>7. Disclaimers</h2>
      <p>The service is provided "as is" without warranties of any kind. We do not verify the identity, age or intentions
      of members and are not responsible for the conduct of third parties. Meet in public places and prioritise your
      own safety.</p>

      <h2>8. Governing law</h2>
      <p>These terms are governed by the laws of the jurisdiction where <?= e(APP_NAME) ?> is hosted. Nothing in these
      terms affects your statutory rights as a consumer.</p>

      <h2>9. Contact</h2>
      <p>Questions about these terms? <a href="/contact.php">Contact us</a>.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
