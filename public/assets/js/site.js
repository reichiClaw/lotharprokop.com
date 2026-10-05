/* Lothar Prokop Fotografie – Frontend-Ergänzungen (progressive enhancement).
   Ohne JavaScript funktionieren Navigation, Filter (Links), Galerien (Bild-Links) und Filme (externer Link). */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Kopfbereich: nach dem ersten Scrollen kompakter ---------- */
  function initHeader() {
    var root = document.documentElement;
    if (!document.querySelector('.site-header')) return;
    var ticking = false;
    function update() {
      ticking = false;
      var y = window.pageYOffset || root.scrollTop || 0;
      root.classList.toggle('is-scrolled', y > 24);
    }
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    }, { passive: true });
    update();
  }

  /* ---------- Bildfolge im Kopfbereich der Startseite ----------
     Ohne JavaScript zeigt der Kopfbereich das erste Bild – wie ein festes Startbild.
     Die weiteren Bilder stecken in <template> und werden erst geladen, wenn sie gebraucht werden. */
  function initHero() {
    var box = document.querySelector('[data-hero]');
    if (!box) return;
    var section = box.closest('.hero') || box;
    var slots = Array.prototype.slice.call(box.querySelectorAll('[data-hero-slide], template[data-hero-slide-template]'));
    if (slots.length < 2) return;
    var credits = Array.prototype.slice.call(section.querySelectorAll('[data-hero-credit]'));
    var dots = Array.prototype.slice.call(section.querySelectorAll('[data-hero-dot]'));
    var toggle = section.querySelector('[data-hero-toggle]');
    var interval = Math.max(2000, parseInt(box.getAttribute('data-hero-interval'), 10) || 6000);
    var fadeMs = cssDuration('--dur-hero-fade', 1500);
    var index = 0;
    var timer = null;
    var paused = false;

    function cssDuration(name, fallback) {
      var raw = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      var value = parseFloat(raw);
      if (isNaN(value)) return fallback;
      return raw.indexOf('ms') === -1 ? value * 1000 : value;
    }

    // Aus dem <template> ein echtes Bild machen; das Laden beginnt erst hier.
    // Das Bild wird sofort geladen und vorab dekodiert, damit beim Einblenden nichts mehr zu rechnen ist.
    function materialize(i) {
      var slot = slots[i];
      if (slot.tagName !== 'TEMPLATE') return slot;
      var fragment = slot.content.cloneNode(true);
      var el = fragment.querySelector('[data-hero-slide]');
      slot.parentNode.insertBefore(fragment, slot);
      slot.parentNode.removeChild(slot);
      slots[i] = el;
      el.querySelectorAll('img').forEach(function (img) {
        img.removeAttribute('loading');
        if (img.decode) {
          var decode = function () { img.decode().catch(function () {}); };
          if (img.complete) decode(); else img.addEventListener('load', decode, { once: true });
        }
      });
      return el;
    }

    // Ruft cb auf, sobald die Bilder der Folie geladen sind (oder sofort, wenn das schon der Fall ist).
    function whenLoaded(el, cb) {
      var pending = Array.prototype.filter.call(el.querySelectorAll('img'), function (img) { return !img.complete; });
      if (!pending.length) { cb(); return; }
      var done = false;
      function finish() {
        if (done) return;
        done = true;
        cb();
      }
      pending.forEach(function (img) {
        img.addEventListener('load', finish, { once: true });
        img.addEventListener('error', finish, { once: true });
      });
      // Notbremse: nach einer Wechselzeit auf jeden Fall weiter.
      window.setTimeout(finish, interval);
    }

    // Ausgehende Folie: blendet aus und wird danach unsichtbar in den Ausgangszustand zurückgesetzt.
    function leave(el) {
      el.classList.remove('is-active');
      el.classList.add('is-leaving');
      setHidden(el, true);
      if (el._leaveTimer) window.clearTimeout(el._leaveTimer);
      el._leaveTimer = window.setTimeout(function () {
        el._leaveTimer = null;
        el.classList.remove('is-leaving');
      }, fadeMs + 50);
    }

    // Verborgene Folien enthalten Links: für Vorlesesoftware und Tastatur ausblenden.
    function setHidden(el, hidden) {
      if (!el) return;
      if (hidden) el.setAttribute('aria-hidden', 'true');
      else el.removeAttribute('aria-hidden');
      el.querySelectorAll('a[href]').forEach(function (a) {
        if (hidden) a.setAttribute('tabindex', '-1');
        else a.removeAttribute('tabindex');
      });
    }

    function show(target) {
      var i = ((target % slots.length) + slots.length) % slots.length;
      if (i === index) return;
      var previous = slots[index];
      var el = materialize(i);
      index = i;
      if (previous && previous.tagName !== 'TEMPLATE') {
        leave(previous);
      }
      if (el._leaveTimer) {
        window.clearTimeout(el._leaveTimer);
        el._leaveTimer = null;
      }
      el.classList.remove('is-leaving');
      el.classList.add('is-active');
      setHidden(el, false);
      credits.forEach(function (credit, k) {
        credit.classList.toggle('is-active', k === i);
        setHidden(credit, k !== i);
      });
      dots.forEach(function (dot, k) {
        dot.classList.toggle('is-active', k === i);
        if (k === i) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
      // Das nächste Bild im Hintergrund vorbereiten.
      materialize((i + 1) % slots.length);
    }

    var cycle = 0;

    function stop() {
      cycle++;
      if (timer) { window.clearTimeout(timer); timer = null; }
    }

    // Automatischer Wechsel: erst wenn das nächste Bild geladen ist, damit die Überblendung nicht ins Leere läuft.
    function schedule() {
      var current = cycle;
      timer = window.setTimeout(function () {
        timer = null;
        var next = materialize((index + 1) % slots.length);
        whenLoaded(next, function () {
          if (current !== cycle) return; // inzwischen angehalten oder neu gestartet
          show(index + 1);
          schedule();
        });
      }, interval);
    }

    function start() {
      stop();
      if (paused || reduceMotion || document.hidden) return;
      schedule();
    }

    dots.forEach(function (dot, k) {
      dot.addEventListener('click', function () { show(k); start(); });
    });

    if (toggle) {
      if (reduceMotion) {
        // Bei reduzierter Bewegung läuft nichts von allein; eine Pause-Taste wäre irreführend.
        toggle.hidden = true;
      } else {
        toggle.addEventListener('click', function () {
          paused = !paused;
          toggle.classList.toggle('is-paused', paused);
          toggle.setAttribute('aria-label', paused ? 'Bildwechsel fortsetzen' : 'Bildwechsel anhalten');
          if (paused) stop(); else start();
        });
      }
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop(); else start();
    });
    section.addEventListener('focusin', stop);
    section.addEventListener('focusout', function () {
      window.setTimeout(function () {
        if (!section.contains(document.activeElement)) start();
      }, 0);
    });

    // Das erste Bild ist beim Laden bereits aktiv; das Einsinken erst nach dem ersten gezeichneten Bild
    // freigeben, damit es wie bei den Folgebildern als Übergang läuft statt sofort am Endpunkt zu stehen.
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () { box.classList.add('is-running'); });
    });

    // Das zweite Bild erst nach dem ersten Seitenaufbau vorbereiten.
    window.setTimeout(function () { materialize(1); }, 1200);
    start();
  }

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

  /* ==========================================================================
     Spielereien (Easter Eggs) – einzeln im Adminbereich abschaltbar.
     Das Layout schreibt die aktiven in <body data-eggs="darkroom shutter …">.
     ========================================================================== */
  function initEggs() {
    var enabled = (document.body.getAttribute('data-eggs') || '').split(/\s+/).filter(Boolean);
    function has(name) { return enabled.indexOf(name) !== -1; }
    var brand = document.querySelector('.brand');
    if (has('darkroom')) initDarkroom(brand);
    if (has('shutter') && brand) initShutter(brand);
    if (has('autofocus')) initAutofocus();
    if (has('lightleak')) initLightLeak();
  }

  function isTypingTarget(el) {
    return !!el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
  }

  /* ---------- Dunkelkammer ----------
     „dunkelkammer“ tippen oder das Logo 1,5 s gedrückt halten: rotes Schutzlicht, die Bilder entwickeln sich
     aus weißem Papier. Esc, erneutes Tippen oder Gedrückthalten schaltet das Licht wieder an. */
  function initDarkroom(brand) {
    var root = document.documentElement;
    var WORD = 'dunkelkammer';
    var typed = '';
    var toast = null;
    var toastTimer = null;
    var developTimer = null;

    function note(text) {
      if (!toast) {
        toast = document.createElement('button');
        toast.type = 'button';
        toast.className = 'egg-toast';
        toast.addEventListener('click', leave);
        document.body.appendChild(toast);
      }
      if (toastTimer) window.clearTimeout(toastTimer);
      toast.textContent = text;
      toast.hidden = false;
      window.requestAnimationFrame(function () { toast.classList.add('is-visible'); });
      toastTimer = window.setTimeout(function () { toast.classList.remove('is-visible'); }, 3600);
    }

    function isOn() { return root.classList.contains('is-darkroom'); }

    function enter() {
      // Jeder Abzug entwickelt sich etwas anders schnell – kein Gleichschritt.
      document.querySelectorAll('img').forEach(function (img) {
        img.style.setProperty('--develop-delay', Math.round(Math.random() * 1100) + 'ms');
      });
      root.classList.add('is-darkroom', 'is-developing');
      if (developTimer) window.clearTimeout(developTimer);
      developTimer = window.setTimeout(function () { root.classList.remove('is-developing'); }, 4800);
      note('Dunkelkammer – Licht an: Esc oder hier antippen');
    }

    function leave() {
      root.classList.remove('is-darkroom', 'is-developing');
      if (developTimer) { window.clearTimeout(developTimer); developTimer = null; }
      if (toast) { toast.classList.remove('is-visible'); if (toastTimer) window.clearTimeout(toastTimer); }
    }

    function toggle() { if (isOn()) leave(); else enter(); }

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && isOn()) { leave(); return; }
      if (ev.metaKey || ev.ctrlKey || ev.altKey || isTypingTarget(ev.target)) return;
      if (!ev.key || ev.key.length !== 1) return;
      typed = (typed + ev.key.toLowerCase()).slice(-WORD.length);
      if (typed === WORD) { typed = ''; toggle(); }
    });

    if (!brand) return;
    var pressTimer = null;
    var longPressed = false;
    function cancelPress() {
      if (pressTimer) { window.clearTimeout(pressTimer); pressTimer = null; }
      brand.classList.remove('is-pressing');
    }
    brand.addEventListener('pointerdown', function (ev) {
      if (ev.button !== 0) return;
      longPressed = false;
      brand.classList.add('is-pressing');
      pressTimer = window.setTimeout(function () {
        pressTimer = null;
        longPressed = true;
        brand.classList.remove('is-pressing');
        toggle();
      }, 1500);
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (type) {
      brand.addEventListener(type, cancelPress);
    });
    // Nach dem Gedrückthalten soll der Klick nicht zur Startseite führen (Capture-Phase: läuft vor dem Verschluss).
    brand.addEventListener('click', function (ev) {
      if (!longPressed) return;
      longPressed = false;
      ev.preventDefault();
      ev.stopImmediatePropagation();
    }, true);
    // Auf Touchgeräten öffnet langes Drücken sonst das Kontextmenü des Links.
    brand.addEventListener('contextmenu', function (ev) {
      if (pressTimer || longPressed) ev.preventDefault();
    });
  }

  /* ---------- Verschluss am Logo ----------
     Doppelklick schließt und öffnet eine sechsblättrige Blende über der Seite. Damit der erste Klick nicht schon
     zur Startseite springt, wartet die Navigation kurz auf einen möglichen zweiten Klick. */
  function initShutter(brand) {
    if (reduceMotion) return;
    var clickTimer = null;
    var overlay = null;
    var busy = false;

    function play() {
      if (busy) return;
      busy = true;
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'shutter';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML = '<div class="shutter__iris"><i></i><i></i><i></i><i></i><i></i><i></i></div>';
        document.body.appendChild(overlay);
      }
      var iris = overlay.firstChild;
      var done = function () {
        overlay.classList.remove('is-playing');
        busy = false;
      };
      iris.addEventListener('animationend', done, { once: true });
      window.setTimeout(done, 1500);
      overlay.classList.add('is-playing');
    }

    brand.addEventListener('click', function (ev) {
      if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey || ev.button !== 0) return;
      ev.preventDefault();
      if (clickTimer) {
        window.clearTimeout(clickTimer);
        clickTimer = null;
        play();
        return;
      }
      clickTimer = window.setTimeout(function () {
        clickTimer = null;
        if (window.location.pathname === '/') {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          window.location.href = brand.href;
        }
      }, 280);
    });
  }

  /* ---------- Autofokus auf der 404-Seite ----------
     Der Fokusrahmen folgt dem Zeiger; bleibt er kurz stehen, rastet er grün ein und das Bild wird scharf.
     Touch: Antippen stellt scharf, erneutes Antippen folgt dem Link. Tastatur: Fokus stellt mittig scharf. */
  function initAutofocus() {
    var box = document.querySelector('[data-af]');
    if (!box) return;
    var frame = box.querySelector('.af__frame');
    var status = box.querySelector('[data-af-status]');
    var SETTLE_MS = 420;
    var settle = null;

    function say(text) { if (status) status.textContent = text; }

    if (reduceMotion || !frame) {
      box.classList.add('is-focused');
      say('Scharf');
      return;
    }

    function lock() {
      settle = null;
      box.classList.remove('is-hunting');
      box.classList.add('is-focused');
      say('Scharf');
    }

    function moveTo(clientX, clientY) {
      var r = box.getBoundingClientRect();
      var x = Math.min(Math.max(clientX - r.left, 0), r.width);
      var y = Math.min(Math.max(clientY - r.top, 0), r.height);
      frame.style.transform = 'translate(' + Math.round(x) + 'px, ' + Math.round(y) + 'px) translate(-50%, -50%)';
      box.classList.add('is-tracking', 'is-hunting');
      box.classList.remove('is-focused');
      say('Fokus suchen …');
      if (settle) window.clearTimeout(settle);
      settle = window.setTimeout(lock, SETTLE_MS);
    }

    function release() {
      if (settle) { window.clearTimeout(settle); settle = null; }
      box.classList.remove('is-tracking', 'is-hunting', 'is-focused');
      say('Fokus suchen …');
    }

    box.addEventListener('pointermove', function (ev) {
      if (ev.pointerType === 'touch') return;
      moveTo(ev.clientX, ev.clientY);
    });
    box.addEventListener('pointerleave', function (ev) {
      if (ev.pointerType === 'touch') return;
      release();
    });
    box.addEventListener('touchstart', function (ev) {
      if (box.classList.contains('is-focused')) return; // zweites Antippen: Link folgt
      var t = ev.touches[0];
      moveTo(t.clientX, t.clientY);
    }, { passive: true });
    box.addEventListener('click', function (ev) {
      if (box.classList.contains('is-focused')) return;
      ev.preventDefault();
      moveTo(ev.clientX, ev.clientY);
    });
    box.addEventListener('focus', function () {
      var r = box.getBoundingClientRect();
      moveTo(r.left + r.width / 2, r.top + r.height / 2);
    });
    box.addEventListener('blur', release);
  }

  /* ---------- Lichteinfall am Seitenende ----------
     Wer die Projektübersicht oder eine Galerie bis ganz unten scrollt, sieht einmal einen warmen Lichteinfall.
     Erst nach einem Stück Zurückscrollen kann er erneut ausgelöst werden. */
  function initLightLeak() {
    if (reduceMotion) return;
    if (!document.querySelector('.portfolio, .project')) return;
    var leak = null;
    var armed = true;
    var playing = false;
    var ticking = false;

    function play() {
      if (!leak) {
        leak = document.createElement('div');
        leak.className = 'light-leak';
        leak.setAttribute('aria-hidden', 'true');
        document.body.appendChild(leak);
      }
      playing = true;
      leak.classList.add('is-on');
      window.setTimeout(function () {
        leak.classList.remove('is-on');
        playing = false;
      }, 2700);
    }

    function check() {
      ticking = false;
      var root = document.documentElement;
      var max = root.scrollHeight - window.innerHeight;
      if (max < 400) return; // zu kurze Seite: kein „Ende erreicht“
      var y = window.pageYOffset || root.scrollTop || 0;
      if (y >= max - 2) {
        if (armed && !playing) { armed = false; play(); }
      } else if (y < max - 320) {
        armed = true;
      }
    }

    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(check);
    }, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initHeader();
    initHero();
    initReveal();
    initFilter();
    initVideos();
    initLightbox();
    initEggs();
  });
})();
