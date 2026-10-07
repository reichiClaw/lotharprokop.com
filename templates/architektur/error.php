<?php
/**
 * @var string $title
 * @var int $code
 * @var string $message
 */
?>
<article class="error-page">
  <header class="page-head">
    <p class="page-head__kicker"><?= (int) $code ?></p>
    <h1 class="page-head__title"><?= e($title) ?></h1>
    <p class="page-head__note"><?= e($message) ?></p>
  </header>
  <p class="error-page__links">
    <a class="link-arrow" href="<?= e(path('/')) ?>">Startseite</a>
    <a class="link-arrow" href="<?= e(path('/leistungen')) ?>">Leistungen</a>
    <a class="link-arrow" href="<?= e(path('/projekte')) ?>">Projekte</a>
    <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontakt</a>
  </p>
</article>
