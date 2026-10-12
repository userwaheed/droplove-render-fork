<?php
/**
 * Landing page view. Included by index.php after the layout head.
 */
$title = APP_TAGLINE;
$home = true;
require __DIR__ . '/_layout_top.php';
?>

<!-- ══════════════ HERO ══════════════ -->
<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div>
        <span class="eyebrow">&#10084; <?= e(t('hero.eyebrow', ['languages' => count(LANGS), 'communities' => count(CATEGORIES)])) ?></span>
        <h1><?= e(t('hero.title_plain')) ?> <span><?= e(t('hero.title_accent')) ?></span></h1>
        <p class="lead"><?= e(t('hero.lead')) ?></p>

        <div class="hero-cta">
          <a class="btn btn-primary btn-lg" href="/register.php"><?= e(t('hero.cta_register')) ?></a>
          <a class="btn btn-ghost btn-lg" href="/discover.php"><?= e(t('hero.cta_browse')) ?></a>
        </div>

        <div class="hero-stats">
          <div class="hero-stat"><div class="n" id="memberCount" data-base="<?= $stats['members'] ?>"><?= number_format((int)$stats['members']) ?></div><div class="l"><?= e(t('hero.stat_members')) ?></div></div>
          <div class="hero-stat"><div class="n"><?= number_format((int)$stats['likes']) ?></div><div class="l"><?= e(t('hero.stat_likes')) ?></div></div>
          <div class="hero-stat"><div class="n"><?= $countries ?></div><div class="l"><?= e(t('hero.stat_countries')) ?></div></div>
        </div>
      </div>

      <div class="hero-art" aria-hidden="true">
        <div class="tile">
          <img src="assets/img/hero-tile-1.svg" alt="" loading="lazy">
          <span class="tile-cap"><span class="dot on"></span> <?= e(t('ui.online_now')) ?></span>
        </div>
        <div class="tile">
          <img src="assets/img/hero-tile-2.svg" alt="" loading="lazy">
          <span class="tile-cap">♂♂♀ MMF</span>
        </div>
        <div class="tile">
          <img src="assets/img/hero-tile-2.svg" alt="" loading="lazy">
          <span class="tile-cap">⚧ Couples</span>
        </div>
        <div class="tile">
          <img src="assets/img/hero-tile-1.svg" alt="" loading="lazy">
          <span class="tile-cap">♀♀♂ FFM</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ CATEGORIES ══════════════ -->
<section class="section" id="categories">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.communities')) ?></span>
      <h2><?= e(t('sec.communities_h')) ?></h2>
      <p class="lead"><?= e(t('sec.communities_p')) ?></p>
    </div>

    <div class="feat-grid">
      <?php foreach (CATEGORIES as $key => $c): ?>
        <a class="feat card" href="/discover.php?category=<?= e($key) ?>">
          <div class="fi" aria-hidden="true"><?= e($c['icon']) ?></div>
          <h3><?= e($c['label']) ?></h3>
          <p><?= e(t('sec.communities_join', ['category' => $c['label']])) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════ HOW IT WORKS ══════════════ -->
<section class="section" id="how" style="background:var(--bg-2)">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.how')) ?></span>
      <h2><?= e(t('sec.how_title')) ?></h2>
    </div>

    <div class="steps">
      <div class="step" data-reveal>
        <h3><?= e(t('how.1_t')) ?></h3>
        <p><?= e(t('how.1_b')) ?></p>
      </div>
      <div class="step" data-reveal>
        <h3><?= e(t('how.2_t')) ?></h3>
        <p><?= e(t('how.2_p')) ?></p>
      </div>
      <div class="step" data-reveal>
        <h3><?= e(t('how.3_t')) ?></h3>
        <p><?= e(t('how.3_p')) ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ GALLERY ══════════════ -->
<section class="section" id="gallery">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.gallery')) ?></span>
      <h2><?= e(t('sec.gallery_h')) ?></h2>
      <p class="lead"><?= e(t('sec.gallery_p')) ?></p>
    </div>

    <div class="gallery" id="gallery-male">
      <a href="/discover.php?category=single_m"><img class="gallery-img" src="assets/img/hero-tile-1.svg" alt="Profile example" loading="lazy"><span class="tag">♂ Singles — Male</span></a>
      <a href="/discover.php?category=single_f"><img class="gallery-img" src="assets/img/hero-tile-2.svg" alt="Profile example" loading="lazy"><span class="tag">♀ Singles — Female</span></a>
      <a href="/discover.php?category=couple"><img class="gallery-img" src="assets/img/hero-tile-1.svg" alt="Profile example" loading="lazy"><span class="tag">⚧ Couples</span></a>
      <a href="/discover.php?category=mmf"><img class="gallery-img" src="assets/img/hero-tile-2.svg" alt="Profile example" loading="lazy"><span class="tag">♂♂♀ MMF</span></a>
      <a href="/discover.php?category=ffm"><img class="gallery-img" src="assets/img/hero-tile-1.svg" alt="Profile example" loading="lazy"><span class="tag">♀♀♂ FFM</span></a>
      <a href="/discover.php?category=single_m"><img class="gallery-img" src="assets/img/hero-tile-2.svg" alt="Profile example" loading="lazy"><span class="tag">♂ Singles — Male</span></a>
      <a href="/discover.php?category=couple"><img class="gallery-img" src="assets/img/hero-tile-1.svg" alt="Profile example" loading="lazy"><span class="tag">⚧ Couples</span></a>
      <a href="/discover.php?category=single_f"><img class="gallery-img" src="assets/img/hero-tile-2.svg" alt="Profile example" loading="lazy"><span class="tag">♀ Singles — Female</span></a>
      <a href="/discover.php?category=mmf"><img class="gallery-img" src="assets/img/hero-tile-1.svg" alt="Profile example" loading="lazy"><span class="tag">♂♂♀ MMF</span></a>
      <a href="/discover.php?category=single_f"><img class="gallery-img" src="assets/img/hero-tile-2.svg" alt="Profile example" loading="lazy"><span class="tag">♀ Singles — Female</span></a>
    </div>
    <p class="text-muted" style="text-align:center;margin-top:1.4rem;font-size:.85rem">
      <?= e(t('sec.gallery_note')) ?>
    </p>
  </div>
</section>

<!-- ══════════════ TESTIMONIALS ══════════════ -->
<section class="section" style="background:var(--bg-2)">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.stories')) ?></span>
      <h2><?= e(t('sec.stories_h')) ?></h2>
    </div>
    <div class="quotes">
      <div class="quote card" data-reveal>
        <div class="stars" aria-label="5 out of 5">★★★★★</div>
        <blockquote>“I'd tried four other apps. This one finally felt like it understood that not everyone is a straight single person looking for one thing.”</blockquote>
        <cite>
          <img class="avatar sm" src="assets/img/avatar-placeholder.svg" alt="" data-fb>
          <span>Lina, 29 · Couple</span>
        </cite>
      </div>
      <div class="quote card" data-reveal>
        <div class="stars" aria-label="5 out of 5">★★★★★</div>
        <blockquote>“The language switcher is huge. I met my partner while browsing in French, then we switched to Spanish. It just worked.”</blockquote>
        <cite>
          <img class="avatar sm" src="assets/img/avatar-placeholder.svg" alt="" data-fb>
          <span>Marco, 34 · Singles</span>
        </cite>
      </div>
      <div class="quote card" data-reveal>
        <div class="stars" aria-label="5 out of 5">★★★★★</div>
        <blockquote>“Profile activation, photo galleries, report buttons — it feels grown-up. No bots, no nonsense.”</blockquote>
        <cite>
          <img class="avatar sm" src="assets/img/avatar-placeholder.svg" alt="" data-fb>
          <span>Amira, 26 · MMF</span>
        </cite>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ SAFETY ══════════════ -->
<section class="section" id="safety">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.safety')) ?></span>
      <h2><?= e(t('sec.safety_h')) ?></h2>
    </div>
    <div class="safety-grid">
      <div class="safety-item" data-reveal>
        <div class="si" aria-hidden="true">🔒</div>
        <div><h3>Encrypted passwords</h3><p>Your password is hashed with a modern algorithm — never stored in plain text, never readable by us.</p></div>
      </div>
      <div class="safety-item" data-reveal>
        <div class="si" aria-hidden="true">18+</div>
        <div><h3>Strict 18+ verification</h3><p>Birth dates are validated on every signup and profile edit. Under-18s cannot create an account.</p></div>
      </div>
      <div class="safety-item" data-reveal>
        <div class="si" aria-hidden="true">🛡</div>
        <div><h3>Report &amp; block</h3><p>Every profile has a report button. Reports are reviewed and repeat offenders are suspended.</p></div>
      </div>
      <div class="safety-item" data-reveal>
        <div class="si" aria-hidden="true">✉</div>
        <div><h3>You own your data</h3><p>Export or delete your account at any time from profile settings. Deletion is permanent.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ LANGUAGES ══════════════ -->
<section class="section" id="languages" style="background:var(--bg-2)">
  <div class="container">
    <div class="section-head center" data-reveal>
      <span class="eyebrow"><?= e(t('sec.multilingual')) ?></span>
      <h2><?= e(t('sec.multilingual_h', ['languages' => count(LANGS)])) ?></h2>
      <p class="lead"><?= e(t('sec.multilingual_p')) ?></p>
    </div>
    <div class="lang-grid">
      <?php foreach (LANGS as $code => [$name, $r]): ?>
        <a class="lang-pill<?= $code === $lang ? ' cur' : '' ?>" href="/?lang=<?= e($code) ?>"><?= e($name) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════ CTA ══════════════ -->
<section class="section">
  <div class="container">
    <div class="cta-band" data-reveal>
      <h2><?= e(t('sec.cta_h')) ?></h2>
      <p class="lead"><?= e(t('sec.cta_p')) ?></p>
      <div class="hero-cta" style="justify-content:center">
        <a class="btn btn-primary btn-lg" href="/register.php"><?= e(t('hero.cta_register')) ?></a>
        <a class="btn btn-ghost btn-lg" href="/login.php"><?= e(t('nav.sign_in')) ?></a>
      </div>
      <p class="text-muted" style="margin:1rem 0 0;font-size:.85rem"><?= e(t('sec.cta_note')) ?></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
