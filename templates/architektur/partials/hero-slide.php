<?php
/**
 * Eine Folie im Kopfbereich der Architektur-Startseite. Mit Projektverweis ist das Bild anklickbar.
 * @var array $slide  ['image' => array, 'gallery' => ?array]
 * @var int $index    Position (0 = beim Laden sichtbar)
 */
use App\Picture;

$image = $slide['image'];
$gallery = $slide['gallery'];
$first = $index === 0;
$alt = $image['alt'] !== '' ? $image['alt'] : ($gallery['title'] ?? '');
$picture = Picture::render($image, [
    'sizes' => '100vw',
    'cover' => true,
    'alt' => $alt,
    'loading' => $first ? 'eager' : 'lazy',
    'fetchpriority' => $first ? 'high' : null,
]);
?>
<div class="hero__slide<?= $first ? ' is-active' : '' ?>" data-hero-slide<?= $first ? '' : ' aria-hidden="true"' ?>>
  <?php if ($gallery !== null): ?>
  <a class="hero__link" href="<?= e(path('/projekte/' . eurl($gallery['slug']))) ?>"<?= $first ? '' : ' tabindex="-1"' ?> aria-label="Zum Projekt <?= e($gallery['title']) ?>"><?= $picture ?></a>
  <?php else: ?>
  <span class="hero__link"><?= $picture ?></span>
  <?php endif; ?>
</div>
