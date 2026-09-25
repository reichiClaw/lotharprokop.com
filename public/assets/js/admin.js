/* Verwaltung – Upload mit Fortschritt, Sortieren per Drag-and-drop, Fokuspunkt, Bestätigungen.
   Alle Funktionen haben eine Formular-Alternative ohne JavaScript. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function post(url, formData) {
    formData.append('_token', csrf);
    return fetch(url, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf }
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Unerwartete Antwort (' + r.status + ')' }; })
        .then(function (data) { if (!r.ok && data.ok !== false) data.ok = false; return data; });
    });
  }

  function setState(el, text, cls) {
    if (!el) return;
    el.hidden = false;
    el.textContent = text;
    el.className = 'a-savestate' + (cls ? ' is-' + cls : '');
  }

  /* ---------- Bestätigungen ---------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  /* ---------- Schutz vor Datenverlust ---------- */
  document.querySelectorAll('form[data-dirty-guard]').forEach(function (form) {
    var dirty = false;
    var label = form.querySelector('[data-dirty-label]');
    form.addEventListener('input', function () { dirty = true; if (label) label.hidden = false; });
    form.addEventListener('change', function () { dirty = true; if (label) label.hidden = false; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (ev) {
      if (dirty) { ev.preventDefault(); ev.returnValue = ''; }
    });
  });

  /* ---------- Slug-Vorschlag ---------- */
  (function () {
    var source = document.querySelector('[data-slug-source]');
    var target = document.querySelector('[data-slug-target]');
    if (!source || !target) return;
    var touched = target.value !== '';
    target.addEventListener('input', function () { touched = target.value !== ''; });
    source.addEventListener('input', function () {
      if (touched) return;
      target.placeholder = source.value.toLowerCase()
        .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'wird aus dem Titel gebildet';
    });
  })();

  /* ---------- Sortieren (Drag-and-drop + Pfeiltasten) ---------- */
  document.querySelectorAll('[data-sortable]').forEach(function (list) {
    var url = list.getAttribute('data-sortable');
    var name = list.getAttribute('data-sortable-name') || 'order';
    var extra = {};
    try { extra = JSON.parse(list.getAttribute('data-sortable-extra') || '{}'); } catch (e) { extra = {}; }
    var state = list.parentElement.querySelector('[data-savestate]');
    var items = function () { return Array.prototype.slice.call(list.children); };
    var dragging = null;

    function renumber() {
      items().forEach(function (li, i) {
        var n = li.querySelector('.a-image__num');
        if (n) n.textContent = i + 1;
        var up = li.querySelector('[aria-label="Nach vorne"], [aria-label="Nach oben"]');
        var down = li.querySelector('[aria-label="Nach hinten"], [aria-label="Nach unten"]');
        if (up) up.disabled = i === 0;
        if (down) down.disabled = i === items().length - 1;
      });
    }

    function save() {
      if (!url) {
        var form = list.closest('form');
        var lbl = form && form.querySelector('[data-dirty-label]');
        if (lbl) lbl.hidden = false;
        return;
      }
      var fd = new FormData();
      items().forEach(function (li) { fd.append(name + '[]', li.getAttribute('data-id')); });
      Object.keys(extra).forEach(function (k) { fd.append(k, extra[k]); });
      setState(state, 'Reihenfolge wird gespeichert …', '');
      post(url, fd).then(function (data) {
        if (data.ok) setState(state, 'Reihenfolge gespeichert.', 'ok');
        else setState(state, 'Speichern fehlgeschlagen: ' + (data.error || 'Unbekannter Fehler') + ' – Seite neu laden.', 'error');
      }).catch(function () {
        setState(state, 'Keine Verbindung – Reihenfolge wurde nicht gespeichert.', 'error');
      });
    }

    list.addEventListener('dragstart', function (ev) {
      var li = ev.target.closest('[data-id]');
      if (!li || li.getAttribute('draggable') !== 'true') return;
      dragging = li;
      li.classList.add('is-dragging');
      ev.dataTransfer.effectAllowed = 'move';
      try { ev.dataTransfer.setData('text/plain', li.getAttribute('data-id')); } catch (e) { /* IE */ }
    });
    list.addEventListener('dragover', function (ev) {
      if (!dragging) return;
      ev.preventDefault();
      var li = ev.target.closest('[data-id]');
      if (!li || li === dragging) return;
      items().forEach(function (x) { x.classList.remove('is-over'); });
      li.classList.add('is-over');
      var rect = li.getBoundingClientRect();
      var horizontal = list.classList.contains('a-images');
      var before = horizontal ? (ev.clientX - rect.left) < rect.width / 2 : (ev.clientY - rect.top) < rect.height / 2;
      list.insertBefore(dragging, before ? li : li.nextSibling);
    });
    list.addEventListener('dragend', function () {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      items().forEach(function (x) { x.classList.remove('is-over'); });
      dragging = null;
      renumber();
      save();
    });
    list.addEventListener('drop', function (ev) { ev.preventDefault(); });

    // Pfeil-Schaltflächen: ohne Seitenwechsel verschieben.
    list.addEventListener('click', function (ev) {
      var btn = ev.target.closest('button');
      if (!btn) return;
      var li = btn.closest('[data-id]');
      var dir = 0;
      var label = btn.getAttribute('aria-label') || '';
      if (btn.hasAttribute('data-move')) dir = parseInt(btn.getAttribute('data-move'), 10);
      else if (/oben|vorne/.test(label)) dir = -1;
      else if (/unten|hinten/.test(label)) dir = 1;
      if (!dir || !li) return;
      if (btn.closest('form') && !url && !btn.hasAttribute('data-move')) return;
      ev.preventDefault();
      var sibling = dir < 0 ? li.previousElementSibling : li.nextElementSibling;
      if (!sibling) return;
      list.insertBefore(li, dir < 0 ? sibling : sibling.nextSibling);
      renumber();
      btn.focus();
      save();
    });
  });

  /* ---------- Upload mit echtem Fortschritt (eine Datei pro Anfrage) ---------- */
  document.querySelectorAll('form[data-upload]').forEach(function (form) {
    var input = form.querySelector('[data-upload-input]');
    var listEl = form.querySelector('[data-upload-list]');
    var maxBytes = parseInt(form.getAttribute('data-max-bytes'), 10) || 0;
    var queue = [];
    var running = 0;
    var added = 0;

    function item(file) {
      var li = document.createElement('li');
      li.className = 'a-upload-item';
      li.innerHTML = '<img alt=""><div><div class="a-upload-item__name"></div><progress max="100" value="0"></progress></div><span class="a-upload-item__status">Wartet …</span>';
      li.querySelector('.a-upload-item__name').textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
      if (file.type.indexOf('image/') === 0 && file.size < 30 * 1024 * 1024) {
        var reader = new FileReader();
        reader.onload = function () { li.querySelector('img').src = reader.result; };
        reader.readAsDataURL(file);
      }
      listEl.appendChild(li);
      return li;
    }

    function finish(li, ok, text) {
      li.classList.add(ok ? 'is-ok' : 'is-error');
      li.querySelector('.a-upload-item__status').textContent = text;
      li.querySelector('progress').value = 100;
    }

    function next() {
      if (running >= 2 || !queue.length) {
        if (!running && !queue.length && added > 0) {
          var note = document.createElement('li');
          note.className = 'a-upload-item is-ok';
          note.innerHTML = '<span></span><div>' + added + ' Bild(er) hochgeladen. <a href="">Seite neu laden</a>, um Reihenfolge und Details zu bearbeiten.</div><span></span>';
          listEl.appendChild(note);
          added = 0;
          setTimeout(function () { window.location.reload(); }, 1500);
        }
        return;
      }
      var job = queue.shift();
      running++;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'fetch');
      xhr.setRequestHeader('X-CSRF-Token', csrf);
      xhr.upload.onprogress = function (ev) {
        if (ev.lengthComputable) {
          var pct = Math.round(ev.loaded / ev.total * 100);
          job.li.querySelector('progress').value = pct;
          job.li.querySelector('.a-upload-item__status').textContent = pct < 100 ? pct + ' %' : 'Wird verarbeitet …';
        }
      };
      xhr.onload = function () {
        running--;
        var data = null;
        try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }
        if (data && data.uploaded && data.uploaded.length) {
          finish(job.li, true, 'Fertig');
          if (data.uploaded[0].thumb) job.li.querySelector('img').src = data.uploaded[0].thumb;
          added++;
        } else {
          var msg = (data && (data.error || (data.errors && data.errors[0] && data.errors[0].error))) || ('Fehler ' + xhr.status);
          finish(job.li, false, msg);
        }
        next();
      };
      xhr.onerror = function () { running--; finish(job.li, false, 'Verbindungsfehler'); next(); };
      var fd = new FormData();
      fd.append('_token', csrf);
      fd.append('files[]', job.file, job.file.name);
      xhr.send(fd);
    }

    function addFiles(files) {
      Array.prototype.forEach.call(files, function (file) {
        var li = item(file);
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { finish(li, false, 'Dateityp nicht erlaubt (nur JPEG, PNG, WebP)'); return; }
        if (maxBytes && file.size > maxBytes) { finish(li, false, 'Zu groß (max. ' + Math.round(maxBytes / 1048576) + ' MB)'); return; }
        queue.push({ file: file, li: li });
      });
      next(); next();
    }

    input.addEventListener('change', function () { if (input.files.length) { addFiles(input.files); input.value = ''; } });
    ['dragenter', 'dragover'].forEach(function (evName) {
      form.addEventListener(evName, function (ev) { ev.preventDefault(); form.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (evName) {
      form.addEventListener(evName, function (ev) { ev.preventDefault(); form.classList.remove('is-over'); });
    });
    form.addEventListener('drop', function (ev) {
      if (ev.dataTransfer && ev.dataTransfer.files.length) addFiles(ev.dataTransfer.files);
    });
    form.addEventListener('submit', function (ev) { ev.preventDefault(); });
  });

  /* ---------- Fokuspunkt ---------- */
  (function () {
    var box = document.querySelector('[data-focus]');
    if (!box) return;
    var marker = box.querySelector('.a-focus__marker');
    var fx = document.querySelector('[data-focus-x]');
    var fy = document.querySelector('[data-focus-y]');
    var previews = document.querySelectorAll('[data-focus-preview]');
    function apply(x, y) {
      x = Math.min(1, Math.max(0, x)); y = Math.min(1, Math.max(0, y));
      marker.style.left = (x * 100) + '%';
      marker.style.top = (y * 100) + '%';
      fx.value = x.toFixed(2); fy.value = y.toFixed(2);
      previews.forEach(function (p) { p.style.objectPosition = (x * 100) + '% ' + (y * 100) + '%'; });
      var form = box.closest('form');
      var lbl = form && form.querySelector('[data-dirty-label]');
      if (lbl) lbl.hidden = false;
    }
    function fromEvent(ev) {
      var rect = box.querySelector('img').getBoundingClientRect();
      var cx = ev.touches ? ev.touches[0].clientX : ev.clientX;
      var cy = ev.touches ? ev.touches[0].clientY : ev.clientY;
      apply((cx - rect.left) / rect.width, (cy - rect.top) / rect.height);
    }
    var down = false;
    box.addEventListener('mousedown', function (ev) { down = true; fromEvent(ev); ev.preventDefault(); });
    window.addEventListener('mousemove', function (ev) { if (down) fromEvent(ev); });
    window.addEventListener('mouseup', function () { down = false; });
    box.addEventListener('touchstart', function (ev) { fromEvent(ev); }, { passive: true });
    box.addEventListener('touchmove', function (ev) { fromEvent(ev); }, { passive: true });
    [fx, fy].forEach(function (inp) { inp.addEventListener('input', function () { apply(parseFloat(fx.value) || 0, parseFloat(fy.value) || 0); }); });
  })();
})();
