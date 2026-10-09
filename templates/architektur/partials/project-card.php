<?php
/**
 * Projektverweis („Blatt“): Titelbild im festen Format, darunter Blattnummer, Titel, Kategorie und Jahr.
 * Beim Überfahren hebt sich ein Hinweis aus dem Bild, die Blattnummer wird rot.
 * @var array $gallery
 * @var string $sizes
 * @var string|null $loading
 * @var string|null $index   Laufende Nummer, z. B. '01'
 * @var string|null $size    'xl' | 'md' | 'sm' – Bildformat innerhalb des Blattrasters
 */
use App\Picture;

$cover = $gallery['cover'] ?? null;
$cats = array_column($gallery['categories'] ?? [], 'name');
$catSlugs = array_column($gallery['categories'] ?? [], 'slug');
$size = $size ?? 'md';
$max = match ($size) { 'xl' => 2400, 'sm' => 1200, default => 1600 };
?>
<article class="card card--<?= e($size) ?>" data-categories="<?= e(implode(' ', $catSlugs)) ?>">
  <a class="card__link" href="<?= e(path('/projekte/' . eurl($gallery['slug']))) ?>">
    <div class="card__media">
      <?php if ($cover): ?>
        <?= Picture::render($cover, ['sizes' => $sizes, 'cover' => true, 'alt' => $cover['alt'] !== '' ? $cover['alt'] : $gallery['title'], 'loading' => $loading ?? 'lazy', 'max' => $max]) ?>
      <?php else: ?>
        <div class="card__empty" aria-hidden="true"></div>
      <?php endif; ?>
      <span class="card__hover" aria-hidden="true"><?= (int) ($gallery['image_count'] ?? 0) > 0 ? (int) $gallery['image_count'] . ' Ansichten' : 'Projekt' ?> – ansehen</span>
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
