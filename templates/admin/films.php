<?php
/** @var array $films */
use App\Csrf;
use App\Films;
use App\Images;
?>
<div class="a-head">
  <h1 class="a-title">Filme</h1>
  <a class="a-btn" href="/admin/filme/neu">Neuer Film</a>
</div>
<p class="a-help">Filme werden auf der Seite „Film“ in dieser Reihenfolge angezeigt. Der externe Player lädt erst nach Klick der Besucher.</p>
<?php if ($films === []): ?>
  <p class="a-help">Noch keine Filme.</p>
<?php else: ?>
<ol class="a-sortable" data-sortable="/admin/filme/sortieren" data-sortable-name="order">
  <?php foreach ($films as $i => $f): ?>
  <li class="a-sort-item" data-id="<?= $f['id'] ?>" draggable="true">
    <span class="a-thumb"><?php if ($f['poster']): $v = Images::variantFor($f['poster'], 480); ?><img src="<?= e(Images::variantUrl($f['poster'], $v, 'jpg', true)) ?>" alt=""><?php endif; ?></span>
    <div class="a-sort-item__body">
      <a class="a-list__title" href="/admin/filme/<?= $f['id'] ?>"><?= e($f['title']) ?></a>
      <div class="a-muted"><?= e(Films::PROVIDERS[$f['provider']] ?? $f['provider']) ?> · <?= e($f['video_id']) ?><?= $f['poster'] ? '' : ' · <span class="a-warn">kein Vorschaubild</span>' ?></div>
    </div>
    <span class="a-badge a-badge--<?= e($f['status']) ?>"><?= $f['status'] === 'published' ? 'Veröffentlicht' : 'Entwurf' ?></span>
    <div class="a-sort-item__actions">
      <a class="a-btn a-btn--sm a-btn--ghost" href="/admin/filme/<?= $f['id'] ?>">Bearbeiten</a>
    </div>
  </li>
  <?php endforeach; ?>
</ol>
<p class="a-savestate" data-savestate hidden></p>
<?php endif; ?>
