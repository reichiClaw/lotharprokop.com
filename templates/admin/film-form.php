<?php
/** @var array|null $film */
use App\Csrf;
use App\Films;
use App\Images;

$isNew = $film === null;
$f = $film ?? ['title' => '', 'description' => '', 'client' => '', 'year' => '', 'provider' => 'youtube', 'video_id' => '', 'status' => 'draft', 'poster' => null];
?>
<div class="a-head">
  <div>
    <p class="a-crumbs"><a href="/admin/filme">Filme</a> / <?= $isNew ? 'Neu' : e($f['title']) ?></p>
    <h1 class="a-title"><?= $isNew ? 'Neuer Film' : e($f['title']) ?></h1>
  </div>
</div>
<div class="a-grid-form">
<form method="post" action="<?= $isNew ? '/admin/filme/neu' : '/admin/filme/' . $f['id'] ?>" enctype="multipart/form-data" class="a-form a-form--wide" data-dirty-guard>
  <?= Csrf::field() ?>
  <div class="a-field"><label for="title">Titel</label><input type="text" id="title" name="title" required value="<?= e($f['title']) ?>"></div>
  <div class="a-row-3">
    <div class="a-field">
      <label for="provider">Anbieter</label>
      <select id="provider" name="provider"><?php foreach (Films::PROVIDERS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['provider'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    </div>
    <div class="a-field a-field--span2"><label for="video_id">Video-URL oder -ID</label><input type="text" id="video_id" name="video_id" required value="<?= e($f['video_id']) ?>" placeholder="https://www.youtube.com/watch?v=…"></div>
  </div>
  <div class="a-row-3">
    <div class="a-field"><label for="client">Kunde <span class="a-muted">(optional)</span></label><input type="text" id="client" name="client" value="<?= e($f['client']) ?>"></div>
    <div class="a-field"><label for="year">Jahr <span class="a-muted">(optional)</span></label><input type="text" id="year" name="year" value="<?= e($f['year']) ?>" maxlength="9"></div>
    <div class="a-field">
      <label for="status">Status</label>
      <select id="status" name="status"><option value="draft" <?= $f['status'] === 'draft' ? 'selected' : '' ?>>Entwurf</option><option value="published" <?= $f['status'] === 'published' ? 'selected' : '' ?>>Veröffentlicht</option></select>
    </div>
  </div>
  <div class="a-field"><label for="description">Kurze Information <span class="a-muted">(optional)</span></label><textarea id="description" name="description" rows="4"><?= e($f['description']) ?></textarea></div>
  <div class="a-field">
    <label for="poster">Vorschaubild <span class="a-muted">(eigenes Standbild, 16:9 empfohlen – wird lokal ausgeliefert)</span></label>
    <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp">
  </div>
  <div class="a-form__actions">
    <button type="submit" class="a-btn"><?= $isNew ? 'Film anlegen' : 'Speichern' ?></button>
    <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
  </div>
</form>
<?php if (!$isNew): ?>
<aside class="a-side">
  <h2 class="a-subtitle">Vorschaubild</h2>
  <?php if ($f['poster']): $v = Images::variantFor($f['poster'], 960); ?>
    <img class="a-preview" src="<?= e(Images::variantUrl($f['poster'], $v, 'jpg', true)) ?>" alt="" style="aspect-ratio:16/9; object-fit:cover; object-position:<?= $f['poster']['focus_x'] * 100 ?>% <?= $f['poster']['focus_y'] * 100 ?>%">
    <p class="a-help"><a href="/admin/bilder/<?= $f['poster']['id'] ?>">Fokuspunkt / Alternativtext bearbeiten</a></p>
  <?php else: ?>
    <p class="a-help a-warn">Kein Vorschaubild. Ohne eigenes Standbild wird eine dunkle Fläche gezeigt – es wird bewusst kein Bild von YouTube nachgeladen.</p>
  <?php endif; ?>
  <h2 class="a-subtitle">Löschen</h2>
  <form method="post" action="/admin/filme/<?= $f['id'] ?>/loeschen" data-confirm="Film „<?= e($f['title']) ?>“ löschen?"><?= Csrf::field() ?><button type="submit" class="a-btn a-btn--danger">Film löschen</button></form>
</aside>
<?php endif; ?>
</div>
