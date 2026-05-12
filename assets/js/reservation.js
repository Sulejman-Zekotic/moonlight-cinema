// ==============================
// MC SEAT PICKER – FULL VERSION (FINAL + LOCKED OCCUPIED)
// ==============================

const gridLoader = document.getElementById('mc-grid-loader');

function showGridLoader() {
  gridLoader?.classList.remove('is-hidden');
}
function hideGridLoader() {
  gridLoader?.classList.add('is-hidden');
}

document.addEventListener('DOMContentLoaded', () => {

  // ==============================
  // FILM CARD ELEMENTS
  // ==============================
  const filmCard   = document.getElementById('mc-film-card');
  const filmPoster = document.getElementById('mc-film-poster');
  const filmTitle  = document.getElementById('mc-film-title');
  const filmDesc   = document.getElementById('mc-film-description');
  const filmDur    = document.getElementById('mc-film-duration');
  const filmDir    = document.getElementById('mc-film-director');

  // ==============================
  // CORE ELEMENTS
  // ==============================
  const closeModalBtn = document.getElementById('mc-close-modal');
  const stepReview    = document.getElementById('mc-step-review');
  const stepPayment   = document.getElementById('mc-step-payment');

  const toPaymentBtn  = document.getElementById('mc-to-payment');
  const backBtn       = document.getElementById('mc-back-to-review');

  const movieSelect   = document.getElementById('mc-movie');          // fallback (ako nema mid)
  const movieTitleBox = document.getElementById('mc-movie-title');    // prikaz naslova kad je iz URL

  const dateInput  = document.getElementById('mc-date');
  const timesWrap  = document.getElementById('mc-times');
  const timesField = document.querySelector('.mc-field--times');
  const seatsWrap  = document.getElementById('mc-seats');
  const gridEl     = document.getElementById('mc-grid');
  const hallNameEl = document.getElementById('mc-hall-name');
  const totalEl    = document.getElementById('mc-total');
  const confirmBtn = document.getElementById('mc-confirm');

  const modal      = document.getElementById('mc-reservation-modal');
  const modalMovie = document.getElementById('modal-movie');
  const modalDate  = document.getElementById('modal-datetime');
  const modalHall  = document.getElementById('modal-hall');
  const modalSeats = document.getElementById('modal-seats');
  const modalTotal = document.getElementById('modal-total');
  const overlay    = modal?.querySelector('.mc-modal__overlay');
const filmLoading = document.getElementById('mc-film-loading');
const guestEmailInputReview = document.getElementById('mc-guest-email-review');

function getGuestEmailOrBlock() {
  // ako input ne postoji -> znači app user (ulogovan) -> ne treba email
  if (!guestEmailInputReview) return "";

  const email = guestEmailInputReview.value.trim();
  if (!email) {
    showToast("❗ Unesite email prije nastavka.");
    return null;
  }

  const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  if (!ok) {
    showToast("❗ Unesite ispravan email (npr. ime@email.com).");
    return null;
  }

  return email;
}

  if (!dateInput || !timesWrap || !seatsWrap || !gridEl) return;

  // ==============================
  // ASSETS
  // ==============================
  const SVG_SINGLE = mcAsset('2025/12/OneSeat.svg');
  const PNG_DOUBLE = mcAsset('2025/12/TwoSeats.png');
  const SVG_ACCESS = mcAsset('2025/12/Accessability.svg');

  // ==============================
  // STATE
  // ==============================
  window.__MC_ACTIVE_MOVIE_ID__ = null;

  let allowedDates = [];
  let selectedSeats = [];
  let wheelchairToastShown = false;

  let activeScreeningId = null;

  // ==============================
  // HELPERS
  // ==============================
  function getMovieFromUrl() {
    const p = new URLSearchParams(window.location.search);
    return p.get('mid'); // ?mid=7
  }

  function setActiveMovieId(id) {
    window.__MC_ACTIVE_MOVIE_ID__ = id ? String(id) : null;
  }

  // ==============================
  // INIT UI DEFAULTS
  // ==============================
  timesField?.classList.add('is-hidden');
  seatsWrap.classList.add('is-hidden');
  hallNameEl.textContent = '';
  setTotal(0);
  confirmBtn.disabled = true;

  // date disabled dok se ne zna film (ili dok se ne učitaju allowedDates)
  dateInput.disabled = true;

  // ==============================
  // LOAD FILM CARD (by ID)
  // ==============================
  function loadFilmCard(id) {
    if (!id) return;
    filmLoading?.classList.remove('is-hidden');
filmCard?.classList.add('is-hidden');

    fetch(mcAjax.ajaxurl
, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=mc_get_movie_by_id&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) return;

      const m = resp.data;
      setActiveMovieId(m.id);

      if (filmPoster) filmPoster.src = m.poster_url || '';
      if (filmTitle)  filmTitle.textContent = m.title || '';
      if (filmDesc)   filmDesc.textContent = m.description || '';
     if (filmDur) {
  filmDur.innerHTML = m.duration_minutes 
    ? `<img src="${window.MC_BASE_URL}/assets/media/2026/02/clock-4.png" 
            alt="Trajanje" 
            class="mc-info-icon"> ${m.duration_minutes} min`
    : '';

}

if (filmDir) {
  filmDir.innerHTML = m.director 
    ? `<img src="${window.MC_BASE_URL}/assets/media/2026/02/clapperboard.png" 
            alt="Režiser" 
            class="mc-info-icon"> ${m.director}`
    : '';
}
      const genresWrap = document.getElementById('mc-film-genres');
if (genresWrap) {
  genresWrap.innerHTML = '';

  if (m.genres && m.genres.length) {
    m.genres.forEach(g => {
      const span = document.createElement('span');
      span.className = `tag tag-${g.slug}`;
      span.textContent = g.name;
      genresWrap.appendChild(span);
    });
  }
}
      filmCard?.classList.remove('is-hidden');

      // naslov iz URL moda (umjesto select)
      if (movieTitleBox && (m.title || '')) {
        movieTitleBox.textContent = m.title;
        movieTitleBox.classList.remove('is-hidden');
      }
      if (movieSelect) {
        movieSelect.classList.add('is-hidden');
      }
      filmLoading?.classList.add('is-hidden');
filmCard?.classList.remove('is-hidden');

    });
  }

  // ==============================
  // LOAD AVAILABLE DATES (MUST BE OUTSIDE .then)
  // ==============================
  function loadAvailableDatesForMovie(movieId) {
    if (!movieId) return;

    dateInput.disabled = true;
    dateInput.value = '';
    allowedDates = [];

    fetch(mcAjax.ajaxurl
, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=mc_get_available_dates_for_movie&movie_id=${encodeURIComponent(movieId)}`
    })
   .then(r => r.json())
.then(resp => {
  if (!resp.success || !resp.data || !resp.data.length) {
    showToast("❌ Nema projekcija za ovaj film.");
    timesField?.classList.add('is-hidden');
    return;
  }

  allowedDates = resp.data;

  dateInput.min = allowedDates[0];
  dateInput.max = allowedDates[allowedDates.length - 1];
  dateInput.disabled = false;

  dateInput.value = allowedDates[0];
  dateInput.dispatchEvent(new Event('change'));
});

  }

  // ==============================
  // URL MODE INIT
  // ==============================
  const urlMovieId = getMovieFromUrl();
  if (urlMovieId) {
    setActiveMovieId(urlMovieId);

    // Sakrij select odmah (ako postoji), pokaži title box
    if (movieSelect) movieSelect.classList.add('is-hidden');
    if (movieTitleBox) movieTitleBox.classList.remove('is-hidden');

    loadFilmCard(urlMovieId);
    loadAvailableDatesForMovie(urlMovieId);
  }

  // ==============================
  // FALLBACK MODE (NO mid) -> LOAD MOVIES INTO SELECT
  // ==============================
  if (!urlMovieId && movieSelect) {

    movieSelect.innerHTML = `<option disabled selected>Učitavanje filmova...</option>`;

    fetch(mcAjax.ajaxurl
, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=mc_get_movies'
    })
    .then(r => r.json())
    .then(resp => {
      movieSelect.innerHTML = `<option value="" disabled selected hidden>Odaberi film</option>`;
      if (!resp.success || !resp.data) return;

      resp.data.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.title;
        movieSelect.appendChild(opt);
      });
    });

    // kad korisnik izabere film u fallback modu
    movieSelect.addEventListener('change', () => {
      hideSeatMap();
      timesWrap.innerHTML = '';
      timesField?.classList.add('is-hidden');

      dateInput.value = '';
      dateInput.disabled = true;
      allowedDates = [];

      if (!movieSelect.value) return;

      setActiveMovieId(movieSelect.value);

      // pokaži title box ako želiš (opciono)
      if (movieTitleBox) {
        const txt = movieSelect.options[movieSelect.selectedIndex]?.text || '';
        movieTitleBox.textContent = txt;
        movieTitleBox.classList.remove('is-hidden');
      }

      loadAvailableDatesForMovie(movieSelect.value);
    });
  }

  // ==============================
  // DATE CHANGE (ONLY ONE listener)
  // ==============================
  dateInput.addEventListener('change', () => {

    // ako imamo allowedDates listu, zabrani van tog opsega
    if (allowedDates.length && !allowedDates.includes(dateInput.value)) {
      showToast("❌ Ovaj film se ne prikazuje na odabrani datum.");
      dateInput.value = '';
      hideSeatMap();
      timesField?.classList.add('is-hidden');
      return;
    }

    maybeLoadScreenings();
  });

  // ==============================
  // LOAD SCREENINGS
  // ==============================
  function maybeLoadScreenings() {

    timesWrap.innerHTML = '';
    hideSeatMap();
    resetSelection();

    const movieId = window.__MC_ACTIVE_MOVIE_ID__ || (movieSelect ? movieSelect.value : null);

    if (!movieId || !dateInput.value) {
      timesField?.classList.add('is-hidden');
      return;
    }

    timesField?.classList.remove('is-hidden');
    timesWrap.innerHTML = `<p class="mc-loading" style="color:white!important;">Učitavanje termina...</p>`;

    fetch(mcAjax.ajaxurl
, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=mc_get_screenings&movie_id=${encodeURIComponent(movieId)}&date=${encodeURIComponent(dateInput.value)}`
    })
    .then(r => r.json())
    .then(resp => {
      timesWrap.innerHTML = '';

      if (!resp.success || !resp.data || !resp.data.length) {
        timesWrap.innerHTML = `<p class="mc-muted">Nema termina</p>`;
        return;
      }

      resp.data.forEach(s => {

        const card = document.createElement('div');
        card.className = 'mc-time-card';
        card.innerHTML = `
<strong>${s.start} – ${s.end}</strong>
          <span>${s.hall_name || ''}</span>
        `;

        card.addEventListener('click', () => {

          document.querySelectorAll('.mc-time-card').forEach(c => c.classList.remove('active'));
          card.classList.add('active');

          hallNameEl.textContent = s.hall_name;
          activeScreeningId = s.id;

          showSeatMap();
          showGridLoader();
          loadSeats(activeScreeningId);
        });

        timesWrap.appendChild(card);
      });
    });
  }

  // ==============================
  // LOAD SEATS
  // ==============================
  function loadSeats(id) {
    if (!id) return;

    resetSelection();
    gridEl.innerHTML = '';
    showGridLoader();

    fetch(mcAjax.ajaxurl
, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=mc_get_seats&screening_id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(resp => {
      hideGridLoader();
      if (!resp.success) return;
      renderSeats(resp.data);
    })
    .catch(() => hideGridLoader());
  }

  // ==============================
  // RENDER SEATS (LOCKED)
  // ==============================
function renderSeats(seats) {
  gridEl.innerHTML = '';

  const rows = [...new Set(seats.map(s => s.row_label))];
  const allCols = [...new Set(seats.map(s => Number(s.seat_number)))]
    .sort((a, b) => a - b);

  const TOTAL_COLS = allCols.length;
  const HAS_AISLE = TOTAL_COLS >= 10;
  const AISLE_AFTER = Math.floor(TOTAL_COLS / 2);
  const isMobile = window.matchMedia('(max-width: 760px)').matches;
  const rowLabelWidth = isMobile ? 16 : 22;
  const aisleWidth = isMobile ? 4 : 8;

  gridEl.style.width = isMobile ? '100%' : 'min(100%, 920px)';
  gridEl.style.gridTemplateColumns = HAS_AISLE
    ? `${rowLabelWidth}px repeat(${AISLE_AFTER}, minmax(0, 1fr)) minmax(${aisleWidth}px, 0.35fr) repeat(${TOTAL_COLS - AISLE_AFTER}, minmax(0, 1fr))`
    : `${rowLabelWidth}px repeat(${TOTAL_COLS}, minmax(0, 1fr))`;

  rows.forEach(r => {

    // ⬅ slovo reda
    const rowLbl = document.createElement('div');
    rowLbl.className = 'mc-row-label';
    rowLbl.textContent = r;
    gridEl.appendChild(rowLbl);

    const rowSeats = seats
      .filter(s => s.row_label === r)
      .sort((a, b) => a.seat_number - b.seat_number);

    const existing = rowSeats.map(s => Number(s.seat_number));
    const min = Math.min(...existing);
    const max = Math.max(...existing);
    const rowWidth = max - min + 1;

    const emptyLeft = Math.floor((TOTAL_COLS - rowWidth) / 2);

    let printed = 0;

    for (let i = 1; i <= TOTAL_COLS; i++) {

      if (HAS_AISLE && printed === AISLE_AFTER) {
        const aisle = document.createElement('div');
        aisle.className = 'mc-aisle';
        gridEl.appendChild(aisle);
      }

      let seat = null;

      if (i > emptyLeft && i <= emptyLeft + rowWidth) {
        const realSeatNumber = min + (i - emptyLeft - 1);
        seat = rowSeats.find(s => Number(s.seat_number) === realSeatNumber);
      }

      if (!seat) {
        gridEl.appendChild(document.createElement('div'));
        printed++;
        continue;
      }

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mc-seat';
      btn.dataset.key = `${r}-${seat.seat_number}`;
      btn.dataset.price = seat.price;

      const type = (seat.seat_type || '').toLowerCase();
      const status = (seat.status || '').toLowerCase().trim();

      let svg = SVG_SINGLE;

      if (type === 'love') {
        svg = PNG_DOUBLE;
        btn.classList.add('is-love');
      }

      if (type === 'wheelchair') {
        svg = SVG_ACCESS;
        btn.classList.add('is-wheelchair');
      }

      btn.innerHTML = `<img src="${svg}" alt="">`;

      if (status === 'occupied' || status === 'reserved') {
        btn.classList.add('occupied');
        btn.disabled = true;
      } else {
        btn.addEventListener('click', () => toggleSeat(btn));
      }

      gridEl.appendChild(btn);
      printed++;
    }
  });
}


  // ==============================
  // TOGGLE SEAT (DOUBLE SAFETY)
  // ==============================
  function toggleSeat(btn) {
    if (btn.disabled || btn.classList.contains('occupied')) return;

    const key = btn.dataset.key;
    const price = Number(btn.dataset.price);

    const i = selectedSeats.findIndex(s => s.key === key);
    if (i >= 0) {
      selectedSeats.splice(i, 1);
      btn.classList.remove('selected');
    } else {
      selectedSeats.push({ key, price });
      btn.classList.add('selected');
    }


    updateTotal();
    toggleProofUpload();

  }
    function hasWheelchairSeatSelected() {
  return selectedSeats.some(s => {
    const el = document.querySelector(`.mc-seat[data-key="${s.key}"]`);
    return el && el.classList.contains('is-wheelchair');
  });
}
  function updateTotal() {
    const sum = selectedSeats.reduce((a,b)=>a + (Number(b.price) || 0), 0);
    setTotal(sum);
    confirmBtn.disabled = !selectedSeats.length;
  }
function toggleProofUpload() {
  const box = document.getElementById('mc-disability-proof');
  if (!box) return;

  const hasWheelchair = hasWheelchairSeatSelected();

  if (hasWheelchair) {
    box.style.display = 'block';

    if (!wheelchairToastShown) {
      showToast(
        '♿ <strong>Odabrano je pristupačno sjedište.</strong><br>' +
        'Ako plaćate <strong>karticom</strong>, potrebno je uploadovati dokaz o invaliditetu.',
        6500
      );
      wheelchairToastShown = true;
    }

  } else {
    box.style.display = 'none';
    wheelchairToastShown = false;

    const fileInput = document.getElementById('mc-proof-file');
    if (fileInput) fileInput.value = '';
  }
}


  function setTotal(v) {
    totalEl.textContent = `Ukupno: ${Number(v).toFixed(2)} KM`;
  }

  function resetSelection() {
    selectedSeats = [];
    updateTotal();
    document.querySelectorAll('.mc-seat.selected').forEach(b => b.classList.remove('selected'));
  }

  function hideSeatMap() {
    seatsWrap.classList.add('is-hidden');
    gridEl.innerHTML = '';
    hallNameEl.textContent = '';
    activeScreeningId = null;
  }

  function showSeatMap() {
    seatsWrap.classList.remove('is-hidden');
  }

  // ==============================
  // MODAL
  // ==============================
  confirmBtn.addEventListener('click', () => {
    if (!activeScreeningId || !selectedSeats.length) return;

    // ✅ Movie title: prvo iz film kartice, pa fallback iz selecta
    const titleFromCard = filmTitle?.textContent?.trim();
    const titleFromSelect = movieSelect?.options[movieSelect.selectedIndex]?.text;

    modalMovie.textContent = titleFromCard || titleFromSelect || '—';

    modalHall.textContent  = hallNameEl.textContent || '—';
    modalTotal.textContent = totalEl.textContent.replace('Ukupno:', '').trim();

    const activeTime = document.querySelector('.mc-time-card.active strong');
    modalDate.textContent = `${dateInput.value}, ${activeTime?.textContent || ''}`;
    modalSeats.textContent = selectedSeats.map(s => s.key).join(', ');

    modal.classList.remove('is-hidden');
  });

  // ==============================
  // MODAL ACTIONS
  // ==============================
  closeModalBtn?.addEventListener('click', () => {
    modal.classList.add('is-hidden');
  });

  overlay?.addEventListener('click', () => {
    modal.classList.add('is-hidden');
  });

  // ==============================
  // MODAL WIZARD LOGIKA (STEP 1 / STEP 2)
  // ==============================
  const payCashBtn = document.getElementById('mc-pay-cash');
  const payBtn     = document.getElementById('mc-pay-submit');

  // ==============================
  // CARD INPUT FORMAT + VALIDATION
  // ==============================
  const inName = document.getElementById('card-name');
  const inNum  = document.getElementById('card-number');
  const inExp  = document.getElementById('card-expiry');
  const inCvc  = document.getElementById('card-cvc');

  const errName = document.getElementById('err-card-name');
  const errNum  = document.getElementById('err-card-number');
  const errExp  = document.getElementById('err-card-expiry');
  const errCvc  = document.getElementById('err-card-cvc');

  function setErr(input, errEl, msg) {
    if (!input || !errEl) return;
    errEl.textContent = msg || '';
    input.classList.toggle('mc-input-invalid', !!msg);
  }

  function clearAllErr() {
    setErr(inName, errName, '');
    setErr(inNum,  errNum,  '');
    setErr(inExp,  errExp,  '');
    setErr(inCvc,  errCvc,  '');
  }

  function onlyDigits(s) {
    return (s || '').replace(/\D+/g, '');
  }

  function formatCardNumber(value) {
    const digits = onlyDigits(value).slice(0, 16);
    return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
  }

  function formatExpirySmart(value, prevValue = '') {
    const digits = onlyDigits(value).slice(0, 4);
    if (!digits) return '';

    if (digits.length === 1) {
      const d = digits[0];
      if (d === '0' || d === '1') return d;
      return `0${d}/`;
    }

    let mm = digits.slice(0, 2);
    let rest = digits.slice(2);
    if (rest.length > 0) return `${mm}/${rest}`;
    return `${mm}/`;
  }

  function setValueKeepCursor(input, newVal) {
    const start = input.selectionStart || 0;
    const before = input.value;
    input.value = newVal;

    const diff = newVal.length - before.length;
    const nextPos = Math.max(0, start + diff);
    requestAnimationFrame(() => input.setSelectionRange(nextPos, nextPos));
  }

  inNum?.addEventListener('input', () => {
    const formatted = formatCardNumber(inNum.value);
    setValueKeepCursor(inNum, formatted);
    if (onlyDigits(formatted).length >= 13) setErr(inNum, errNum, '');
  });

  let expPrev = '';
  inExp?.addEventListener('focus', () => { expPrev = inExp.value; });

  inExp?.addEventListener('input', () => {
    const formatted = formatExpirySmart(inExp.value, expPrev);
    expPrev = formatted;
    setValueKeepCursor(inExp, formatted);

    const digits = onlyDigits(formatted);
    if (digits.length >= 3) setErr(inExp, errExp, '');
  });

  inCvc?.addEventListener('input', () => {
    const digits = onlyDigits(inCvc.value).slice(0, 4);
    setValueKeepCursor(inCvc, digits);
    if (digits.length >= 3) setErr(inCvc, errCvc, '');
  });

  inName?.addEventListener('input', () => {
    if ((inName.value || '').trim().length >= 2) setErr(inName, errName, '');
  });

  function validateCardForm() {
    clearAllErr();

    const name = (inName?.value || '').trim();
    const numDigits = onlyDigits(inNum?.value || '');
    const expDigits = onlyDigits(inExp?.value || '');
    const cvcDigits = onlyDigits(inCvc?.value || '');

    let ok = true;

    if (name.length < 2) {
      setErr(inName, errName, 'Unesi ime i prezime (min 2 slova).');
      ok = false;
    }

    if (numDigits.length !== 16) {
      setErr(inNum, errNum, 'Broj kartice mora imati 16 cifara.');
      ok = false;
    }

    if (expDigits.length < 4) {
      setErr(inExp, errExp, 'Unesi datum isteka u formatu MM/YY.');
      ok = false;
    } else {
      const mm = parseInt(expDigits.slice(0,2), 10);
      const yy = parseInt(expDigits.slice(2,4), 10);

      if (!(mm >= 1 && mm <= 12)) {
        setErr(inExp, errExp, 'Mjesec mora biti od 01 do 12.');
        ok = false;
      } else {
        const now = new Date();
        const curYY = now.getFullYear() % 100;
        const curMM = now.getMonth() + 1;

        if (yy < curYY || (yy === curYY && mm < curMM)) {
          setErr(inExp, errExp, 'Kartica je istekla.');
          ok = false;
        }
      }
    }

    if (!(cvcDigits.length === 3 || cvcDigits.length === 4)) {
      setErr(inCvc, errCvc, 'CVC mora imati 3 ili 4 cifre.');
      ok = false;
    }

    return ok;
  }

  function closeModalWizard() {
    modal.classList.add('is-hidden');
    stepPayment.classList.add('is-hidden');
    stepReview.classList.remove('is-hidden');
  }

toPaymentBtn?.addEventListener('click', () => {
  const guestEmail = getGuestEmailOrBlock();
  if (guestEmail === null) return; // blokiraj prelaz

  stepReview.classList.add('is-hidden');
  stepPayment.classList.remove('is-hidden');

  // zapamti email za kasnije slanje u rezervaciji
  window.__MC_GUEST_EMAIL__ = guestEmail;

  // ♿ LOGIKA ZA DOKAZ O INVALIDITETU (OBJE VARIJANTE)
  const proofBox = document.getElementById('mc-disability-proof');

  const needsProof =
    (typeof window.payNeedsProof !== "undefined")
      ? window.payNeedsProof        // 👉 dolazi sa "Moje karte"
      : hasWheelchairSeatSelected(); // 👉 dolazi sa seat pickera

  if (proofBox) {
    proofBox.style.display = needsProof ? 'block' : 'none';
  }
});

  backBtn?.addEventListener('click', () => {
    stepPayment.classList.add('is-hidden');
    stepReview.classList.remove('is-hidden');
  });

  closeModalBtn?.addEventListener('click', closeModalWizard);
  overlay?.addEventListener('click', closeModalWizard);
function restorePayBtn(btn, text) {
  btn.disabled = false;
  btn.innerHTML = text;
}
  // 🟡 PLATI NA BLAGAJNI → PENDING
payCashBtn?.addEventListener('click', async (e) => {
  e.preventDefault();
  e.stopPropagation();

  const guestEmail = getGuestEmailOrBlock();
  if (guestEmail === null) return;

  payCashBtn.disabled = true;
  const oldText = payCashBtn.innerHTML;
  payCashBtn.innerHTML = 'Obrada...';

  let success = false;

  try {
    const resp = await fetch(mcAjax.ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({
        action: 'mc_create_reservation',
        screening_id: activeScreeningId,
        seats: selectedSeats.map(s => s.key).join(','),
        payment_type: 'cash',
        guest_email: guestEmail || ''
      })
    });

    const data = await resp.json();
    if (!data.success) {
      showToast(data.data || 'Greška pri rezervaciji', 5000);
      return;
    }

    success = true;

    showToast(
      data.data?.guest
        ? '<strong>Rezervacija uspješna!</strong><br>Karta je poslana na email. Plaćanje najkasnije pola sata prije projekcije.'
        : '<strong>Rezervacija uspješna!</strong><br>Karta se nalazi u <strong>Moje karte.</strong><br> Plaćanje najkasnije pola sata prije projekcije.',
      6000
    );

    setTimeout(() => {
      closeModalWizard();
      resetSelection();
      loadSeats(activeScreeningId);

      // 🔁 tek SAD vrati dugme
      payCashBtn.disabled = false;
      payCashBtn.innerHTML = oldText;
    }, 1200);

  } catch {
    showToast('Greška u komunikaciji sa serverom.', 5000);
  } finally {
    // ❌ ako je uspjeh – NE diraj dugme ovdje
    if (!success) {
      payCashBtn.disabled = false;
      payCashBtn.innerHTML = oldText;
    }
  }
});



  // 🟢 PLATI KARTICOM → CONFIRMED
 // 🟢 PLATI KARTICOM → CONFIRMED
payBtn?.addEventListener('click', async (e) => {
  e.preventDefault();
  e.stopPropagation();

  payBtn.disabled = true;
  const oldText = payBtn.innerHTML;
  payBtn.innerHTML = 'Obrada...';

  let success = false;

  try {
    // ♿ dokaz za wheelchair
    if (hasWheelchairSeatSelected()) {
      const fileInput = document.getElementById('mc-proof-file');
      if (!fileInput || !fileInput.files.length) {
        showToast("❗ Potrebno je uploadovati dokaz o invaliditetu.", 5000);
        restorePayBtn(payBtn, oldText);
        return;
      }
    }

    // 💳 validacija kartice
    if (!validateCardForm()) {
      restorePayBtn(payBtn, oldText);
      return;
    }

    // 📧 guest email
    const guestEmail = getGuestEmailOrBlock();
    if (guestEmail === null) {
      restorePayBtn(payBtn, oldText);
      return;
    }

    // 🔁 FETCH
    const formData = new FormData();
    formData.append('action', 'mc_create_reservation');
    formData.append('screening_id', activeScreeningId);
    formData.append('seats', selectedSeats.map(s => s.key).join(','));
    formData.append('payment_type', 'card');
    formData.append('guest_email', guestEmail || '');

    const proofInput = document.getElementById('mc-proof-file');
    if (proofInput?.files.length) {
      formData.append('disability_proof', proofInput.files[0]);
    }

    const resp = await fetch(mcAjax.ajaxurl, {
      method: 'POST',
      body: formData
    });

    const data = await resp.json();
    if (!data.success) {
      showToast(data.data || 'Greška pri plaćanju', 5000);
      restorePayBtn(payBtn, oldText);
      return;
    }

    success = true;

    showToast(
      data.data?.guest
        ? '<strong>Plaćanje uspješno!</strong><br>Karta je poslana na email sa QR kodom.'
        : '<strong>Plaćanje uspješno!</strong><br>Kartu možete vidjeti u <strong>Moje karte</strong>.',
      6000
    );

    setTimeout(() => {
      closeModalWizard();
      resetSelection();
      loadSeats(activeScreeningId);
      restorePayBtn(payBtn, oldText);
    }, 1200);

  } catch {
    showToast('Greška u komunikaciji sa serverom.', 5000);
    restorePayBtn(payBtn, oldText);
  }
});





document.addEventListener('change', (e) => {
  if (e.target.id === 'mc-proof-file') {
    const label = e.target.closest('.mc-proof-upload');
    if (e.target.files.length) {
      label.classList.add('has-file');
      label.querySelector('span').textContent = '✅ Fajl odabran: ' + e.target.files[0].name;
    }
  }
});

function showToast (message, duration = 4000) {
  const toast = document.getElementById('mc-toast');
  const text  = document.getElementById('mc-toast-text');

  if (!toast || !text) return;

  text.innerHTML = message;

  toast.classList.remove('is-hidden');
  requestAnimationFrame(() => toast.classList.add('show'));

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.classList.add('is-hidden'), 300);
  }, duration);
};

});

