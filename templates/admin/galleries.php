<?php
/** @var array $galleries */
/** @var string $status */
use App\Csrf;
use App\Galleries;
use App\Images;
?>
<div class="a-head">
  <h1 class="a-title">Galerien</h1>
  <a class="a-btn" href="/admin/galerien/neu">Neue Galerie</a>
</div>

<nav class="a-tabs" aria-label="Status filtern">
  <a href="/admin/galerien" <?= $status === '' ? 'aria-current="true"' : '' ?>>Alle</a>
  <?php foreach (Galleries::STATUSES as $key => $label): ?>
  <a href="/admin/galerien?status=<?= e($key) ?>" <?= $status === $key ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($galleries === []): ?>
  <p class="a-help">Keine Galerien in dieser Ansicht.</p>
<?php else: ?>
<p class="a-help">Reihenfolge = Reihenfolge auf der Seite „Fotografie“. Ziehen mit der Maus oder Pfeiltasten verwenden.<?= $status !== '' ? ' (Sortieren nur in der Ansicht „Alle“ möglich.)' : '' ?></p>
<ol class="a-sortable" data-sortable="<?= $status === '' ? '/admin/galerien/sortieren' : '' ?>" data-sortable-name="order">
  <?php foreach ($galleries as $i => $g): ?>
  <li class="a-sort-item" data-id="<?= $g['id'] ?>" draggable="<?= $status === '' ? 'true' : 'false' ?>">
    <span class="a-thumb"><?php if ($g['cover']): $v = Images::variantFor($g['cover'], 480); ?><img src="<?= e(Images::variantUrl($g['cover'], $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $g['cover']['focus_x'] * 100 ?>% <?= $g['cover']['focus_y'] * 100 ?>%"><?php endif; ?></span>
    <div class="a-sort-item__body">
      <a class="a-list__title" href="/admin/galerien/<?= $g['id'] ?>"><?= e($g['title']) ?></a>
      <div class="a-muted">
        /fotografie/<?= e($g['slug']) ?> · <?= $g['image_count'] ?> Bilder · <?= e(Galleries::LAYOUTS[$g['layout']] ?? $g['layout']) ?>
        <?php if ($g['categories']): ?> · <?= e(implode(', ', array_column($g['categories'], 'name'))) ?><?php endif; ?>
      </div>
    </div>
    <span class="a-badge a-badge--<?= e($g['status']) ?>"><?= e(Galleries::STATUSES[$g['status']]) ?></span>
    <?php if ($g['featured']): ?><span class="a-badge a-badge--featured" title="Auf der Startseite">Startseite</span><?php endif; ?>
    <div class="a-sort-item__actions">
      <?php if ($g['status'] !== 'published'): ?>
      <form method="post" action="/admin/galerien/<?= $g['id'] ?>/status"><?= Csrf::field() ?><input type="hidden" name="status" value="published"><input type="hidden" name="back" value="list"><button class="a-btn a-btn--sm" type="submit">Veröffentlichen</button></form>
      <?php else: ?>
      <a class="a-btn a-btn--sm a-btn--ghost" href="/fotografie/<?= eurl($g['slug']) ?>" target="_blank" rel="noopener">Ansehen</a>
      <?php endif; ?>
      <?php if ($status === ''): ?>
      <form method="post" action="/admin/galerien/sortieren" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $g['id'] ?>"><input type="hidden" name="direction" value="-1"><button class="a-iconbtn" type="submit" aria-label="Nach oben" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
      <form method="post" action="/admin/galerien/sortieren" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $g['id'] ?>"><input type="hidden" name="direction" value="1"><button class="a-iconbtn" type="submit" aria-label="Nach unten" <?= $i === count($galleries) - 1 ? 'disabled' : '' ?>>↓</button></form>
      <?php endif; ?>
    </div>
  </li>
  <?php endforeach; ?>
</ol>
<p class="a-savestate" data-savestate hidden></p>
<?php endif; ?>
