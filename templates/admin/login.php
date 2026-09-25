<?php
/** @var string|null $error */
/** @var string|null $username */
use App\Csrf;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Anmelden</h1>
  <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/login" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="username">Benutzername</label><input type="text" id="username" name="username" required autocomplete="username" value="<?= e($username ?? '') ?>" autofocus></div>
    <div class="a-field"><label for="password">Passwort</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button type="submit" class="a-btn">Anmelden</button>
  </form>
  <p class="a-help"><a href="/">Zur Website</a></p>
</div>
