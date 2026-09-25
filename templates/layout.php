<?php
/** @var array $meta */
/** @var string $content */
$current = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$nav = [
    '/fotografie' => 'Fotografie',
    '/film' => 'Film',
    '/vita' => 'Vita',
    '/kontakt' => 'Kontakt',
];
$isHome = $current === '/';
$email = (string) App\Settings::get('contact_email', '');
$phone = (string) App\Settings::get('contact_phone', '');
$phoneLink = (string) App\Settings::get('contact_phone_link', '');
$instagram = (string) App\Settings::get('social_instagram', '');
$facebook = (string) App\Settings::get('social_facebook', '');
$linkedin = (string) App\Settings::get('social_linkedin', '');
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
<link rel="canonical" href="<?= e($meta['canonical']) ?>">
<meta property="og:site_name" content="<?= e(App\Config::get('site_name')) ?>">
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
<link rel="icon" href="/assets/img/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/cormorant-garamond.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/ibm-plex-sans.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/site.css?v=<?= e(App\Config::get('asset_version', '1')) ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body class="<?= $isHome ? 'is-home' : '' ?>">
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
<header class="site-header" id="oben">
  <div class="site-header__inner">
    <a class="brand" href="/" <?= $isHome ? 'aria-current="page"' : '' ?>>
      <img src="/assets/img/logo.png" alt="Lothar Prokop Fotografie" width="999" height="233" class="brand__logo" decoding="async">
    </a>
    <nav class="site-nav" aria-label="Hauptnavigation">
      <ul class="site-nav__list">
        <?php foreach ($nav as $href => $label): $active = str_starts_with($current, $href); ?>
        <li><a href="<?= e($href) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a></li>
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
    <div class="site-footer__col">
      <p class="site-footer__name">Lothar Prokop<br><span>Fotograf, Ried im Innkreis</span></p>
    </div>
    <div class="site-footer__col">
      <?php if ($email !== ''): ?><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
      <?php if ($phone !== ''): ?><p><a href="tel:<?= e($phoneLink ?: preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></p><?php endif; ?>
    </div>
    <div class="site-footer__col">
      <ul class="site-footer__links">
        <?php if ($instagram !== ''): ?><li><a href="<?= e($instagram) ?>" rel="noopener me">Instagram</a></li><?php endif; ?>
        <?php if ($facebook !== ''): ?><li><a href="<?= e($facebook) ?>" rel="noopener me">Facebook</a></li><?php endif; ?>
        <?php if ($linkedin !== ''): ?><li><a href="<?= e($linkedin) ?>" rel="noopener me">LinkedIn</a></li><?php endif; ?>
      </ul>
    </div>
    <div class="site-footer__col">
      <ul class="site-footer__links">
        <li><a href="/impressum">Impressum</a></li>
        <li><a href="/datenschutz">Datenschutz</a></li>
        <li><a href="/bildrechte">Bildrechte</a></li>
      </ul>
    </div>
  </div>
  <p class="site-footer__copy">© <?= date('Y') ?> Lothar Prokop. Alle Fotografien urheberrechtlich geschützt.</p>
</footer>
<script src="/assets/js/site.js?v=<?= e(App\Config::get('asset_version', '1')) ?>" defer></script>
</body>
</html>
