/*
 * Lothar Prokop Architekturfotografie – seiteneigene Bewegung.
 * Ergänzt das gemeinsame assets/js/site.js (Kopfbild-Wechsel, Einblenden, Filter, Lightbox):
 *  - leichter Parallaxversatz des Kopfbilds und Ausblenden des Textblocks beim Scrollen – nur für Browser
 *    ohne scrollgebundene CSS-Animationen; alle anderen bekommen den Versatz aus architektur.css,
 *  - Leistungsindex: beim Überfahren oder Fokussieren einer Zeile wechselt das Vorschaubild.
 * Die Fortschrittslinie im Plankopf läuft rein in CSS (Animation auf .hero__dot.is-active).
 * Kein Inline-Code, keine externen Abhängigkeiten; reduzierte Bewegung wird respektiert.
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Parallax: Bild bewegt sich langsamer als die Seite, Text blendet aus ---------- */
  function initParallax() {
    var media = document.querySelector('[data-parallax]');
    if (!media || reduceMotion) return;
    // Scrollgebundene Animationen laufen im Takt des Scrollens; ein Skript kommt immer einen Moment später.
    if (window.CSS && CSS.supports && CSS.supports('animation-timeline: view()')) return;
    var section = media.parentElement;
    var text = section.querySelector('.hero__inner, .service-head__inner');
    // Auf der Startseite blendet nur der Textblock aus; der Plankopf daneben behält seinen Weichzeichner.
    var fade = section.querySelector('.hero__text') || text;
    var fine = window.matchMedia('(min-width: 860px) and (hover: hover)');
    var ticking = false;
    var active = false;

    function update() {
      ticking = false;
      var height = section.offsetHeight;
      var y = Math.min(height, Math.max(0, window.scrollY || window.pageYOffset || 0));
      // Ungerundet: auf ganze Pixel gerundet bewegt sich das Bild in Stufen statt gleichmäßig.
      media.style.transform = 'translate3d(0,' + (y * 0.22).toFixed(2) + 'px,0)';
      if (text) {
        fade.style.opacity = (1 - Math.min(1, y / (height * 0.6))).toFixed(3);
        text.style.transform = 'translate3d(0,' + (y * 0.08).toFixed(2) + 'px,0)';
      }
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    }

    function bind() {
      if (fine.matches && !active) {
        active = true;
        window.addEventListener('scroll', onScroll, { passive: true });
        update();
      } else if (!fine.matches && active) {
        active = false;
        window.removeEventListener('scroll', onScroll);
        media.style.transform = '';
        if (text) {
          fade.style.opacity = '';
          text.style.transform = '';
        }
      }
    }

    if (fine.addEventListener) fine.addEventListener('change', bind);
    else if (fine.addListener) fine.addListener(bind);
    bind();
  }

  /* ---------- Leistungsindex: Zeile aktiv → zugehöriges Titelbild sichtbar ---------- */
  function initIndex() {
    document.querySelectorAll('[data-index]').forEach(function (box) {
      var rows = Array.prototype.slice.call(box.querySelectorAll('[data-index-row]'));
      var images = Array.prototype.slice.call(box.querySelectorAll('[data-index-img]'));
      if (!rows.length) return;

      function activate(key) {
        rows.forEach(function (row) {
          row.classList.toggle('is-active', row.getAttribute('data-index-row') === key);
        });
        var hasImage = images.some(function (img) { return img.getAttribute('data-index-img') === key; });
        // Ohne eigenes Bild bleibt das zuletzt gezeigte stehen – lieber ein Bild als eine leere Fläche.
        if (!hasImage) return;
        images.forEach(function (img) {
          img.classList.toggle('is-active', img.getAttribute('data-index-img') === key);
        });
      }

      rows.forEach(function (row) {
        var key = row.getAttribute('data-index-row');
        row.addEventListener('mouseenter', function () { activate(key); });
        row.addEventListener('focusin', function () { activate(key); });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initParallax();
    initIndex();
  });
})();
