<?php
/** @var array $categories */
use App\Csrf;
?>
<div class="a-head">
  <h1 class="a-title">Kategorien</h1>
</div>
<p class="a-help">Kategorien dienen als Filter auf der Seite „Fotografie“. Nur Kategorien mit veröffentlichten Galerien werden dort angezeigt. Das Löschen einer Kategorie entfernt nur die Zuordnung, nicht die Galerien.</p>

<form method="post" action="/admin/kategorien" class="a-form a-form--inline">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="create">
  <input type="text" name="name" placeholder="Neue Kategorie" required aria-label="Name der neuen Kategorie">
  <button type="submit" class="a-btn">Anlegen</button>
</form>

<?php if ($categories !== []): ?>
<ol class="a-sortable" data-sortable="/admin/kategorien" data-sortable-name="order" data-sortable-extra='{"action":"reorder"}'>
  <?php foreach ($categories as $i => $c): ?>
  <li class="a-sort-item" data-id="<?= (int) $c['id'] ?>" draggable="true">
    <form method="post" action="/admin/kategorien" class="a-sort-item__body a-form--inline">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <input type="text" name="name" value="<?= e($c['name']) ?>" required aria-label="Name">
      <input type="text" name="slug" value="<?= e($c['slug']) ?>" pattern="[a-z0-9-]*" aria-label="Slug" class="a-input--slug">
      <span class="a-muted"><?= (int) $c['published_count'] ?>/<?= (int) $c['total_count'] ?> Galerien</span>
      <button type="submit" class="a-btn a-btn--sm a-btn--ghost">Speichern</button>
    </form>
    <div class="a-sort-item__actions">
      <form method="post" action="/admin/kategorien" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="direction" value="-1"><button class="a-iconbtn" type="submit" aria-label="Nach oben" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
      <form method="post" action="/admin/kategorien" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="direction" value="1"><button class="a-iconbtn" type="submit" aria-label="Nach unten" <?= $i === count($categories) - 1 ? 'disabled' : '' ?>>↓</button></form>
      <form method="post" action="/admin/kategorien" class="a-inline" data-confirm="Kategorie „<?= e($c['name']) ?>“ löschen? Die Zuordnung wird bei <?= (int) $c['total_count'] ?> Galerien entfernt."><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="a-btn a-btn--sm a-btn--danger" type="submit">Löschen</button></form>
    </div>
  </li>
  <?php endforeach; ?>
</ol>
<p class="a-savestate" data-savestate hidden></p>
<?php endif; ?>
