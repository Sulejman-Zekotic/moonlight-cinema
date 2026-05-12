<?php
$statusLabels = [
    'active' => 'Aktivna',
    'cancelled' => 'Otkazana',
    'archived' => 'Arhivirana',
];

$screeningPageUrl = static function (int $page) use ($screeningFilters): string {
    $query = array_filter([
        'movie_id' => $screeningFilters['movie_id'],
        'hall_id' => $screeningFilters['hall_id'],
        'status' => $screeningFilters['status'],
        'search' => $screeningFilters['search'],
        'date_from' => $screeningFilters['date_from'],
        'date_to' => $screeningFilters['date_to'],
        'page' => $page,
    ], static fn($value): bool => $value !== '' && $value !== null);

    return url_for('admin/projekcije') . ($query ? '?' . http_build_query($query) : '');
};

$pageWindow = [];
$windowStart = max(1, $screeningPage - 2);
$windowEnd = min($screeningPageCount, $screeningPage + 2);

if ($screeningPage <= 3) {
    $windowEnd = min($screeningPageCount, 5);
}

if ($screeningPage >= $screeningPageCount - 2) {
    $windowStart = max(1, $screeningPageCount - 4);
}

for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++) {
    $pageWindow[] = $pageNumber;
}
?>

<section class="mc-admin-screenings-page">
  <a href="<?= e(url_for('admin')) ?>" class="mc-admin-back">
    <img src="<?= e(media_url('2026/01/arrow-left-1.png')) ?>" class="mc-admin-icon" alt="">
    Nazad na admin panel
  </a>

  <div class="mc-admin-screenings-head">
    <div>
      <h1 class="mc-admin-screenings-title">
        <img src="<?= e(media_url('2026/01/clapperboard-1.png')) ?>" class="mc-admin-icon-p" alt="">
        Sve projekcije
      </h1>
    </div>
  </div>

  <div class="mc-admin-screenings-toolbar">
    <button type="button" class="mc-admin-screenings-mobile-toggle" data-screening-filter-open aria-label="Otvori filtere projekcija">
      <svg class="mc-filter-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 7h16" />
        <path d="M4 17h16" />
        <circle cx="9" cy="7" r="2.5" />
        <circle cx="15" cy="17" r="2.5" />
      </svg>
      <span>Filteri</span>
    </button>
  </div>

  <div class="mc-admin-screenings-filter-overlay" data-screening-filter-overlay></div>

  <form class="mc-admin-screenings-filters" method="get" action="<?= e(url_for('admin/projekcije')) ?>" data-screening-filter-panel>
    <div class="mc-admin-screenings-filters__sheet-head">
      <h2>Filteri projekcija</h2>
      <button type="button" class="mc-admin-screenings-filters__close" data-screening-filter-close aria-label="Zatvori filtere">&times;</button>
    </div>

    <div class="mc-admin-screenings-filters__grid">
      <label class="mc-admin-screenings-search mc-filter-field" for="screening-search">
        <span>Pretraga</span>
        <input id="screening-search" name="search" type="text" value="<?= e($screeningFilters['search']) ?>" placeholder="Unesite naziv filma" data-screening-search>
      </label>

      <div class="mc-filter-field">
        <label for="screening-movie">Film</label>
        <select id="screening-movie" name="movie_id" data-screening-auto-submit>
          <option value="">Svi filmovi</option>
          <?php foreach ($movies as $movie): ?>
            <option value="<?= e((string) $movie['id']) ?>" <?= $screeningFilters['movie_id'] === (string) $movie['id'] ? 'selected' : '' ?>>
              <?= e($movie['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-filter-field">
        <label for="screening-hall">Sala</label>
        <select id="screening-hall" name="hall_id" data-screening-auto-submit>
          <option value="">Sve sale</option>
          <?php foreach ($halls as $hall): ?>
            <option value="<?= e((string) $hall['id']) ?>" <?= $screeningFilters['hall_id'] === (string) $hall['id'] ? 'selected' : '' ?>>
              <?= e($hall['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-filter-field">
        <label for="screening-status">Status</label>
        <select id="screening-status" name="status" data-screening-auto-submit>
          <option value="">Svi statusi</option>
          <option value="active" <?= $screeningFilters['status'] === 'active' ? 'selected' : '' ?>>Aktivna</option>
          <option value="cancelled" <?= $screeningFilters['status'] === 'cancelled' ? 'selected' : '' ?>>Otkazana</option>
          <option value="archived" <?= $screeningFilters['status'] === 'archived' ? 'selected' : '' ?>>Arhivirana</option>
        </select>
      </div>

      <div class="mc-filter-field">
        <label for="screening-date-from">Datum od</label>
        <input id="screening-date-from" type="date" name="date_from" value="<?= e($screeningFilters['date_from']) ?>" data-screening-auto-submit>
      </div>

      <div class="mc-filter-field">
        <label for="screening-date-to">Datum do</label>
        <input id="screening-date-to" type="date" name="date_to" value="<?= e($screeningFilters['date_to']) ?>" data-screening-auto-submit>
      </div>

      <div class="mc-admin-screenings-filters__actions">
        <a href="<?= e(url_for('admin/projekcije')) ?>" class="mc-admin-screenings-btn mc-admin-screenings-btn--ghost" aria-label="Resetuj filtere">
          <img src="<?= e(media_url('2026/01/rotate-ccw-1.png')) ?>" alt="">
        </a>
      </div>
    </div>
  </form>

  <div class="mc-screenings-board">
    <?php if ($screenings): ?>
      <div class="mc-screenings-list">
        <?php foreach ($screenings as $screening): ?>
          <?php
          $statusKey = strtolower((string) $screening['status']);
          $statusLabel = $statusLabels[$statusKey] ?? ucfirst($statusKey);
          $formattedDate = format_date_local($screening['screening_date']);
          $formattedTime = format_time_local($screening['screening_time']);
          ?>
          <article
            class="mc-screening-row"
            data-screening-modal-trigger
            data-screening-movie="<?= e($screening['movie_title']) ?>"
            data-screening-hall="<?= e($screening['hall_name']) ?>"
            data-screening-date="<?= e($formattedDate) ?>"
            data-screening-time="<?= e($formattedTime) ?>"
            data-screening-price="<?= e(number_format((float) $screening['price'], 2)) ?> KM"
            data-screening-status="<?= e($statusLabel) ?>"
          >
            <div class="mc-screening-row__lead">
              <h3 class="mc-screening-row__title"><?= e($screening['movie_title']) ?></h3>
            </div>
            <div class="mc-screening-row__details">
              <span><?= e($screening['hall_name']) ?></span>
              <span><?= e($formattedDate) ?></span>
              <span><?= e($formattedTime) ?></span>
              <span><?= e(number_format((float) $screening['price'], 2)) ?> KM</span>
              <strong class="mc-screening-row__status mc-screening-row__status--<?= e($statusKey) ?>"><?= e($statusLabel) ?></strong>
            </div>

            <button type="button" class="mc-screening-row__view" aria-label="Prikaži detalje projekcije">
              <img src="<?= e(media_url('2026/02/eye.png')) ?>" alt="">
            </button>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="mc-screenings-empty">
        <h3>Nema projekcija za odabrane filtere</h3>
        <p>Pokušajte promijeniti kriterije pretrage ili resetovati filtere.</p>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($screeningPageCount > 1): ?>
    <nav class="mc-screenings-pagination" aria-label="Paginacija projekcija">
      <a class="mc-screenings-pagination__nav <?= $screeningPage <= 1 ? 'is-disabled' : '' ?>" href="<?= $screeningPage <= 1 ? '#' : e($screeningPageUrl($screeningPage - 1)) ?>" <?= $screeningPage <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        &lsaquo;
      </a>

      <div class="mc-screenings-pagination__pages">
        <?php
        $lastPrinted = 0;
        foreach ($pageWindow as $pageNumber):
            if ($lastPrinted && $pageNumber > $lastPrinted + 1):
        ?>
          <span class="mc-screenings-pagination__dots">&hellip;</span>
        <?php
            endif;
        ?>
          <a class="mc-screenings-pagination__page <?= $pageNumber === $screeningPage ? 'is-active' : '' ?>" href="<?= e($screeningPageUrl($pageNumber)) ?>">
            <?= e((string) $pageNumber) ?>
          </a>
        <?php
            $lastPrinted = $pageNumber;
        endforeach;
        ?>
      </div>

      <a class="mc-screenings-pagination__nav <?= $screeningPage >= $screeningPageCount ? 'is-disabled' : '' ?>" href="<?= $screeningPage >= $screeningPageCount ? '#' : e($screeningPageUrl($screeningPage + 1)) ?>" <?= $screeningPage >= $screeningPageCount ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        &rsaquo;
      </a>
    </nav>
  <?php endif; ?>
</section>

<div class="mc-screening-modal is-hidden" data-screening-modal>
  <div class="mc-screening-modal__overlay" data-screening-modal-close></div>
  <div class="mc-screening-modal__dialog">
    <button type="button" class="mc-screening-modal__close" data-screening-modal-close aria-label="Zatvori modal">&times;</button>
    <h2>Detalji projekcije</h2>
    <h3 class="mc-screening-modal__movie" data-screening-movie></h3>

    <div class="mc-screening-modal__grid">
      <div class="mc-screening-modal__row">
        <span><img src="<?= e(media_url('2026/01/video-camera-svgrepo-com-1.svg')) ?>" alt=""> Sala</span>
        <strong data-screening-hall></strong>
      </div>
      <div class="mc-screening-modal__row">
        <span><img src="<?= e(media_url('2026/01/calendar-date-svgrepo-com.svg')) ?>" alt=""> Datum</span>
        <strong data-screening-date></strong>
      </div>
      <div class="mc-screening-modal__row">
        <span><img src="<?= e(media_url('2026/02/clock-4.png')) ?>" alt=""> Vrijeme</span>
        <strong data-screening-time></strong>
      </div>
      <div class="mc-screening-modal__row">
        <span><img src="<?= e(media_url('2026/01/credit-card.png')) ?>" alt=""> Cijena</span>
        <strong data-screening-price></strong>
      </div>
      <div class="mc-screening-modal__row">
        <span><img src="<?= e(media_url('2026/01/flame.png')) ?>" alt=""> Status</span>
        <strong data-screening-status></strong>
      </div>
    </div>
  </div>
</div>
