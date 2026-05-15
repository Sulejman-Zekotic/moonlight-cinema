<section class="mc-state-page">
  <div class="mc-state-box" style="max-width:760px;">
    <h1>Otkazivanje rezervacije</h1>

    <?php if ($message): ?>
      <div class="mc-admin-msg mc-success" style="margin-bottom:24px;"><?= e($message) ?></div>
    <?php elseif ($error): ?>
      <div class="mc-admin-msg mc-error" style="margin-bottom:24px;"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($reservation && !$message): ?>
      <?php
        $isPaid = $reservation['status'] === 'paid';
        $payDeadline = '';

        if (!$isPaid) {
            $deadlineTimestamp = strtotime((string) $reservation['screening_date'] . ' ' . (string) $reservation['screening_time']);
            if ($deadlineTimestamp !== false) {
                $payDeadline = date('d.m.Y, H:i', $deadlineTimestamp - (30 * 60));
            }
        }
      ?>

      <p>Da li ste sigurni da želite otkazati ovu rezervaciju?</p>
      <div class="mc-modal__card" style="margin: 24px auto; max-width: 560px; text-align:left;">
        <div class="mc-row"><span>Film:</span><strong><?= e($reservation['title']) ?></strong></div>
        <div class="mc-row"><span>Datum i vrijeme:</span><strong><?= e(format_date_local($reservation['screening_date'])) ?>, <?= e(format_time_local($reservation['screening_time'])) ?></strong></div>
        <div class="mc-row"><span>Sala:</span><strong><?= e($reservation['hall_name']) ?></strong></div>
        <div class="mc-row"><span>Status rezervacije:</span><strong><?= e($isPaid ? 'Plaćena' : 'Rezervisana') ?></strong></div>
        <div class="mc-row"><span>Status plaćanja:</span><strong><?= e($isPaid ? 'Plaćena' : 'Nije plaćena') ?></strong></div>
        <?php if (!$isPaid && $payDeadline !== ''): ?>
          <div class="mc-row"><span>Najkasnije platiti do:</span><strong><?= e($payDeadline) ?></strong></div>
        <?php endif; ?>
      </div>

      <?php if ($isPaid): ?>
        <div style="background: rgba(180, 30, 30, 0.18); border: 1px solid rgba(255, 120, 120, 0.35); border-radius: 18px; padding: 18px; margin-bottom: 24px; color: #ffd6d6;">
          Ova karta je već plaćena. Otkazivanjem rezervacije povrat novca će biti evidentiran naknadno.
        </div>
      <?php endif; ?>

      <div class="mc-cancel-actions" style="justify-content:center;">
        <a href="<?= e(url_for('/')) ?>" id="mc-cancel-no" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Ne, nazad</a>

        <?php if (!$isPaid): ?>
          <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&pay=1')) ?>" id="mc-cancel-pay" class="mc-btn-pay" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Plati odmah</a>
        <?php endif; ?>

        <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&confirm=1')) ?>" id="mc-cancel-yes" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Da, otkaži</a>
      </div>

      <?php if (!$isPaid): ?>
        <script>
          window.mcGuestPayment = {
            reservationId: <?= json_encode((string) $reservation['id']) ?>,
            token: <?= json_encode((string) $token) ?>,
            needsProof: <?= ((int) ($reservation['has_wheelchair'] ?? 0)) === 1 ? 'true' : 'false' ?>,
            open: <?= !empty($openPayment) ? 'true' : 'false' ?>
          };
        </script>
        <?php render_partial('partials/reservation-modal', ['auth' => $auth]); ?>
      <?php endif; ?>
    <?php elseif (!$message): ?>
      <p>Rezervacija nije pronađena ili link više nije validan.</p>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php else: ?>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php endif; ?>
  </div>
</section>
