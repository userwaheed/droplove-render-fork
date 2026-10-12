<?php
/**
 * _layout_bottom.php — Footer + scripts. Included at the end of every view.
 */
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand" href="/">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
            <path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.2 3 5 6.2 5c2 0 3.4 1.2 4.1 2.5h1.4C12.4 6.2 13.8 5 15.8 5 19 5 21.1 8.2 20.6 11.8 18.5 16.4 12 21 12 21z" fill="#ff5fa2"/>
          </svg>
        </span>
        <span class="brand-name"><?= e(APP_NAME) ?></span>
      </a>
      <p class="footer-tag"><?= e(t('footer.tag')) ?></p>
    </div>

    <nav class="footer-col" aria-label="Account">
      <h3><?= e(t('footer.account')) ?></h3>
      <a href="/register.php"><?= e(t('nav.create_profile')) ?></a>
      <a href="/login.php"><?= e(t('nav.sign_in')) ?></a>
      <a href="/edit_profile.php"><?= e(t('footer.profile_settings')) ?></a>
      <a href="/dashboard.php"><?= e(t('nav.dashboard')) ?></a>
    </nav>

    <nav class="footer-col" aria-label="Explore">
      <h3><?= e(t('footer.explore')) ?></h3>
      <?php foreach (CATEGORIES as $k => $c): ?>
        <a href="/discover.php?category=<?= e($k) ?>"><?= e($c['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <nav class="footer-col" aria-label="Support">
      <h3><?= e(t('footer.support')) ?></h3>
      <a href="/#safety"><?= e(t('footer.safety')) ?></a>
      <a href="/terms.php"><?= e(t('footer.terms')) ?></a>
      <a href="/privacy.php"><?= e(t('footer.privacy')) ?></a>
      <a href="/contact.php"><?= e(t('footer.contact')) ?></a>
    </nav>

    <div class="footer-col footer-lang-col">
      <h3><?= e(t('footer.language')) ?></h3>
      <form method="get" action="<?= e($_SERVER['REQUEST_URI']) ?>" class="lang-form">
        <?php
          // Preserve all existing GET params except 'lang'
          $footerParams = $_GET;
          unset($footerParams['lang']);
          foreach ($footerParams as $k => $v): ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endforeach; ?>
        <select name="lang" class="lang-select" onchange="this.form.submit()" aria-label="<?= e(t('footer.select_lang')) ?>">
          <?php foreach (LANGS as $code => [$name, $r]): ?>
            <option value="<?= e($code) ?>" <?= $code === $lang ? 'selected' : '' ?>><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <p class="footer-note"><?= e(t('footer.lang_note', ['languages' => count(LANGS), 'categories' => count(CATEGORIES)])) ?></p>
    </div>
  </div>

  <div class="container footer-base">
    <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · v<?= e(APP_VERSION) ?> · 18+ only</p>
    <p class="footer-base-note"><?= e(t('footer.base')) ?></p>
  </div>
</footer>

<button id="toTop" class="to-top" type="button" aria-label="Back to top">&uarr;</button>

<script src="/assets/js/app.js?v=<?= e((string)filemtime(BASE_PATH . '/assets/js/app.js')) ?>" defer></script>
<script>
  document.documentElement.setAttribute('dir', '<?= $rtl ? 'rtl' : 'ltr' ?>');
</script>
</body>
</html>
