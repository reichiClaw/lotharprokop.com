<?php
/** @var array $meta */
/** @var string $content */
/** @var array|null $flash */
use App\Auth;
use App\Csrf;

$loggedIn = Auth::check();
$current = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$nav = [
    '/admin' => 'Übersicht',
    '/admin/galerien' => 'Galerien',
    '/admin/startseite' => 'Startseite',
    '/admin/kategorien' => 'Kategorien',
    '/admin/filme' => 'Filme',
    '/admin/einstellungen' => 'Einstellungen',
    '/admin/system' => 'System',
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($meta['title'] !== '' ? $meta['title'] . ' – Verwaltung' : 'Verwaltung') ?></title>
<link rel="icon" href="/assets/img/favicon.png" type="image/png">
<link rel="stylesheet" href="/assets/css/admin.css?v=<?= e(App\Config::get('asset_version', '1')) ?>">
<?php if ($loggedIn): ?><meta name="csrf-token" content="<?= e(Csrf::token()) ?>"><?php endif; ?>
</head>
<body class="admin">
<header class="a-header">
  <div class="a-header__inner">
    <a class="a-brand" href="/admin">Lothar Prokop <span>Verwaltung</span></a>
    <?php if ($loggedIn): ?>
    <nav class="a-nav" aria-label="Verwaltung">
      <?php foreach ($nav as $href => $label): $active = $href === '/admin' ? $current === '/admin' : str_starts_with($current, $href); ?>
        <a href="<?= e($href) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="a-header__user">
      <a href="/" rel="noopener" target="_blank">Website ansehen</a>
      <a href="/admin/passwort"><?= e(Auth::username()) ?></a>
      <form method="post" action="/admin/logout"><?= Csrf::field() ?><button type="submit" class="a-linkbtn">Abmelden</button></form>
    </div>
    <?php endif; ?>
  </div>
</header>
<main class="a-main" id="inhalt">
  <?php if (!empty($flash)): ?>
  <div class="a-flash a-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['text']) ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>
<script src="/assets/js/admin.js?v=<?= e(App\Config::get('asset_version', '1')) ?>" defer></script>
</body>
</html>
