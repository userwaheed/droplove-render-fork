<?php
/**
 * Discover view — filterable grid of profiles.
 */
$title = 'Discover';
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding-top:2.4rem;padding-bottom:2.4rem">
    <div class="section-head" style="margin-bottom:1.6rem">
      <span class="eyebrow"><?= e(t('nav.discover')) ?></span>
      <h2 style="font-size:1.9rem"><?= e(t('ui.people_community')) ?></h2>
    </div>

    <!-- Filters -->
    <form class="card card-pad filters" method="get" action="/discover.php">
      <div class="field">
        <label for="f_cat"><?= e(t('ui.f_cat')) ?></label>
        <select class="select" id="f_cat" name="category">
          <option value="all"><?= e(t('ui.f_cat_all')) ?></option>
          <?php foreach (CATEGORIES as $k => $c): ?>
            <option value="<?= e($k) ?>" <?= $cat===$k?'selected':'' ?>><?= e($c['icon']) ?> <?= e($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f_gen"><?= e(t('ui.f_gender')) ?></label>
        <select class="select" id="f_gen" name="gender">
          <option value="all" <?= $gen==='all'?'selected':'' ?>><?= e(t('ui.f_any')) ?></option>
          <option value="female" <?= $gen==='female'?'selected':'' ?>><?= e(t('ui.female')) ?></option>
          <option value="male" <?= $gen==='male'?'selected':'' ?>><?= e(t('ui.male')) ?></option>
          <option value="other" <?= $gen==='other'?'selected':'' ?>><?= e(t('ui.f_other')) ?></option>
        </select>
      </div>
      <div class="field">
        <label for="f_min"><?= e(t('ui.f_age')) ?></label>
        <div style="display:flex;align-items:center;gap:.4rem">
          <input class="input" type="number" id="f_min" name="min_age" min="18" max="99" value="<?= e($min) ?>" style="width:78px">
          <span class="text-muted">–</span>
          <input class="input" type="number" name="max_age" min="18" max="99" value="<?= e($max) ?>" style="width:78px">
        </div>
      </div>
      <div class="field grow">
        <label for="f_loc"><?= e(t('ui.f_location')) ?></label>
        <input class="input" type="text" id="f_loc" name="location" value="<?= e($loc) ?>" placeholder="Paris, France…">
      </div>
      <div class="field">
        <label for="f_sort"><?= e(t('ui.f_sort')) ?></label>
        <select class="select" id="f_sort" name="sort">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>><?= e(t('ui.sort_newest')) ?></option>
          <option value="online" <?= $sort==='online'?'selected':'' ?>><?= e(t('ui.sort_online')) ?></option>
          <option value="age"    <?= $sort==='age'?'selected':'' ?>><?= e(t('ui.sort_oldest')) ?></option>
          <option value="likes"  <?= $sort==='likes'?'selected':'' ?>><?= e(t('ui.sort_likes')) ?></option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit"><?= e(t('ui.apply')) ?></button>
      <a class="btn btn-ghost" href="/discover.php"><?= e(t('ui.reset')) ?></a>
    </form>

    <div class="filter-count">
      <?= e(t('ui.f_showing', ['shown' => count($profiles), 'total' => number_format((int)$pg['total'])])) ?>
    </div>

    <?php if (empty($profiles)): ?>
      <div class="panel">
        <div class="empty">
          <div class="icon" aria-hidden="true">🔭</div>
          <h3><?= e(t('ui.empty_discover')) ?></h3>
          <p><?= e(t('ui.empty_discover_p')) ?></p>
          <a class="btn btn-primary" href="/discover.php"><?= e(t('ui.reset_filters')) ?></a>
        </div>
      </div>
    <?php else: ?>
      <div class="grid profiles">
        <?php foreach ($profiles as $p):
          $age = user_age($p['birth_date']);
          $loc = array_filter([$p['city'], $p['country']]);
          $cat = CATEGORIES[$p['category']] ?? ['icon'=>'•','label'=>$p['category']];
        ?>
        <article class="profile-card">
          <a class="pc-photo" href="/profile.php?id=<?= (int)$p['id'] ?>" aria-label="View <?= e($p['username']) ?>">
            <img src="<?= e(avatar_url($p['avatar'] ?: null, $p['username'])) ?>" alt="Photo of <?= e($p['username']) ?>" data-fb loading="lazy">
            <?php if ($p['online_last'] && strtotime('+10 minutes', strtotime($p['online_last'])) > time()): ?>
              <span class="tile-cap" style="top:.6rem;inset-inline-start:.6rem"><span class="dot on"></span> <?= e(t('ui.online')) ?></span>
            <?php endif; ?>
          </a>
          <div class="pc-body">
            <div class="pc-name">
              <?= e($p['username']) ?>
              <?php if ($age): ?><small><?= e($age) ?></small><?php endif; ?>
            </div>
            <div class="pc-meta">
              <?= e($cat['icon']) ?>
              <?php if ($loc): ?><span>📍 <?= e(implode(', ', $loc)) ?></span><?php else: ?><span>📍 <?= e(t('ui.location_private')) ?></span><?php endif; ?>
            </div>
            <?php if ($p['bio']): ?>
              <p class="pc-bio"><?= e($p['bio']) ?></p>
            <?php endif; ?>
            <div class="pchips">
              <span class="chip chip-pink"><?= e($cat['label']) ?></span>
              <?php if (!empty($p['interests'])): ?>
                <span class="chip"><?= e(array_slice(explode(',', $p['interests']),0,2)[0] ?? '') ?></span>
              <?php endif; ?>
            </div>
            <div class="pc-foot" style="margin-top:1rem">
              <a class="btn btn-ghost btn-sm" href="/profile.php?id=<?= (int)$p['id'] ?>"><?= e(t('ui.view_profile')) ?></a>
              <form method="post" action="/profile.php?id=<?= (int)$p['id'] ?>" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="like">
                <button class="btn btn-outline btn-sm" type="submit" <?= $p['i_liked']?'disabled':'' ?>>
                  <?= $p['i_liked'] ? '♥ ' . e(t('ui.liked')) : '♡ ' . e(t('ui.like')) ?>
                </button>
              </form>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <?php if ($pg['pages'] > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($pg['page'] > 1): ?>
            <a href="<?= e(qs(['page'=>$pg['page']-1])) ?>">&lsaquo; <?= e(t('ui.prev')) ?></a>
          <?php endif; ?>
          <?php for ($i=1;$i<=$pg['pages'];$i++):
            if ($i===1 || $i===$pg['pages'] || abs($i-$pg['page'])<=2): ?>
              <span class="<?= $i===$pg['page']?'cur':'' ?>">
                <?= $i===$pg['page'] ? $i : '<a href="'.e(qs(['page'=>$i])).'">'.$i.'</a>' ?>
              </span>
            <?php elseif (abs($i-$pg['page'])===3): ?>
              <span class="gap">…</span>
          <?php endif; endfor; ?>
          <?php if ($pg['page'] < $pg['pages']): ?>
            <a href="<?= e(qs(['page'=>$pg['page']+1])) ?>"><?= e(t('ui.next')) ?> &rsaquo;</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
