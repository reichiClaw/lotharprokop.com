<?php
/**
 * Eine Folie im Kopfbereich der Startseite.
 * @var array $slide  ['image' => array, 'gallery' => array|null]
 * @var int $index    Position (0 = beim Laden sichtbar)
 */
use App\Picture;

$image = $slide['image'];
$gallery = $slide['gallery'];
$first = $index === 0;
$alt = $image['alt'] !== '' ? $image['alt'] : ($gallery !== null ? $gallery['title'] : 'Fotografie von Lothar Prokop');
?>
<div class="hero__slide<?= $first ? ' is-active' : '' ?>" data-hero-slide<?= $first ? '' : ' aria-hidden="true"' ?>>
  <?php if ($gallery !== null): ?><a class="hero__link" href="/fotografie/<?= eurl($gallery['slug']) ?>"<?= $first ? '' : ' tabindex="-1"' ?> aria-label="Zum Projekt <?= e($gallery['title']) ?>"><?php endif; ?>
  <?= Picture::render($image, [
      'sizes' => '100vw',
      'cover' => true,
      'alt' => $alt,
      'loading' => $first ? 'eager' : 'lazy',
      'fetchpriority' => $first ? 'high' : null,
  ]) ?>
  <?php if ($gallery !== null): ?></a><?php endif; ?>
</div>
