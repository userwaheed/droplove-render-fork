<?php
/**
 * Admin user detail view — shows a single user with moderation actions.
 * Variables provided by admin.php: $me, $du, $likesIn, $likesOut, $msgs, $photos, $uploadFiles, $resetPw
 */
$title = 'Admin: ' . ($du['username'] ?? 'User');
$cat = CATEGORIES[$du['category'] ?? ''] ?? ['icon'=>'•','label'=>$du['category'] ?? ''];
$age = user_age($du['birth_date'] ?? '');
require __DIR__ . '/_layout_top.php';
?>
<div class="container">
  <div style="padding-top:2.4rem;padding-bottom:3rem">
    <div class="section-head" style="margin-bottom:1.6rem">
      <span class="eyebrow">Moderation</span>
      <h2 style="font-size:1.9rem">User: <?= e($du['username'] ?? '') ?></h2>
      <p class="text-muted" style="margin:0">
        <?= e($du['first_name'] ?? '') ?> <?= e($du['last_name'] ?? '') ?>
        · ID <?= (int)($du['id'] ?? 0) ?>
        · <?= e($du['email'] ?? '') ?>
      </p>
    </div>

    <!-- One-shot password display -->
    <?php if (!empty($resetPw)): ?>
      <div class="flash flash-info" style="margin-bottom:1.6rem">
        <div><strong>Password reset for @<?= e($resetPw['username']) ?></strong></div>
        <div style="margin-top:.4rem">New password: <code style="padding:.2rem .5rem;background:var(--surface-3);border-radius:4px;font-size:1rem;font-weight:700"><?= e($resetPw['password']) ?></code></div>
        <div style="margin-top:.4rem;font-size:.85rem">This password will not be shown again.</div>
      </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stat-grid" style="margin-bottom:2rem">
      <div class="stat panel card-pad"><div class="n">♥ <?= number_format($likesIn) ?></div><div class="l">Likes received</div></div>
      <div class="stat panel card-pad"><div class="n">♥ <?= number_format($likesOut) ?></div><div class="l">Likes sent</div></div>
      <div class="stat panel card-pad"><div class="n">✉ <?= number_format($msgs) ?></div><div class="l">Messages</div></div>
    </div>

    <!-- Moderation actions -->
    <div class="panel card-pad" style="margin-bottom:2rem">
      <h3 style="margin:0 0 .8rem">Moderation</h3>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <?php if ((int)($du['banned'] ?? 0) === 1): ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Unsuspend this user?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="unsuspend">
            <button class="btn btn-ghost" type="submit">Unsuspend</button>
          </form>
        <?php else: ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Suspend (block) this user? They will be unable to log in or appear anywhere.')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="suspend">
            <button class="btn btn-ghost" type="submit">Suspend</button>
          </form>
        <?php endif; ?>

        <?php if ((int)($du['is_active'] ?? 1) === 1): ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Hide this profile from Discover? (They remain logged-in-able.)')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="deactivate">
            <button class="btn btn-ghost" type="submit">Deactivate</button>
          </form>
        <?php else: ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Reactivate this user in Discover?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="activate">
            <button class="btn btn-ghost" type="submit">Activate</button>
          </form>
        <?php endif; ?>

        <?php if ((int)($du['is_admin'] ?? 0) === 1): ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Revoke admin privileges?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="toggle_admin">
            <button class="btn btn-ghost" type="submit">Revoke Admin</button>
          </form>
        <?php else: ?>
          <form method="post" action="/admin.php" onsubmit="return confirm('Grant admin privileges?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
            <input type="hidden" name="action" value="toggle_admin">
            <button class="btn btn-ghost" type="submit">Grant Admin</button>
          </form>
        <?php endif; ?>

        <form method="post" action="/admin.php" onsubmit="return confirm('Toggle verified badge for this user?')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
          <input type="hidden" name="action" value="toggle_verified">
          <button class="btn btn-ghost" type="submit">
            <?php if ((int)($du['verified'] ?? 0) === 1): ?>✓ Unverify<?php else: ?>☆ Verify<?php endif; ?>
          </button>
        </form>

        <form method="post" action="/admin.php" onsubmit="return confirm('Reset this user\'s password? The new password will be shown once on the next screen.')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
          <input type="hidden" name="action" value="reset">
          <button class="btn btn-ghost" type="submit">Reset Password</button>
        </form>

        <form method="post" action="/admin.php" onsubmit="return confirm('Permanently delete this user and all their data? This cannot be undone.')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$du['id'] ?>">
          <input type="hidden" name="action" value="delete">
          <button class="btn btn-danger" type="submit">Delete User</button>
        </form>
      </div>
    </div>

    <!-- Profile details -->
    <div class="panel card-pad" style="margin-bottom:2rem">
      <h3 style="margin:0 0 .8rem">Profile details</h3>
      <ul style="display:flex;flex-direction:column;gap:.5rem">
        <li><strong>Username:</strong> <?= e($du['username'] ?? '') ?></li>
        <li><strong>Full name:</strong> <?= e(($du['first_name'] ?? '') . ' ' . ($du['last_name'] ?? '')) ?></li>
        <li><strong>Age:</strong> <?= $age ? e($age) . ' years' : '—' ?></li>
        <li><strong>Gender:</strong> <?= e(['male'=>'Male','female'=>'Female','other'=>'Other'][$du['gender'] ?? '' ?? '…'] ?? '…') ?></li>
        <li><strong>Category:</strong> <?= e($cat['icon']) ?> <?= e($cat['label']) ?></li>
        <li><strong>Location:</strong> <?= e(array_filter([$du['city'] ?? '', $du['country'] ?? '']) ? implode(', ', array_filter([$du['city'] ?? '', $du['country'] ?? ''])) : '—') ?></li>
        <li><strong>ZIP:</strong> <?= e($du['zip'] ?? '') ?: '—' ?></li>
        <li><strong>Height:</strong> <?= !empty($du['height_cm']) ? e($du['height_cm']) . ' cm' : '—' ?></li>
        <li><strong>Orientation:</strong> <?= e(ucfirst($du['orientation'] ?? '')) ?: '—' ?></li>
        <li><strong>Looking for:</strong> <?= e(ucfirst($du['looking_for'] ?? '')) ?: '—' ?></li>
        <li><strong>Bio:</strong> <?= e($du['bio'] ?? '') ?: '—' ?></li>
        <li><strong>Interests:</strong> <?= e($du['interests'] ?? '') ?: '—' ?></li>
        <li><strong>Registered:</strong> <?= e(date('M j, Y H:i', strtotime($du['created_at'] ?? ''))) ?></li>
        <li><strong>Last seen:</strong> <?= !empty($du['online_last']) ? e(date('M j, Y H:i', strtotime($du['online_last']))) : 'Never' ?></li>
      </ul>
    </div>

    <!-- Uploaded files -->
    <?php if (!empty($uploadFiles)): ?>
      <div class="panel card-pad" style="margin-bottom:2rem">
        <h3 style="margin:0 0 .8rem">Uploaded files</h3>
        <ul style="display:flex;flex-direction:column;gap:.4rem;font-size:.88rem">
          <?php foreach ($uploadFiles as $f): ?>
            <li style="font-family:monospace"><?= e($f) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="text-muted" style="font-size:.8rem;margin-top:.6rem">These files are on disk in data/uploads/. They were not automatically deleted when the account was created — use the "Delete User" action above to remove them via cascade.</p>
      </div>
    <?php endif; ?>

    <!-- Gallery -->
    <div class="panel card-pad">
      <h3 style="margin:0 0 .8rem">Photo gallery</h3>
      <?php if (empty($photos)): ?>
        <p class="text-muted">No gallery photos.</p>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:1rem">
          <?php foreach ($photos as $p): ?>
            <img src="<?= e(avatar_url($p['photo'], null)) ?>" alt="Photo" style="width:100%;border-radius:var(--r-sm)">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div style="margin-top:2rem">
      <a class="btn btn-ghost" href="/admin.php">&larr; Back to admin</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
