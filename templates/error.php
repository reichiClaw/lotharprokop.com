<?php
/** @var string $title */
/** @var int $code */
/** @var string $message */
/** @var array|null $focusGallery  Nur auf der 404-Seite bei aktiver Spielerei „Autofokus“ */
use App\Picture;

$focusGallery = $focusGallery ?? null;
$focusCover = $focusGallery['cover'] ?? null;
?>
<article class="error-page">
  <header class="page-head">
    <p class="page-head__note"><?= (int) $code ?></p>
    <h1 class="page-head__title"><?= e($title) ?></h1>
  </header>
  <p><?= e($message) ?></p>
  <p class="error-page__links">
    <a class="link-arrow" href="/">Startseite</a>
    <a class="link-arrow" href="/fotografie">Fotografie</a>
    <a class="link-arrow" href="/kontakt">Kontakt</a>
  </p>
  <?php if ($focusCover): ?>
  <?php /* Spielerei: unscharfes Foto, der Fokusrahmen folgt dem Zeiger und stellt beim Verweilen scharf (site.js). */ ?>
  <a class="af <?= Picture::isPortrait($focusCover) ? 'af--portrait' : 'af--landscape' ?>" href="/fotografie/<?= eurl($focusGallery['slug']) ?>" data-af aria-label="Zum Projekt „<?= e($focusGallery['title']) ?>“">
    <span class="af__media"><?= Picture::render($focusCover, ['sizes' => '(min-width: 900px) 62vw, 100vw', 'cover' => true, 'loading' => 'eager', 'fetchpriority' => 'low', 'max' => 1600, 'alt' => '']) ?></span>
    <span class="af__frame" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
    <span class="af__caption" aria-hidden="true"><span class="af__status" data-af-status>Fokus suchen …</span><span class="af__title"><?= e($focusGallery['title']) ?></span></span>
  </a>
  <?php endif; ?>
</article>
