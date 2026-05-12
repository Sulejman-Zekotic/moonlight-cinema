document.addEventListener('DOMContentLoaded', () => {
  const movieSelect = document.getElementById('mc-add-movie');
  const hallSelect = document.getElementById('mc-add-hall');
  const dateInput = document.getElementById('mc-add-date');
  const timeSelect = document.getElementById('mc-add-time');

  if (!movieSelect || !hallSelect || !dateInput || !timeSelect) {
    return;
  }

  function setPlaceholder(message, disabled = true) {
    timeSelect.innerHTML = `<option value="">${message}</option>`;
    timeSelect.disabled = disabled;
  }

  async function loadMovies() {
    const data = new FormData();
    data.append('action', 'mc_get_movies_admin');

    try {
      const response = await fetch(mcAdmin.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      }).then((res) => res.json());

      if (!response.success) {
        return;
      }

      movieSelect.innerHTML = '<option value="">Izaberite film</option>';
      response.data.forEach((movie) => {
        const option = document.createElement('option');
        option.value = movie.id;
        option.textContent = movie.genres ? `${movie.title} — ${movie.genres}` : movie.title;
        movieSelect.appendChild(option);
      });
    } catch {
      setPlaceholder('Greška pri učitavanju filmova');
    }
  }

  async function loadAvailableTimes() {
    const movieId = movieSelect.value;
    const hallId = hallSelect.value;
    const date = dateInput.value;

    if (!movieId || !hallId || !date) {
      setPlaceholder('Odaberite film, salu i datum');
      return;
    }

    setPlaceholder('Učitavanje termina...');

    const data = new FormData();
    data.append('action', 'mc_get_available_times_admin');
    data.append('movie_id', movieId);
    data.append('hall_id', hallId);
    data.append('date', date);

    try {
      const response = await fetch(mcAdmin.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      }).then((res) => res.json());

      if (!response.success || !Array.isArray(response.data) || response.data.length === 0) {
        setPlaceholder('Nema slobodnih termina');
        return;
      }

      timeSelect.innerHTML = '<option value="">Izaberite vrijeme</option>';
      response.data.forEach((time) => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        timeSelect.appendChild(option);
      });

      timeSelect.disabled = false;
    } catch {
      setPlaceholder('Greška pri učitavanju termina');
    }
  }

  loadMovies();
  [movieSelect, hallSelect, dateInput].forEach((element) => {
    element.addEventListener('change', loadAvailableTimes);
  });
});
