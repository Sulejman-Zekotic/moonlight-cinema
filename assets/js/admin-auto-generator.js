document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('mc-auto-form');
  const result = document.getElementById('mc-auto-result');
  const submitBtn = document.getElementById('mc-generate-btn');
  const movieChecks = Array.from(document.querySelectorAll('input[name="movies[]"]'));
  const MIN_MOVIES = 2;

  if (!form || !submitBtn) {
    return;
  }

  const originalButtonHtml = submitBtn.innerHTML;

  function getCheckedMovies() {
    return movieChecks.filter((checkbox) => checkbox.checked);
  }

  function syncMovieCards() {
    movieChecks.forEach((checkbox) => {
      checkbox.closest('.mc-movie-checkbox')?.classList.toggle('is-selected', checkbox.checked);
    });
  }

  function showToast(message, duration = 3600) {
    const toast = document.getElementById('mc-toast');
    const toastText = document.getElementById('mc-toast-text');

    if (!toast || !toastText) {
      return;
    }

    toastText.textContent = message;
    toast.classList.remove('is-hidden');
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.classList.add('is-hidden'), 220);
    }, duration);
  }

  function setResult(message = '', isError = false) {
    if (!result) {
      return;
    }

    result.textContent = message;
    result.classList.toggle('is-error', isError);
  }

  function updateDateLimits() {
    const checkedCount = Math.max(getCheckedMovies().length, MIN_MOVIES);
    const fromInput = form.querySelector('input[name="date_from"]');
    const toInput = form.querySelector('input[name="date_to"]');

    if (!fromInput || !toInput) {
      return;
    }

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    const maxWindow = new Date(tomorrow);
    maxWindow.setDate(tomorrow.getDate() + checkedCount);

    const toIso = (date) => date.toISOString().split('T')[0];

    if (!fromInput.value || fromInput.value < toIso(tomorrow)) {
      fromInput.value = toIso(tomorrow);
    }

    fromInput.min = toIso(tomorrow);
    toInput.min = fromInput.value;
    toInput.max = toIso(maxWindow);

    if (!toInput.value || toInput.value < fromInput.value || toInput.value > toInput.max) {
      toInput.value = toInput.max;
    }
  }

  movieChecks.forEach((checkbox) => {
    checkbox.addEventListener('change', (event) => {
      if (!event.currentTarget.checked && getCheckedMovies().length < MIN_MOVIES) {
        event.currentTarget.checked = true;
        showToast('Moraju biti odabrana najmanje 2 filma.');
      }

      syncMovieCards();
      updateDateLimits();
    });
  });

  form.querySelector('input[name="date_from"]')?.addEventListener('change', updateDateLimits);
  syncMovieCards();
  updateDateLimits();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const checkedMovies = getCheckedMovies();
    if (checkedMovies.length < MIN_MOVIES) {
      showToast('Odaberite barem 2 filma.');
      return;
    }

    const data = new FormData(form);
    checkedMovies.forEach((checkbox) => data.append('movies[]', checkbox.value));
    data.append('action', 'mc_generate_schedule');
    data.append('nonce', mcAuto.nonce);

    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');
    submitBtn.innerHTML = '<span class="spinner"></span>';
    setResult('');

    try {
      const response = await fetch(mcAuto.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      });

      const payload = await response.json();
      if (!payload.success) {
        const message = typeof payload.data === 'string' ? payload.data : 'Generisanje nije uspjelo.';
        setResult(message, true);
        showToast(message);
        return;
      }

      const count = Number(payload.data?.count || 0);
      setResult(`Uspješno generisano ${count} projekcija.`, false);
    } catch {
      const message = 'Greška u komunikaciji sa serverom.';
      setResult(message, true);
      showToast(message);
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove('is-loading');
      submitBtn.innerHTML = originalButtonHtml;
    }
  });
});
