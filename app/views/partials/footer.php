<footer class="mc-site-footer">
  <div class="mc-site-footer__inner">
    <div class="mc-site-footer__group mc-site-footer__group--nav">
      <h3 class="mc-site-footer__title">Navigacija</h3>
      <div class="mc-site-footer__menu">
        <a class="mc-site-footer__menu-link" href="<?= e(url_for('/')) ?>">
          <img src="<?= e(media_url('2026/02/house.png')) ?>" alt="">
          <span>Početna</span>
        </a>
        <a class="mc-site-footer__menu-link" href="<?= e(url_for('filmovi')) ?>">
          <img src="<?= e(media_url('2026/02/search.png')) ?>" alt="">
          <span>Filmovi</span>
        </a>
        <?php if ($auth->check()): ?>
          <a class="mc-site-footer__menu-link" href="<?= e(url_for('moje-karte')) ?>">
            <img src="<?= e(media_url('2026/02/tickets.png')) ?>" alt="">
            <span>Moje karte</span>
          </a>
        <?php endif; ?>
        <?php if ($auth->isAdmin()): ?>
          <a class="mc-site-footer__menu-link" href="<?= e(url_for('admin')) ?>">
            <img src="<?= e(media_url('2026/01/settings.png')) ?>" alt="">
            <span>Admin panel</span>
          </a>
        <?php endif; ?>
        <?php if ($auth->check()): ?>
          <form class="mc-site-footer__logout" action="<?= e(url_for('odjava')) ?>" method="post">
            <button type="submit" class="mc-site-footer__button">
              <img src="<?= e(media_url('2026/02/log-out.png')) ?>" alt="">
              <span>Odjavi se</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="mc-site-footer__group">
      <h3 class="mc-site-footer__title">Kontakt</h3>
      <div class="mc-site-footer__contact">
        <span class="mc-site-footer__contact-item">
          <img src="<?= e(media_url('2026/02/map-pin-house.png')) ?>" alt="">
          <span>Mostar, BiH – Maršala Tita 12</span>
        </span>
        <a class="mc-site-footer__contact-item" href="mailto:info@moonlightcinema.ba">
          <img src="<?= e(media_url('2026/02/mail.png')) ?>" alt="">
          <span>info@moonlightcinema.ba</span>
        </a>
        <a class="mc-site-footer__contact-item" href="tel:+38761123456">
          <img src="<?= e(media_url('2026/02/phone.png')) ?>" alt="">
          <span>+387 61 123 456</span>
        </a>
      </div>
    </div>

    <div class="mc-site-footer__group">
      <h3 class="mc-site-footer__title">Društvene mreže</h3>
      <div class="mc-site-footer__social">
        <a class="mc-site-footer__social-link" href="https://facebook.com" target="_blank" rel="noreferrer">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M13.5 21v-7h2.4l.4-3h-2.8V9.1c0-.9.3-1.6 1.6-1.6H16V4.8c-.5-.1-1.3-.2-2.4-.2-2.4 0-4 1.4-4 4.2V11H7v3h2.2v7h4.3Z"></path>
          </svg>
          <span>Facebook</span>
        </a>
        <a class="mc-site-footer__social-link" href="https://instagram.com" target="_blank" rel="noreferrer">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4Zm0 2.2A1.8 1.8 0 0 0 5.2 7v10A1.8 1.8 0 0 0 7 18.8h10a1.8 1.8 0 0 0 1.8-1.8V7A1.8 1.8 0 0 0 17 5.2H7Zm10.2 1.5a.9.9 0 1 1 0 1.8.9.9 0 0 1 0-1.8ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2.2A2.8 2.8 0 1 0 12 14.8 2.8 2.8 0 0 0 12 9.2Z"></path>
          </svg>
          <span>Instagram</span>
        </a>
      </div>
    </div>
  </div>

  <div class="mc-site-footer__copy">
    Copyright © <?= date('Y') ?> Powered by MOONLIGHT CINEMA
  </div>
</footer>
