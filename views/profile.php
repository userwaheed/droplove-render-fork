<?php
/**
 * Profile view — public profile with gallery, bio, actions, report.
 */
$title = $user['username'];
$isOwner = $me && (int)$me['id'] === (int)$user['id'];
$age = user_age($user['birth_date']);
$cat = CATEGORIES[$user['category']] ?? ['icon'=>'•','label'=>$user['category']];
$loc = array_filter([$user['city'], $user['country']]);
$isOnline = $user['online_last'] && strtotime('+10 minutes', strtotime($user['online_last'])) > time();
$canMessage = $me && !$isOwner && ($iLiked || !empty($user['likes_in']));
$genderLabel = ['female'=>'Female','male'=>'Male','other'=>'Other'][$user['gender']] ?? $user['gender'];
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding-top:2rem;padding-bottom:3rem">

    <div class="profile-head">
      <?php if ($user['cover']): ?>
        <img src="<?= e(avatar_url($user['cover'], null)) ?>" alt="Cover photo" data-fb>
      <?php else: ?>
        <div class="pcover-fallback" aria-hidden="true"><?= e($cat['icon']) ?></div>
      <?php endif; ?>
    </div>

    <div class="profile-main">
      <div>
        <div class="profile-id profile-id-wide">
          <img class="avatar xl" src="<?= e(avatar_url($user['avatar'], $user['username'])) ?>" alt="<?= e($user['username']) ?>" data-fb>
          <div class="identity">
            <h1>
              <?= e($user['username']) ?>
              <?php if ($age): ?><span style="color:var(--muted);font-weight:500;font-size:1.1rem">· <?= e($age) ?></span><?php endif; ?>
              <?php if ($isOnline): ?><span class="chip chip-green" style="margin-inline-start:.4rem"><span class="dot on"></span> Online</span><?php endif; ?>
            </h1>
            <div class="sub">
              <span class="chip chip-pink"><?= e($cat['icon']) ?> <?= e($cat['label']) ?></span>
              <span class="chip"><?= e($genderLabel) ?></span>
              <?php if ($loc): ?><span class="chip">📍 <?= e(implode(', ', $loc)) ?></span><?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-actions">
          <?php if (!$isOwner && $me): ?>
            <form method="post" action="/profile.php?id=<?= (int)$user['id'] ?>" style="margin:0;display:inline">
              <?= csrf_field() ?>
              <?php if ($iLiked): ?>
                <input type="hidden" name="action" value="unlike">
                <button class="btn btn-outline" type="submit">♥ Liked · Unlike</button>
              <?php else: ?>
                <input type="hidden" name="action" value="like">
                <button class="btn btn-primary" type="submit">♡ Like <?= e($user['username']) ?></button>
              <?php endif; ?>
            </form>
            <?php if ($canMessage): ?>
              <a class="btn btn-ghost" href="/messages.php?with=<?= (int)$user['id'] ?>">✉ Message</a>
            <?php endif; ?>
            <form method="post" action="/profile.php?id=<?= (int)$user['id'] ?>" style="margin:0;display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="report">
              <input type="hidden" name="reason" value="Spam or inappropriate content">
              <button class="btn btn-ghost btn-sm" type="submit" title="Report this profile">⚑ Report</button>
            </form>
          <?php endif; ?>

          <?php if ($isOwner): ?>
            <?php if ((int)$user['is_active'] === 1): ?>
              <span class="chip chip-green" style="padding:.6rem 1.1rem;font-size:.9rem">✓ Profile is live</span>
            <?php else: ?>
              <form method="post" action="/profile.php?id=<?= (int)$user['id'] ?>" style="margin:0;display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="activate">
                <button class="btn btn-primary" type="submit">Go live in Discover</button>
              </form>
            <?php endif; ?>
            <a class="btn btn-ghost" href="/edit_profile.php?tab=photos">Add photos</a>
          <?php endif; ?>
        </div>

        <div class="bio-block">
          <?php if ($user['bio'] && $canSeeBio): ?>
            <h3>About</h3>
            <p class="text-muted" style="white-space:pre-line"><?= e($user['bio']) ?></p>
          <?php elseif ($isOwner): ?>
            <p class="text-muted">You haven't written a bio yet. <a href="/edit_profile.php?tab=about">Add one →</a></p>
          <?php endif; ?>
        </div>

        <div class="bio-block">
          <h3>Details</h3>
          <div class="pchips" style="margin:0">
            <?php if ($user['orientation'] && $user['orientation'] !== 'flexible'): ?>
              <span class="chip chip-violet">Orientation: <?= e(ucfirst($user['orientation'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($user['looking_for'])): ?>
              <span class="chip chip-violet">Looking for: <?= e(ucfirst($user['looking_for'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($user['body_type'])): ?>
              <span class="chip"><?= e(ucfirst($user['body_type'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($user['height_cm'])): ?>
              <span class="chip"><?= e($user['height_cm']) ?> cm</span>
            <?php endif; ?>
            <?php if ($user['zip'] && $canSeeBio): ?>
              <span class="chip">📍 <?= e($user['zip']) ?></span>
            <?php endif; ?>
            <span class="chip chip-green">♥ <?= number_format($likedCount) ?> likes</span>
          </div>
        </div>

        <?php if (!empty($user['interests']) && $canSeeBio): ?>
          <div class="bio-block">
            <h3>Interests</h3>
            <div class="pchips">
              <?php foreach (explode(',', $user['interests']) as $tag):
                $tag = trim($tag); if ($tag): ?>
                <span class="chip"><?= e($tag) ?></span>
              <?php endif; endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($photos)): ?>
          <div class="bio-block">
            <h3>Gallery <span class="text-muted" style="font-weight:400">· <?= count($photos) ?></span></h3>
            <div class="photolist">
              <?php foreach ($photos as $ph): ?>
                <img src="<?= e(avatar_url($ph['photo'], null)) ?>"
                     data-full="<?= e(avatar_url($ph['photo'], null)) ?>"
                     alt="Profile photo <?= e($ph['id']) ?>" data-fb loading="lazy" title="Click to enlarge">
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <p class="text-muted" style="font-size:.8rem;margin-top:2rem">
          Member since <?= e(date('F Y', strtotime($user['created_at']))) ?>
          <?php if ($user['updated_at']): ?>· Updated <?= e(date('F j, Y', strtotime($user['updated_at']))) ?><?php endif; ?>
          · Profile ID <?= (int)$user['id'] ?>
        </p>
      </div>

      <aside>
        <div class="panel card-pad">
          <h3 style="margin-bottom:1rem">Contact info</h3>
          <ul style="display:flex;flex-direction:column;gap:.7rem;font-size:.92rem">
            <li>♂ <?php echo e($genderLabel) ?></li>
            <?php if ($loc): ?>
              <li>📍 <?= e(implode(', ', $loc)) ?></li>
            <?php else: ?>
              <li>📍 Location not shared</li>
            <?php endif; ?>
            <?php if (!empty($user['height_cm'])): ?>
              <li>📏 <?= e($user['height_cm']) ?> cm</li>
            <?php endif; ?>
            <li>♥ <?= number_format($likedCount) ?> likes received</li>
          </ul>
        </div>

        <div class="panel card-pad" style="margin-top:1rem;background:var(--grad-soft);border-color:rgba(255,95,162,.3)">
          <h3>Meet safer</h3>
          <ul style="font-size:.85rem;color:var(--muted);display:flex;flex-direction:column;gap:.5rem">
            <li>· Don't share bank or ID details</li>
            <li>· Meet in a public place first</li>
            <li>· Tell someone where you're going</li>
            <li>· Use the report button if unsure</li>
          </ul>
          <a class="btn btn-ghost btn-sm btn-block" href="/#safety" style="margin-top:1rem">Safety centre</a>
        </div>
      </aside>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
