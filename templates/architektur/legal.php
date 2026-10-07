<?php
/**
 * Impressum, Datenschutz, Bildrechte – dieselben Texte wie auf der Hauptseite (Admin → Einstellungen).
 * @var string $title
 * @var string $text
 * @var string $slug
 */
?>
<article class="legal">
  <header class="page-head">
    <p class="page-head__kicker">Rechtliches</p>
    <h1 class="page-head__title"><?= e($title) ?></h1>
  </header>
  <div class="legal__body prose">
    <?php if (trim($text) === ''): ?>
      <p class="notice">Dieser Text ist noch nicht hinterlegt. Er wird im Adminbereich der Hauptseite unter „Einstellungen“ gepflegt und gilt für beide Auftritte.</p>
    <?php else: ?>
      <?= format_text($text) ?>
    <?php endif; ?>
  </div>
</article>
