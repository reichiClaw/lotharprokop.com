<?php
/** @var array $texts */
/** @var array $fields */
/** @var array $values */
/** @var array|null $portrait */
/** @var array $eggs */
/** @var array $projectLayouts */
/** @var string $projectLayout */
/** @var bool $scrollHint */
use App\Csrf;
use App\Images;
?>
<div class="a-head">
  <h1 class="a-title">Einstellungen</h1>
</div>
<form method="post" action="/admin/einstellungen" enctype="multipart/form-data" class="a-form a-form--wide" data-dirty-guard>
  <?= Csrf::field() ?>

  <h2 class="a-subtitle">Kontaktdaten</h2>
  <div class="a-row-2">
    <?php foreach ($fields as $key => $label): ?>
    <div class="a-field"><label for="<?= e($key) ?>"><?= e($label) ?></label><input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($values[$key] ?? '') ?>"></div>
    <?php endforeach; ?>
  </div>

  <h2 class="a-subtitle">Porträt (Vita, Startseite)</h2>
  <div class="a-row-2">
    <div>
      <?php if ($portrait): $v = Images::variantFor($portrait, 480); ?>
        <img class="a-preview a-preview--sm" src="<?= e(Images::variantUrl($portrait, $v, 'jpg', true)) ?>" alt="">
        <p class="a-help"><a href="/admin/bilder/<?= $portrait['id'] ?>">Alternativtext bearbeiten</a></p>
      <?php else: ?><p class="a-help">Kein Porträt hinterlegt.</p><?php endif; ?>
    </div>
    <div class="a-field"><label for="portrait">Neues Porträt hochladen</label><input type="file" id="portrait" name="portrait" accept="image/jpeg,image/png,image/webp"></div>
  </div>

  <h2 class="a-subtitle">Texte</h2>
  <p class="a-help">Leerzeile = neuer Absatz. Zeilen mit <code>## </code> werden Zwischenüberschriften, Zeilen mit <code>- </code> Aufzählungen. E-Mail-Adressen und https-Links werden automatisch verlinkt. HTML wird nicht interpretiert.</p>
  <?php $group = null; foreach ($texts as $key => $def): ?>
  <?php if (($def['group'] ?? null) !== $group): $group = $def['group'] ?? null; ?>
  <h3 class="a-subtitle a-subtitle--sm"><?= e((string) $group) ?></h3>
  <?php if (str_starts_with($key, App\Architektur::SETTINGS_PREFIX)): ?><p class="a-help">Felder mit grauem Vorschlagstext zeigen den eingebauten Standardtext; er gilt, solange das Feld leer bleibt.</p><?php endif; ?>
  <?php endif; ?>
  <div class="a-field">
    <label for="<?= e($key) ?>"><?= e($def['label']) ?></label>
    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= (int) $def['rows'] ?>"<?= !empty($def['placeholder']) ? ' placeholder="' . e($def['placeholder']) . '"' : '' ?>><?= e($values[$key] ?? '') ?></textarea>
    <?php if (str_starts_with($key, 'legal_')): ?><p class="a-help a-warn">Rechtlich zu prüfender Inhalt – bitte von einer fachkundigen Stelle prüfen lassen (Impressumspflicht ECG/MedienG, DSGVO).</p><?php endif; ?>
  </div>
  <?php endforeach; ?>

  <h2 class="a-subtitle">Startseite: Kopfbereich</h2>
  <div class="a-eggs">
    <label class="a-check a-egg">
      <input type="checkbox" name="hero_scroll_hint" value="1" <?= $scrollHint ? 'checked' : '' ?>>
      <span class="a-egg__text"><strong>Scroll-Hinweis anzeigen</strong><span class="a-egg__help">Am unteren Rand des bildschirmhohen Kopfbereichs erscheint mittig eine dünne Linie mit wanderndem Punkt und dem Wort „Scrollen“. Sie ist anklickbar (führt zum ersten Abschnitt darunter) und verschwindet, sobald gescrollt wird. Nur bei Kopfbereich mit Bild; respektiert „Bewegung reduzieren“.</span></span>
    </label>
  </div>

  <h2 class="a-subtitle">Startseite: Ausgewählte Projekte</h2>
  <p class="a-help">Wie die ausgewählten Projekte unter der Bildauswahl erscheinen. Die Auswahl der Projekte selbst erfolgt unter „Galerien“ (Häkchen „Auf der Startseite hervorheben“).</p>
  <div class="a-layouts" role="radiogroup" aria-label="Darstellung der ausgewählten Projekte">
    <?php foreach ($projectLayouts as $key => $def): ?>
    <label class="a-layout">
      <input type="radio" name="home_projects_layout" value="<?= e($key) ?>" <?= $projectLayout === $key ? 'checked' : '' ?>>
      <span class="a-layout__sketch a-layout__sketch--<?= e($key) ?>" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
      <span class="a-layout__text"><strong><?= e($def['label']) ?></strong><span class="a-layout__help"><?= e($def['help']) ?></span></span>
    </label>
    <?php endforeach; ?>
  </div>

  <h2 class="a-subtitle">Kleine Spielereien (Easter Eggs)</h2>
  <p class="a-help">Versteckte Animationen für Besucher, die genauer hinsehen. Jede lässt sich einzeln abschalten; alle respektieren die Systemeinstellung „Bewegung reduzieren“.</p>
  <div class="a-eggs">
    <?php foreach ($eggs as $key => $def): $on = !array_key_exists($key, $values) || (string) $values[$key] === '1'; ?>
    <label class="a-check a-egg <?= !empty($def['sub']) ? 'a-egg--sub' : '' ?>">
      <input type="checkbox" name="<?= e($key) ?>" value="1" <?= $on ? 'checked' : '' ?>>
      <span class="a-egg__text"><strong><?= e($def['label']) ?></strong><span class="a-egg__help"><?= e($def['help']) ?></span></span>
    </label>
    <?php endforeach; ?>
  </div>

  <div class="a-form__actions">
    <button type="submit" class="a-btn">Einstellungen speichern</button>
    <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
  </div>
</form>
