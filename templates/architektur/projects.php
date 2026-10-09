<?php
/**
 * Projektübersicht: Blattraster im editorialen Rhythmus, links ein haftender Index nach Kategorie mit Zählern.
 * @var array $categories
 * @var array|null $active
 * @var array $galleries
 * @var int $total
 */
use App\View;
?>
<section class="portfolio projects" aria-labelledby="projects-title">
  <header class="page-head page-head--giant">
    <p class="page-head__kicker">Referenzen</p>
    <h1 id="projects-title" class="page-head__title">Projekte</h1>
    <span class="giant page-head__giant" aria-hidden="true"><?= sprintf('%02d', (int) $total) ?></span>
  </header>
  <div class="projects__body">
    <?php if ($categories !== []): ?>
    <nav class="filter" aria-label="Nach Kategorie filtern" data-filter>
      <p class="filter__label">Index</p>
      <ul class="filter__list">
        <li><a href="<?= e(path('/projekte')) ?>" class="filter__link" <?= $active === null ? 'aria-current="true"' : '' ?> data-filter-slug=""><span class="filter__name">Alle Projekte</span><span class="filter__count"><?= sprintf('%02d', (int) $total) ?></span></a></li>
        <?php foreach ($categories as $c): ?>
        <li><a href="<?= e(path('/projekte?kategorie=' . eurl($c['slug']))) ?>" class="filter__link" <?= $active && $active['id'] == $c['id'] ? 'aria-current="true"' : '' ?> data-filter-slug="<?= e($c['slug']) ?>"><span class="filter__name"><?= e($c['name']) ?></span><span class="filter__count"><?= sprintf('%02d', (int) $c['published_count']) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>
    <div class="projects__sheets" id="projekte" data-filter-target aria-live="polite">
      <?php if ($galleries !== []): ?>
      <?= View::partial('partials/project-sheets', ['galleries' => $galleries, 'eager' => 1]) ?>
      <?php endif; ?>
    </div>
    <?php if ($galleries === []): ?>
    <p class="empty"><?= $active ? 'In dieser Kategorie sind derzeit keine Projekte veröffentlicht.' : 'Derzeit sind noch keine Projekte veröffentlicht.' ?></p>
    <?php endif; ?>
  </div>
</section>
