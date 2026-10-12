<?php
/**
 * Admin view — moderation dashboard with user list, search, filters, reports.
 * Variables provided by admin.php:
 *   $me, $search, $status, $catFilter, $pg, $users, $reports, $reportCount
 *   $totalUsers, $activeUsers, $admins, $suspended, $totalLikes, $totalMessages
 *   $newestSignups, $resetPw
 */
$title = 'Admin';
require __DIR__ . '/_layout_top.php';
$statusChip = function (array $u): string {
    if ((int)$u['banned'] === 1) {
        return '<span class="chip chip-pink">Banned / Suspended</span>';
    }
    if ((int)$u['verified'] === 1) {
        // fall through to is_active check, but note verified status
    }
    if ((int)$u['is_active'] === 1) {
        $base = '<span class="chip chip-green">Active</span>';
        if ((int)$u['verified'] === 1) {
            $base = '<span class="chip chip-green">Active ✓</span>';
        }
        return $base;
    }
    return '<span class="chip">Inactive</span>';
};
?>

<div class="container">
  <div style="padding-top:2.4rem;padding-bottom:3rem">

    <!-- Flash messages -->
    <?php foreach (['error','info','success'] as $ft): $fm = get_flash($ft); if ($fm): ?>
      <div class="flash flash-<?= e($ft) ?>" role="status">
        <span><?= e($fm) ?></span>
      </div>
    <?php endif; endforeach; ?>

    <!-- One-shot password display -->
    <?php if (!empty($resetPw)): ?>
      <div class="flash flash-info" role="status" style="margin-top:1rem">
        <div><strong>Password reset for @<?= e($resetPw['username']) ?></strong></div>
        <div style="margin-top:.4rem">New password: <code style="padding:.2rem .5rem;background:var(--surface-3);border-radius:4px;font-size:1rem;font-weight:700"><?= e($resetPw['password']) ?></code></div>
        <div style="margin-top:.4rem;font-size:.85rem">This password will not be shown again.</div>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="section-head" style="margin-bottom:2rem">
      <span class="eyebrow">Moderation</span>
      <h2 style="font-size:2rem">Admin console</h2>
      <p class="lead" style="margin-top:.5rem">User management, reports, and site stats for <?= e(APP_NAME) ?>.</p>
    </div>

    <!-- Summary stats -->
    <div class="stat-grid" style="margin-bottom:2rem">
      <div class="stat panel card-pad"><div class="n"><?= number_format($totalUsers) ?></div><div class="l">Total users</div></div>
      <div class="stat panel card-pad"><div class="n"><?= number_format($activeUsers) ?></div><div class="l">Active members</div></div>
      <div class="stat panel card-pad"><div class="n"><?= number_format($admins) ?></div><div class="l">Admins</div></div>
      <div class="stat panel card-pad"><div class="n"><?= number_format($suspended) ?></div><div class="l">Suspended / blocked</div></div>
      <div class="stat panel card-pad"><div class="n"><?= number_format($totalLikes) ?></div><div class="l">Total likes</div></div>
      <div class="stat panel card-pad"><div class="n"><?= number_format($totalMessages) ?></div><div class="l">Total messages</div></div>
    </div>

    <!-- Newest signups -->
    <?php if (!empty($newestSignups)): ?>
      <div class="panel card-pad" style="margin-bottom:1.6rem">
        <h3 style="margin:0 0 .8rem">Newest signups</h3>
        <ul style="display:flex;flex-direction:column;gap:.4rem;font-size:.9rem">
          <?php foreach ($newestSignups as $ns): ?>
            <li style="display:flex;gap:.6rem;align-items:center">
              <img class="avatar sm" src="<?= e(avatar_url($ns['avatar'] ?: null, $ns['username'])) ?>" alt="<?= e($ns['username']) ?>">
              <span><?= e($ns['username']) ?> (<?= e($ns['email']) ?>)</span>
              <span class="text-muted" style="margin-inline-start:auto;font-size:.8rem"><?= e(date('M j, H:i', strtotime($ns['created_at']))) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Search & filter -->
    <form class="card card-pad filters" method="get" action="/admin.php" style="margin-bottom:1.4rem">
      <div class="field grow">
        <label for="f_search">Search</label>
        <input class="input" type="search" id="f_search" name="search" value="<?= e($search) ?>" placeholder="Username, email, or name…">
      </div>
      <div class="field">
        <label for="f_status">Status</label>
        <select class="select" id="f_status" name="status">
          <option value="all" <?= $status==='all'?'selected':'' ?>>All statuses</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
          <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended / blocked</option>
        </select>
      </div>
      <div class="field">
        <label for="f_cat">Category</label>
        <select class="select" id="f_cat" name="cat">
          <option value="all" <?= $catFilter==='all'?'selected':'' ?>>All categories</option>
          <?php foreach (CATEGORIES as $k => $c): ?>
            <option value="<?= e($k) ?>" <?= $catFilter===$k?'selected':'' ?>><?= e($c['icon']) ?> <?= e($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="min-width:120px">
        <label>&nbsp;</label>
        <button class="btn btn-primary btn-block" type="submit">Filter</button>
      </div>
    </form>

    <!-- User table -->
    <div class="panel card-pad" style="margin-bottom:2rem">
      <h3 style="margin:0 0 1rem">
        Members
        <?php if ($search): ?><span class="text-muted" style="font-weight:400">· results for "<?= e($search) ?>"</span><?php endif; ?>
        <?php if ($status !== 'all'): ?><span class="text-muted" style="font-weight:400">· <?= e($status) ?></span><?php endif; ?>
        <?php if ($catFilter !== 'all'): ?><span class="text-muted" style="font-weight:400">· <?= e(CATEGORIES[$catFilter]['icon'] . ' ' . CATEGORIES[$catFilter]['label']) ?></span><?php endif; ?>
      </h3>

      <?php if (empty($users)): ?>
        <div class="empty">
          <div class="icon" aria-hidden="true">🔍</div>
          <h3>No members match those filters</h3>
          <p class="text-muted">Try clearing the search or adjusting the filters.</p>
          <a class="btn btn-ghost btn-sm" href="/admin.php" style="margin-top:1rem">Clear all filters</a>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:.88rem;min-width:820px">
            <thead>
              <tr style="text-align:start;color:var(--muted);border-bottom:1px solid var(--border)">
                <th style="padding:.6rem">User</th>
                <th style="padding:.6rem">Category</th>
                <th style="padding:.6rem">Status</th>
                <th style="padding:.6rem">Age</th>
                <th style="padding:.6rem">Likes in</th>
                <th style="padding:.6rem">Messages</th>
                <th style="padding:.6rem">Joined</th>
                <th style="padding:.6rem;text-align:end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u):
                $ucat    = CATEGORIES[$u['category']] ?? ['icon'=>'•','label'=>$u['category']];
                $isMe    = (int)$u['id'] === (int)$me['id'];
                $isAdmin = (int)$u['is_admin'] === 1;
                $age     = user_age($u['birth_date']);
              ?>
                <tr style="border-bottom:1px solid var(--border-soft)">
                  <td style="padding:.6rem">
                    <div style="display:flex;gap:.6rem;align-items:center">
                      <img class="avatar sm" src="<?= e(avatar_url($u['avatar'] ?: null, $u['username'])) ?>" alt="<?= e($u['username']) ?>" data-fb>
                      <div style="min-width:0">
                        <div style="font-weight:700">
                          <a href="/admin.php?id=<?= (int)$u['id'] ?>"><?= e($u['username']) ?></a>
                          <?php if ($isAdmin): ?><span class="chip chip-violet" style="margin-inline-start:.4rem">Admin</span><?php endif; ?>
                          <?php if ((int)$u['verified'] === 1): ?><span class="chip chip-green" style="margin-inline-start:.4rem">✓</span><?php endif; ?>
                        </div>
                        <div class="text-muted" style="font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px"><?= e($u['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="padding:.6rem"><?= e($ucat['icon']) ?> <?= e($ucat['label']) ?></td>
                  <td style="padding:.6rem"><?= $statusChip($u) ?></td>
                  <td style="padding:.6rem"><?= $age ? e($age) : '—' ?></td>
                  <td style="padding:.6rem">♥ <?= (int)$u['likes_in'] ?></td>
                  <td style="padding:.6rem">✉ <?= (int)$u['msgs'] ?></td>
                  <td style="padding:.6rem;color:var(--muted)"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                  <td style="padding:.6rem;text-align:end;white-space:nowrap">
                    <?php if ($isMe): ?>
                      <span class="text-muted" style="font-size:.8rem">— you —</span>
                    <?php elseif ($isAdmin): ?>
                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Revoke admin privileges?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="toggle_admin">
                        <button class="btn btn-ghost btn-sm" type="submit" title="Revoke admin">Admin ↓</button>
                      </form>
                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Reset this admin\'s password? The new password will be shown once.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="reset">
                        <button class="btn btn-ghost btn-sm" type="submit">Reset PW</button>
                      </form>
                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Permanently delete this admin? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                      </form>
                    <?php else: ?>
                      <?php if ((int)$u['banned'] === 1): ?>
                        <form method="post" action="/admin.php" style="display:inline"
                              onsubmit="return confirm('Unsuspend this user?')">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="unsuspend">
                          <button class="btn btn-ghost btn-sm" type="submit">Unsuspend</button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="/admin.php" style="display:inline"
                              onsubmit="return confirm('Suspend (block) this user? They will be unable to log in or appear anywhere.')">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="suspend">
                          <button class="btn btn-ghost btn-sm" type="submit" title="Suspend / block">Suspend</button>
                        </form>
                      <?php endif; ?>

                      <?php if ((int)$u['is_active'] === 0): ?>
                        <form method="post" action="/admin.php" style="display:inline"
                              onsubmit="return confirm('Reactivate this user in Discover?')">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="activate">
                          <button class="btn btn-ghost btn-sm" type="submit">Activate</button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="/admin.php" style="display:inline"
                              onsubmit="return confirm('Hide this profile from Discover? (They remain logged-in-able.)')">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                          <input type="hidden" name="action" value="deactivate">
                          <button class="btn btn-ghost btn-sm" type="submit" title="Deactivate">Deactivate</button>
                        </form>
                      <?php endif; ?>

                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Toggle verified badge for this user?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="toggle_verified">
                        <button class="btn btn-ghost btn-sm" type="submit" title="Toggle verified">
                          <?php if ((int)$u['verified'] === 1): ?>✓ Unverify<?php else: ?>☆ Verify<?php endif; ?>
                        </button>
                      </form>

                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Reset this user\'s password? The new password will be shown once on the next screen.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="reset">
                        <button class="btn btn-ghost btn-sm" type="submit">Reset PW</button>
                      </form>

                      <form method="post" action="/admin.php" style="display:inline"
                            onsubmit="return confirm('Permanently delete this user and all their data? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['pages'] > 1): ?>
          <nav class="pager" aria-label="Pagination" style="margin-top:1.2rem">
            <?php if ($pg['page'] > 1): ?>
              <a href="<?= e(qs(['page'=>$pg['page']-1])) ?>">&lsaquo; <?= e(t('ui.prev')) ?></a>
            <?php endif; ?>
            <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
              <?php if ($i===1 || $i===$pg['pages'] || abs($i-$pg['page'])<=2): ?>
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
      </div>
    </div>

    <!-- Reports -->
    <?php if (!empty($reports)): ?>
      <div class="panel card-pad" style="margin-bottom:1.6rem">
        <h3 style="margin:0 0 1rem">
          Reports
          <span class="text-muted" style="font-weight:400;font-size:.85rem">· <?= number_format($reportCount) ?> open</span>
        </h3>
        <div style="overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:.88rem;min-width:640px">
            <thead>
              <tr style="text-align:start;color:var(--muted);border-bottom:1px solid var(--border)">
                <th style="padding:.6rem">Status</th>
                <th style="padding:.6rem">Reporter</th>
                <th style="padding:.6rem">Target</th>
                <th style="padding:.6rem">Reason</th>
                <th style="padding:.6rem">When</th>
                <th style="padding:.6rem"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reports as $r): ?>
                <tr style="border-bottom:1px solid var(--border-soft)">
                  <td style="padding:.6rem">
                    <?php if ($r['status'] === 'open'): ?>
                      <span class="chip chip-pink">Open</span>
                    <?php else: ?>
                      <span class="chip chip-green">Resolved</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:.6rem"><?= e($r['reporter_name'] ?: 'deleted') ?></td>
                  <td style="padding:.6rem">
                    <?php if ($r['target_name']): ?>
                      <a href="/profile.php?id=<?= (int)$r['target_id'] ?>"><?= e($r['target_name']) ?></a>
                    <?php else: ?>
                      <span class="text-muted">deleted</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:.6rem;max-width:260px"><?= e($r['reason']) ?></td>
                  <td style="padding:.6rem;color:var(--muted)"><?= e(date('M j, H:i', strtotime($r['created_at']))) ?></td>
                  <td style="padding:.6rem">
                    <?php if ($r['status'] === 'open'): ?>
                      <form method="post" action="/admin.php" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="resolve_report" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-ghost btn-sm" type="submit">Resolve</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="panel card-pad" style="margin-bottom:1.6rem">
        <p class="text-muted" style="margin:0">No reports. 👍</p>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
