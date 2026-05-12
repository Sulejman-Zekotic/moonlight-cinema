<div class="mc-booking">
  <div id="mc-film-loading" class="mc-film-loading is-hidden">Učitavanje odabranog filma...</div>

  <div id="mc-film-card" class="mc-film-card is-hidden">
    <div class="mc-film-card-poster">
      <img id="mc-film-poster" src="" alt="">
    </div>

    <div class="mc-film-card-info">
      <h2 id="mc-film-title"></h2>
      <div id="mc-film-genres" class="mc-movie-genres"></div>
      <div class="mc-film-meta">
        <span id="mc-film-duration"></span>
        <span id="mc-film-director"></span>
      </div>
      <p id="mc-film-description"></p>
      <div class="mc-film-date">
        <label for="mc-date">Izaberi dan projekcije</label>
        <input type="date" id="mc-date">
      </div>
    </div>
  </div>

  <div class="mc-field mc-field--times is-hidden">
    <label for="mc-times" style="color:white!important;">Termin</label>
    <div id="mc-times" class="mc-times-grid"></div>
  </div>

  <div id="mc-seats" class="mc-seatmap is-hidden">
    <div class="mc-seatgrid">
      <div class="mc-area-hall">
        <div id="mc-hall-name" class="mc-hall-title"></div>
      </div>

      <div class="mc-area-screen">
        <div class="mc-screen">EKRAN</div>
      </div>

      <div class="mc-area-legend">
        <div class="mc-legend">
          <div class="mc-legend__item">
            <span class="mc-dot available"></span>
            <span>Slobodno</span>
          </div>
          <div class="mc-legend__item">
            <span class="mc-dot occupied"></span>
            <span>Zauzeto</span>
          </div>
          <div class="mc-legend__item">
            <span class="mc-dot selected"></span>
            <span>Odabrano</span>
          </div>
          <div class="mc-legend__item">
            <img src="<?= e(media_url('2025/12/TwoSeats.png')) ?>" class="mc-legend-icon mc-love-seat" alt="Ljubavno sjedište">
            <span>Ljubavno sjedište</span>
          </div>
          <div class="mc-legend__item">
            <img src="<?= e(media_url('2026/02/accessibility.png')) ?>" class="mc-legend-icon mc-wheelchair-seat" alt="Pristup za kolica">
            <span>Pristup za kolica</span>
          </div>
        </div>
      </div>

      <div class="mc-area-grid">
        <div class="mc-gridwrap">
          <div id="mc-grid" class="mc-grid"></div>
          <div id="mc-grid-loader" class="mc-grid-loader is-hidden">Učitavanje sjedišta...</div>
        </div>
      </div>

      <div id="mc-total" class="mc-total">Ukupno: 0.00 KM</div>

      <div class="mc-area-confirm">
        <button id="mc-confirm" class="mc-confirm" disabled>Potvrdi odabir sjedišta</button>
      </div>
    </div>
  </div>
</div>

<?php render_partial('partials/reservation-modal', ['auth' => $auth]); ?>
