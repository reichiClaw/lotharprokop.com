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
    // Editorialer Rhythmus statt gleichförmigem Kachelraster: volle Breite, zwei nebeneinander,
    // eingerücktes Einzelbild, Hochformatpaare. Titelbilder werden dafür auf 3:2 bzw. 4:5 normalisiert.
    $normalized = array_map(static function ($g) {
        $cover = $g['cover'];
        $portrait = $cover && $cover['height'] > $cover['width'];
        return ['gallery' => $g, 'width' => $portrait ? 4 : 3, 'height' => $portrait ? 5 : 2];
    }, $galleries);
    $i = 0;
    foreach (Layout::editorial($normalized) as $block):
        $sizes = match ($block['type']) {
            'pair', 'pair-landscape' => '(min-width: 700px) 45vw, 100vw',
            'inset' => '(min-width: 700px) 60vw, 100vw',
            'single-portrait' => '(min-width: 700px) 42vw, 100vw',
            default => '(min-width: 700px) 90vw, 100vw',
        };
    ?>
    <div class="ed ed--<?= e($block['type']) ?> reveal">
      <?php foreach ($block['items'] as $item): ?>
        <?= App\View::partial('partials/gallery-card', ['gallery' => $item['gallery'], 'sizes' => $sizes, 'loading' => $i < 2 ? 'eager' : 'lazy']) ?>
        <?php $i++; ?>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
