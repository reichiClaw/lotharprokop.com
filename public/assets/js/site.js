/* Lothar Prokop Fotografie – Frontend-Ergänzungen (progressive enhancement).
   Ohne JavaScript funktionieren Navigation, Filter (Links), Galerien (Bild-Links) und Filme (externer Link). */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Einblenden beim Scrollen ---------- */
  function initReveal() {
    var items = document.querySelectorAll('.reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    items.forEach(function (el) { io.observe(el); });
    // Sicherheitsnetz: nach kurzer Zeit alles sichtbar machen, falls der Observer nicht feuert.
    setTimeout(function () {
      document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) {
        var rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight * 1.2) el.classList.add('is-visible');
      });
    }, 1200);
    window.addEventListener('pageshow', function () {
      document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) {
        if (el.getBoundingClientRect().top < window.innerHeight) el.classList.add('is-visible');
      });
    });
  }

  /* ---------- Kategoriefilter: Seite per fetch tauschen, URL aktualisieren ---------- */
  function initFilter() {
    var nav = document.querySelector('[data-filter]');
    var target = document.querySelector('[data-filter-target]');
    if (!nav || !target || !('fetch' in window)) return;

    function swap(url, push) {
      nav.classList.add('is-loading');
      target.classList.add('is-swapping');
      fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var newTarget = doc.querySelector('[data-filter-target]');
          var newNav = doc.querySelector('[data-filter]');
          var newEmpty = doc.querySelector('.portfolio .empty');
          if (newTarget) {
            target.innerHTML = newTarget.innerHTML;
            target.hidden = false;
          } else {
            target.innerHTML = '';
          }
          var oldEmpty = document.querySelector('.portfolio .empty');
          if (oldEmpty) oldEmpty.remove();
          if (newEmpty) target.insertAdjacentElement('afterend', newEmpty);
          if (newNav) {
            nav.querySelectorAll('.filter__link').forEach(function (a) {
              var fresh = newNav.querySelector('[data-filter-slug="' + a.getAttribute('data-filter-slug') + '"]');
              if (fresh && fresh.hasAttribute('aria-current')) a.setAttribute('aria-current', 'true');
              else a.removeAttribute('aria-current');
            });
          }
          document.title = doc.title;
          if (push) history.pushState({ filter: url }, '', url);
          target.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
        })
        .catch(function () { window.location.href = url; })
        .then(function () {
          nav.classList.remove('is-loading');
          target.classList.remove('is-swapping');
        });
    }

    nav.addEventListener('click', function (ev) {
      var a = ev.target.closest('a.filter__link');
      if (!a || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) return;
      ev.preventDefault();
      swap(a.href, true);
    });
    window.addEventListener('popstate', function () {
      if (document.querySelector('[data-filter-target]')) swap(window.location.href, false);
    });
  }

  /* ---------- Filme: externer Player erst nach Klick ---------- */
  function initVideos() {
    document.querySelectorAll('[data-video]').forEach(function (box) {
      var play = box.querySelector('[data-play]');
      if (!play) return;
      play.addEventListener('click', function (ev) {
        ev.preventDefault();
        var iframe = document.createElement('iframe');
        iframe.src = box.getAttribute('data-embed');
        iframe.title = box.getAttribute('data-title') || 'Video';
        iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        box.appendChild(iframe);
        play.remove();
        iframe.focus();
      });
    });
  }

  /* ---------- Lightbox ---------- */
  function initLightbox() {
    var links = Array.prototype.slice.call(document.querySelectorAll('a[data-lightbox]'));
    if (!links.length) return;

    var box = document.createElement('div');
    box.className = 'lightbox';
    box.hidden = true;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Bildansicht');
    box.innerHTML =
      '<div class="lightbox__stage"><img class="lightbox__img" alt=""></div>' +
      '<div class="lightbox__loading" aria-hidden="true"></div>' +
      '<button type="button" class="lightbox__btn lightbox__close" aria-label="Schließen (Escape)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
      '<button type="button" class="lightbox__btn lightbox__prev" aria-label="Vorheriges Bild"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>' +
      '<button type="button" class="lightbox__btn lightbox__next" aria-label="Nächstes Bild"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>' +
      '<div class="lightbox__bar"><span class="lightbox__counter" aria-live="polite"></span><p class="lightbox__caption"></p></div>';
    document.body.appendChild(box);

    var img = box.querySelector('.lightbox__img');
    var counter = box.querySelector('.lightbox__counter');
    var caption = box.querySelector('.lightbox__caption');
    var btnClose = box.querySelector('.lightbox__close');
    var btnPrev = box.querySelector('.lightbox__prev');
    var btnNext = box.querySelector('.lightbox__next');
    var current = -1;
    var opener = null;
    var supportsWebp = document.createElement('canvas').toDataURL('image/webp').indexOf('data:image/webp') === 0;

    function itemAt(i) {
      var a = links[i];
      return {
        href: a.getAttribute('href'),
        srcset: supportsWebp ? (a.getAttribute('data-srcset') || a.getAttribute('data-srcset-jpg')) : (a.getAttribute('data-srcset-jpg') || a.getAttribute('data-srcset')),
        alt: (a.querySelector('img') && a.querySelector('img').getAttribute('alt')) || '',
        caption: a.getAttribute('data-caption') || ''
      };
    }

    function preload(i) {
      if (i < 0 || i >= links.length) return;
      var item = itemAt(i);
      var pre = new Image();
      pre.sizes = '100vw';
      pre.srcset = item.srcset;
      pre.src = item.href;
    }

    function show(i, direction) {
      if (i < 0) i = links.length - 1;
      if (i >= links.length) i = 0;
      current = i;
      var item = itemAt(i);
      box.classList.add('is-loading');
      img.classList.remove('is-loaded');
      img.onload = function () {
        img.classList.add('is-loaded');
        box.classList.remove('is-loading');
        // Nur die Nachbarn vorladen, nicht die gesamte Serie.
        preload(i + 1);
        preload(i - 1);
      };
      img.onerror = function () {
        box.classList.remove('is-loading');
        img.classList.add('is-loaded');
        img.alt = 'Bild konnte nicht geladen werden';
      };
      img.alt = item.alt;
      img.sizes = '100vw';
      img.srcset = item.srcset;
      img.src = item.href;
      counter.textContent = (i + 1) + ' / ' + links.length;
      caption.textContent = item.caption;
      btnPrev.hidden = btnNext.hidden = links.length < 2;
    }

    function open(i, fromEl) {
      opener = fromEl || document.activeElement;
      box.hidden = false;
      document.body.classList.add('lightbox-open');
      requestAnimationFrame(function () { box.classList.add('is-open'); });
      show(i);
      btnClose.focus();
      document.addEventListener('keydown', onKey);
    }

    function close() {
      box.classList.remove('is-open');
      document.removeEventListener('keydown', onKey);
      document.body.classList.remove('lightbox-open');
      var done = function () {
        box.hidden = true;
        img.removeAttribute('src');
        img.removeAttribute('srcset');
        img.classList.remove('is-loaded');
        if (opener && typeof opener.focus === 'function') {
          // Zurück zur Ausgangsposition: fokussiertes Element scrollt in den Sichtbereich.
          opener.focus({ preventScroll: false });
        }
      };
      if (reduceMotion) done(); else setTimeout(done, 260);
    }

    function onKey(ev) {
      switch (ev.key) {
        case 'Escape': ev.preventDefault(); close(); break;
        case 'ArrowRight': ev.preventDefault(); show(current + 1); break;
        case 'ArrowLeft': ev.preventDefault(); show(current - 1); break;
        case 'Home': ev.preventDefault(); show(0); break;
        case 'End': ev.preventDefault(); show(links.length - 1); break;
        case 'Tab': trapFocus(ev); break;
      }
    }

    function trapFocus(ev) {
      var focusable = Array.prototype.filter.call(box.querySelectorAll('button:not([hidden])'), function (b) { return !b.hidden; });
      if (!focusable.length) return;
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
      else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
      else if (!box.contains(document.activeElement)) { ev.preventDefault(); first.focus(); }
    }

    links.forEach(function (a, i) {
      a.addEventListener('click', function (ev) {
        if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) return;
        ev.preventDefault();
        open(i, a);
      });
    });
    btnClose.addEventListener('click', close);
    btnPrev.addEventListener('click', function () { show(current - 1); });
    btnNext.addEventListener('click', function () { show(current + 1); });
    box.querySelector('.lightbox__stage').addEventListener('click', function (ev) {
      if (ev.target === ev.currentTarget) close();
    });

    // Touch: horizontales Wischen blättert, vertikales Wischen schließt.
    var touchStart = null;
    box.addEventListener('touchstart', function (ev) {
      if (ev.touches.length !== 1) return;
      touchStart = { x: ev.touches[0].clientX, y: ev.touches[0].clientY, t: Date.now() };
    }, { passive: true });
    box.addEventListener('touchend', function (ev) {
      if (!touchStart) return;
      var dx = ev.changedTouches[0].clientX - touchStart.x;
      var dy = ev.changedTouches[0].clientY - touchStart.y;
      var dt = Date.now() - touchStart.t;
      touchStart = null;
      if (dt > 800) return;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) show(dx < 0 ? current + 1 : current - 1);
      else if (Math.abs(dy) > 90 && Math.abs(dy) > Math.abs(dx) * 1.5) close();
    }, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    initFilter();
    initVideos();
    initLightbox();
  });
})();
