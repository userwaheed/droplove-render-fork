<?php
/**
 * Messages view — inbox + thread.
 */
$title = 'Messages';
$showInbox = !$withId;
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding-top:1.6rem;padding-bottom:2rem">
    <div class="chat<?= $showInbox ? ' inbox-mode' : ' thread-mode' ?>" id="chat">

      <!-- Sidebar / inbox -->
      <div class="chat-side<?= $showInbox ? '' : '' ?>" id="chatSide">
        <div style="padding:1.1rem 1.2rem;border-bottom:1px solid var(--border-soft);position:sticky;top:0;background:var(--surface)">
          <h3><?= e(t('msg.inbox')) ?></h3>
        </div>

        <?php if (!$showInbox): ?>
          <a class="peer-row active" href="/messages.php">
            <img class="avatar sm" src="<?= e(avatar_url($peer['avatar'] ?: null, $peer['username'])) ?>" alt="" data-fb>
            <div class="pr-body">
              <div class="pr-name"><?= e(t('msg.back')) ?></div>
            </div>
          </a>
        <?php endif; ?>

        <?php if ($showInbox): ?>
          <?php if (empty($inbox)): ?>
            <div class="empty" style="padding:2.5rem 1.5rem">
              <div class="icon" aria-hidden="true">💬</div>
              <p><?= e(t('msg.none')) ?></p>
              <p class="text-muted" style="font-size:.85rem"><?= e(t('msg.howto')) ?></p>
              <a class="btn btn-primary btn-sm" href="/discover.php"><?= e(t('msg.find_people')) ?></a>
            </div>
          <?php else: ?>
            <?php foreach ($inbox as $row): ?>
              <a class="peer-row" href="/messages.php?with=<?= (int)$row['peer_id'] ?>">
                <img class="avatar sm" src="<?= e(avatar_url($row['peer_avatar'], $row['peer_name'])) ?>" alt="" data-fb>
                <div class="pr-body">
                  <div class="pr-name">
                    <?= e($row['peer_username']) ?>
                    <?php if (!empty($row['unread'])): ?><span class="badge-count">!</span><?php endif; ?>
                  </div>
                  <div class="li-sub"><?= e(mb_strimwidth($row['last'], 0, 34, '…', 'UTF-8')) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Conversation -->
      <div class="chat-main">
        <?php if (!$peer): ?>
          <?php if (!$showInbox): ?>
            <div class="empty" style="padding:4rem 2rem">
              <div class="icon" aria-hidden="true">🔒</div>
              <h3><?= e(t('msg.gated')) ?></h3>
              <p class="text-muted"><?= e(t('msg.gated_p')) ?></p>
              <a class="btn btn-primary" href="/discover.php"><?= e(t('dash.open_discover')) ?></a>
            </div>
          <?php else: ?>
            <div class="empty" style="padding:5rem 2rem">
              <div class="icon" aria-hidden="true">💬</div>
              <h3><?= e(t('msg.select')) ?></h3>
              <p class="text-muted"><?= e(t('msg.select_p')) ?></p>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="chat-head">
            <img class="avatar sm" src="<?= e(avatar_url($peer['avatar'] ?: null, $peer['username'])) ?>" alt="" data-fb>
            <div style="flex:1;min-width:0">
              <a href="/profile.php?id=<?= (int)$peer['id'] ?>" style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block"><?= e($peer['username']) ?></a>
              <span class="text-muted" style="font-size:.8rem">
                <?= e(CATEGORIES[$peer['category']]['icon'] ?? '') ?> <?= e(CATEGORIES[$peer['category']]['label'] ?? '') ?>
              </span>
            </div>
            <a class="btn btn-ghost btn-sm" href="/profile.php?id=<?= (int)$peer['id'] ?>"><?= e(t('ui.view_profile')) ?></a>
          </div>

          <div class="chat-body" id="chatBody">
            <?php if (empty($thread)): ?>
              <div class="empty" style="margin:auto;text-align:center;color:var(--muted)">
                <p style="margin:0"><?= e(t('msg.start', ['name' => $peer['username']])) ?></p>
              </div>
            <?php else: ?>
              <?php foreach ($thread as $m):
                $mine = (int)$m['from_id'] === (int)$me['id']; ?>
                <div class="bubble <?= $mine ? 'mine' : 'theirs' ?>">
                  <?= nl2br(e($m['body'])) ?>
                  <span class="time"><?= e(date('H:i', strtotime($m['created_at']))) ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <form class="chat-input" id="msgForm" method="post" action="/messages.php?with=<?= (int)$peer['id'] ?>">
            <?= csrf_field() ?>
            <input class="input" type="text" id="msgBody" name="body" maxlength="2000"
                   placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
                   value="<?= e($_POST['body'] ?? '') ?>">
            <button class="btn btn-primary" type="submit"><?= e(t('msg.send')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($errors['body'])): ?>
      <div class="flash flash-error" style="margin-top:1rem">⚠ <?= e($errors['body']) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
