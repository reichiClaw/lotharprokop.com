<?php
/** @var array $films */
use App\Films;
use App\Picture;
?>
<section class="films" aria-labelledby="films-title">
  <header class="page-head">
    <h1 id="films-title" class="page-head__title">Film</h1>
    <p class="page-head__note">Videos werden erst nach dem Klick auf „Film abspielen“ von <?= e(implode(' bzw. ', array_unique(array_map(fn($f) => Films::PROVIDERS[$f['provider']] ?? $f['provider'], $films)) ?: ['YouTube'])) ?> geladen. Vorher wird keine Verbindung zu Drittanbietern aufgebaut.</p>
  </header>
  <?php if ($films === []): ?>
    <p class="empty">Derzeit sind keine Filme veröffentlicht.</p>
  <?php else: ?>
  <div class="films__list">
    <?php foreach ($films as $i => $f): ?>
    <article class="film reveal" id="film-<?= e($f['slug']) ?>">
      <div class="film__player" data-video data-embed="<?= e(Films::embedUrl($f)) ?>" data-title="<?= e($f['title']) ?>">
        <div class="film__poster">
          <?php if ($f['poster']): ?>
            <?= Picture::render($f['poster'], ['sizes' => '(min-width: 1200px) 1100px, 100vw', 'cover' => true, 'alt' => $f['poster']['alt'] !== '' ? $f['poster']['alt'] : 'Vorschaubild ' . $f['title'], 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
          <?php else: ?>
            <div class="film__poster-empty" aria-hidden="true"></div>
          <?php endif; ?>
        </div>
        <a class="film__play" href="<?= e(Films::watchUrl($f)) ?>" rel="noopener" data-play>
          <span class="film__play-icon" aria-hidden="true"></span>
          <span class="film__play-label">Film abspielen<span class="visually-hidden">: <?= e($f['title']) ?> (<?= e(Films::PROVIDERS[$f['provider']] ?? $f['provider']) ?>)</span></span>
        </a>
      </div>
      <div class="film__text">
        <h2 class="film__title"><?= e($f['title']) ?></h2>
        <?php $meta = array_filter([$f['client'], $f['year']]); ?>
        <?php if ($meta !== []): ?><p class="film__meta"><?= e(implode(' · ', $meta)) ?></p><?php endif; ?>
        <?php if ($f['description'] !== ''): ?><div class="film__desc"><?= format_text($f['description']) ?></div><?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
