<?php
/** @var string $title */
/** @var string $text */
/** @var string $slug */
/** @var array $documents  [['title' => string, 'href' => string, 'pages' => int, 'size' => string], …] */
$documents = $documents ?? [];
?>
<article class="legal">
  <header class="page-head">
    <h1 class="page-head__title"><?= e($title) ?></h1>
  </header>
  <div class="legal__body prose">
    <?php if (trim($text) === '' && $documents === []): ?>
      <p class="notice notice--info">Dieser Text ist noch nicht hinterlegt. Er kann im Adminbereich unter „Einstellungen“ gepflegt werden.</p>
    <?php else: ?>
      <?= format_text($text) ?>
    <?php endif; ?>
    <?php if ($documents !== []): ?>
    <ul class="doc-list" aria-label="Dokumente zum Herunterladen">
      <?php foreach ($documents as $doc): ?>
      <li class="doc-list__item">
        <a class="doc-list__link" href="<?= e($doc['href']) ?>" type="application/pdf">
          <span class="doc-list__title"><?= e($doc['title']) ?></span>
          <span class="doc-list__meta">PDF · <?= (int) $doc['pages'] ?> <?= (int) $doc['pages'] === 1 ? 'Seite' : 'Seiten' ?> · <?= e($doc['size']) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</article>
