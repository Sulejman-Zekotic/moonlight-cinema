<section class="mc-state-page">
  <div class="mc-state-box" style="max-width:760px;">
    <h1>Otkazivanje rezervacije</h1>

    <?php if ($message): ?>
      <div class="mc-admin-msg mc-success" style="margin-bottom:24px;"><?= e($message) ?></div>
    <?php elseif ($error): ?>
      <div class="mc-admin-msg mc-error" style="margin-bottom:24px;"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($reservation && !$message): ?>
      <p>Da li ste sigurni da želite otkazati ovu rezervaciju?</p>
      <div class="mc-modal__card" style="margin: 24px auto; max-width: 560px; text-align:left;">
        <div class="mc-row"><span>Film</span><strong><?= e($reservation['title']) ?></strong></div>
        <div class="mc-row"><span>Datum i vrijeme</span><strong><?= e(format_date_local($reservation['screening_date'])) ?>, <?= e(format_time_local($reservation['screening_time'])) ?></strong></div>
        <div class="mc-row"><span>Sala</span><strong><?= e($reservation['hall_name']) ?></strong></div>
        <div class="mc-row"><span>Status</span><strong><?= e($reservation['status'] === 'paid' ? 'Plaćena' : 'Rezervisana') ?></strong></div>
      </div>

      <?php if ($reservation['status'] === 'paid'): ?>
        <div style="background: rgba(180, 30, 30, 0.18); border: 1px solid rgba(255, 120, 120, 0.35); border-radius: 18px; padding: 18px; margin-bottom: 24px; color: #ffd6d6;">
          Ova karta je već plaćena. Otkazivanjem rezervacije povrat novca će biti evidentiran naknadno.
        </div>
      <?php endif; ?>

      <div class="mc-cancel-actions" style="justify-content:center;">
        <a href="<?= e(url_for('/')) ?>" id="mc-cancel-no" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Ne, nazad</a>
        <a href="<?= e(url_for('otkazi-rezervaciju?rid=' . $reservation['id'] . '&token=' . urlencode($reservation['guest_token']) . '&confirm=1')) ?>" id="mc-cancel-yes" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Da, otkaži</a>
      </div>
    <?php elseif (!$message): ?>
      <p>Rezervacija nije pronađena ili link više nije validan.</p>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php else: ?>
      <a href="<?= e(url_for('/')) ?>" class="btn-outline">Nazad na početnu</a>
    <?php endif; ?>
  </div>
</section>
