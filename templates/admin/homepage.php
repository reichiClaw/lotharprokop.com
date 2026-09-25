<?php
/** @var array $featured */
/** @var array $others */
/** @var array|null $hero */
/** @var int $heroGalleryId */
/** @var array $galleries */
use App\Csrf;
use App\Images;
?>
<div class="a-head">
  <h1 class="a-title">Startseite</h1>
  <a class="a-btn a-btn--ghost" href="/" target="_blank" rel="noopener">Startseite ansehen</a>
</div>

<div class="a-grid-2">
<section>
  <h2 class="a-subtitle">Startbild</h2>
  <?php if ($hero): $v = Images::variantFor($hero, 960); ?>
    <img class="a-preview" src="<?= e(Images::variantUrl($hero, $v, 'jpg', true)) ?>" alt="" style="aspect-ratio:16/9; object-fit:cover; object-position:<?= $hero['focus_x'] * 100 ?>% <?= $hero['focus_y'] * 100 ?>%">
    <p class="a-help">Ausschnitt 16:9 (Desktop) bzw. 4:5 (Mobil). Fokuspunkt und Alternativtext unter <a href="/admin/bilder/<?= $hero['id'] ?>">Bild bearbeiten</a>.</p>
  <?php else: ?>
    <p class="a-help">Noch kein Startbild gesetzt.</p>
  <?php endif; ?>
  <form method="post" action="/admin/startseite" enctype="multipart/form-data" class="a-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="hero">
    <div class="a-field">
      <label for="hero">Neues Startbild hochladen <span class="a-muted">(ersetzt das aktuelle)</span></label>
      <input type="file" id="hero" name="hero" accept="image/jpeg,image/png,image/webp">
    </div>
    <div class="a-field">
      <label for="hero_image_id">… oder ein vorhandenes Titelbild verwenden</label>
      <select id="hero_image_id" name="hero_image_id">
        <option value="">– unverändert –</option>
        <?php foreach ($galleries as $g): if ($g['cover']): ?>
        <option value="<?= $g['cover']['id'] ?>"><?= e($g['title']) ?> – Titelbild</option>
        <?php endif; endforeach; ?>
      </select>
    </div>
    <div class="a-field">
      <label for="hero_gallery_id">Verlinktes Projekt <span class="a-muted">(optional, Bildnachweis unter dem Startbild)</span></label>
      <select id="hero_gallery_id" name="hero_gallery_id">
        <option value="0">– keines –</option>
        <?php foreach ($galleries as $g): ?>
        <option value="<?= $g['id'] ?>" <?= $heroGalleryId === $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="a-btn">Startbild speichern</button>
  </form>
</section>

<section>
  <h2 class="a-subtitle">Ausgewählte Projekte</h2>
  <p class="a-help">Diese veröffentlichten Galerien erscheinen in dieser Reihenfolge auf der Startseite. Per Ziehen sortieren oder mit den Pfeilen; Häkchen entfernen nimmt das Projekt von der Startseite. Danach „Speichern“.</p>
  <form method="post" action="/admin/startseite" class="a-form" data-dirty-guard>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="featured">
    <ol class="a-sortable a-sortable--compact" data-sortable="" data-sortable-name="featured">
      <?php foreach ($featured as $g): ?>
      <li class="a-sort-item" data-id="<?= $g['id'] ?>" draggable="true">
        <label class="a-check a-sort-item__body">
          <input type="checkbox" name="featured[]" value="<?= $g['id'] ?>" checked>
          <span class="a-thumb a-thumb--sm"><?php if ($g['cover']): $v = Images::variantFor($g['cover'], 480); ?><img src="<?= e(Images::variantUrl($g['cover'], $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $g['cover']['focus_x'] * 100 ?>% <?= $g['cover']['focus_y'] * 100 ?>%"><?php endif; ?></span>
          <?= e($g['title']) ?>
          <span class="a-muted"><?= $g['cover'] && $g['cover']['height'] > $g['cover']['width'] ? 'Hochformat' : 'Querformat' ?></span>
        </label>
        <div class="a-sort-item__actions">
          <button type="button" class="a-iconbtn" data-move="-1" aria-label="Nach oben">↑</button>
          <button type="button" class="a-iconbtn" data-move="1" aria-label="Nach unten">↓</button>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php if ($others !== []): ?>
    <h3 class="a-subtitle a-subtitle--sm">Weitere veröffentlichte Galerien hinzufügen</h3>
    <div class="a-checks">
      <?php foreach ($others as $g): ?>
      <label class="a-check"><input type="checkbox" name="featured[]" value="<?= $g['id'] ?>"> <?= e($g['title']) ?></label>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="a-form__actions">
      <button type="submit" class="a-btn">Speichern</button>
      <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
    </div>
  </form>
</section>
</div>
