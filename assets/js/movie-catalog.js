document.addEventListener('DOMContentLoaded', () => {
  const resultsWrap = document.getElementById('mc-movie-results');
  const genreSelect = document.getElementById('mc-filter-genre');
  const dateSelect = document.getElementById('mc-filter-date');
  const durationSelect = document.getElementById('mc-filter-duration');
  const hallSelect = document.getElementById('mc-filter-hall');
  const searchInput = document.getElementById('mc-filter-search');
  const resetBtn = document.getElementById('mc-filter-reset');
  const openBtn = document.getElementById('mc-filter-open');
  const closeBtn = document.getElementById('mc-filter-close');
  const filterPanel = document.getElementById('mc-filter-panel');
  const filterOverlay = document.getElementById('mc-filter-overlay');

  if (!resultsWrap) {
    return;
  }

  const mobileQuery = window.matchMedia('(max-width: 760px)');
  let debounceTimer = null;

  function getFilters() {
    return {
      genre: genreSelect?.value || '',
      date: dateSelect?.value || '',
      duration: durationSelect?.value || '',
      hall: hallSelect?.value || '',
      search: searchInput?.value.trim() || '',
    };
  }

  function openFilters() {
    filterPanel?.classList.add('is-open');
    filterOverlay?.classList.add('is-open');
    document.body.classList.add('mc-filter-sheet-open');
  }

  function closeFilters() {
    filterPanel?.classList.remove('is-open');
    filterOverlay?.classList.remove('is-open');
    document.body.classList.remove('mc-filter-sheet-open');
  }

  function renderMessage(color, text) {
    resultsWrap.innerHTML = `
      <div style="width:100%;padding:24px 0;text-align:center;color:${color};">
        ${text}
      </div>
    `;
  }

  async function applyFilters() {
    const filters = getFilters();

    resultsWrap.innerHTML = `
      <div class="mc-film-loading" style="width:100%;">
        Učitavanje filmova...
      </div>
    `;

    try {
      const response = await fetch(mcMovieFilter.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_filter_movies_html',
          genre: filters.genre,
          date: filters.date,
          duration: filters.duration,
          hall: filters.hall,
          search: filters.search,
        }),
      }).then((res) => res.json());

      if (!response?.success) {
        renderMessage('#ff8f86', 'Greška pri učitavanju filmova.');
        return;
      }

      if (!response.data?.html || !response.data.html.trim()) {
        renderMessage('#aab9c3', 'Nema filmova za odabrane filtere.');
        return;
      }

      resultsWrap.innerHTML = response.data.html;
      window.mcNormalizeText?.(resultsWrap);

      if (mobileQuery.matches) {
        closeFilters();
      }
    } catch {
      renderMessage('#ff8f86', 'Greška mreže. Pokušajte ponovo.');
    }
  }

  function applyFiltersDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 220);
  }

  [genreSelect, dateSelect, durationSelect, hallSelect].forEach((field) => {
    field?.addEventListener('change', applyFilters);
  });

  searchInput?.addEventListener('input', applyFiltersDebounced);

  resetBtn?.addEventListener('click', () => {
    if (genreSelect) genreSelect.value = '';
    if (dateSelect) dateSelect.value = '';
    if (durationSelect) durationSelect.value = '';
    if (hallSelect) hallSelect.value = '';
    if (searchInput) searchInput.value = '';
    clearTimeout(debounceTimer);
    applyFilters();
  });

  openBtn?.addEventListener('click', openFilters);
  closeBtn?.addEventListener('click', closeFilters);
  filterOverlay?.addEventListener('click', closeFilters);

  window.addEventListener('resize', () => {
    if (!mobileQuery.matches) {
      closeFilters();
    }
  });

  applyFilters();
});
