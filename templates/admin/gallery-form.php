<?php
/** @var array|null $gallery */
/** @var array $images */
/** @var array $usageCounts */
/** @var array $categories */
/** @var int[] $selected */
use App\Config;
use App\Csrf;
use App\Galleries;
use App\Images;

$isNew = $gallery === null;
$g = $gallery ?? ['title' => '', 'slug' => '', 'description' => '', 'client' => '', 'year' => '', 'credits' => '', 'layout' => 'grid', 'status' => 'draft', 'featured' => 0, 'cover_image_id' => null];
$maxBytes = min((int) Config::get('images.max_upload_bytes'), ini_bytes((string) ini_get('upload_max_filesize')));
$usageCounts = $usageCounts ?? [];
?>
<div class="a-head">
  <div>
    <p class="a-crumbs"><a href="/admin/galerien">Galerien</a> / <?= $isNew ? 'Neu' : e($g['title']) ?></p>
    <h1 class="a-title"><?= $isNew ? 'Neue Galerie' : e($g['title']) ?></h1>
  </div>
  <?php if (!$isNew): ?>
  <div class="a-head__actions">
    <span class="a-badge a-badge--<?= e($g['status']) ?>"><?= e(Galleries::STATUSES[$g['status']]) ?></span>
    <a class="a-btn a-btn--ghost" href="/fotografie/<?= eurl($g['slug']) ?>" target="_blank" rel="noopener"><?= $g['status'] === 'published' ? 'Ansehen' : 'Vorschau (nur angemeldet)' ?></a>
    <?php if ($g['status'] !== 'published'): ?>
      <form method="post" action="/admin/galerien/<?= $g['id'] ?>/status"><?= Csrf::field() ?><input type="hidden" name="status" value="published"><button class="a-btn" type="submit" <?= $images === [] ? 'disabled title="Zuerst Bilder hochladen"' : '' ?>>Veröffentlichen</button></form>
    <?php else: ?>
      <form method="post" action="/admin/galerien/<?= $g['id'] ?>/status"><?= Csrf::field() ?><input type="hidden" name="status" value="draft"><button class="a-btn a-btn--ghost" type="submit">Auf Entwurf setzen</button></form>
    <?php endif; ?>
    <?php if ($g['status'] !== 'archived'): ?>
      <form method="post" action="/admin/galerien/<?= $g['id'] ?>/status"><?= Csrf::field() ?><input type="hidden" name="status" value="archived"><button class="a-btn a-btn--ghost" type="submit">Archivieren</button></form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<div class="a-grid-form">
<form method="post" action="<?= $isNew ? '/admin/galerien/neu' : '/admin/galerien/' . $g['id'] ?>" class="a-form a-form--wide" data-dirty-guard>
  <?= Csrf::field() ?>
  <div class="a-field">
    <label for="title">Titel</label>
    <input type="text" id="title" name="title" required value="<?= e($g['title']) ?>" data-slug-source>
  </div>
  <div class="a-field">
    <label for="slug">URL-Slug <span class="a-muted">/fotografie/…</span></label>
    <input type="text" id="slug" name="slug" value="<?= e($g['slug']) ?>" pattern="[a-z0-9-]*" data-slug-target placeholder="wird aus dem Titel gebildet">
    <?php if (!$isNew && !empty($g['legacy_slug']) && $g['legacy_slug'] !== $g['slug']): ?><p class="a-help">Alte Adresse /portfolio/<?= e($g['legacy_slug']) ?>/ wird automatisch weitergeleitet.</p><?php endif; ?>
  </div>
  <div class="a-field">
    <label for="description">Kurze Beschreibung <span class="a-muted">(optional, Leerzeile = Absatz)</span></label>
    <textarea id="description" name="description" rows="4"><?= e($g['description']) ?></textarea>
  </div>
  <div class="a-row-3">
    <div class="a-field"><label for="client">Kunde <span class="a-muted">(optional)</span></label><input type="text" id="client" name="client" value="<?= e($g['client']) ?>"></div>
    <div class="a-field"><label for="year">Jahr <span class="a-muted">(optional)</span></label><input type="text" id="year" name="year" value="<?= e($g['year']) ?>" inputmode="numeric" maxlength="9"></div>
    <div class="a-field"><label for="credits">Credits <span class="a-muted">(optional)</span></label><input type="text" id="credits" name="credits" value="<?= e($g['credits']) ?>" placeholder="z. B. Make-up: …"></div>
  </div>
  <fieldset class="a-field">
    <legend>Kategorien</legend>
    <?php if ($categories === []): ?><p class="a-help">Noch keine Kategorien – <a href="/admin/kategorien">anlegen</a>.</p><?php endif; ?>
    <div class="a-checks">
      <?php foreach ($categories as $c): ?>
      <label class="a-check"><input type="checkbox" name="categories[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selected, true) ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="a-row-3">
    <div class="a-field">
      <label for="layout">Layoutvariante</label>
      <select id="layout" name="layout">
        <?php foreach (Galleries::LAYOUTS as $key => $label): ?><option value="<?= e($key) ?>" <?= $g['layout'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="a-field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (Galleries::STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= $g['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="a-field a-field--check">
      <label class="a-check"><input type="checkbox" name="featured" value="1" <?= $g['featured'] ? 'checked' : '' ?>> Auf der Startseite hervorheben</label>
      <p class="a-help">Reihenfolge unter <a href="/admin/startseite">Startseite</a>.</p>
    </div>
  </div>
  <?php if (!$isNew): ?><input type="hidden" name="cover_image_id" value="<?= (int) ($g['cover_image_id'] ?? 0) ?>"><?php endif; ?>
  <div class="a-form__actions">
    <button type="submit" class="a-btn"><?= $isNew ? 'Galerie anlegen' : 'Speichern' ?></button>
    <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
  </div>
</form>

<?php if (!$isNew): ?>
<aside class="a-side">
  <h2 class="a-subtitle">Titelbild</h2>
  <?php $cover = $g['cover'] ?? null; ?>
  <?php if ($cover): $v = Images::variantFor($cover, 960); ?>
    <img class="a-preview" src="<?= e(Images::variantUrl($cover, $v, 'jpg', true)) ?>" alt="" style="aspect-ratio: 3/2; object-fit: cover; object-position:<?= $cover['focus_x'] * 100 ?>% <?= $cover['focus_y'] * 100 ?>%">
    <p class="a-help">Ausschnitt 3:2 wie in der Übersicht. Fokuspunkt unter <a href="/admin/bilder/<?= $cover['id'] ?>?galerie=<?= $g['id'] ?>">Bild bearbeiten</a> anpassen. Anderes Bild: „Als Titelbild“ in der Liste.</p>
  <?php else: ?>
    <p class="a-help">Noch kein Titelbild – das erste Bild wird verwendet.</p>
  <?php endif; ?>
</aside>
<?php endif; ?>
</div>

<?php if (!$isNew): ?>
<section class="a-section" id="bilder">
  <div class="a-head">
    <h2 class="a-subtitle">Bilder <span class="a-muted">(<?= count($images) ?>)</span></h2>
  </div>

  <form method="post" action="/admin/galerien/<?= $g['id'] ?>/upload" enctype="multipart/form-data" class="a-dropzone" data-upload data-max-bytes="<?= $maxBytes ?>">
    <?= Csrf::field() ?>
    <p class="a-dropzone__text"><strong>Bilder hierher ziehen</strong> oder</p>
    <label class="a-btn a-btn--ghost a-dropzone__pick"><input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp" data-upload-input> Dateien auswählen</label>
    <p class="a-help">JPEG, PNG oder WebP · max. <?= human_bytes($maxBytes) ?> pro Datei · Serverlimit pro Übertragung: <?= e(ini_get('post_max_size')) ?>. Am besten sRGB-JPEGs mit 2400–4000 px langer Kante.</p>
    <button type="submit" class="a-btn a-dropzone__submit" data-upload-submit>Hochladen</button>
    <ul class="a-upload-list" data-upload-list aria-live="polite"></ul>
  </form>

  <?php if ($images === []): ?>
    <p class="a-help">Noch keine Bilder in dieser Galerie.</p>
  <?php else: ?>
  <p class="a-help">Reihenfolge per Ziehen oder mit den Pfeiltasten ändern. Änderungen werden sofort gespeichert.</p>
  <ol class="a-images" data-sortable="/admin/galerien/<?= $g['id'] ?>/bilder/sortieren" data-sortable-name="order">
    <?php foreach ($images as $i => $img): $v = Images::variantFor($img, 480); $isCover = ($g['cover_image_id'] ?? null) === $img['id']; $multi = ($usageCounts[$img['id']] ?? 1) > 1; ?>
    <li class="a-image <?= $isCover ? 'is-cover' : '' ?>" data-id="<?= $img['id'] ?>" id="bild-<?= $img['id'] ?>" draggable="true">
      <a class="a-image__media" href="/admin/bilder/<?= $img['id'] ?>?galerie=<?= $g['id'] ?>" title="Bild bearbeiten">
        <?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="<?= e($img['alt']) ?>" width="<?= $v['w'] ?>" height="<?= $v['h'] ?>" loading="lazy"><?php else: ?><span class="a-image__missing">Datei fehlt</span><?php endif; ?>
      </a>
      <div class="a-image__body">
        <div class="a-image__name" title="<?= e($img['original_name']) ?>"><?= e($img['original_name']) ?></div>
        <div class="a-muted"><?= $img['width'] ?>×<?= $img['height'] ?><?= $img['alt'] === '' ? ' · <span class="a-warn">kein Alternativtext</span>' : '' ?><?= $multi ? ' · in ' . $usageCounts[$img['id']] . ' Galerien' : '' ?></div>
        <?php if ($isCover): ?><span class="a-badge a-badge--featured">Titelbild</span><?php endif; ?>
      </div>
      <div class="a-image__actions">
        <span class="a-image__num"><?= $i + 1 ?></span>
        <form method="post" action="/admin/galerien/<?= $g['id'] ?>/bilder/<?= $img['id'] ?>/bewegen" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="direction" value="up"><button class="a-iconbtn" type="submit" aria-label="Nach vorne" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
        <form method="post" action="/admin/galerien/<?= $g['id'] ?>/bilder/<?= $img['id'] ?>/bewegen" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="direction" value="down"><button class="a-iconbtn" type="submit" aria-label="Nach hinten" <?= $i === count($images) - 1 ? 'disabled' : '' ?>>↓</button></form>
        <a class="a-btn a-btn--sm a-btn--ghost" href="/admin/bilder/<?= $img['id'] ?>?galerie=<?= $g['id'] ?>">Bearbeiten</a>
        <?php if (!$isCover): ?><form method="post" action="/admin/galerien/<?= $g['id'] ?>/bilder/<?= $img['id'] ?>/titelbild" class="a-inline"><?= Csrf::field() ?><button class="a-btn a-btn--sm a-btn--ghost" type="submit">Als Titelbild</button></form><?php endif; ?>
        <form method="post" action="/admin/galerien/<?= $g['id'] ?>/bilder/<?= $img['id'] ?>/entfernen" class="a-inline" data-confirm="<?= $multi ? 'Bild aus dieser Galerie entfernen? Es bleibt in den anderen Galerien erhalten.' : 'Bild entfernen? Die Datei wird endgültig gelöscht, da sie nur hier verwendet wird.' ?>"><?= Csrf::field() ?><button class="a-btn a-btn--sm a-btn--danger" type="submit"><?= $multi ? 'Entfernen' : 'Löschen' ?></button></form>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="a-savestate" data-savestate hidden></p>
  <?php endif; ?>
</section>

<section class="a-section a-section--danger">
  <h2 class="a-subtitle">Galerie löschen</h2>
  <p class="a-help">Löscht die Galerie samt Zuordnungen. Bilder, die nur in dieser Galerie verwendet werden, werden ebenfalls endgültig gelöscht. Zum Bestätigen den Slug <code><?= e($g['slug']) ?></code> eingeben.</p>
  <form method="post" action="/admin/galerien/<?= $g['id'] ?>/loeschen" class="a-form a-form--inline" data-confirm="Galerie „<?= e($g['title']) ?>“ wirklich endgültig löschen?">
    <?= Csrf::field() ?>
    <input type="text" name="confirm" placeholder="<?= e($g['slug']) ?>" autocomplete="off" aria-label="Slug zur Bestätigung">
    <button type="submit" class="a-btn a-btn--danger">Galerie endgültig löschen</button>
  </form>
</section>
<?php endif; ?>
