<?php
/**
 * 404 view.
 */
$title = 'Not found';
require __DIR__ . '/_layout_top.php';
?>

<div class="notfound">
  <div class="code" aria-hidden="true">404</div>
  <h2>This page doesn't exist</h2>
  <p class="text-muted" style="max-width:440px;margin:0 auto 2rem">
    The profile you're looking for may have been removed, or the link is wrong.
  </p>
  <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-primary" href="/discover.php">Browse profiles</a>
    <a class="btn btn-ghost" href="/">Back home</a>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
