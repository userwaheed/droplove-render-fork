<?php
/**
 * Register view — category picker + account fields.
 */
$title = 'Create profile';
$step  = (int)($_GET['step'] ?? 1);
require __DIR__ . '/_layout_top.php';
?>

<div class="auth-wrap">
  <div class="card card-pad auth-card wide">
    <div class="auth-head">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none">
          <path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.2 3 5 6.2 5c2 0 3.4 1.2 4.1 2.5h1.4C12.4 6.2 13.8 5 15.8 5 19 5 21.1 8.2 20.6 11.8 18.5 16.4 12 21 12 21z" fill="#fff"/>
        </svg>
      </span>
      <h1 style="font-size:1.7rem"><?= e(t('auth.create_profile')) ?></h1>
      <p class="text-muted" style="margin:0"><?= e(t('auth.register_sub', ['app' => APP_NAME])) ?></p>
    </div>

    <form method="post" action="/register.php" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>

      <!-- Community picker -->
      <div class="field">
        <label><?= e(t('auth.joining_as')) ?></label>
        <div class="cat-grid">
          <?php foreach (CATEGORIES as $key => $c): ?>
            <label class="cat-opt">
              <input type="radio" name="category" value="<?= e($key) ?>" <?= ($old['category'] === $key) ? 'checked' : '' ?>>
              <span class="cat-body">
                <span class="cat-icon" aria-hidden="true"><?= e($c['icon']) ?></span>
                <span class="cat-label"><?= e($c['label']) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if ($errors['category'] ?? null): ?><div class="err">⚠ <?= e($errors['category']) ?></div><?php endif; ?>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="first_name"><?= e(t('auth.first_name')) ?></label>
          <input class="input<?= field_has_error($errors,'first_name') ?>" type="text" id="first_name" name="first_name"
                 value="<?= e($old['first_name']) ?>" autocomplete="given-name" required maxlength="60" placeholder="Ana">
          <?php if ($errors['first_name'] ?? null): ?><div class="err">⚠ <?= e($errors['first_name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label for="last_name"><?= e(t('auth.last_name')) ?> <span class="text-muted" style="font-weight:400"><?= e(t('auth.optional')) ?></span></label>
          <input class="input" type="text" id="last_name" name="last_name"
                 value="<?= e($old['last_name']) ?>" autocomplete="family-name" maxlength="60" placeholder="Sofia">
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="username">Username</label>
          <input class="input<?= field_has_error($errors,'username') ?>" type="text" id="username" name="username"
                 value="<?= e($old['username']) ?>" autocomplete="username" required minlength="3" maxlength="30"
                 pattern="[A-Za-z0-9_]+" placeholder="ana_sofia">
          <?php if ($errors['username'] ?? null): ?><div class="err">⚠ <?= e($errors['username']) ?></div><?php endif; ?>
          <div class="hint"><?= e(t('auth.username_hint')) ?></div>
        </div>
        <div class="field">
          <label for="birth_date"><?= e(t('auth.birth_date')) ?> <span class="text-muted" style="font-weight:400">(18+)</span></label>
          <input class="input<?= field_has_error($errors,'birth_date') ?>" type="date" id="birth_date" name="birth_date"
                 value="<?= e($old['birth_date']) ?>" required max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
          <?php if ($errors['birth_date'] ?? null): ?><div class="err">⚠ <?= e($errors['birth_date']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="email"><?= e(t('auth.email')) ?></label>
          <input class="input<?= field_has_error($errors,'email') ?>" type="email" id="email" name="email"
                 value="<?= e($old['email']) ?>" autocomplete="email" required placeholder="you@example.com"
                 <?php if ($errors['email'] ?? null): ?>aria-describedby="err-email" aria-invalid="true"<?php endif; ?>>
          <?php if ($errors['email'] ?? null): ?><div id="err-email" class="err" role="alert">⚠ <?= e($errors['email']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label for="gender"><?= e(t('auth.gender')) ?></label>
          <select class="select" id="gender" name="gender">
            <option value="female" <?= $old['gender']==='female'?'selected':'' ?>><?= e(t('ui.female')) ?></option>
            <option value="male"   <?= $old['gender']==='male'?'selected':'' ?>><?= e(t('ui.male')) ?></option>
            <option value="other"  <?= $old['gender']==='other'?'selected':'' ?>><?= e(t('ui.other')) ?></option>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="password"><?= e(t('auth.password')) ?></label>
          <input class="input<?= field_has_error($errors,'password') ?>" type="password" id="password" name="password"
                 autocomplete="new-password" required minlength="8" placeholder="At least 8 characters">
          <div id="pwStrength" class="hint" aria-live="polite"></div>
          <?php if ($errors['password'] ?? null): ?><div class="err">⚠ <?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label for="password_confirm"><?= e(t('auth.confirm_pw')) ?></label>
          <input class="input<?= field_has_error($errors,'password_confirm') ?>" type="password" id="password_confirm" name="password_confirm"
                 autocomplete="new-password" required minlength="8" placeholder="Repeat your password">
          <?php if ($errors['password_confirm'] ?? null): ?><div class="err">⚠ <?= e($errors['password_confirm']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="orientation">Orientation</label>
          <select class="select" id="orientation" name="orientation">
            <?php foreach (['flexible','straight','gay','bi','pan','ace'] as $o): ?>
              <option value="<?= e($o) ?>" <?= $old['orientation']===$o?'selected':'' ?>><?= e(ucfirst($o)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="looking_for">Looking for</label>
          <select class="select" id="looking_for" name="looking_for">
            <?php foreach (['friends','dating','relationship','couples','mmf','ffm','open'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($old['looking_for'] ?? '')===$o?'selected':'' ?>><?= e(ucfirst($o)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="city"><?= e(t('auth.city')) ?> <span class="text-muted" style="font-weight:400"><?= e(t('auth.optional')) ?></span></label>
          <input class="input" type="text" id="city" name="city" value="<?= e($old['city']) ?>" maxlength="60" placeholder="Paris">
        </div>
        <div class="field">
          <label for="country"><?= e(t('auth.country')) ?> <span class="text-muted" style="font-weight:400"><?= e(t('auth.optional')) ?></span></label>
          <input class="input" type="text" id="country" name="country" value="<?= e($old['country']) ?>" maxlength="60" placeholder="France">
        </div>
      </div>

      <div class="field">
        <label for="bio"><?= e(t('auth.bio')) ?> <span class="text-muted" style="font-weight:400"><?= e(t('auth.optional')) ?>, 2000 chars</span></label>
        <textarea class="input" id="bio" name="bio" maxlength="2000" placeholder="Tell us a little about yourself…"><?= e($old['bio']) ?></textarea>
      </div>

      <div class="field">
        <label class="check">
          <input type="checkbox" name="tos" value="1" <?= !empty($_POST['tos']) ? 'checked' : '' ?> required>
          <span>I confirm I am <strong>18 years or older</strong> and I accept the
            <a href="/terms.php" target="_blank" rel="noopener">Terms of Service</a> and
            <a href="/privacy.php" target="_blank" rel="noopener">Privacy Policy</a>.</span>
        </label>
        <?php if ($errors['tos'] ?? null): ?><div class="err">⚠ <?= e($errors['tos']) ?></div><?php endif; ?>
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('auth.create_profile')) ?></button>
    </form>

    <p class="auth-switch">
      <?= e(t('auth.have_account')) ?> <a href="/login.php"><?= e(t('nav.sign_in')) ?></a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
