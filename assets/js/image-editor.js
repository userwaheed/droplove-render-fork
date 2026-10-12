/* ============================================================
   image-editor.js - Zoom, pan, and filter editor for profile photos.
   Opens a modal with a canvas preview, zoom controls, pan, and CSS filters.
   ============================================================ */
(function () {
  'use strict';

  var doc = document;

  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

  /* ---- State ---- */
  var state = {
    img: null,
    scale: 1,
    minScale: 1,
    maxScale: 4,
    panX: 0,
    panY: 0,
    dragging: false,
    dragStart: null,
    filter: 'none',
    filters: [
      { id: 'none', label: 'Original', css: 'none' },
      { id: 'grayscale', label: 'Grayscale', css: 'grayscale(100%)' },
      { id: 'sepia', label: 'Sepia', css: 'sepia(100%)' },
      { id: 'invert', label: 'Invert', css: 'invert(100%)' },
      { id: 'brightness-125', label: 'Brighter', css: 'brightness(125%)' },
      { id: 'brightness-75', label: 'Darker', css: 'brightness(75%)' },
      { id: 'contrast-150', label: 'Contrast', css: 'contrast(150%)' },
      { id: 'saturate-200', label: 'Vibrant', css: 'saturate(200%)' },
      { id: 'blur-2', label: 'Blur', css: 'blur(2px)' },
      { id: 'hue-90', label: 'Hue Shift', css: 'hue-rotate(90deg)' },
      { id: 'warm', label: 'Warm', css: 'sepia(30%) saturate(150%) brightness(110%)' },
      { id: 'cool', label: 'Cool', css: 'hue-rotate(180deg) saturate(120%)' },
      { id: 'vintage', label: 'Vintage', css: 'sepia(50%) contrast(120%) brightness(90%)' },
      { id: 'noir', label: 'Noir', css: 'grayscale(100%) contrast(150%)' }
    ],
    container: null,
    canvas: null,
    ctx: null,
    modal: null
  };

  /* ---- Init triggers ---- */
  $all('[data-img-edit]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var src = btn.getAttribute('data-img-src') || '';
      var alt = btn.getAttribute('data-img-alt') || 'Photo';
      if (!src || src.indexOf('avatar-placeholder') !== -1) {
        // Try to find adjacent img
        var parent = btn.closest('.card, .edit-img-wrap, .photolist');
        if (parent) {
          var img = parent.querySelector('img');
          if (img) src = img.src;
        }
      }
      if (src) openEditor(src, alt);
    });
  });

  /* ---- Open editor ---- */
  function openEditor(src, alt) {
    closeModal(); // clean up any existing

    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function () {
      state.img = img;
      state.scale = 1;
      state.panX = 0;
      state.panY = 0;
      state.filter = 'none';
      buildModal();
      render();
      bindEvents();
    };
    img.onerror = function () {
      vkmToast('Could not load image', 'err');
    };
    img.src = src;
  }

  /* ---- Build modal DOM ---- */
  function buildModal() {
    var modal = doc.createElement('div');
    modal.className = 'img-editor-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-label', 'Image editor');

    modal.innerHTML =
      '<div class="img-editor-backdrop"></div>' +
      '<div class="img-editor-panel">' +
        '<div class="img-editor-header">' +
          '<h3>Photo Editor</h3>' +
          '<button class="img-editor-close" type="button" aria-label="Close editor">&#10005;</button>' +
        '</div>' +
        '<div class="img-editor-body">' +
          '<div class="img-editor-viewport">' +
            '<canvas class="img-editor-canvas"></canvas>' +
          '</div>' +
          '<div class="img-editor-controls">' +
            '<div class="img-editor-zoom">' +
              '<button class="img-btn" data-action="zoom-out" type="button" title="Zoom out">−</button>' +
              '<span class="img-editor-zoom-val">100%</span>' +
              '<button class="img-btn" data-action="zoom-in" type="button" title="Zoom in">+</button>' +
              '<button class="img-btn" data-action="zoom-reset" type="button" title="Reset">⟲</button>' +
            '</div>' +
            '<div class="img-editor-filters" role="tablist" aria-label="Filters"></div>' +
            '<div class="img-editor-actions">' +
              '<button class="btn btn-primary btn-sm" data-action="apply" type="button">Apply</button>' +
              '<button class="btn btn-ghost btn-sm" data-action="cancel" type="button">Cancel</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';

    // Build filter buttons
    var filterBar = modal.querySelector('.img-editor-filters');
    state.filters.forEach(function (f) {
      var b = doc.createElement('button');
      b.type = 'button';
      b.className = 'img-filter-btn' + (f.id === 'none' ? ' active' : '');
      b.textContent = f.label;
      b.setAttribute('data-filter', f.id);
      b.setAttribute('role', 'tab');
      b.setAttribute('aria-selected', f.id === 'none' ? 'true' : 'false');
      b.title = f.css;
      filterBar.appendChild(b);
    });

    state.modal = modal;
    state.canvas = modal.querySelector('.img-editor-canvas');
    state.ctx = state.canvas.getContext('2d');
    state.container = modal.querySelector('.img-editor-viewport');

    doc.body.appendChild(modal);
    doc.body.style.overflow = 'hidden';

    // Animate in
    requestAnimationFrame(function () {
      modal.classList.add('open');
    });
  }

  /* ---- Render image to canvas ---- */
  function render() {
    if (!state.img || !state.canvas || !state.ctx) return;

    var cw = state.container.clientWidth;
    var ch = state.container.clientHeight;
    if (cw < 10) { cw = 400; ch = 400; }

    var imgW = state.img.naturalWidth;
    var imgH = state.img.naturalHeight;

    // Fit image to container initially
    var baseScale = Math.min(cw / imgW, ch / imgH);
    state.minScale = baseScale;
    var finalScale = baseScale * state.scale;

    var drawW = imgW * finalScale;
    var drawH = imgH * finalScale;

    // Center with pan offset
    var dx = (cw - drawW) / 2 + state.panX;
    var dy = (ch - drawH) / 2 + state.panY;

    // Set canvas size
    state.canvas.width = cw;
    state.canvas.height = ch;

    // Clear
    state.ctx.clearRect(0, 0, cw, ch);

    // Apply filter
    state.ctx.filter = state.filter;

    // Draw
    state.ctx.drawImage(state.img, dx, dy, drawW, drawH);

    // Reset filter
    state.ctx.filter = 'none';

    // Update zoom display
    var zoomVal = state.modal.querySelector('.img-editor-zoom-val');
    if (zoomVal) zoomVal.textContent = Math.round(state.scale * 100) + '%';
  }

  /* ---- Bind events ---- */
  function bindEvents() {
    if (!state.modal) return;

    // Close button
    var closeBtn = state.modal.querySelector('.img-editor-close');
    var backdrop = state.modal.querySelector('.img-editor-backdrop');
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // Zoom buttons
    state.modal.querySelectorAll('[data-action]').forEach(function (btn) {
      var action = btn.getAttribute('data-action');
      btn.addEventListener('click', function () {
        if (action === 'zoom-in') {
          state.scale = Math.min(state.maxScale, state.scale * 1.25);
          render();
        } else if (action === 'zoom-out') {
          state.scale = Math.max(0.5, state.scale * 0.8);
          render();
        } else if (action === 'zoom-reset') {
          state.scale = 1;
          state.panX = 0;
          state.panY = 0;
          render();
        } else if (action === 'apply') {
          applyFilter();
        } else if (action === 'cancel') {
          closeModal();
        }
      });
    });

    // Filter buttons
    state.modal.querySelectorAll('.img-filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var filterId = btn.getAttribute('data-filter');
        var filter = state.filters.find(function (f) { return f.id === filterId; });
        if (filter) {
          state.filter = filter.css;
          // Update active state
          state.modal.querySelectorAll('.img-filter-btn').forEach(function (b) {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
          });
          btn.classList.add('active');
          btn.setAttribute('aria-selected', 'true');
          render();
        }
      });
    });

    // Mouse wheel zoom
    state.container.addEventListener('wheel', function (e) {
      e.preventDefault();
      var delta = e.deltaY > 0 ? 0.85 : 1.18;
      state.scale = Math.max(0.5, Math.min(state.maxScale, state.scale * delta));
      render();
    }, { passive: false });

    // Pan (mouse)
    state.container.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      state.dragging = true;
      state.dragStart = { x: e.clientX - state.panX, y: e.clientY - state.panY };
      state.container.style.cursor = 'grabbing';
      e.preventDefault();
    });

    doc.addEventListener('mousemove', function (e) {
      if (!state.dragging) return;
      state.panX = e.clientX - state.dragStart.x;
      state.panY = e.clientY - state.dragStart.y;
      render();
    });

    doc.addEventListener('mouseup', function () {
      if (state.dragging) {
        state.dragging = false;
        state.container.style.cursor = 'grab';
      }
    });

    // Pan (touch)
    state.container.addEventListener('touchstart', function (e) {
      if (e.touches.length === 1) {
        state.dragging = true;
        state.dragStart = { x: e.touches[0].clientX - state.panX, y: e.touches[0].clientY - state.panY };
      }
    }, { passive: true });

    doc.addEventListener('touchmove', function (e) {
      if (!state.dragging || e.touches.length !== 1) return;
      state.panX = e.touches[0].clientX - state.dragStart.x;
      state.panY = e.touches[0].clientY - state.dragStart.y;
      render();
    }, { passive: true });

    doc.addEventListener('touchend', function () {
      state.dragging = false;
    });

    // Keyboard
    doc.addEventListener('keydown', function onKey(e) {
      if (e.key === 'Escape') {
        closeModal();
        doc.removeEventListener('keydown', onKey);
      }
    });

    // Resize
    window.addEventListener('resize', render);
  }

  /* ---- Apply filter (just close for now, visual only) ---- */
  function applyFilter() {
    // For now, the filter is visual only. In the future, we can add
    // server-side filter application via GD/Imagick.
    vkmToast('Filter applied: ' + state.filter, 'ok');
    closeModal();
  }

  /* ---- Close modal ---- */
  function closeModal() {
    if (!state.modal) return;
    state.modal.classList.remove('open');
    setTimeout(function () {
      if (state.modal) {
        state.modal.remove();
        state.modal = null;
      }
      doc.body.style.overflow = '';
    }, 200);
  }

  // Expose for external use
  window.vkmImageEditor = { open: openEditor, close: closeModal };
})();
