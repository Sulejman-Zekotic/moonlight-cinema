document.addEventListener('DOMContentLoaded', () => {
  const filterOpen = document.querySelector('[data-screening-filter-open]');
  const filterClose = document.querySelector('[data-screening-filter-close]');
  const filterOverlay = document.querySelector('[data-screening-filter-overlay]');
  const filterPanel = document.querySelector('[data-screening-filter-panel]');
  const searchInput = document.querySelector('[data-screening-search]');
  const searchMirror = document.querySelector('[data-screening-search-mirror]');
  const autoSubmitFields = Array.from(document.querySelectorAll('[data-screening-auto-submit]'));
  const mobileQuery = window.matchMedia('(max-width: 760px)');

  const modal = document.querySelector('[data-screening-modal]');
  const modalCloseNodes = document.querySelectorAll('[data-screening-modal-close]');
  const modalTriggers = document.querySelectorAll('[data-screening-modal-trigger]');

  let debounceTimer = null;

  function submitFilters() {
    if (!filterPanel) {
      return;
    }

    if (searchMirror && searchInput) {
      searchMirror.value = searchInput.value.trim();
    }

    const pageField = filterPanel.querySelector('input[name="page"]');
    if (pageField) {
      pageField.value = '1';
    }

    filterPanel.submit();
  }

  function openFilters() {
    filterPanel?.classList.add('is-open');
    filterOverlay?.classList.add('is-open');
    document.body.classList.add('mc-admin-screenings-sheet-open');
  }

  function closeFilters() {
    filterPanel?.classList.remove('is-open');
    filterOverlay?.classList.remove('is-open');
    document.body.classList.remove('mc-admin-screenings-sheet-open');
  }

  function openModalFromTrigger(trigger) {
    if (!modal || !trigger) {
      return;
    }

    modal.querySelector('[data-screening-movie]').textContent = trigger.dataset.screeningMovie || '';
    modal.querySelector('[data-screening-hall]').textContent = trigger.dataset.screeningHall || '';
    modal.querySelector('[data-screening-date]').textContent = trigger.dataset.screeningDate || '';
    modal.querySelector('[data-screening-time]').textContent = trigger.dataset.screeningTime || '';
    modal.querySelector('[data-screening-price]').textContent = trigger.dataset.screeningPrice || '';
    modal.querySelector('[data-screening-status]').textContent = trigger.dataset.screeningStatus || '';

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
  }

  function closeModal() {
    modal?.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  searchInput?.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(submitFilters, 240);
  });

  autoSubmitFields.forEach((field) => {
    field.addEventListener('change', submitFilters);
  });

  filterOpen?.addEventListener('click', openFilters);
  filterClose?.addEventListener('click', closeFilters);
  filterOverlay?.addEventListener('click', closeFilters);

  modalTriggers.forEach((trigger) => {
    trigger.addEventListener('click', () => openModalFromTrigger(trigger));
  });

  modalCloseNodes.forEach((node) => {
    node.addEventListener('click', closeModal);
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeFilters();
      closeModal();
    }
  });

  window.addEventListener('resize', () => {
    if (!mobileQuery.matches) {
      closeFilters();
    }
  });
});
