<?php
/**
 * Projektkarte (Titelbild mit Fokuspunkt, Titel, Kategorien).
 * @var array $gallery
 * @var string $sizes
 * @var string $ratioClass  z. B. 'card--landscape' | 'card--portrait'
 * @var string|null $loading
 */
use App\Picture;

$cover = $gallery['cover'] ?? null;
$portrait = $cover ? Picture::isPortrait($cover) : false;
$cats = array_column($gallery['categories'] ?? [], 'name');
$catSlugs = array_column($gallery['categories'] ?? [], 'slug');
?>
<article class="card <?= $portrait ? 'card--portrait' : 'card--landscape' ?> <?= e($ratioClass ?? '') ?>" data-categories="<?= e(implode(' ', $catSlugs)) ?>">
  <a class="card__link" href="/fotografie/<?= eurl($gallery['slug']) ?>">
    <div class="card__media">
      <?php if ($cover): ?>
        <?= Picture::render($cover, ['sizes' => $sizes, 'cover' => true, 'alt' => $cover['alt'] !== '' ? $cover['alt'] : $gallery['title'], 'loading' => $loading ?? 'lazy', 'max' => 1600]) ?>
      <?php else: ?>
        <div class="card__empty" aria-hidden="true"></div>
      <?php endif; ?>
    </div>
    <div class="card__text">
      <h3 class="card__title"><?= e($gallery['title']) ?></h3>
      <?php if ($cats !== []): ?><p class="card__meta"><?= e(implode(', ', $cats)) ?></p><?php endif; ?>
    </div>
  </a>
</article>
