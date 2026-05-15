<section class="mc-state-page">
  <style>
    .mc-cancel-card {
      margin: 24px auto 28px;
      max-width: 680px;
      text-align: left;
      background: #1b3149;
      border-radius: 20px;
      padding: 20px 28px;
    }

    .mc-cancel-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 18px;
      align-items: center;
      margin-bottom: 12px;
      color: #ffffff;
      font-size: 16px;
    }

    .mc-cancel-row:last-child {
      margin-bottom: 0;
    }

    .mc-cancel-row span {
      color: #ffffff;
      font-weight: 500;
    }

    .mc-cancel-row strong {
      color: #ffffff;
      font-weight: 800;
      text-align: right;
    }

    .mc-cancel-actions {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      margin-top: 28px;
    }

    .mc-cancel-actions.is-cancel-only {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }

    .mc-cancel-actions a {
      width: 100%;
      height: 54px;
      border-radius: 18px;
      font-size: 16px;
      font-weight: 800;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
    }

    #mc-cancel-no {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #ffffff;
    }

    #mc-cancel-pay {
      background: #4ea8de;
      border: 1px solid #4ea8de;
      color: #071628;
    }

    #mc-cancel-yes {
      background: #b30808;
      border: 1px solid #b30808;
      color: #ffffff;
    }

    .mc-guest-payment-panel {
      margin: 28px auto 0;
      max-width: 680px;
      text-align: left;
      background: #13263b;
      border-radius: 22px;
      padding: 28px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
    }

    .mc-guest-payment-panel h2 {
      margin: 0 0 22px;
      text-align: center;
      color: #ffffff;
      font-size: 26px;
    }

    .mc-guest-payment-actions {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
      margin-top: 22px;
    }

    .mc-guest-payment-actions button,
    .mc-guest-payment-actions a {
      width: 100%;
      height: 54px;
      border-radius: 18px;
      font-size: 16px;
      font-weight: 800;
      border: 0;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      box-sizing: border-box;
    }

    #mc-guest-pay-submit {
      background: #4ea8de;
      color: #071628;
    }

    .mc-guest-payment-back {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      color: #ffffff;
    }

    .mc-guest-payment-panel .mc-payment-form input {
      width: 100%;
      box-sizing: border-box;
    }

    @media (max-width: 760px) {
      .mc-cancel-actions,
      .mc-cancel-actions.is-cancel-only,
      .mc-guest-payment-actions {
        grid-template-columns: 1fr;
      }

      .mc-cancel-row {
        grid-template-columns: 1fr;
        gap: 4px;
      }

      .mc-cancel-row strong {
        text-align: left;
      }
    }
  </style>

  <div class="mc-state-box" style="max-width:780px;">
    <h1><?= ($mode ?? 'cancel') === 'pay' ? 'Plaćanje karte' : 'Otkazivanje rezervacije' ?></h1>

    <?php if ($message): ?>
      <div class="mc-admin-msg mc-success" style="margin-bottom:24px;"><?= e($message) ?></div>
    <?php elseif ($error): ?>
      <div class="mc-admin-msg mc-error" style="margin-bottom:24px;"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($reservation && !$message): ?>
      <?php
        $isPaid = $reservation['status'] === 'paid';
        $deadline = null;
        if (!$isPaid) {
            $deadline = date('d.m.Y, H:i', strtotime($reservation['screening_date'] . ' ' . $reservation['screening_time'] . ' -30 minutes'));
        }
      ?>

      <p>
        <?= ($mode ?? 'cancel') === 'pay'
          ? 'Unesite podatke za plaćanje ove rezervacije.'
          : 'Da li ste sigurni da želite otkazati ovu rezervaciju?' ?>
      </p>

      <div class="mc-cancel-card">
        <div class="mc-cancel-row">
          <span>Film:</span>
          <strong><?= e($reservation['title']) ?></strong>
        </div>

        <div class="mc-cancel-row">
          <span>Datum i vrijeme:</span>
          <strong><?= e(format_date_local($reservation['screening_date'])) ?>, <?= e(format_time_local($reservation['screening_time'])) ?></strong>
        </div>

        <div class="mc-cancel-row">
          <span>Sala:</span>
          <strong><?= e($reservation['hall_name']) ?></strong>
        </div>

        <div class="mc-cancel-row">
          <span>Status plaćanja:</span>
          <strong><?= e($isPaid ? 'Plaćena' : 'Nije plaćena') ?></strong>
        </div>

        <?php if (!$isPaid && $deadline): ?>
          <div class="mc-cancel-row">
            <span>Najkasnije platiti do:</span>
            <strong><?= e($deadline) ?></strong>
          </div>
        <?php endif; ?>
      </div>

      <?php if (($mode ?? 'cancel') === 'pay' && !$isPaid): ?>
        <div class="mc-guest-payment-panel">
          <h2>Plaćanje karticom</h2>

          <div id="mc-disability-proof" class="mc-proof-box" style="<?= !empty($reservation['needs_proof']) ? 'display:block;' : 'display:none;' ?>">
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

          <div class="mc-guest-payment-actions">
            <button id="mc-guest-pay-submit" type="button">Plati</button>
            <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&mode=cancel')) ?>" class="mc-guest-payment-back">Nazad</a>
          </div>
        </div>

        <script>
          window.mcGuestPayment = {
            reservationId: <?= json_encode((string) $reservation['id']) ?>,
            token: <?= json_encode((string) $reservation['guest_token']) ?>,
            needsProof: <?= !empty($reservation['needs_proof']) ? 'true' : 'false' ?>
          };
        </script>
      <?php else: ?>
        <?php if ($isPaid): ?>
          <div style="background: rgba(180, 30, 30, 0.18); border: 1px solid rgba(255, 120, 120, 0.35); border-radius: 18px; padding: 18px; margin-bottom: 24px; color: #ffd6d6;">
            Ova karta je već plaćena. Otkazivanjem rezervacije povrat novca će biti evidentiran naknadno.
          </div>
        <?php endif; ?>

        <div class="mc-cancel-actions <?= $isPaid ? 'is-cancel-only' : '' ?>">
          <a href="<?= e(url_for('/')) ?>" id="mc-cancel-no">Ne, nazad</a>

          <?php if (!$isPaid): ?>
            <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&mode=pay')) ?>" id="mc-cancel-pay">Plati odmah</a>
          <?php endif; ?>

          <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&mode=cancel&confirm=1')) ?>" id="mc-cancel-yes">Da, otkaži</a>
        </div>
      <?php endif; ?>
    <?php elseif (!$message): ?>
      <p>Rezervacija nije pronađena ili link više nije validan.</p>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php else: ?>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php endif; ?>
  </div>
</section>
