<?php if (!$movie): ?>
  <section class="mc-state-page">
    <div class="mc-state-box">
      <h1>Film nije pronađen</h1>
      <p>Traženi film ne postoji u katalogu.</p>
      <a href="<?= e(url_for('filmovi')) ?>" class="btn-outline">Nazad na filmove</a>
    </div>
  </section>
<?php else: ?>
  <section class="mc-hero" style="background-image:url('<?= e($movie['hero_url']) ?>')">
    <div class="mc-hero-overlay"></div>
    <div class="mc-hero-card">
      <div class="mc-hero-poster">
        <img src="<?= e($movie['poster_url']) ?>" alt="<?= e($movie['title']) ?>">
      </div>
      <div class="mc-hero-info">
        <h1><?= e($movie['title']) ?></h1>
        <?php if ($movie['genres']): ?>
          <div class="mc-hero-tags">
            <?php foreach ($movie['genres'] as $genre): ?>
              <span class="tag tag-<?= e($genre['slug']) ?>"><?= e($genre['name']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="mc-hero-meta">
          <span><img src="<?= e(media_url('2026/02/clock-4.png')) ?>" alt=""><?= (int) $movie['duration_minutes'] ?> min</span>
          <span><img src="<?= e(media_url('2026/01/clapperboard-open-play-svgrepo-com.svg')) ?>" alt=""><?= e($movie['director']) ?></span>
        </div>
        <p class="mc-hero-desc"><?= e($movie['description']) ?></p>
        <div class="mc-hero-actions">
          <?php if (!empty($movie['trailer_url'])): ?>
            <a href="<?= e($movie['trailer_url']) ?>" target="_blank" rel="noreferrer" class="btn-outline">
              <span>Pogledaj trailer</span>
              <img src="<?= e(media_url('2026/01/play-video-movie-film-svgrepo-com.svg')) ?>" alt="">
            </a>
          <?php endif; ?>
          <?php if ($repo->movieHasAvailableScreenings((int) $movie['id'])): ?>
            <a href="<?= e(reservation_link((int) $movie['id'])) ?>" class="btn-primary-reservation">
              <span>Rezerviši</span>
              <img src="<?= e(media_url('2026/02/ticket.png')) ?>" alt="">
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php render_partial('partials/reviews-section', ['movie' => $movie]); ?>
<?php endif; ?>
