<?php
/** @var array $state */
/** @var bool $mailEnabled */
use App\Settings;

$name = (string) Settings::get('contact_name', 'Lothar Prokop');
$email = (string) Settings::get('contact_email', '');
$phone = (string) Settings::get('contact_phone', '');
$phoneLink = (string) Settings::get('contact_phone_link', '');
$address = array_values(array_filter(array_map('trim', explode('|', (string) Settings::get('contact_address', '')))));
$uid = (string) Settings::get('contact_uid', '');
$maps = (string) Settings::get('contact_maps_url', '');
$intro = (string) Settings::get('contact_intro', '');
$errors = $state['errors'];
$values = $state['values'];
?>
<article class="contact">
  <header class="page-head">
    <h1 class="page-head__title">Kontakt</h1>
    <?php if ($intro !== ''): ?><p class="page-head__note"><?= e($intro) ?></p><?php endif; ?>
  </header>
  <div class="contact__body">
    <div class="contact__direct">
      <h2 class="contact__name"><?= e($name) ?></h2>
      <?php if ($email !== ''): ?><p class="contact__line"><a class="contact__big" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
      <?php if ($phone !== ''): ?><p class="contact__line"><a class="contact__big" href="tel:<?= e($phoneLink ?: preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></p><?php endif; ?>
      <?php if ($address !== []): ?>
      <address class="contact__address">
        <?php foreach ($address as $line): ?><?= e($line) ?><br><?php endforeach; ?>
      </address>
      <?php endif; ?>
      <?php if ($uid !== ''): ?><p class="contact__small">UID: <?= e($uid) ?></p><?php endif; ?>
      <?php if ($maps !== ''): ?><p class="contact__small"><a href="<?= e($maps) ?>" rel="noopener">Wegbeschreibung öffnen</a> (externer Kartendienst)</p><?php endif; ?>
    </div>

    <?php if ($mailEnabled): ?>
    <div class="contact__form">
      <?php if ($state['sent']): ?>
        <div class="notice notice--ok" role="status"><p>Vielen Dank – die Nachricht wurde gesendet. Ich melde mich so bald wie möglich.</p></div>
      <?php else: ?>
      <h2 class="contact__form-title">Nachricht schreiben</h2>
      <?php if (!empty($errors['form'])): ?><div class="notice notice--error" role="alert"><p><?= e($errors['form']) ?></p></div><?php endif; ?>
      <form method="post" action="/kontakt" class="form" novalidate>
        <input type="hidden" name="_t" value="<?= time() ?>">
        <p class="form__hp" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></p>
        <div class="form__field">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" required autocomplete="name" value="<?= e($values['name'] ?? '') ?>" <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="err-name"' : '' ?>>
          <?php if (isset($errors['name'])): ?><p class="form__error" id="err-name"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="form__field">
          <label for="email">E-Mail</label>
          <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($values['email'] ?? '') ?>" <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
          <?php if (isset($errors['email'])): ?><p class="form__error" id="err-email"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>
        <div class="form__field">
          <label for="message">Nachricht</label>
          <textarea id="message" name="message" rows="7" required <?= isset($errors['message']) ? 'aria-invalid="true" aria-describedby="err-message"' : '' ?>><?= e($values['message'] ?? '') ?></textarea>
          <?php if (isset($errors['message'])): ?><p class="form__error" id="err-message"><?= e($errors['message']) ?></p><?php endif; ?>
        </div>
        <p class="form__hint">Die Angaben werden ausschließlich zur Beantwortung der Anfrage verwendet. Details in der <a href="/datenschutz">Datenschutzerklärung</a>.</p>
        <button type="submit" class="button">Nachricht senden</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</article>
