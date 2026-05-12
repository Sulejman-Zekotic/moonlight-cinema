<?php

$isLoggedIn = $auth->check();
$isAdmin = $auth->isAdmin();
?>
<header class="mc-site-header">
  <div class="mc-site-header__inner">
    <a class="mc-site-brand" href="<?= e(url_for('/')) ?>">
      <img src="<?= e(media_url('2026/02/film.png')) ?>" alt="Moonlight Cinema">
      <span>MOONLIGHT CINEMA</span>
    </a>

    <button
      type="button"
      class="mc-nav-toggle"
      aria-expanded="false"
      aria-controls="mc-site-nav"
      aria-label="Otvori meni"
      data-nav-toggle
    >
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="mc-site-nav-shell" id="mc-site-nav-shell" data-nav-shell>
      <div class="mc-site-nav-backdrop" data-nav-close></div>
      <nav class="mc-site-nav" id="mc-site-nav" aria-label="Glavna navigacija">
        <div class="mc-site-nav__head">
          <div class="mc-site-nav__brand">
            <img src="<?= e(media_url('2026/02/film.png')) ?>" alt="">
            <span>Moonlight Cinema</span>
          </div>
          <button type="button" class="mc-site-nav__dismiss" aria-label="Zatvori meni" data-nav-close>
            <img src="<?= e(media_url('2026/02/arrow-left.png')) ?>" alt="">
          </button>
        </div>

        <a class="mc-site-nav__link" href="<?= e(url_for('/')) ?>">
          <img src="<?= e(media_url('2026/02/house.png')) ?>" alt="">
          <span>Početna</span>
        </a>

        <a class="mc-site-nav__link" href="<?= e(url_for('filmovi')) ?>">
          <img src="<?= e(media_url('2026/02/search.png')) ?>" alt="">
          <span>Filmovi</span>
        </a>

        <?php if ($isLoggedIn): ?>
          <a class="mc-site-nav__link" href="<?= e(url_for('moje-karte')) ?>">
            <img src="<?= e(media_url('2026/02/tickets.png')) ?>" alt="">
            <span>Moje karte</span>
          </a>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
          <a class="mc-site-nav__link" href="<?= e(url_for('admin')) ?>">
            <img src="<?= e(media_url('2026/01/settings.png')) ?>" alt="">
            <span>Admin panel</span>
          </a>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
          <form class="mc-site-nav__logout" action="<?= e(url_for('odjava')) ?>" method="post">
            <button type="submit" class="mc-site-nav__button">
              <img src="<?= e(media_url('2026/02/log-out.png')) ?>" alt="">
              <span>Odjavi se</span>
            </button>
          </form>
        <?php else: ?>
          <a class="mc-site-nav__link" href="<?= e(url_for('prijava')) ?>">
            <img src="<?= e(media_url('2026/02/log-in.png')) ?>" alt="">
            <span>Registracija/Prijava</span>
          </a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>
