<?php
/**
 * Dashboard view.
 */
$title = 'Dashboard';
$age = user_age($me['birth_date']);
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding-top:2.4rem;padding-bottom:3rem">
    <div class="section-head" style="margin-bottom:1.6rem">
      <span class="eyebrow">Dashboard</span>
      <h2 style="font-size:1.9rem">Hey <?= e($me['username']) ?> 👋</h2>
      <p class="text-muted" style="margin:0">
        <?php if ((int)$me['is_active'] === 1): ?>
          <span class="chip chip-green"><span class="dot on"></span> <?= e(t('dash.live')) ?></span>
        <?php else: ?>
          <span class="chip"><?= e(t('dash.hidden')) ?></span>
        <?php endif; ?>
      </p>
    </div>

    <div class="stat-grid">
      <div class="stat panel card-pad">
        <div class="n"><?= number_format((int)$stats['likes_in']) ?></div>
        <div class="l"><?= e(t('dash.likes_in')) ?></div>
      </div>
      <div class="stat panel card-pad">
        <div class="n"><?= number_format((int)$stats['likes_out']) ?></div>
        <div class="l"><?= e(t('dash.likes_out')) ?></div>
      </div>
      <div class="stat panel card-pad">
        <div class="n"><?= number_format((int)$stats['unread']) ?></div>
        <div class="l"><?= e(t('dash.unread')) ?></div>
      </div>
      <div class="stat panel card-pad">
        <div class="n"><?= number_format((int)$stats['members']) ?></div>
        <div class="l"><?= e(t('dash.members')) ?></div>
      </div>
    </div>

    <div class="dash-cols">
      <div>
        <div class="panel card-pad">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
            <h3 style="margin:0"><?= e(t('dash.for_you')) ?></h3>
            <a class="btn btn-ghost btn-sm" href="/discover.php"><?= e(t('dash.view_all')) ?></a>
          </div>
          <?php if (empty($forYou)): ?>
            <div class="empty" style="padding:2rem">
              <div class="icon" aria-hidden="true">🎯</div>
              <p><?= e(t('dash.no_profiles')) ?></p>
              <a class="btn btn-primary btn-sm" href="/discover.php"><?= e(t('dash.open_discover')) ?></a>
            </div>
          <?php else: ?>
            <div class="grid profiles">
              <?php foreach (array_slice($forYou, 0, 6) as $p):
                $pa = user_age($p['birth_date']);
                $cat = CATEGORIES[$p['category']] ?? ['icon'=>'•','label'=>$p['category']];
              ?>
              <article class="profile-card">
                <a class="pc-photo" href="/profile.php?id=<?= (int)$p['id'] ?>">
                  <img src="<?= e(avatar_url($p['avatar'], $p['username'])) ?>" alt="<?= e($p['username']) ?>" data-fb loading="lazy">
                </a>
                <div class="pc-body">
                  <div class="pc-name"><?= e($p['username']) ?><?php if ($pa): ?><small><?= e($pa) ?></small><?php endif; ?></div>
                  <div class="pc-meta"><?= e($cat['icon']) ?> · ♥ <?= (int)$p['likes_count'] ?></div>
                  <?php if ($p['bio']): ?><p class="pc-bio"><?= e($p['bio']) ?></p><?php endif; ?>
                  <a class="btn btn-outline btn-sm btn-block" href="/profile.php?id=<?= (int)$p['id'] ?>">View</a>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <aside>
        <div class="panel card-pad">
          <h3><?= e(t('dash.quick_actions')) ?></h3>
          <div style="display:flex;flex-direction:column;gap:.6rem">
            <?php if ((int)$me['is_active'] === 0): ?>
              <form method="post" action="/profile.php?id=<?= (int)$me['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="activate">
                <button class="btn btn-primary btn-block" type="submit"><?= e(t('dash.go_live')) ?></button>
              </form>
            <?php endif; ?>
            <a class="btn btn-ghost btn-block" href="/discover.php"><?= e(t('dash.browse')) ?></a>
            <a class="btn btn-ghost btn-block" href="/messages.php"><?= e(t('dash.messages')) ?></a>
            <a class="btn btn-ghost btn-block" href="/edit_profile.php?tab=photos"><?= e(t('dash.add_photos')) ?></a>
            <a class="btn btn-ghost btn-block" href="/edit_profile.php?tab=about"><?= e(t('dash.edit_profile')) ?></a>
            <a class="btn btn-ghost btn-block" href="/profile.php?id=<?= (int)$me['id'] ?>"><?= e(t('dash.my_profile')) ?></a>
          </div>
        </div>

        <div class="panel card-pad" style="margin-top:1rem">
          <h3><?= e(t('dash.completeness')) ?></h3>
          <?php
            $checks = [
              'Profile photo'   => (bool)$me['avatar'],
              'Bio written'     => (bool)$me['bio'],
              'Cover photo'     => (bool)$me['cover'],
              'Gallery photos'  => false, // filled below
              'City / country'  => (bool)($me['city'] || $me['country']),
              'Interests'       => (bool)$me['interests'],
              'Looking for'     => (bool)$me['looking_for'],
            ];
          ?>
          <ul style="display:flex;flex-direction:column;gap:.55rem;font-size:.88rem">
            <?php foreach ($checks as $label => $done): ?>
              <li style="display:flex;gap:.5rem;align-items:center">
                <span style="color:<?= $done ? 'var(--success)' : 'var(--muted-2)' ?>"><?= $done ? '✓' : '○' ?></span>
                <span style="color:<?= $done ? 'var(--text)' : 'var(--muted)' ?>"><?= e($label) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
