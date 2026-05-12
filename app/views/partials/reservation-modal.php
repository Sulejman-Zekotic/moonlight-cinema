<div id="mc-toast" class="mc-toast is-hidden">
  <div id="mc-toast-text"></div>
</div>

<div id="mc-reservation-modal" class="mc-modal is-hidden">
  <div class="mc-modal__overlay"></div>
  <div class="mc-modal__content">
    <button id="mc-close-modal" class="mc-modal__close" aria-label="Zatvori modal">&times;</button>

    <div id="mc-step-review">
      <h3 class="mc-modal__title">Pregled rezervacije</h3>

      <div class="mc-modal__body">
        <div class="mc-modal__card">
          <div class="mc-row"><span>Film</span><strong id="modal-movie"></strong></div>
          <div class="mc-row"><span>Datum i vrijeme</span><strong id="modal-datetime"></strong></div>
          <div class="mc-row"><span>Sala</span><strong id="modal-hall"></strong></div>
          <div class="mc-row"><span>Sjedišta</span><strong id="modal-seats"></strong></div>
          <div class="mc-row"><span>Ukupno</span><strong id="modal-total"></strong></div>
        </div>

        <?php if (!$auth->check()): ?>
          <div class="mc-guest-email">
            <label for="mc-guest-email-review">Email za slanje karte</label>
            <input type="email" id="mc-guest-email-review" placeholder="ime@email.com" required>
            <small>Na ovaj email će vam biti poslana karta i QR kod.</small>
          </div>
        <?php endif; ?>
      </div>

      <div class="mc-modal__actions">
        <button id="mc-to-payment" class="btn-primary" type="button">Plati karticom</button>
        <button id="mc-pay-cash" class="btn-secondary" type="button">Plati na blagajni</button>
      </div>
    </div>

    <div id="mc-step-payment" class="is-hidden">
      <h3 class="mc-modal__title">Plaćanje karticom</h3>

      <div class="mc-modal__body mc-modal__body--scroll">
        <div id="mc-disability-proof" class="mc-proof-box" style="display:none;">
          <div class="mc-proof-header">Dokaz o invaliditetu</div>
          <label class="mc-proof-upload">
            <input type="file" id="mc-proof-file" accept="image/*,application/pdf">
            <span>Kliknite ovdje za upload slike ili PDF-a</span>
          </label>
          <small>Dozvoljeni formati: JPG, PNG, PDF</small>
        </div>

        <div class="mc-payment-form">
          <div class="mc-card-brands">
            <img src="<?= e(media_url('2025/12/visa.svg')) ?>" alt="Visa">
            <img src="<?= e(media_url('2025/12/mastercard.svg')) ?>" alt="Mastercard">
          </div>

          <div class="mc-input-group">
            <input id="card-name" placeholder="Ime na kartici">
            <div id="err-card-name" class="mc-input-error"></div>
          </div>

          <div class="mc-input-group">
            <input id="card-number" placeholder="0000 0000 0000 0000">
            <div id="err-card-number" class="mc-input-error"></div>
          </div>

          <div class="mc-input-row">
            <div class="mc-input-group">
              <input id="card-expiry" placeholder="MM/YY">
              <div id="err-card-expiry" class="mc-input-error"></div>
            </div>
            <div class="mc-input-group">
              <input id="card-cvc" placeholder="CVC">
              <div id="err-card-cvc" class="mc-input-error"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="mc-modal__actions">
        <button id="mc-pay-submit" type="button" class="btn-primary">Plati</button>
        <button id="mc-back-to-review" class="btn-secondary" type="button">Nazad</button>
      </div>
    </div>
  </div>
</div>
