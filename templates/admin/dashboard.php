<?php
/** @var array $counts */
/** @var array $recent */
/** @var array|null $hero */
use App\Galleries;
use App\Images;
?>
<div class="a-head">
  <h1 class="a-title">Übersicht</h1>
  <a class="a-btn" href="/admin/galerien/neu">Neue Galerie</a>
</div>

<div class="a-stats">
  <a class="a-stat" href="/admin/galerien?status=published"><strong><?= $counts['published'] ?></strong><span>veröffentlichte Galerien</span></a>
  <a class="a-stat" href="/admin/galerien?status=draft"><strong><?= $counts['draft'] ?></strong><span>Entwürfe</span></a>
  <a class="a-stat" href="/admin/galerien?status=archived"><strong><?= $counts['archived'] ?></strong><span>archiviert</span></a>
  <a class="a-stat" href="/admin/galerien"><strong><?= $counts['images'] ?></strong><span>Bilder</span></a>
  <a class="a-stat" href="/admin/filme"><strong><?= $counts['films'] ?></strong><span>Filme online</span></a>
</div>

<div class="a-grid-2">
  <section>
    <h2 class="a-subtitle">Zuletzt bearbeitet</h2>
    <?php if ($recent === []): ?>
      <p class="a-help">Noch keine Galerien. <a href="/admin/galerien/neu">Erste Galerie anlegen</a> oder Bestandsinhalte per <code>php bin/import-legacy.php</code> importieren.</p>
    <?php else: ?>
    <ul class="a-list">
      <?php foreach ($recent as $g): ?>
      <li class="a-list__item">
        <span class="a-thumb a-thumb--sm"><?php if ($g['cover']): $v = Images::variantFor($g['cover'], 480); ?><img src="<?= e(Images::variantUrl($g['cover'], $v, 'jpg', true)) ?>" alt="" style="object-position:<?= $g['cover']['focus_x'] * 100 ?>% <?= $g['cover']['focus_y'] * 100 ?>%"><?php endif; ?></span>
        <a class="a-list__title" href="/admin/galerien/<?= $g['id'] ?>"><?= e($g['title']) ?></a>
        <span class="a-badge a-badge--<?= e($g['status']) ?>"><?= e(Galleries::STATUSES[$g['status']]) ?></span>
        <span class="a-muted"><?= $g['image_count'] ?> Bilder</span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
  <section>
    <h2 class="a-subtitle">Startbild</h2>
    <?php if ($hero): $v = Images::variantFor($hero, 960); ?>
      <a href="/admin/startseite"><img class="a-preview" src="<?= e(Images::variantUrl($hero, $v, 'jpg', true)) ?>" alt="" style="aspect-ratio: 16/9; object-fit: cover; object-position:<?= $hero['focus_x'] * 100 ?>% <?= $hero['focus_y'] * 100 ?>%"></a>
    <?php else: ?>
      <p class="a-help">Noch kein Startbild gewählt. <a href="/admin/startseite">Jetzt festlegen</a>.</p>
    <?php endif; ?>
  </section>
</div>
