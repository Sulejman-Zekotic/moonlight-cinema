document.addEventListener('DOMContentLoaded', () => {
  const config = window.mcGuestPayment;

  if (!config) {
    return;
  }

  const payBtn = document.getElementById('mc-guest-pay-submit');
  const proofInput = document.getElementById('mc-proof-file');
  const cardName = document.getElementById('card-name');
  const cardNumber = document.getElementById('card-number');
  const cardExpiry = document.getElementById('card-expiry');
  const cardCvc = document.getElementById('card-cvc');
  const errName = document.getElementById('err-card-name');
  const errNumber = document.getElementById('err-card-number');
  const errExpiry = document.getElementById('err-card-expiry');
  const errCvc = document.getElementById('err-card-cvc');

  function showToast(message, duration = 4200) {
    const existing = document.getElementById('mc-toast');
    const text = document.getElementById('mc-toast-text');

    if (existing && text) {
      text.textContent = message;
      existing.classList.remove('is-hidden');
      requestAnimationFrame(() => existing.classList.add('show'));
      setTimeout(() => {
        existing.classList.remove('show');
        setTimeout(() => existing.classList.add('is-hidden'), 250);
      }, duration);
      return;
    }

    alert(message);
  }

  function onlyDigits(value) {
    return (value || '').replace(/\D+/g, '');
  }

  function setErr(input, errEl, message) {
    if (errEl) {
      errEl.textContent = message || '';
    }

    input?.classList.toggle('mc-input-invalid', Boolean(message));
  }

  function clearErrors() {
    setErr(cardName, errName, '');
    setErr(cardNumber, errNumber, '');
    setErr(cardExpiry, errExpiry, '');
    setErr(cardCvc, errCvc, '');
  }

  function keepCursor(input, value) {
    const position = input.selectionStart || 0;
    const previousLength = input.value.length;
    input.value = value;
    const nextPosition = Math.max(0, position + (value.length - previousLength));
    requestAnimationFrame(() => input.setSelectionRange(nextPosition, nextPosition));
  }

  function formatCardNumber(value) {
    return onlyDigits(value).slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ').trim();
  }

  function formatExpiry(value) {
    const digits = onlyDigits(value).slice(0, 4);
    if (!digits) return '';
    if (digits.length <= 2) return digits;
    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }

  function validateCardForm() {
    clearErrors();

    const name = (cardName?.value || '').trim();
    const number = onlyDigits(cardNumber?.value || '');
    const expiry = onlyDigits(cardExpiry?.value || '');
    const cvc = onlyDigits(cardCvc?.value || '');
    let valid = true;

    if (name.length < 2) {
      setErr(cardName, errName, 'Unesite ime sa kartice.');
      valid = false;
    }

    if (number.length !== 16) {
      setErr(cardNumber, errNumber, 'Broj kartice mora imati 16 cifara.');
      valid = false;
    }

    if (expiry.length !== 4) {
      setErr(cardExpiry, errExpiry, 'Unesite datum isteka u formatu MM/YY.');
      valid = false;
    } else {
      const month = Number(expiry.slice(0, 2));
      const year = Number(expiry.slice(2, 4));
      const now = new Date();
      const currentYear = now.getFullYear() % 100;
      const currentMonth = now.getMonth() + 1;

      if (month < 1 || month > 12) {
        setErr(cardExpiry, errExpiry, 'Mjesec mora biti između 01 i 12.');
        valid = false;
      } else if (year < currentYear || (year === currentYear && month < currentMonth)) {
        setErr(cardExpiry, errExpiry, 'Kartica je istekla.');
        valid = false;
      }
    }

    if (!(cvc.length === 3 || cvc.length === 4)) {
      setErr(cardCvc, errCvc, 'CVC mora imati 3 ili 4 cifre.');
      valid = false;
    }

    return valid;
  }

  cardNumber?.addEventListener('input', () => keepCursor(cardNumber, formatCardNumber(cardNumber.value)));
  cardExpiry?.addEventListener('input', () => keepCursor(cardExpiry, formatExpiry(cardExpiry.value)));
  cardCvc?.addEventListener('input', () => keepCursor(cardCvc, onlyDigits(cardCvc.value).slice(0, 4)));

  document.addEventListener('change', (event) => {
    if (event.target?.id !== 'mc-proof-file') {
      return;
    }

    const label = event.target.closest('.mc-proof-upload');
    const span = label?.querySelector('span');

    if (span) {
      span.textContent = event.target.files?.[0]
        ? `✅ Fajl odabran: ${event.target.files[0].name}`
        : 'Kliknite ovdje za upload slike ili PDF-a';
    }
  });

  payBtn?.addEventListener('click', async () => {
    if (!validateCardForm()) {
      return;
    }

    if (config.needsProof && (!proofInput || !proofInput.files.length)) {
      showToast('Potrebno je uploadovati dokaz o invaliditetu.');
      return;
    }

    const original = payBtn.textContent;
    payBtn.disabled = true;
    payBtn.textContent = 'Obrada...';

    const formData = new FormData();
    formData.append('action', 'mc_pay_guest_reservation');
    formData.append('reservation_id', config.reservationId);
    formData.append('token', config.token);

    if (proofInput?.files?.[0]) {
      formData.append('disability_proof', proofInput.files[0]);
    }

    try {
      const response = await fetch(window.mcAjax.ajaxurl, {
        method: 'POST',
        body: formData,
      }).then((res) => res.json());

      if (!response.success) {
        showToast(response.data || 'Plaćanje nije uspjelo.');
        return;
      }

      showToast('Plaćanje je uspješno završeno.');
      setTimeout(() => window.location.href = window.location.href.replace('mode=pay', 'mode=cancel'), 800);
    } catch {
      showToast('Greška u komunikaciji sa serverom.');
    } finally {
      payBtn.disabled = false;
      payBtn.textContent = original;
    }
  });
});
