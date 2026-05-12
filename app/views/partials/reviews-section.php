<section id="recenzije" class="mc-reviews" data-movie="<?= e((string) $movie['id']) ?>">
  <h2 class="mc-reviews-title">Ocjene i recenzije</h2>

  <div class="mc-reviews-summary">
    <div class="mc-reviews-avg">
      <div id="mc-avg-stars"></div>
      <div class="mc-avg-line">
        <span class="mc-avg-number" id="mc-avg-number">0</span>
        <span>/ 5</span>
        <span class="mc-avg-meta" id="mc-avg-meta">Na osnovu 0 recenzija</span>
      </div>
    </div>
    <div class="mc-reviews-bars" id="mc-bars"></div>
  </div>

  <h3 class="mc-reviews-subtitle">Ostavite vašu recenziju</h3>
  <div class="mc-reviews-list" id="mc-reviews-list"></div>

  <div class="mc-review-form">
    <div id="mc-form-stars"></div>
    <div class="mc-form-row">
      <textarea id="mc-review-text" maxlength="300" placeholder="Napišite vašu recenziju..."></textarea>
      <button id="mc-review-submit">Pošalji recenziju</button>
    </div>
    <div class="mc-form-meta">
      <span id="mc-review-count">0 / 300</span>
    </div>
  </div>
</section>
