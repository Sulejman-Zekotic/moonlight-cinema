document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('.mc-reviews');
  if (!root) return;

  const movieId = root.dataset.movie;
  const avgStarsEl = document.getElementById('mc-avg-stars');
  const avgNumEl = document.getElementById('mc-avg-number');
  const avgMetaEl = document.getElementById('mc-avg-meta');
  const listEl = document.getElementById('mc-reviews-list');
  const formStarsEl = document.getElementById('mc-form-stars');
  const textEl = document.getElementById('mc-review-text');
  const countEl = document.getElementById('mc-review-count');
  const submitBtn = document.getElementById('mc-review-submit');

  let selectedRating = 0;

  function stars(rating, size = 'md') {
    const full = Math.round(rating);
    let html = `<div class="mc-stars mc-stars-${size}">`;
    for (let index = 1; index <= 5; index += 1) {
      html += `<span class="mc-star ${index <= full ? 'on' : 'off'}">★</span>`;
    }
    html += '</div>';
    return html;
  }

  function showToast(text, type = 'success') {
    let box = document.querySelector('.mc-review-toast');

    if (!box) {
      box = document.createElement('div');
      box.className = 'mc-review-toast';
      document.body.appendChild(box);
    }

    box.className = `mc-review-toast ${type}`;
    box.textContent = text;
    requestAnimationFrame(() => box.classList.add('show'));

    setTimeout(() => {
      box.classList.remove('show');
    }, 2800);
  }

  function showConfirm({ rating, message }, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'mc-review-overlay show';
    overlay.innerHTML = `
      <div class="mc-review-modal">
        <h3>Potvrda slanja</h3>
        <p>Da li ste sigurni da želite poslati recenziju?</p>
        <div class="mc-review-rating"><strong>Ocjena:</strong> ${rating} ★</div>
        <div class="mc-review-modal__message"><strong>Poruka:</strong>\n${message || 'Bez teksta.'}</div>
        <div class="mc-review-actions">
          <button class="mc-review-cancel">Otkaži</button>
          <button class="mc-review-send">Pošalji</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    overlay.querySelector('.mc-review-cancel').onclick = () => overlay.remove();
    overlay.querySelector('.mc-review-send').onclick = () => {
      overlay.remove();
      onConfirm();
    };
  }

  async function load() {
    const response = await fetch(`${mcReviews.ajaxurl}?action=mc_get_reviews&movie_id=${movieId}`);
    const json = await response.json();
    if (!json.success) return;

    const stats = json.data.stats;
    const reviews = json.data.reviews;

    avgStarsEl.innerHTML = stars(stats.avg_rating || 0);
    avgNumEl.textContent = (stats.avg_rating || 0).toString().replace('.', ',');
    avgMetaEl.textContent = `${stats.total || 0} recenzija`;

    if (!reviews.length) {
      listEl.innerHTML = '<div class="mc-empty">Još nema recenzija.</div>';
      return;
    }

    listEl.innerHTML = reviews.map((review) => `
      <div class="mc-review-card">
        <div class="mc-review-avatar">${review.display_name.charAt(0)}</div>
        <div class="mc-review-body">
          <div class="mc-review-header">
            <div class="mc-review-name">${review.display_name}</div>
            ${stars(review.rating, 'sm')}
          </div>
          <div class="mc-review-text">${review.comment || ''}</div>
          <div class="mc-review-date">
            <img src="${window.MC_BASE_URL}/assets/media/2026/02/clock-4.png" style="width:16px;height:16px;filter:brightness(0) invert(1);vertical-align:middle;" alt="">
            ${review.date}
          </div>
        </div>
      </div>
    `).join('');
  }

  function renderFormStars() {
    let html = '<div class="mc-form-stars">';
    for (let index = 1; index <= 5; index += 1) {
      html += `<span data-rate="${index}" class="mc-star-btn ${index <= selectedRating ? 'on' : 'off'}">★</span>`;
    }
    html += '</div>';

    formStarsEl.innerHTML = html;
    formStarsEl.querySelectorAll('.mc-star-btn').forEach((star) => {
      star.onclick = () => {
        selectedRating = Number(star.dataset.rate);
        renderFormStars();
      };
    });
  }

  textEl.addEventListener('input', () => {
    countEl.textContent = `${textEl.value.length} / 300`;
  });

  submitBtn.addEventListener('click', () => {
    if (!selectedRating) {
      showToast('Odaberite ocjenu.', 'error');
      return;
    }

    showConfirm({ rating: selectedRating, message: textEl.value }, sendReview);
  });

  async function sendReview() {
    const formData = new FormData();
    formData.append('action', 'mc_submit_review');
    formData.append('movie_id', movieId);
    formData.append('rating', selectedRating);
    formData.append('comment', textEl.value);

    try {
      const response = await fetch(mcReviews.ajaxurl, { method: 'POST', body: formData });
      const json = await response.json();

      if (!json.success) {
        showToast(json.data || 'Greška pri slanju.', 'error');
        return;
      }

      showToast('Recenzija uspješno poslana.');
      textEl.value = '';
      selectedRating = 0;
      countEl.textContent = '0 / 300';
      renderFormStars();
      load();
    } catch {
      showToast('Greška u komunikaciji sa serverom.', 'error');
    }
  }

  renderFormStars();
  load();
});
