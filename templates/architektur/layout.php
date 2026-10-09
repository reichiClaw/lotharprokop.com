<?php
/**
 * Seitengerüst der Architekturseite: dunkle Fläche, Haarlinien, Plan-Motive (Maßlinien, Plankopf, Blattnummern).
 * Alle Links laufen über path(), damit die Seite als Unterordner und unter eigener Domain funktioniert.
 * @var array $meta
 * @var string $content
 */
use App\Architektur;
use App\Settings;
use App\Site;

$current = Site::requestPath();
$nav = [
    '/leistungen' => 'Leistungen',
    '/projekte' => 'Projekte',
    '/profil' => 'Profil',
    '/kontakt' => 'Kontakt',
];
$isHome = $current === '/';
$email = (string) Settings::get('contact_email', '');
$phone = (string) Settings::get('contact_phone', '');
$phoneLink = (string) Settings::get('contact_phone_link', '');
$instagram = (string) Settings::get('social_instagram', '');
$linkedin = (string) Settings::get('social_linkedin', '');
$facebook = (string) Settings::get('social_facebook', '');
$assetVersion = (string) App\Config::get('asset_version', App\View::ASSET_VERSION);
$mainSite = App\Config::baseUrl();
// Blattbezeichnung in der Legende: erster Pfadabschnitt, z. B. „projekte“.
$sheet = trim(explode('/', trim($current, '/'))[0] ?? '');
$sheet = $sheet === '' ? 'start' : $sheet;
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($meta['full_title']) ?></title>
<?php if ($meta['description'] !== ''): ?>
<meta name="description" content="<?= e($meta['description']) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($meta['robots']) ?>">
<meta name="color-scheme" content="dark">
<meta name="theme-color" content="#0b0b0c">
<link rel="canonical" href="<?= e($meta['canonical']) ?>">
<meta property="og:site_name" content="<?= e(Site::name()) ?>">
<meta property="og:type" content="<?= e($meta['type']) ?>">
<meta property="og:title" content="<?= e($meta['full_title']) ?>">
<?php if ($meta['description'] !== ''): ?>
<meta property="og:description" content="<?= e($meta['description']) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= e($meta['canonical']) ?>">
<meta property="og:locale" content="de_AT">
<?php if (!empty($meta['image'])): ?>
<meta property="og:image" content="<?= e($meta['image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<link rel="icon" href="<?= e(path('/assets/img/favicon.png')) ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= e(path('/assets/img/apple-touch-icon.png')) ?>">
<link rel="preload" href="<?= e(path('/assets/fonts/barlow-condensed-300.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(path('/assets/fonts/inter.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(path('/assets/css/architektur.css')) ?>?v=<?= e($assetVersion) ?>">
<script><?= App\View::JS_BOOT ?></script>
</head>
<body class="arch<?= $isHome ? ' is-home' : '' ?>">
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
<header class="site-header" id="oben">
  <div class="site-header__inner">
    <a class="brand" href="<?= e(path('/')) ?>" <?= $isHome ? 'aria-current="page"' : '' ?>>
      <span class="brand__mark" aria-hidden="true"></span>
      <span class="brand__text">
        <span class="brand__name">Lothar Prokop</span>
        <span class="brand__field">Architekturfotografie</span>
      </span>
    </a>
    <nav class="site-nav" aria-label="Hauptnavigation">
      <ul class="site-nav__list">
        <?php $n = 0; foreach ($nav as $href => $label): $n++; $active = str_starts_with($current, $href); ?>
        <li><a href="<?= e(path($href)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><span class="site-nav__num" aria-hidden="true"><?= sprintf('%02d', $n) ?></span><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<main id="inhalt" class="site-main" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="site-footer__inner">
    <div class="dim dim--footer" aria-hidden="true"><span class="dim__label">Legende</span></div>
    <div class="site-footer__cols">
      <div class="site-footer__col site-footer__col--name">
        <p class="site-footer__name">Lothar Prokop</p>
        <p class="site-footer__sub">Architekturfotografie<br>Ried im Innkreis, Österreich</p>
      </div>
      <div class="site-footer__col">
        <p class="site-footer__label">Kontakt</p>
        <?php if ($email !== ''): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
        <?php if ($phone !== ''): ?><p><a href="tel:<?= e($phoneLink ?: preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></p><?php endif; ?>
      </div>
      <div class="site-footer__col">
        <p class="site-footer__label">Leistungen</p>
        <ul class="site-footer__links">
          <?php $n = 0; foreach (Architektur::SERVICES as $slug => $service): $n++; ?>
          <li><a href="<?= e(path('/leistungen/' . $slug)) ?>"><span class="site-footer__num"><?= sprintf('%02d', $n) ?></span><?= e($service['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="site-footer__col">
        <p class="site-footer__label">Weiteres</p>
        <ul class="site-footer__links">
          <?php if ($mainSite !== ''): ?><li><a href="<?= e($mainSite) ?>" rel="noopener">Fotografie – lotharprokop.com</a></li><?php endif; ?>
          <?php if ($instagram !== ''): ?><li><a href="<?= e($instagram) ?>" rel="noopener me">Instagram</a></li><?php endif; ?>
          <?php if ($linkedin !== ''): ?><li><a href="<?= e($linkedin) ?>" rel="noopener me">LinkedIn</a></li><?php endif; ?>
          <?php if ($facebook !== ''): ?><li><a href="<?= e($facebook) ?>" rel="noopener me">Facebook</a></li><?php endif; ?>
          <li><a href="<?= e(path('/impressum')) ?>">Impressum</a></li>
          <li><a href="<?= e(path('/datenschutz')) ?>">Datenschutz</a></li>
          <li><a href="<?= e(path('/bildrechte')) ?>">Bildrechte</a></li>
        </ul>
      </div>
    </div>
    <dl class="site-footer__legend">
      <div><dt>Blatt</dt><dd><?= e($sheet) ?></dd></div>
      <div><dt>Stand</dt><dd><?= date('Y') ?></dd></div>
      <div><dt>Maßstab</dt><dd>1 : 1</dd></div>
      <div><dt>Urheber</dt><dd>© Lothar Prokop – alle Fotografien urheberrechtlich geschützt</dd></div>
    </dl>
  </div>
</footer>
<script src="<?= e(path('/assets/js/site.js')) ?>?v=<?= e($assetVersion) ?>" defer></script>
<script src="<?= e(path('/assets/js/architektur.js')) ?>?v=<?= e($assetVersion) ?>" defer></script>
</body>
</html>
