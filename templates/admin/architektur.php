<?php
/**
 * Architekturseite: Bildfolge im Kopfbereich gezielt zusammenstellen.
 * @var array $slides            eigene Auswahl (HeroSlides, Auftritt 'architektur')
 * @var int $slideInterval
 * @var array $galleries         Projekte im Umfang der Architekturseite (alle Status)
 * @var array $groups            Bildwähler: alle Bilder nach Galerie, Umfang zuerst
 * @var array $slideImageIds     Bildkennung => true – schon im Kopfbereich
 * @var int $imageTotal
 * @var array $automatic         automatische Bildfolge (Titelbilder der hervorgehobenen Projekte)
 * @var int $automaticAddable    davon noch nicht in der eigenen Auswahl
 * @var string $siteUrl
 */
use App\Csrf;
use App\Galleries;
use App\HeroSlides;
use App\Images;
use App\View;

$manual = $slides !== [];
?>
<div class="a-head">
  <h1 class="a-title">Architekturseite</h1>
  <a class="a-btn a-btn--ghost" href="<?= e($siteUrl) ?>" target="_blank" rel="noopener">Architekturseite ansehen</a>
</div>

<section>
  <h2 class="a-subtitle">Kopfbereich: Bildfolge</h2>
  <p class="a-help">Diese Bilder füllen den großen Kopfbereich der Architektur-Startseite; der Plankopf rechts unten zeigt zu jedem Bild das verknüpfte Projekt (Kategorie, Jahr) und die Blattnummer. Ab zwei Bildern wechseln sie sich ab. Ausschnitt 16:9 (Desktop) bzw. hochkant auf dem Telefon – der Fokuspunkt jedes Bildes entscheidet, was sichtbar bleibt. Ohne eigene Auswahl zeigt die Seite <strong>automatisch</strong> die Titelbilder der hervorgehobenen Projekte im Umfang (Reihenfolge wie unter <a href="/admin/startseite">Startseite → Ausgewählte Projekte</a>).</p>

  <?php if (!$manual): ?>
    <p class="a-help"><strong>Zurzeit automatisch.</strong>
      <?php if ($automatic === []): ?>
        Es gibt noch kein hervorgehobenes Projekt mit Titelbild im Umfang – der Kopfbereich beginnt direkt mit der Typografie.
      <?php else: ?>
        Der Kopfbereich zeigt derzeit <?= count($automatic) === 1 ? 'dieses Titelbild' : 'diese ' . count($automatic) . ' Titelbilder' ?>:
      <?php endif; ?>
    </p>
    <?php if ($automatic !== []): ?>
    <ol class="a-sortable a-sortable--compact">
      <?php foreach ($automatic as $n => $slide): $img = $slide['image']; $v = Images::variantFor($img, 480); ?>
      <li class="a-sort-item">
        <span class="a-image__num"><?= $n + 1 ?></span>
        <span class="a-thumb"><?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $img['focus_x'] * 100 ?>% <?= $img['focus_y'] * 100 ?>%"><?php endif; ?></span>
        <div class="a-sort-item__body"><?= e($slide['gallery']['title']) ?> <span class="a-muted">Titelbild</span></div>
      </li>
      <?php endforeach; ?>
    </ol>
    <form method="post" action="/admin/architektur" class="a-form">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="slides_automatic">
      <button type="submit" class="a-btn">Diese Auswahl übernehmen und bearbeiten</button>
    </form>
    <p class="a-help">Oder unten gezielt Bilder aus der Bibliothek wählen – sobald ein Bild in der eigenen Auswahl steht, gilt nur noch sie.</p>
    <?php endif; ?>
  <?php else: ?>
  <form method="post" action="/admin/architektur" class="a-form" data-dirty-guard>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slides">
    <ol class="a-sortable" data-sortable="" data-sortable-name="slide">
      <?php foreach ($slides as $n => $slide): $img = $slide['image']; $v = Images::variantFor($img, 480); ?>
      <li class="a-sort-item" data-id="<?= $slide['id'] ?>" draggable="true">
        <input type="hidden" name="slide[]" value="<?= $slide['id'] ?>">
        <span class="a-image__num"><?= $n + 1 ?></span>
        <span class="a-thumb"><?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $img['focus_x'] * 100 ?>% <?= $img['focus_y'] * 100 ?>%"><?php endif; ?></span>
        <div class="a-sort-item__body">
          <label class="a-label" for="slide-gallery-<?= $slide['id'] ?>">Projekt <span class="a-muted">(nur Projekte im Umfang der Architekturseite)</span></label>
          <select id="slide-gallery-<?= $slide['id'] ?>" name="slide_gallery[<?= $slide['id'] ?>]">
            <option value="0">– kein Verweis (Plankopf zeigt Bildunterschrift oder Alternativtext) –</option>
            <?php foreach ($galleries as $g): ?>
            <option value="<?= $g['id'] ?>" <?= $slide['gallery'] !== null && $slide['gallery']['id'] === $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?><?= $g['status'] !== 'published' ? ' (' . e(Galleries::STATUSES[$g['status']] ?? $g['status']) . ' – wird nicht verlinkt)' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <p class="a-help"><?= $img['width'] ?> × <?= $img['height'] ?> px · <a href="/admin/bilder/<?= $img['id'] ?>">Fokuspunkt, Alternativtext und Bildunterschrift</a></p>
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
  <?php if ($automaticAddable > 0): ?>
  <form method="post" action="/admin/architektur" class="a-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slides_automatic">
    <button type="submit" class="a-btn a-btn--ghost">Fehlende Titelbilder der hervorgehobenen Projekte anhängen (<?= $automaticAddable ?>)</button>
  </form>
  <?php endif; ?>
  <form method="post" action="/admin/architektur" class="a-form" data-confirm="Eigene Auswahl wirklich löschen? Der Kopfbereich zeigt danach wieder automatisch die hervorgehobenen Projekte. Bilder, die sonst nirgends verwendet werden, werden dabei gelöscht.">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slides_reset">
    <button type="submit" class="a-btn a-btn--ghost">Eigene Auswahl löschen, wieder automatisch</button>
  </form>
  <?php endif; ?>
</section>

<section id="bibliothek">
  <h2 class="a-subtitle">Bilder aus der Bibliothek in den Kopfbereich aufnehmen <span class="a-muted">(aus allen <?= $imageTotal ?> Bildern; Projekte im Umfang zuerst)</span></h2>
  <p class="a-help">Galerie aufklappen, Bilder anhaken, unten „In den Kopfbereich aufnehmen“. Bilder, die schon im Kopfbereich stehen, sind markiert. Neue Bilder werden hinten angehängt; Reihenfolge und Projektverweis lassen sich oben ändern. Ein Bild aus einer Galerie außerhalb des Umfangs ist erlaubt – es wird dann im eigenen Bildordner der Architekturseite veröffentlicht, nur der Projektverweis bleibt leer.</p>
  <?php
  $linkOptions = '<div class="a-row-3"><div class="a-field"><label for="slide_pick_gallery">Verknüpftes Projekt</label><select id="slide_pick_gallery" name="slide_pick_gallery">'
      . '<option value="auto">– automatisch: Galerie im Umfang, in der das Bild liegt –</option>'
      . '<option value="0">– kein Verweis –</option>';
  foreach ($galleries as $g) {
      $linkOptions .= '<option value="' . (int) $g['id'] . '">' . e($g['title']) . '</option>';
  }
  $linkOptions .= '</select></div></div>';
  ?>
  <?= View::partial('admin/partials/image-picker', [
      'action' => '/admin/architektur',
      'groups' => $groups,
      'selectedIds' => $slideImageIds,
      'submitLabel' => 'In den Kopfbereich aufnehmen',
      'usedLabel' => 'bereits im Kopfbereich der Architekturseite',
      'usedCount' => 'im Kopfbereich',
      'openFirst' => false,
      'hidden' => '<input type="hidden" name="action" value="slide_pick">',
      'options' => $linkOptions,
  ]) ?>
</section>

<section>
  <h2 class="a-subtitle">Projekt-Titelbild oder neue Datei in den Kopfbereich</h2>
  <form method="post" action="/admin/architektur" enctype="multipart/form-data" class="a-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slide_add">
    <div class="a-row-2">
      <div class="a-field">
        <label for="slide_gallery_id">Projekt übernehmen <span class="a-muted">(dessen Titelbild, verlinkt auf das Projekt)</span></label>
        <select id="slide_gallery_id" name="slide_gallery_id">
          <option value="0">– keines –</option>
          <?php foreach ($galleries as $g): ?>
          <option value="<?= $g['id'] ?>"><?= e($g['title']) ?><?= $g['cover'] ? '' : ' (ohne Titelbild)' ?><?= $g['status'] !== 'published' ? ' – ' . e(Galleries::STATUSES[$g['status']] ?? $g['status']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="a-field">
        <label for="slide_file">… oder eigenes Bild hochladen <span class="a-muted">(JPEG, PNG oder WebP; das Projekt links wird dann verlinkt)</span></label>
        <input type="file" id="slide_file" name="slide_file" accept="image/jpeg,image/png,image/webp">
      </div>
    </div>
    <button type="submit" class="a-btn">Hinzufügen</button>
  </form>
</section>
