<?php
/**
 * Einzelnes Galeriebild als Lightbox-Link.
 * @var array $image
 * @var string $sizes
 * @var int $index
 * @var bool $admin
 * @var string|null $loading
 */
use App\Picture;

$admin = $admin ?? false;
$large = Picture::largestUrl($image, 'jpg', $admin);
$caption = trim((string) ($image['caption'] ?? ''));
?>
<figure class="figure" style="--ratio: <?= Picture::ratio($image) ?>">
  <a class="figure__link" href="<?= e($large ?? '#') ?>"
     data-lightbox="galerie"
     data-index="<?= (int) $index ?>"
     data-srcset="<?= e(Picture::srcset($image, 'webp', $admin)) ?>"
     data-srcset-jpg="<?= e(Picture::srcset($image, 'jpg', $admin)) ?>"
     data-width="<?= (int) $image['width'] ?>" data-height="<?= (int) $image['height'] ?>"
     data-caption="<?= e($caption) ?>"
     aria-label="<?= e($image['alt'] !== '' ? $image['alt'] : 'Bild ' . ($index + 1)) ?> – in groß ansehen">
    <?= Picture::render($image, ['sizes' => $sizes, 'loading' => $loading ?? 'lazy', 'admin' => $admin]) ?>
  </a>
  <?php if ($caption !== ''): ?>
  <figcaption class="figure__caption"><?= e($caption) ?></figcaption>
  <?php endif; ?>
</figure>
