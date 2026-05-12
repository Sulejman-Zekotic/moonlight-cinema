<?php
$error = null;
$success = null;

if (is_post()) {
    try {
        $repo->addScreening(
            (int) ($_POST['movie_id'] ?? 0),
            (int) ($_POST['hall_id'] ?? 0),
            (string) ($_POST['date'] ?? ''),
            (string) ($_POST['time'] ?? ''),
            (float) ($_POST['price'] ?? 0)
        );
        $success = 'Projekcija je uspješno dodana.';
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}
?>

<section class="mc-admin-add-page">
  <a href="<?= e(url_for('admin')) ?>" class="mc-admin-back">
    <img src="<?= e(media_url('2026/01/arrow-left-1.png')) ?>" class="mc-admin-icon" alt="">
    Nazad na admin panel
  </a>

  <div class="mc-admin-add-card">
    <h1>
      <img src="<?= e(media_url('2026/01/clapperboard-1.png')) ?>" class="mc-admin-add-card__icon" alt="">
      Dodaj projekciju
    </h1>

    <?php if ($error): ?>
      <div class="mc-admin-msg mc-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="mc-admin-form">
      <div class="mc-admin-form__grid">
        <div class="mc-admin-form__field">
          <label for="mc-add-movie">Film</label>
          <select name="movie_id" id="mc-add-movie" required>
            <option value="">Izaberite film</option>
            <?php foreach ($movies as $movie): ?>
              <option value="<?= e((string) $movie['id']) ?>">
                <?= e($movie['title']) ?><?php if ($movie['genres']): ?> — <?= e(implode(', ', array_map(fn(array $genre): string => $genre['name'], $movie['genres']))) ?><?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mc-admin-form__field">
          <label for="mc-add-hall">Sala</label>
          <select name="hall_id" id="mc-add-hall" required>
            <option value="">Izaberite salu</option>
            <?php foreach ($halls as $hall): ?>
              <option value="<?= e((string) $hall['id']) ?>"><?= e($hall['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mc-admin-form__field">
          <label for="mc-add-date">Datum</label>
          <input type="date" name="date" id="mc-add-date" required min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
        </div>

        <div class="mc-admin-form__field">
          <label for="mc-add-time">Vrijeme</label>
          <select name="time" id="mc-add-time" disabled required>
            <option value="">Odaberite film, salu i datum</option>
          </select>
        </div>

        <div class="mc-admin-form__field mc-admin-form__field--price">
          <label for="mc-add-price">Cijena (KM)</label>
          <input type="number" name="price" id="mc-add-price" step="0.5" min="0" placeholder="npr. 8.00" required>
        </div>

        <div class="mc-admin-form__actions">
          <button type="submit" name="mc_add_screening">Dodaj projekciju</button>
        </div>
      </div>
    </form>
  </div>
  <?php if ($success): ?>
    <div id="mc-toast" class="mc-toast" data-auto-toast><?= e($success) ?></div>
  <?php endif; ?>
</section>
