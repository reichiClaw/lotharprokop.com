<?php
/**
 * Projektübersicht: strenges Raster mit Haarlinien, Filter nach Kategorie.
 * @var array $categories
 * @var array|null $active
 * @var array $galleries
 */
use App\View;

$count = count($galleries);
?>
<section class="portfolio projects" aria-labelledby="projects-title">
  <header class="page-head">
    <p class="page-head__kicker">Referenzen</p>
    <h1 id="projects-title" class="page-head__title">Projekte</h1>
    <?php if ($categories !== []): ?>
    <nav class="filter" aria-label="Nach Kategorie filtern" data-filter>
      <ul class="filter__list">
        <li><a href="<?= e(path('/projekte')) ?>" class="filter__link" <?= $active === null ? 'aria-current="true"' : '' ?> data-filter-slug="">Alle</a></li>
        <?php foreach ($categories as $c): ?>
        <li><a href="<?= e(path('/projekte?kategorie=' . eurl($c['slug']))) ?>" class="filter__link" <?= $active && $active['id'] == $c['id'] ? 'aria-current="true"' : '' ?> data-filter-slug="<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </header>

  <?php if ($galleries === []): ?>
    <p class="empty"><?= $active ? 'In dieser Kategorie sind derzeit keine Projekte veröffentlicht.' : 'Derzeit sind noch keine Projekte veröffentlicht.' ?></p>
  <?php else: ?>
  <div class="grid-lines grid-lines--2 projects__grid" id="projekte" data-filter-target aria-live="polite">
    <?php foreach ($galleries as $i => $g): ?>
    <div class="cell reveal">
      <?= View::partial('partials/project-card', ['gallery' => $g, 'sizes' => '(min-width: 760px) 50vw, 100vw', 'loading' => $i < 2 ? 'eager' : 'lazy', 'index' => sprintf('%02d', $i + 1)]) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($count % 2 === 1): ?><div class="cell cell--fill" aria-hidden="true"></div><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
