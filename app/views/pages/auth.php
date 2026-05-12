<section class="mc-auth-page">
  <div class="mc-auth-wrapper">
    <div class="mc-auth-card" id="mc-loginBox" data-auth-panel="login">
      <h1>Prijava</h1>

      <form id="mc-loginForm" novalidate>
        <label class="mc-field" for="mc-loginEmail">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/email-mail-message-letter-envelope-svgrepo-com.svg')) ?>" alt="">
          </span>
          <input type="email" id="mc-loginEmail" placeholder="Email adresa" required>
        </label>

        <label class="mc-field mc-password-field" for="mc-loginPassword">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/lock.png')) ?>" alt="">
          </span>
          <input type="password" id="mc-loginPassword" placeholder="Lozinka" required>
          <button class="mc-toggle-password" type="button" data-target="mc-loginPassword" aria-label="Prikaži lozinku">
            <img src="<?= e(media_url('2026/01/eye-closed.png')) ?>" alt="">
          </button>
        </label>

        <button class="mc-btn-main" type="submit">Prijavi se</button>

        <div class="mc-auth-links">
          <a href="#" data-auth-target="forgot">Zaboravljena lozinka?</a>
          <p class="mc-switch">Nemaš nalog? <a href="#" data-auth-target="register">Registruj se</a></p>
        </div>

        <p class="mc-error" id="mc-loginError"></p>
      </form>
    </div>

    <div class="mc-auth-card mc-hidden" id="mc-registerBox" data-auth-panel="register">
      <h1>Registracija</h1>

      <form id="mc-registerForm" novalidate>
        <label class="mc-field" for="mc-regName">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/user-svgrepo-com.svg')) ?>" alt="">
          </span>
          <input type="text" id="mc-regName" placeholder="Ime i prezime" required minlength="2">
        </label>

        <label class="mc-field" for="mc-regEmail">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/email-mail-message-letter-envelope-svgrepo-com.svg')) ?>" alt="">
          </span>
          <input type="email" id="mc-regEmail" placeholder="Email adresa" required>
        </label>

        <label class="mc-field mc-password-field" for="mc-regPassword">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/lock.png')) ?>" alt="">
          </span>
          <input type="password" id="mc-regPassword" placeholder="Postavite lozinku" required>
          <button class="mc-toggle-password" type="button" data-target="mc-regPassword" aria-label="Prikaži lozinku">
            <img src="<?= e(media_url('2026/01/eye-closed.png')) ?>" alt="">
          </button>
        </label>

        <button class="mc-btn-main" type="submit">Registruj se</button>

        <div class="mc-auth-links">
          <p class="mc-switch">Već imate nalog? <a href="#" data-auth-target="login">Prijavite se</a></p>
        </div>

        <p class="mc-error" id="mc-registerError"></p>
      </form>
    </div>

    <div class="mc-auth-card mc-hidden" id="mc-forgotBox" data-auth-panel="forgot">
      <h1>Reset lozinke</h1>
      <p class="mc-auth-note">Unesite email adresu i poslat ćemo vam link za postavljanje nove lozinke.</p>

      <form id="mc-forgotForm" novalidate>
        <label class="mc-field" for="mc-forgotEmail">
          <span class="mc-field__icon">
            <img src="<?= e(media_url('2026/01/email-mail-message-letter-envelope-svgrepo-com.svg')) ?>" alt="">
          </span>
          <input type="email" id="mc-forgotEmail" placeholder="Email adresa" required>
        </label>

        <button class="mc-btn-main" type="submit">Pošalji reset link</button>

        <div class="mc-auth-links">
          <p class="mc-switch">Sjetili ste se lozinke? <a href="#" data-auth-target="login">Nazad na prijavu</a></p>
        </div>

        <p class="mc-error" id="mc-forgotError"></p>
      </form>
    </div>
  </div>
</section>
