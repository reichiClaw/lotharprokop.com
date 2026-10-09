<?php
/**
 * Bildauswahl: alle ausgewählten Fotografien (unabhängig von Galerien), editorial gesetzt, mit Lightbox.
 * @var array $images
 * @var string $intro
 */
use App\Layout;
use App\View;

$count = count($images);
?>
<section class="selection-page" aria-labelledby="selection-title">
  <header class="page-head">
    <p class="page-head__back"><a href="/fotografie" class="link-back">Fotografie</a></p>
    <h1 id="selection-title" class="page-head__title">Ausgewählte Fotografien</h1>
    <?php if ($intro !== ''): ?><p class="page-head__note"><?= e($intro) ?></p><?php endif; ?>
    <p class="visually-hidden"><?= $count ?> <?= $count === 1 ? 'Bild' : 'Bilder' ?></p>
  </header>

  <?php if ($images === []): ?>
    <p class="empty">Derzeit sind keine Bilder ausgewählt.</p>
  <?php else: ?>
  <div class="series series--editorial">
    <?php $i = 0; foreach (Layout::editorial($images) as $block): ?>
      <div class="ed ed--<?= e($block['type']) ?> reveal">
        <?php foreach ($block['items'] as $img): ?>
          <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => match ($block['type']) { 'wide' => '(min-width: 1740px) 1560px, 92vw', 'pair', 'pair-landscape' => '(min-width: 700px) 46vw, 100vw', 'inset' => '(min-width: 700px) 66vw, 100vw', default => '(min-width: 700px) 52vw, 100vw' }, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
          <?php $i++; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
