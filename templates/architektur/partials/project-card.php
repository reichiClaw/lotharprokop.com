<?php
/**
 * Projektverweis: Titelbild im festen Format, darunter eine Zeile mit Titel, Kategorie und Jahr.
 * @var array $gallery
 * @var string $sizes
 * @var string|null $loading
 * @var string|null $index   Laufende Nummer, z. B. '01'
 */
use App\Picture;

$cover = $gallery['cover'] ?? null;
$cats = array_column($gallery['categories'] ?? [], 'name');
$catSlugs = array_column($gallery['categories'] ?? [], 'slug');
$portrait = $cover ? Picture::isPortrait($cover) : false;
?>
<article class="card<?= $portrait ? ' card--portrait' : '' ?>" data-categories="<?= e(implode(' ', $catSlugs)) ?>">
  <a class="card__link" href="<?= e(path('/projekte/' . eurl($gallery['slug']))) ?>">
    <div class="card__media">
      <?php if ($cover): ?>
        <?= Picture::render($cover, ['sizes' => $sizes, 'cover' => true, 'alt' => $cover['alt'] !== '' ? $cover['alt'] : $gallery['title'], 'loading' => $loading ?? 'lazy', 'max' => 1600]) ?>
      <?php else: ?>
        <div class="card__empty" aria-hidden="true"></div>
      <?php endif; ?>
    </div>
    <div class="card__text">
      <?php if (!empty($index)): ?><span class="card__index"><?= e($index) ?></span><?php endif; ?>
      <h3 class="card__title"><?= e($gallery['title']) ?></h3>
      <p class="card__meta">
        <?php if ($cats !== []): ?><span><?= e(implode(', ', $cats)) ?></span><?php endif; ?>
        <?php if ($gallery['year'] !== ''): ?><span><?= e($gallery['year']) ?></span><?php endif; ?>
      </p>
    </div>
  </a>
</article>
