<?php
/**
 * Contact view.
 */
$title = 'Contact';
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding:3rem 0 4rem;max-width:680px">
    <span class="eyebrow">Support</span>
    <h1 style="font-size:2.4rem">Contact us</h1>
    <p class="text-muted">Questions, feedback, or a privacy request? Send us a message.</p>

    <?php if ($sent): ?>
      <div class="flash flash-success" role="status">✓ Thank you — your message has been received. We'll reply as soon as we can.</div>
    <?php endif; ?>

    <form class="card card-pad" method="post" action="/contact.php" novalidate>
      <?= csrf_field() ?>

      <div class="row-2">
        <div class="field">
          <label for="c_name">Your name</label>
          <input class="input<?= field_has_error($errors,'name') ?>" type="text" id="c_name" name="name"
                 value="<?= e($_POST['name'] ?? '') ?>" required minlength="2" maxlength="80">
          <?php if (!empty($errors['name'])): ?><div class="err">⚠ <?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label for="c_email">Your email</label>
          <input class="input<?= field_has_error($errors,'email') ?>" type="email" id="c_email" name="email"
                 value="<?= e($_POST['email'] ?? '') ?>" required placeholder="you@example.com">
          <?php if (!empty($errors['email'])): ?><div class="err">⚠ <?= e($errors['email']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label for="c_subject">Subject</label>
        <input class="input<?= field_has_error($errors,'subject') ?>" type="text" id="c_subject" name="subject"
               value="<?= e($_POST['subject'] ?? '') ?>" required minlength="3" maxlength="120" placeholder="Account help, bug report…">
        <?php if (!empty($errors['subject'])): ?><div class="err">⚠ <?= e($errors['subject']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="c_body">Message</label>
        <textarea class="input<?= field_has_error($errors,'body') ?>" id="c_body" name="body"
                  required minlength="10" maxlength="2000" placeholder="How can we help?"><?= e($_POST['body'] ?? '') ?></textarea>
        <?php if (!empty($errors['body'])): ?><div class="err">⚠ <?= e($errors['body']) ?></div><?php endif; ?>
      </div>

      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
