<?php
$chartLabels = $stats['chart']['labels'] ?? [];
$chartValues = $stats['chart']['values'] ?? [];
$points = [];

if ($chartValues) {
    $max = max($chartValues) ?: 1;
    $count = count($chartValues);

    foreach ($chartValues as $index => $value) {
        $x = $count === 1 ? 70 : 70 + (760 / max(1, $count - 1)) * $index;
        $y = 260 - (($value / $max) * 170);
        $points[] = $x . ',' . $y;
    }
}

$summaryBars = [
    ['label' => 'Karte', 'value' => (int) $stats['tickets']],
    ['label' => 'Rezervacije', 'value' => (int) $stats['reservations']],
    ['label' => 'Projekcije', 'value' => (int) $stats['screenings']],
    ['label' => 'Filmovi', 'value' => (int) $stats['movies']],
];
$summaryMax = max(array_column($summaryBars, 'value')) ?: 1;
?>
<section class="mc-admin-stats-page">
  <a href="<?= e(url_for('admin')) ?>" class="mc-admin-back">
    <img src="<?= e(media_url('2026/01/arrow-left-1.png')) ?>" class="mc-admin-icon" alt="">
    Nazad na admin panel
  </a>

  <h1 class="mc-admin-title">
    <img src="<?= e(media_url('2026/01/chart-line.png')) ?>" class="mc-admin-icon" alt="">
    Statistika
  </h1>

  <?php if (!empty($stats['top_movie'])): ?>
    <div class="mc-top-movie">
      <div class="mc-top-movie-card">
        <img src="<?= e($stats['top_movie']['poster_url']) ?>" alt="">
        <div>
          <span>
            <img src="<?= e(media_url('2026/02/star-removebg-preview.png')) ?>" alt="">
            Najgledaniji film
          </span>
          <h3><?= e($stats['top_movie']['title']) ?></h3>
          <p><?= e((string) $stats['top_movie']['reservations_count']) ?> rezervacija</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="mc-admin-stats-grid">
    <div class="mc-stat-card blue"><span>Prodane karte</span><strong><?= e((string) $stats['tickets']) ?></strong></div>
    <div class="mc-stat-card orange"><span>Rezervacije</span><strong><?= e((string) $stats['reservations']) ?></strong></div>
    <div class="mc-stat-card pink"><span>Projekcije</span><strong><?= e((string) $stats['screenings']) ?></strong></div>
    <div class="mc-stat-card green"><span>Filmovi</span><strong><?= e((string) $stats['movies']) ?></strong></div>
  </div>

  <div class="mc-admin-charts-grid">
    <div class="mc-admin-chart-box">
      <h2>
        <img src="<?= e(media_url('2026/01/chart-line.png')) ?>" class="mc-admin-icon" alt="">
        Rezervacije po danima
      </h2>
      <div class="mc-chart-scroll">
        <svg viewBox="0 0 900 320" width="100%" height="320" aria-label="Graf rezervacija">
          <defs>
            <linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="rgba(78,168,222,0.65)"></stop>
              <stop offset="100%" stop-color="rgba(78,168,222,0.08)"></stop>
            </linearGradient>
          </defs>
          <line x1="70" y1="260" x2="840" y2="260" stroke="rgba(255,255,255,0.18)"></line>
          <line x1="70" y1="60" x2="70" y2="260" stroke="rgba(255,255,255,0.18)"></line>
          <?php for ($i = 0; $i <= 4; $i++): ?>
            <?php $gridY = 260 - ($i * 50); ?>
            <line x1="70" y1="<?= e((string) $gridY) ?>" x2="840" y2="<?= e((string) $gridY) ?>" stroke="rgba(255,255,255,0.07)"></line>
          <?php endfor; ?>
          <?php if ($points): ?>
            <polygon fill="url(#chartFill)" points="<?= e(implode(' ', $points)) ?> 840,260 70,260"></polygon>
            <polyline fill="none" stroke="#4EA8DE" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" points="<?= e(implode(' ', $points)) ?>"></polyline>
            <?php foreach ($points as $index => $point): [$x, $y] = array_map('floatval', explode(',', $point)); ?>
              <circle cx="<?= e((string) $x) ?>" cy="<?= e((string) $y) ?>" r="7" fill="#4EA8DE"></circle>
              <text x="<?= e((string) $x) ?>" y="<?= e((string) ($y - 14)) ?>" text-anchor="middle" fill="#ffffff" font-size="15" font-weight="700"><?= e((string) $chartValues[$index]) ?></text>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php foreach ($chartLabels as $index => $label): ?>
            <?php $x = count($chartLabels) === 1 ? 70 : 70 + (760 / max(1, count($chartLabels) - 1)) * $index; ?>
            <text x="<?= e((string) $x) ?>" y="294" text-anchor="middle" fill="#cbd5e1" font-size="16"><?= e($label) ?></text>
          <?php endforeach; ?>
        </svg>
      </div>
    </div>

    <div class="mc-admin-chart-box">
      <h2>
        <img src="<?= e(media_url('2026/01/chart-line.png')) ?>" class="mc-admin-icon" alt="">
        Pregled sistema
      </h2>
      <div class="mc-bars-chart" aria-label="Graf pregleda sistema">
        <?php foreach ($summaryBars as $bar): ?>
          <?php $height = max(10, (int) round(($bar['value'] / $summaryMax) * 100)); ?>
          <div class="mc-bars-chart__item">
            <strong><?= e((string) $bar['value']) ?></strong>
            <span class="mc-bars-chart__bar" style="height: <?= e((string) $height) ?>%;"></span>
            <small><?= e($bar['label']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
