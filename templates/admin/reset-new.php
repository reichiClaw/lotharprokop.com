<?php
/** @var string $token */
/** @var string|null $account */
/** @var string|null $error */
use App\Csrf;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Neues Passwort</h1>
  <?php if ($account === null): ?>
    <div class="a-flash a-flash--error" role="alert">Dieser Link ist ungültig, abgelaufen oder wurde schon benutzt.</div>
    <p class="a-help"><a href="/admin/passwort-vergessen">Neuen Link anfordern</a> · <a href="/admin/login">Zur Anmeldung</a></p>
  <?php else: ?>
    <p class="a-help">Neues Passwort für <strong><?= e($account) ?></strong> festlegen.</p>
    <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/passwort-neu" class="a-form">
      <?= Csrf::field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <input type="text" name="username" value="<?= e($account) ?>" autocomplete="username" hidden readonly>
      <div class="a-field"><label for="password">Neues Passwort (mindestens 12 Zeichen)</label><input type="password" id="password" name="password" required minlength="12" autocomplete="new-password" autofocus></div>
      <div class="a-field"><label for="password2">Neues Passwort wiederholen</label><input type="password" id="password2" name="password2" required minlength="12" autocomplete="new-password"></div>
      <button type="submit" class="a-btn">Passwort speichern</button>
    </form>
  <?php endif; ?>
</div>
