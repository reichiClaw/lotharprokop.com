<?php
/** @var array $featured */
/** @var array $others */
/** @var array $slides */
/** @var int $slideInterval */
/** @var int $featuredAddable */
/** @var array $galleries */
/** @var array $groups          Alle Bilder nach Galerie (Bildwähler) */
/** @var array $slideImageIds   Bildkennung => true – schon im Kopfbereich */
/** @var int $imageTotal */
use App\Csrf;
use App\HeroSlides;
use App\Images;
use App\View;
?>
<div class="a-head">
  <h1 class="a-title">Startseite</h1>
  <a class="a-btn a-btn--ghost" href="/" target="_blank" rel="noopener">Startseite ansehen</a>
</div>

<section>
  <h2 class="a-subtitle">Kopfbereich: Bildfolge</h2>
  <p class="a-help">Diese Bilder füllen den großen Kopfbereich der Startseite. Ab zwei Bildern wechseln sie sich ab (Überblendung, dazu eine Pause-Taste und Striche zum Umschalten); ein einzelnes Bild bleibt ein festes Startbild. Das verknüpfte Projekt macht das Bild anklickbar und erscheint als kleiner Bildnachweis. Ausschnitt 16:9 (Desktop) bzw. hochkant auf dem Telefon – der Fokuspunkt jedes Bildes entscheidet, was sichtbar bleibt. Ohne JavaScript zeigt der Kopfbereich das erste Bild.</p>
  <?php if ($slides === []): ?>
    <p class="a-help">Noch kein Bild im Kopfbereich – die Startseite beginnt dann direkt mit der Typografie.</p>
  <?php else: ?>
  <form method="post" action="/admin/startseite" class="a-form" data-dirty-guard>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slides">
    <ol class="a-sortable" data-sortable="" data-sortable-name="slide">
      <?php foreach ($slides as $n => $slide): $img = $slide['image']; $v = Images::variantFor($img, 480); ?>
      <li class="a-sort-item" data-id="<?= $slide['id'] ?>" draggable="true">
        <input type="hidden" name="slide[]" value="<?= $slide['id'] ?>">
        <span class="a-image__num"><?= $n + 1 ?></span>
        <span class="a-thumb"><?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $img['focus_x'] * 100 ?>% <?= $img['focus_y'] * 100 ?>%"><?php endif; ?></span>
        <div class="a-sort-item__body">
          <label class="a-label" for="slide-gallery-<?= $slide['id'] ?>">Projekt</label>
          <select id="slide-gallery-<?= $slide['id'] ?>" name="slide_gallery[<?= $slide['id'] ?>]">
            <option value="0">– kein Verweis –</option>
            <?php foreach ($galleries as $g): ?>
            <option value="<?= $g['id'] ?>" <?= $slide['gallery'] !== null && $slide['gallery']['id'] === $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="a-help"><?= $img['width'] ?> × <?= $img['height'] ?> px · <a href="/admin/bilder/<?= $img['id'] ?>">Fokuspunkt und Alternativtext</a></p>
        </div>
        <div class="a-sort-item__actions">
          <button type="button" class="a-iconbtn" data-move="-1" aria-label="Nach oben">↑</button>
          <button type="button" class="a-iconbtn" data-move="1" aria-label="Nach unten">↓</button>
          <label class="a-check"><input type="checkbox" name="slide_remove[]" value="<?= $slide['id'] ?>"> Entfernen</label>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
    <div class="a-row-3">
      <div class="a-field">
        <label for="hero_interval">Bildwechsel alle … Sekunden</label>
        <input type="number" id="hero_interval" name="hero_interval" min="<?= HeroSlides::INTERVAL_MIN ?>" max="<?= HeroSlides::INTERVAL_MAX ?>" step="1" value="<?= $slideInterval ?>">
      </div>
    </div>
    <div class="a-form__actions">
      <button type="submit" class="a-btn">Bildfolge speichern</button>
      <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
    </div>
  </form>
  <?php endif; ?>
</section>

<section id="bibliothek">
  <h2 class="a-subtitle">Bilder aus der Bibliothek in den Kopfbereich aufnehmen <span class="a-muted">(aus allen <?= $imageTotal ?> Bildern)</span></h2>
  <p class="a-help">Jedes vorhandene Bild lässt sich in die Bildfolge aufnehmen – Galerie aufklappen, Bilder anhaken, unten „In den Kopfbereich aufnehmen“. Bilder, die schon im Kopfbereich stehen, sind markiert. Neue Bilder werden hinten angehängt; Reihenfolge und Projektverweis lassen sich oben ändern.</p>
  <?php
  $linkOptions = '<div class="a-row-3"><div class="a-field"><label for="slide_pick_gallery">Verknüpftes Projekt</label><select id="slide_pick_gallery" name="slide_pick_gallery">'
      . '<option value="auto">– automatisch: Galerie, in der das Bild liegt –</option>'
      . '<option value="0">– kein Verweis –</option>';
  foreach ($galleries as $g) {
      $linkOptions .= '<option value="' . (int) $g['id'] . '">' . e($g['title']) . '</option>';
  }
  $linkOptions .= '</select></div></div>';
  ?>
  <?= View::partial('admin/partials/image-picker', [
      'action' => '/admin/startseite',
      'groups' => $groups,
      'selectedIds' => $slideImageIds,
      'submitLabel' => 'In den Kopfbereich aufnehmen',
      'usedLabel' => 'bereits im Kopfbereich',
      'usedCount' => 'im Kopfbereich',
      'openFirst' => false,
      'hidden' => '<input type="hidden" name="action" value="slide_pick">',
      'options' => $linkOptions,
  ]) ?>
</section>

<div class="a-grid-2">
<section>
  <h2 class="a-subtitle">Projekt-Titelbild oder neue Datei in den Kopfbereich</h2>
  <form method="post" action="/admin/startseite" enctype="multipart/form-data" class="a-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slide_add">
    <div class="a-field">
      <label for="slide_gallery_id">Projekt übernehmen <span class="a-muted">(dessen Titelbild, verlinkt auf das Projekt)</span></label>
      <select id="slide_gallery_id" name="slide_gallery_id">
        <option value="0">– keines –</option>
        <?php foreach ($galleries as $g): ?>
        <option value="<?= $g['id'] ?>"><?= e($g['title']) ?><?= $g['cover'] ? '' : ' (ohne Titelbild)' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="a-field">
      <label for="slide_file">… oder eigenes Bild hochladen <span class="a-muted">(JPEG, PNG oder WebP; das Projekt oben wird dann verlinkt)</span></label>
      <input type="file" id="slide_file" name="slide_file" accept="image/jpeg,image/png,image/webp">
    </div>
    <button type="submit" class="a-btn">Hinzufügen</button>
  </form>
  <?php if ($featuredAddable > 0): ?>
  <form method="post" action="/admin/startseite" class="a-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slides_featured">
    <button type="submit" class="a-btn a-btn--ghost">Alle ausgewählten Projekte übernehmen (<?= $featuredAddable ?>)</button>
  </form>
  <p class="a-help">Übernimmt die Titelbilder der rechts ausgewählten Projekte in der dortigen Reihenfolge.</p>
  <?php endif; ?>
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
