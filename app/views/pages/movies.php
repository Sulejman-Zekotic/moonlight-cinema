<section class="mc-hci-filter">
  <div class="mc-filter-mobile-bar">
    <button id="mc-filter-open" class="mc-filter-mobile-button" type="button" aria-label="Otvori filtere">
      <svg class="mc-filter-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 7h16" />
        <path d="M4 17h16" />
        <circle cx="9" cy="7" r="2.5" />
        <circle cx="15" cy="17" r="2.5" />
      </svg>
      <span>Filteri</span>
    </button>
  </div>

  <div id="mc-filter-overlay" class="mc-filter-overlay"></div>

  <div id="mc-filter-panel" class="mc-filter-panel">
    <div class="mc-filter-sheet-head">
      <h2>Filteri filmova</h2>
      <button id="mc-filter-close" class="mc-filter-close" type="button" aria-label="Zatvori filtere">&times;</button>
    </div>

    <div class="mc-filter-row">
      <div class="mc-filter-group mc-filter-search-group">
        <label for="mc-filter-search">Naziv filma</label>
        <input type="text" id="mc-filter-search" placeholder="Unesite naziv filma">
      </div>

      <div class="mc-filter-group">
        <label for="mc-filter-genre">Žanr</label>
        <select id="mc-filter-genre">
          <option value="">Svi žanrovi</option>
          <?php foreach ($genres as $genre): ?>
            <option value="<?= e((string) $genre['id']) ?>"><?= e($genre['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-filter-group">
        <label for="mc-filter-date">Datum</label>
        <input type="date" id="mc-filter-date">
      </div>

      <div class="mc-filter-group">
        <label for="mc-filter-duration">Trajanje</label>
        <select id="mc-filter-duration">
          <option value="">Sva trajanja</option>
          <option value="short">Manje od 90 min</option>
          <option value="medium">90 do 120 min</option>
          <option value="long">Više od 120 min</option>
        </select>
      </div>

      <div class="mc-filter-group">
        <label for="mc-filter-hall">Sala</label>
        <select id="mc-filter-hall">
          <option value="">Sve sale</option>
          <?php foreach ($halls as $hall): ?>
            <option value="<?= e((string) $hall['id']) ?>"><?= e($hall['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-filter-actions">
        <button id="mc-filter-reset" class="mc-reset-btn" type="button" aria-label="Resetuj filtere">
          <img src="<?= e(media_url('2026/01/rotate-ccw-1.png')) ?>" alt="">
        </button>
      </div>
    </div>
  </div>

  <div id="mc-movie-results" class="mc-movie-grid"></div>
</section>
