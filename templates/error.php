<?php
/** @var string $title */
/** @var int $code */
/** @var string $message */
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
</article>
