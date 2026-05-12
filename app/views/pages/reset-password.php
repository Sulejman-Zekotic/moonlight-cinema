<section class="mc-auth-page">
  <div class="mc-auth-wrapper">
    <div class="mc-auth-card mc-auth-card--single">
      <h1>Nova lozinka</h1>

      <?php if (!$resetState['valid']): ?>
        <p class="mc-auth-note mc-auth-note--error"><?= e($resetState['message']) ?></p>
        <div class="mc-auth-links mc-auth-links--center">
          <a href="<?= e(url_for('prijava')) ?>">Nazad na prijavu</a>
        </div>
      <?php else: ?>
        <p class="mc-auth-note">Postavite novu lozinku za svoj Moonlight Cinema nalog.</p>

        <form id="mc-resetForm" novalidate>
          <input type="hidden" id="mc-resetToken" value="<?= e($token) ?>">

          <label class="mc-field mc-password-field" for="mc-resetPassword">
            <span class="mc-field__icon">
              <img src="<?= e(media_url('2026/01/lock.png')) ?>" alt="">
            </span>
            <input type="password" id="mc-resetPassword" placeholder="Nova lozinka" required>
            <button class="mc-toggle-password" type="button" data-target="mc-resetPassword" aria-label="Prikaži lozinku">
              <img src="<?= e(media_url('2026/01/eye-closed.png')) ?>" alt="">
            </button>
          </label>

          <label class="mc-field mc-password-field" for="mc-resetPasswordConfirm">
            <span class="mc-field__icon">
              <img src="<?= e(media_url('2026/01/lock.png')) ?>" alt="">
            </span>
            <input type="password" id="mc-resetPasswordConfirm" placeholder="Ponovite lozinku" required>
            <button class="mc-toggle-password" type="button" data-target="mc-resetPasswordConfirm" aria-label="Prikaži lozinku">
              <img src="<?= e(media_url('2026/01/eye-closed.png')) ?>" alt="">
            </button>
          </label>

          <button class="mc-btn-main" type="submit">Sačuvaj novu lozinku</button>
          <p class="mc-error" id="mc-resetError"></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
