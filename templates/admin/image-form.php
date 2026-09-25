<?php
/** @var array $image */
/** @var array $usage */
/** @var int|null $backGallery */
use App\Config;
use App\Csrf;
use App\Images;

$v = Images::variantFor($image, 960);
$src = $v ? Images::variantUrl($image, $v, 'jpg', true) : '';
$galleryParam = $backGallery ? '?galerie=' . $backGallery : '';
$usageCount = count($usage['galleries']) + count($usage['other']);
?>
<div class="a-head">
  <div>
    <p class="a-crumbs"><a href="/admin/galerien">Galerien</a> / <?php if ($backGallery): ?><a href="/admin/galerien/<?= $backGallery ?>#bild-<?= $image['id'] ?>">Galerie</a> / <?php endif; ?>Bild</p>
    <h1 class="a-title"><?= e($image['original_name']) ?></h1>
  </div>
  <?php if ($backGallery): ?><a class="a-btn a-btn--ghost" href="/admin/galerien/<?= $backGallery ?>#bild-<?= $image['id'] ?>">Zurück zur Galerie</a><?php endif; ?>
</div>

<div class="a-grid-form">
  <form method="post" action="/admin/bilder/<?= $image['id'] ?>" class="a-form a-form--wide" data-dirty-guard>
    <?= Csrf::field() ?>
    <?php if ($backGallery): ?><input type="hidden" name="galerie" value="<?= $backGallery ?>"><?php endif; ?>
    <div class="a-field">
      <label for="alt">Alternativtext <span class="a-muted">(beschreibt das Motiv, wichtig für Barrierefreiheit und Suchmaschinen)</span></label>
      <input type="text" id="alt" name="alt" value="<?= e($image['alt']) ?>" maxlength="250" placeholder="z. B. Landwirt auf Mähdrescher im Abendlicht">
    </div>
    <div class="a-field">
      <label for="caption">Bildunterschrift <span class="a-muted">(optional, wird unter dem Bild und in der Lightbox angezeigt)</span></label>
      <input type="text" id="caption" name="caption" value="<?= e($image['caption']) ?>" maxlength="300">
    </div>
    <div class="a-field">
      <span class="a-label">Fokuspunkt für Ausschnitte <span class="a-muted">(Titelbilder, Startbild – in das Bild klicken)</span></span>
      <div class="a-focus" data-focus>
        <img src="<?= e($src) ?>" alt="" width="<?= $v['w'] ?? 0 ?>" height="<?= $v['h'] ?? 0 ?>" draggable="false">
        <span class="a-focus__marker" style="left: <?= $image['focus_x'] * 100 ?>%; top: <?= $image['focus_y'] * 100 ?>%" aria-hidden="true"></span>
      </div>
      <div class="a-row-3">
        <div class="a-field"><label for="focus_x">Horizontal (0–1)</label><input type="number" id="focus_x" name="focus_x" min="0" max="1" step="0.01" value="<?= $image['focus_x'] ?>" data-focus-x></div>
        <div class="a-field"><label for="focus_y">Vertikal (0–1)</label><input type="number" id="focus_y" name="focus_y" min="0" max="1" step="0.01" value="<?= $image['focus_y'] ?>" data-focus-y></div>
        <div class="a-field"><span class="a-label">Vorschau Ausschnitt 3:2 / 4:5</span><div class="a-focus-previews"><img src="<?= e($src) ?>" alt="" data-focus-preview style="aspect-ratio:3/2;object-position:<?= $image['focus_x'] * 100 ?>% <?= $image['focus_y'] * 100 ?>%"><img src="<?= e($src) ?>" alt="" data-focus-preview style="aspect-ratio:4/5;object-position:<?= $image['focus_x'] * 100 ?>% <?= $image['focus_y'] * 100 ?>%"></div></div>
      </div>
    </div>
    <div class="a-form__actions">
      <button type="submit" class="a-btn">Speichern</button>
      <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
    </div>
  </form>

  <aside class="a-side">
    <h2 class="a-subtitle">Datei</h2>
    <dl class="a-dl">
      <dt>Abmessungen</dt><dd><?= $image['width'] ?> × <?= $image['height'] ?> px</dd>
      <dt>Original</dt><dd><?= human_bytes((int) $image['bytes']) ?>, <?= e($image['mime']) ?> (privat gespeichert)</dd>
      <dt>Varianten</dt><dd><?= e(implode(', ', array_map(fn($x) => max($x['w'], $x['h']) . 'px', $image['variants']))) ?></dd>
      <dt>Öffentlich</dt><dd><?= $image['is_public'] ? 'ja' : 'nein (nur in Entwürfen verwendet)' ?></dd>
      <dt>Verwendung</dt>
      <dd>
        <?php if ($usageCount === 0): ?>keine<?php else: ?>
        <?php foreach ($usage['galleries'] as $ug): ?><a href="/admin/galerien/<?= (int) $ug['id'] ?>"><?= e($ug['title']) ?></a><br><?php endforeach; ?>
        <?php foreach ($usage['other'] as $o): ?><?= e($o) ?><br><?php endforeach; ?>
        <?php endif; ?>
      </dd>
    </dl>

    <h2 class="a-subtitle">Datei ersetzen</h2>
    <p class="a-help">Die neue Datei übernimmt Zuordnungen, Reihenfolge, Texte und Fokuspunkt dieses Bildes.</p>
    <form method="post" action="/admin/bilder/<?= $image['id'] ?>/ersetzen" enctype="multipart/form-data" class="a-form">
      <?= Csrf::field() ?>
      <?php if ($backGallery): ?><input type="hidden" name="galerie" value="<?= $backGallery ?>"><?php endif; ?>
      <div class="a-field"><input type="file" name="file" accept="image/jpeg,image/png,image/webp" required aria-label="Neue Bilddatei"></div>
      <button type="submit" class="a-btn a-btn--ghost">Ersetzen</button>
    </form>

    <h2 class="a-subtitle">Endgültig löschen</h2>
    <form method="post" action="/admin/bilder/<?= $image['id'] ?>/loeschen" class="a-form" data-confirm="Dieses Bild endgültig löschen? Es wird aus allen Verwendungen entfernt.">
      <?= Csrf::field() ?>
      <?php if ($backGallery): ?><input type="hidden" name="galerie" value="<?= $backGallery ?>"><?php endif; ?>
      <label class="a-check"><input type="checkbox" name="confirm" value="ja" required> Ja, Datei und alle Zuordnungen löschen</label>
      <?php if ($usageCount > 1): ?><label class="a-check"><input type="checkbox" name="confirm_multi" value="ja" required> Mir ist bewusst, dass das Bild an <?= $usageCount ?> Stellen verwendet wird</label><?php endif; ?>
      <button type="submit" class="a-btn a-btn--danger">Bild löschen</button>
    </form>
  </aside>
</div>
