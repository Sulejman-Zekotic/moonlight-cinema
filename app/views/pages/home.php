<?php if ($heroMovie): ?>
  <section class="mc-hero" style="background-image:url('<?= e($heroMovie['hero_url']) ?>')">
    <div class="mc-hero-overlay"></div>
    <div class="mc-hero-card">
      <div class="mc-hero-poster">
        <img src="<?= e($heroMovie['poster_url']) ?>" alt="<?= e($heroMovie['title']) ?>">
      </div>
      <div class="mc-hero-info">
        <h1><?= e($heroMovie['title']) ?></h1>
        <p class="mc-hero-desc"><?= e($heroMovie['hero_excerpt'] ?: $heroMovie['description']) ?></p>
        <?php if ($heroMovie['genres']): ?>
          <div class="mc-hero-tags">
            <?php foreach ($heroMovie['genres'] as $genre): ?>
              <span class="tag tag-<?= e($genre['slug']) ?>"><?= e($genre['name']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="mc-hero-meta">
          <span><img src="<?= e(media_url('2026/02/clock-4.png')) ?>" alt=""><?= (int) $heroMovie['duration_minutes'] ?> min</span>
          <span><img src="<?= e(media_url('2026/01/clapperboard-open-play-svgrepo-com.svg')) ?>" alt=""><?= e($heroMovie['director']) ?></span>
        </div>
        <div class="mc-hero-actions">
          <a href="<?= e(movie_link((int) $heroMovie['id'])) ?>" class="btn-outline">
            <span>Saznaj više</span>
          </a>
          <a href="<?= e(reservation_link((int) $heroMovie['id'])) ?>" class="btn-primary-reservation">
            <span>Rezerviši</span>
            <img src="<?= e(media_url('2026/02/ticket.png')) ?>" alt="">
          </a>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="mc-section">
  <h2 class="mc-section-title">NA PROGRAMU</h2>
  <div class="mc-movie-grid">
    <?php foreach ($nowShowingMovies as $movie): ?>
      <?= $repo->renderMovieCard($movie) ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="mc-section">
  <h2 class="mc-section-title">USKORO</h2>
  <?php if ($comingSoonMovies): ?>
    <div class="mc-movie-grid">
      <?php foreach ($comingSoonMovies as $movie): ?>
        <?= $repo->renderMovieCard($movie) ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="mc-empty">Trenutno nema filmova u najavi.</p>
  <?php endif; ?>
</section>

<section class="mc-section">
  <h2 class="mc-section-title">TOP 3 FILMA PO REZERVACIJAMA</h2>
  <div class="mc-movie-grid">
    <?php foreach ($topReservedMovies as $movie): ?>
      <?= $repo->renderMovieCard($movie) ?>
    <?php endforeach; ?>
  </div>
</section>
