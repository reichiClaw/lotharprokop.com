<?php
/** @var array $texts */
/** @var array $fields */
/** @var array $values */
/** @var array|null $portrait */
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
  <?php foreach ($texts as $key => $def): ?>
  <div class="a-field">
    <label for="<?= e($key) ?>"><?= e($def['label']) ?></label>
    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= (int) $def['rows'] ?>"><?= e($values[$key] ?? '') ?></textarea>
    <?php if (str_starts_with($key, 'legal_')): ?><p class="a-help a-warn">Rechtlich zu prüfender Inhalt – bitte von einer fachkundigen Stelle prüfen lassen (Impressumspflicht ECG/MedienG, DSGVO).</p><?php endif; ?>
  </div>
  <?php endforeach; ?>

  <div class="a-form__actions">
    <button type="submit" class="a-btn">Einstellungen speichern</button>
    <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
  </div>
</form>
