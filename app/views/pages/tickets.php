<?php if (!$auth->check()): ?>
  <section class="mc-state-page">
    <div class="mc-state-box">
      <h1>Morate biti prijavljeni</h1>
      <p>Prijavite se kako biste vidjeli svoje rezervacije i kupljene karte.</p>
      <a href="<?= e(url_for('prijava')) ?>" class="btn-outline">Idi na prijavu</a>
    </div>
  </section>
<?php elseif (!$tickets): ?>
  <div class="mc-no-tickets">
    <h2>Nemate rezervisanih karata</h2>
    <p>Trenutno nemate nijednu aktivnu rezervaciju.</p>
    <a href="<?= e(url_for('filmovi')) ?>" class="mc-no-tickets-btn">Pogledaj filmove</a>
  </div>
<?php else: ?>
  <section class="mc-my-tickets">
    <?php foreach ($tickets as $ticket): ?>
      <?php
      $seatIcon = match ($ticket['seat_type']) {
          'wheelchair' => media_url('2026/02/accessibility.png'),
          'love' => media_url('2025/12/TwoSeats.png'),
          default => media_url('2025/12/OneSeat.svg'),
      };
      $status = strtolower(trim($ticket['status']));
      ?>
      <div class="mc-ticket-card">
        <img src="<?= e($ticket['poster_url']) ?>" class="mc-ticket-poster" alt="<?= e($ticket['title']) ?>">

        <div class="mc-ticket-info">
          <h3><?= e($ticket['title']) ?></h3>

          <div class="mc-ticket-meta">
            <span><img src="<?= e(media_url('2026/01/calendar-date-svgrepo-com.svg')) ?>" alt=""> <?= e(format_date_local($ticket['screening_date'], 'd. m. Y.')) ?></span>
            <span><img src="<?= e(media_url('2026/02/clock-4.png')) ?>" alt=""> <?= e(format_time_local($ticket['screening_time'])) ?></span>
            <span><img src="<?= e(media_url('2026/01/video-camera-svgrepo-com-1.svg')) ?>" alt=""> <?= e($ticket['hall_name']) ?></span>
          </div>

          <div class="mc-ticket-seats <?= e($ticket['seat_type']) ?>">
            <img src="<?= e($seatIcon) ?>" alt="">
            <?= e(implode(', ', $ticket['seats'])) ?>
          </div>

          <div class="mc-ticket-price">
            <?= $status === 'paid' ? 'Plaćeno:' : 'Cijena:' ?> <strong><?= e(number_format((float) $ticket['total_price'], 2)) ?> KM</strong>
          </div>
        </div>

        <div class="mc-ticket-actions">
          <button class="mc-btn mc-open-ticket" data-id="<?= e((string) $ticket['reservation_id']) ?>" data-code="<?= e($ticket['ticket_code']) ?>">Prikaži QR kod</button>

          <?php if ($status === 'pending'): ?>
            <button class="mc-btn mc-btn-pay mc-pay-now" data-reservation="<?= e((string) $ticket['reservation_id']) ?>" data-wheelchair="<?= $ticket['seat_type'] === 'wheelchair' ? '1' : '0' ?>">Plati odmah</button>
            <button class="mc-cancel-ticket mc-btn mc-btn-cancel" data-id="<?= e((string) $ticket['reservation_id']) ?>">Otkaži rezervaciju</button>
          <?php else: ?>
            <a class="mc-btn mc-btn-pdf" target="_blank" href="<?= e($ticket['download_url']) ?>">Preuzmi kartu</a>
            <button class="mc-cancel-ticket mc-btn mc-btn-cancel" data-id="<?= e((string) $ticket['reservation_id']) ?>" data-paid="1">Otkaži kartu</button>
            <a class="mc-btn mc-btn-review" href="<?= e(movie_link((int) $ticket['movie_id'])) ?>#recenzije">Ostavi recenziju</a>
          <?php endif; ?>
        </div>

        <span class="mc-ticket-status <?= e($status) ?>"><?= $status === 'paid' ? 'Kupljena' : 'Rezervisana' ?></span>
      </div>
    <?php endforeach; ?>
  </section>

  <div id="mc-ticket-modal" class="mc-modal">
    <div class="mc-modal-box mc-qr-only">
      <h2 class="mc-qr-title">Vaša karta</h2>
      <div class="mc-qr-box">
        <img id="mc-random-qr" src="" alt="QR kod">
      </div>
      <p class="mc-qr-text">Skenirajte ovaj kod na blagajni.</p>
      <button id="mc-close-ticket" class="mc-qr-close">Zatvori</button>
    </div>
  </div>

  <div id="mc-cancel-modal" class="mc-cancel-modal">
    <div class="mc-cancel-box">
      <h3 id="mc-cancel-title">Otkaži rezervaciju?</h3>
      <div id="mc-cancel-text" class="mc-cancel-message">Da li ste sigurni da želite otkazati ovu rezervaciju?</div>
      <div class="mc-cancel-actions">
        <button type="button" id="mc-cancel-no">Ne</button>
        <button type="button" id="mc-cancel-yes">Da, otkaži</button>
      </div>
    </div>
  </div>

  <?php render_partial('partials/reservation-modal', ['auth' => $auth]); ?>
<?php endif; ?>
