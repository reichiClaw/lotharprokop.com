<?php
/** @var array $categories */
/** @var array|null $active */
/** @var array $galleries */
use App\Layout;
?>
<section class="portfolio" aria-labelledby="portfolio-title">
  <header class="page-head">
    <h1 id="portfolio-title" class="page-head__title">Fotografie</h1>
    <?php if ($categories !== []): ?>
    <nav class="filter" aria-label="Nach Kategorie filtern" data-filter>
      <ul class="filter__list">
        <li><a href="/fotografie" class="filter__link" <?= $active === null ? 'aria-current="true"' : '' ?> data-filter-slug="">Alle</a></li>
        <?php foreach ($categories as $c): ?>
        <li><a href="/fotografie?kategorie=<?= eurl($c['slug']) ?>" class="filter__link" <?= $active && $active['id'] == $c['id'] ? 'aria-current="true"' : '' ?> data-filter-slug="<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </header>

  <?php if ($galleries === []): ?>
    <p class="empty">Derzeit sind in dieser Kategorie keine Projekte veröffentlicht.</p>
  <?php else: ?>
  <div class="portfolio__grid" id="projekte" data-filter-target aria-live="polite">
    <?php
    // Reihen gleicher Höhe aus Titelbildern: Hochformate 4:5, Querformate 3:2.
    $normalized = array_map(static function ($g) {
        $cover = $g['cover'];
        $portrait = $cover && $cover['height'] > $cover['width'];
        return ['gallery' => $g, 'width' => $portrait ? 4 : 3, 'height' => $portrait ? 5 : 2];
    }, $galleries);
    foreach (Layout::justified($normalized, 2.6, 3) as $row):
    ?>
    <div class="row <?= !empty($row['last']) ? 'row--last' : '' ?>" style="--row-ratio: <?= round($row['ratio'], 4) ?>; --n: <?= count($row['items']) ?>">
      <?php foreach ($row['items'] as $item): $g = $item['gallery']; $ratio = $item['width'] / $item['height']; ?>
      <div class="row__item reveal" style="--ratio: <?= $item['width'] ?> / <?= $item['height'] ?>; --flex: <?= round($ratio, 4) ?>">
        <?= App\View::partial('partials/gallery-card', ['gallery' => $g, 'sizes' => '(min-width: 700px) ' . round(100 * $ratio / $row['ratio']) . 'vw, 100vw']) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
