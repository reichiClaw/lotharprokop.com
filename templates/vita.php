<?php
/** @var array|null $portrait */
use App\Picture;
use App\Settings;

$about = (string) Settings::get('about_text', '');
$services = array_values(array_filter(array_map('trim', explode("\n", (string) Settings::get('about_services', '')))));
$quotesRaw = trim((string) Settings::get('about_quotes', ''));
$quotes = [];
if ($quotesRaw !== '') {
    foreach (preg_split('/\n{2,}/', str_replace("\r", '', $quotesRaw)) ?: [] as $block) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
        if ($lines === []) {
            continue;
        }
        $author = '';
        if (count($lines) > 1 && str_starts_with(end($lines), '—')) {
            $author = trim(ltrim((string) array_pop($lines), '— '));
        }
        $quotes[] = ['text' => implode(' ', $lines), 'author' => $author];
    }
}
$email = (string) Settings::get('contact_email', '');
?>
<article class="vita">
  <header class="page-head">
    <h1 class="page-head__title">Lothar Prokop</h1>
    <p class="page-head__note"><?= e(Settings::get('site_tagline', '')) ?></p>
  </header>
  <div class="vita__body">
    <?php if ($portrait): ?>
    <div class="vita__portrait reveal">
      <?= Picture::render($portrait, ['sizes' => '(min-width: 900px) 40vw, 100vw', 'max' => 1600, 'alt' => $portrait['alt'] !== '' ? $portrait['alt'] : 'Porträt Lothar Prokop', 'loading' => 'eager']) ?>
    </div>
    <?php endif; ?>
    <div class="vita__text">
      <?= format_text($about) ?>
      <?php if ($services !== []): ?>
      <h2 class="vita__subtitle">Arbeitsfelder</h2>
      <ul class="vita__services">
        <?php foreach ($services as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <p class="vita__contact">
        <a class="link-arrow" href="/kontakt">Kontakt aufnehmen</a>
        <?php if ($email !== ''): ?><a class="link-arrow" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
      </p>
    </div>
  </div>
  <?php if ($quotes !== []): ?>
  <section class="quotes reveal" aria-labelledby="quotes-title">
    <h2 id="quotes-title" class="section-title">Stimmen</h2>
    <div class="quotes__list">
      <?php foreach ($quotes as $q): ?>
      <blockquote class="quote">
        <p><?= e($q['text']) ?></p>
        <?php if ($q['author'] !== ''): ?><footer class="quote__author"><?= e($q['author']) ?></footer><?php endif; ?>
      </blockquote>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</article>
