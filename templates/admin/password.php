<?php
/** @var string|null $error */
use App\Csrf;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Passwort ändern</h1>
  <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/passwort" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="current">Aktuelles Passwort</label><input type="password" id="current" name="current" required autocomplete="current-password"></div>
    <div class="a-field"><label for="password">Neues Passwort (mindestens 12 Zeichen)</label><input type="password" id="password" name="password" required minlength="12" autocomplete="new-password"></div>
    <div class="a-field"><label for="password2">Neues Passwort wiederholen</label><input type="password" id="password2" name="password2" required minlength="12" autocomplete="new-password"></div>
    <button type="submit" class="a-btn">Passwort speichern</button>
  </form>
</div>
