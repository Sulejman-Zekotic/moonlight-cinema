<section class="mc-admin-auto-page">
  <a href="<?= e(url_for('admin')) ?>" class="mc-admin-back mc-admin-auto-back">
    <img src="<?= e(media_url('2026/01/arrow-left-1.png')) ?>" class="mc-admin-icon" alt="">
    Nazad na admin panel
  </a>

  <header class="mc-admin-auto-intro">
    <h1>Automatski raspored</h1>
  </header>

  <div class="mc-movie-select">
    <?php foreach ($movies as $movie): ?>
      <label class="mc-movie-checkbox is-selected">
        <input class="mc-movie-check-input" type="checkbox" name="movies[]" value="<?= e((string) $movie['id']) ?>" checked>

        <span class="mc-auto-poster-wrap">
          <img class="mc-auto-poster" src="<?= e($movie['poster_url']) ?>" alt="<?= e($movie['title']) ?>">
        </span>

        <div class="mc-movie-checkbox__content">
          <strong><?= e($movie['title']) ?></strong>
          <?php if ($movie['genres']): ?>
            <div class="mc-movie-genres">
              <?php foreach ($movie['genres'] as $genre): ?>
                <span class="tag tag-<?= e($genre['slug']) ?>"><?= e($genre['name']) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <span class="mc-duration"><?= e((string) $movie['duration_minutes']) ?> min</span>
        </div>
      </label>
    <?php endforeach; ?>
  </div>

  <form id="mc-auto-form" class="mc-auto-box">
    <div class="mc-auto-grid">
      <div class="mc-auto-field">
        <label for="mc-auto-from">Datum od</label>
        <input id="mc-auto-from" type="date" name="date_from" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" required>
      </div>

      <div class="mc-auto-field">
        <label for="mc-auto-to">Datum do</label>
        <input id="mc-auto-to" type="date" name="date_to" value="<?= e(date('Y-m-d', strtotime('+2 day'))) ?>" required>
      </div>

      <div class="mc-auto-actions">
        <button type="submit" class="mc-auto-generate" id="mc-generate-btn">
          <img src="<?= e(media_url('2026/01/calendar.png')) ?>" alt="">
          <span>Generiši projekcije</span>
        </button>
      </div>
    </div>

    <div id="mc-auto-result" class="mc-auto-result"></div>
  </form>

  <div id="mc-toast" class="mc-toast is-hidden">
    <div id="mc-toast-text"></div>
  </div>
</section>
