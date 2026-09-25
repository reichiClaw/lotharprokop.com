<?php
/** @var string $title */
/** @var string $text */
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">
<main class="a-main a-main--narrow">
  <h1 class="a-title"><?= e($title) ?></h1>
  <p><?= e($text) ?></p>
  <p><a href="/admin/galerien" class="a-btn">Zu den Galerien</a> <a href="/admin" class="a-btn a-btn--ghost">Zur Übersicht</a></p>
</main>
</body>
</html>
