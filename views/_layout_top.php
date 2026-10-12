<?php
/**
 * _layout_top.php — <head>, theme, header + flash messages.
 * Included by every view. Requires $title to be set.
 */

declare(strict_types=1);

$lang = current_lang();
$rtl  = is_rtl();
$me   = current_user();
$docTitle = $title . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= $rtl ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#14070f">
<title><?= e($docTitle) ?></title>
<meta name="description" content="<?= e(APP_TAGLINE) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?= e((string)filemtime(BASE_PATH . '/assets/css/style.css')) ?>">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php if (in_array($title ?? '', ['Dashboard', 'Discover'], true)): ?>
<meta http-equiv="refresh" content="60">
<?php endif; ?>
</head>
<body class="<?= $rtl ? 'rtl' : 'ltr' ?>">

<!-- Skip link for keyboard users -->
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>

<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="/">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none">
          <path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.2 3 5 6.2 5c2 0 3.4 1.2 4.1 2.5h1.4C12.4 6.2 13.8 5 15.8 5 19 5 21.1 8.2 20.6 11.8 18.5 16.4 12 21 12 21z"
                fill="url(#g1)"/>
          <defs><linearGradient id="g1" x1="2" y1="4" x2="22" y2="20">
            <stop stop-color="#ff5fa2"/><stop offset="1" stop-color="#b14cff"/>
          </linearGradient></defs>
        </svg>
      </span>
      <span class="brand-name"><?= e(APP_NAME) ?></span>
    </a>

    <nav class="main-nav" id="mainNav" aria-label="Primary">
      <a href="/discover.php" class="nav-link"><?= e(t('nav.discover')) ?></a>
      <?php if ($me): ?>
        <a href="/messages.php" class="nav-link"><?= e(t('nav.messages')) ?></a>
        <a href="/dashboard.php" class="nav-link"><?= e(t('nav.dashboard')) ?></a>
      <?php endif; ?>
      <a href="/#gallery" class="nav-link"><?= e(t('nav.gallery')) ?></a>
      <a href="/#how" class="nav-link"><?= e(t('nav.how_it_works')) ?></a>
    </nav>

    <div class="header-actions">
      <button class="nav-toggle" id="navToggle" type="button" aria-label="<?= e(t('nav.toggle_menu')) ?>" aria-expanded="false" aria-controls="mainNav">&#9776;</button>
      <?php if ($me): ?>
        <a class="nav-link" href="/profile.php?id=<?= (int)$me['id'] ?>"><?= e($me['username']) ?></a>
        <?php if ((int)$me['is_admin'] === 1): ?>
          <a class="nav-link" href="/admin.php"><?= e(t('nav.admin')) ?></a>
        <?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="/edit_profile.php"><?= e(t('nav.edit_profile')) ?></a>
        <form method="post" action="/logout.php" class="inline-form">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-ghost btn-sm"><?= e(t('nav.sign_out')) ?></button>
        </form>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="/login.php"><?= e(t('nav.sign_in')) ?></a>
        <a class="btn btn-primary btn-sm" href="/register.php"><?= e(t('nav.create_profile')) ?></a>
      <?php endif; ?>

      <!-- Language bar -->
      <div class="lang-bar">
        <button
          class="lang-trigger"
          type="button"
          aria-label="<?= e(t('lang.switch_to')) ?>"
          aria-expanded="false"
          aria-controls="langDropdown"
          id="langTrigger"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
          <span id="langTriggerLabel"><?= e(lang_name($lang)) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <div class="lang-dropdown" id="langDropdown" role="menu" aria-label="<?= e(t('lang.cur')) ?>">
          <div class="lang-dropdown-header"><?= e(t('lang.cur')) ?></div>
          <?php foreach (LANGS as $code => [$name, $r]): ?>
            <?php
              // Build URL: keep current lang param, update if present, keep other GET params
              $params = $_GET;
              $params['lang'] = $code;
            ?>
            <a
              class="lang-option<?= $code === $lang ? ' cur' : '' ?>"
              href="?<?= http_build_query($params) ?>"
              data-lang="<?= e($code) ?>"
              <?= $code === $lang ? 'aria-current="true"' : '' ?>
            >
              <span><?= e($name) ?></span>
              <span class="lang-native"><?= e($code === $lang ? '✓' : '') ?></span>
            </a>
          <?php endforeach; ?>
          <div style="font-size:.75rem;color:var(--muted-2);padding:.4rem .6rem 0;line-height:1.4">
            <?= e(t('lang.note')) ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</header>

<?php foreach (['error','info','success'] as $ft): $fm = get_flash($ft); if ($fm): ?>
  <div class="container">
    <div class="flash flash-<?= e($ft) ?>" role="status">
      <span><?= e($fm) ?></span>
      <button class="flash-close" type="button" aria-label="<?= e(t('ui.dismiss')) ?>">&times;</button>
    </div>
  </div>
<?php endif; endforeach; ?>

<main id="main">
