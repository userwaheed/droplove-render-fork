/* ============================================================
   app.js - mobile nav, back-to-top, toasts, password strength,
   photo lightbox, image fallback, message autoscroll.
   Framework-free. No build step.
   ============================================================ */
(function () {
  'use strict';

  var doc = document;

  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

  function toast(msg, kind) {
    var wrap = $('#toastWrap');
    if (!wrap) {
      wrap = doc.createElement('div');
      wrap.id = 'toastWrap';
      wrap.className = 'toast-wrap';
      wrap.setAttribute('aria-live', 'polite');
      doc.body.appendChild(wrap);
    }
    var el = doc.createElement('div');
    el.className = 'toast ' + (kind === 'ok' ? 'ok' : kind === 'err' ? 'err' : '');
    el.textContent = msg;
    wrap.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity .3s, transform .3s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(8px)';
      setTimeout(function () { el.remove(); }, 320);
    }, 3600);
  }
  window.vkmToast = toast;

  /* mobile nav */
  var toggle = $('#navToggle'), nav = $('#mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    $all('a', nav).forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); });
    });
  }

  /* back to top */
  var top = $('#toTop');
  if (top) {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        top.classList.toggle('show', window.scrollY > 600);
        ticking = false;
      });
    }, { passive: true });
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  /* dismissible flash banners */
  $all('.flash-close').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = b.closest('.flash');
      if (f) { f.style.transition = 'opacity .2s'; f.style.opacity = '0'; setTimeout(function () { f.remove(); }, 200); }
    });
  });

  /* broken-image fallback */
  $all('img[data-fb]').forEach(function (img) {
    img.addEventListener('error', function () {
      if (img.dataset.done) return;
      img.dataset.done = '1';
      img.style.minHeight = (parseInt(img.getAttribute('height') || '0', 10)) + 'px';
      img.src = 'assets/img/avatar-placeholder.svg';
    });
  });

  /* photo lightbox */
  $all('.photolist img').forEach(function (img) {
    img.addEventListener('click', function (e) {
      e.preventDefault();
      var box = doc.createElement('div');
      box.style.cssText = 'position:fixed;inset:0;z-index:200;background:rgba(10,3,8,.92);display:flex;align-items:center;justify-content:center;padding:2rem;cursor:zoom-out';
      var big = doc.createElement('img');
      big.src = img.getAttribute('data-full') || img.src;
      big.style.cssText = 'max-width:100%;max-height:100%;border-radius:12px;box-shadow:0 30px 90px rgba(0,0,0,.7)';
      box.appendChild(big);
      var close = function () { box.remove(); doc.removeEventListener('keydown', onkey); doc.body.style.overflow = ''; };
      var onkey = function (ev) { if (ev.key === 'Escape') close(); };
      box.addEventListener('click', close);
      doc.addEventListener('keydown', onkey);
      doc.body.appendChild(box);
      doc.body.style.overflow = 'hidden';
    });
  });

  /* password strength meter */
  $all('input[name="password"][type="password"]').forEach(function (inp) {
    var out = $('#pwStrength');
    if (!out) return;
    inp.addEventListener('input', function () {
      var v = inp.value, score = 0;
      if (v.length >= 8) score++;
      if (v.length >= 12) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      var labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong', 'Excellent'];
      var colors = ['#ff5f6d', '#ff5f6d', '#f5b041', '#f5d76e', '#35d399', '#35d399'];
      var i = Math.max(1, Math.min(6, score));
      out.textContent = v ? labels[i] : '';
      out.style.color = colors[i];
    });
  });

  /* confirm destructive actions */
  $all('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* messages: Enter to send */
  var msgForm = $('#msgForm'), msgInput = $('#msgBody'), chatBody = $('#chatBody');
  if (msgInput) {
    msgInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (msgForm) msgForm.submit(); }
    });
  }
  if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;

  /* language bar: dropdown toggle + click-outside close */
  var langTrigger = $('#langTrigger'), langDropdown = $('#langDropdown');
  if (langTrigger && langDropdown) {
    langTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = langTrigger.getAttribute('aria-expanded') === 'true';
      langTrigger.setAttribute('aria-expanded', open ? 'false' : 'true');
      langDropdown.classList.toggle('open', !open);
    });
    /* close when clicking a language option */
    $all('.lang-option', langDropdown).forEach(function (link) {
      link.addEventListener('click', function () {
        langDropdown.classList.remove('open');
        langTrigger.setAttribute('aria-expanded', 'false');
      });
    });
    /* close on outside click */
    doc.addEventListener('click', function (e) {
      if (!langDropdown.contains(e.target) && e.target !== langTrigger) {
        langDropdown.classList.remove('open');
        langTrigger.setAttribute('aria-expanded', 'false');
      }
    });
    /* close on Escape */
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && langDropdown.classList.contains('open')) {
        langDropdown.classList.remove('open');
        langTrigger.setAttribute('aria-expanded', 'false');
        langTrigger.focus();
      }
    });
  }

  /* footer lang form already has correct action via PHP (current REQUEST_URI);
     no JS needed — the form submits normally on change. */
})();
