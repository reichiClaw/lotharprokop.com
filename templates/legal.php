<?php
/** @var string $title */
/** @var string $text */
/** @var string $slug */
?>
<article class="legal">
  <header class="page-head">
    <h1 class="page-head__title"><?= e($title) ?></h1>
  </header>
  <div class="legal__body prose">
    <?php if (trim($text) === ''): ?>
      <p class="notice notice--info">Dieser Text ist noch nicht hinterlegt. Er kann im Adminbereich unter „Einstellungen“ gepflegt werden.</p>
    <?php else: ?>
      <?= format_text($text) ?>
    <?php endif; ?>
  </div>
</article>
