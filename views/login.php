<?php
/**
 * Login view.
 */
$title = 'Sign in';
require __DIR__ . '/_layout_top.php';
?>

<div class="auth-wrap">
  <div class="card card-pad auth-card">
    <div class="auth-head">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none">
          <path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.2 3 5 6.2 5c2 0 3.4 1.2 4.1 2.5h1.4C12.4 6.2 13.8 5 15.8 5 19 5 21.1 8.2 20.6 11.8 18.5 16.4 12 21 12 21z" fill="#fff"/>
        </svg>
      </span>
      <h1 style="font-size:1.7rem"><?= e(t('auth.signin_title')) ?></h1>
      <p class="text-muted" style="margin:0"><?= e(t('auth.signin_sub', ['app' => APP_NAME])) ?></p>
    </div>

    <form method="post" action="/login.php" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="email"><?= e(t('auth.email_user')) ?></label>
        <input class="input<?= field_has_error($errors,'email') ?>" type="email" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" autocomplete="username" required placeholder="you@example.com">
        <?php if ($errors['email'] ?? null): ?><div class="err">⚠ <?= e($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="password"><?= e(t('auth.password')) ?></label>
        <input class="input<?= field_has_error($errors,'password') ?>" type="password" id="password" name="password"
               autocomplete="current-password" required placeholder="••••••••">
        <?php if ($errors['password'] ?? null): ?><div class="err">⚠ <?= e($errors['password']) ?></div><?php endif; ?>
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('auth.signin')) ?></button>
    </form>

    <p class="auth-switch">
      <?= e(t('auth.new_here')) ?> <a href="/register.php"><?= e(t('auth.create_link')) ?></a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
