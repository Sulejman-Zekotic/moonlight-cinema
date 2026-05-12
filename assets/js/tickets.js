document.addEventListener('DOMContentLoaded', () => {
  let cancelReservationId = null;
  let cancelWasPaid = false;
  let payReservationId = null;
  let payNeedsProof = false;

  const checkoutModal = document.getElementById('mc-reservation-modal');
  const reviewStep = document.getElementById('mc-step-review');
  const paymentStep = document.getElementById('mc-step-payment');
  const backBtn = document.getElementById('mc-back-to-review');
  const closeCheckoutBtn = document.getElementById('mc-close-modal');
  const checkoutOverlay = checkoutModal?.querySelector('.mc-modal__overlay');
  const paySubmitBtn = document.getElementById('mc-pay-submit');
  const proofBox = document.getElementById('mc-disability-proof');
  const proofInput = document.getElementById('mc-proof-file');

  const qrModal = document.getElementById('mc-ticket-modal');
  const qrImage = document.getElementById('mc-random-qr');
  const qrClose = document.getElementById('mc-close-ticket');

  const cancelModal = document.getElementById('mc-cancel-modal');
  const cancelTitle = document.getElementById('mc-cancel-title');
  const cancelText = document.getElementById('mc-cancel-text');
  const cancelNo = document.getElementById('mc-cancel-no');
  const cancelYes = document.getElementById('mc-cancel-yes');

  const cardName = document.getElementById('card-name');
  const cardNumber = document.getElementById('card-number');
  const cardExpiry = document.getElementById('card-expiry');
  const cardCvc = document.getElementById('card-cvc');
  const errName = document.getElementById('err-card-name');
  const errNumber = document.getElementById('err-card-number');
  const errExpiry = document.getElementById('err-card-expiry');
  const errCvc = document.getElementById('err-card-cvc');

  function showToast(message, duration = 4200) {
    const toast = document.getElementById('mc-toast');
    const text = document.getElementById('mc-toast-text');

    if (!toast || !text) {
      return;
    }

    text.textContent = message;
    toast.classList.remove('is-hidden');
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.classList.add('is-hidden'), 250);
    }, duration);
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

  function onlyDigits(value) {
    return (value || '').replace(/\D+/g, '');
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

  function keepCursor(input, value) {
    const position = input.selectionStart || 0;
    const previousLength = input.value.length;
    input.value = value;
    const nextPosition = Math.max(0, position + (value.length - previousLength));
    requestAnimationFrame(() => input.setSelectionRange(nextPosition, nextPosition));
  }

  cardNumber?.addEventListener('input', () => keepCursor(cardNumber, formatCardNumber(cardNumber.value)));
  cardExpiry?.addEventListener('input', () => keepCursor(cardExpiry, formatExpiry(cardExpiry.value)));
  cardCvc?.addEventListener('input', () => keepCursor(cardCvc, onlyDigits(cardCvc.value).slice(0, 4)));

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

  function openCheckoutFromTicket(reservationId, needsProof) {
    if (!checkoutModal || !reviewStep || !paymentStep) {
      return;
    }

    payReservationId = reservationId;
    payNeedsProof = needsProof;

    checkoutModal.classList.remove('is-hidden');
    reviewStep.classList.add('is-hidden');
    paymentStep.classList.remove('is-hidden');
    document.body.classList.add('modal-open');

    if (backBtn) {
      backBtn.style.display = 'none';
    }

    if (proofBox) {
      proofBox.style.display = payNeedsProof ? 'block' : 'none';
    }

    if (proofInput) {
      proofInput.value = '';
    }
  }

  function closeCheckoutModal() {
    checkoutModal?.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
    payReservationId = null;
    payNeedsProof = false;
    clearErrors();

    if (reviewStep && paymentStep) {
      paymentStep.classList.add('is-hidden');
      reviewStep.classList.remove('is-hidden');
    }

    if (backBtn) {
      backBtn.style.display = '';
    }
  }

  function openQrModal(code) {
    if (!qrModal || !qrImage) {
      return;
    }

    qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(code)}`;
    qrModal.style.display = 'flex';
    document.body.classList.add('modal-open');
  }

  function closeQrModal() {
    if (!qrModal) {
      return;
    }

    qrModal.style.display = 'none';
    document.body.classList.remove('modal-open');
  }

  function openCancelModal(id, paid) {
    if (!cancelModal || !cancelTitle || !cancelText) {
      return;
    }

    cancelReservationId = id;
    cancelWasPaid = paid;

    if (paid) {
      cancelTitle.textContent = 'Otkaži kupljenu kartu?';
      cancelText.innerHTML = `
        <div style="background:rgba(40,167,69,0.12);border:1px solid rgba(40,167,69,0.38);border-radius:14px;padding:14px 16px;">
          <strong style="color:#7be7a7;">Karta je već plaćena.</strong><br><br>
          Otkazivanjem karte povrat novca bit će izvršen u roku od <strong>3 do 5 radnih dana</strong>
          na karticu kojom je izvršeno plaćanje.
        </div>
      `;
    } else {
      cancelTitle.textContent = 'Otkaži rezervaciju?';
      cancelText.textContent = 'Da li ste sigurni da želite otkazati ovu rezervaciju?';
    }

    cancelModal.style.display = 'flex';
    document.body.classList.add('modal-open');
  }

  function closeCancelModal() {
    cancelModal.style.display = 'none';
    document.body.classList.remove('modal-open');
    cancelReservationId = null;
    cancelWasPaid = false;
  }

  document.addEventListener('click', (event) => {
    const openQrBtn = event.target.closest('.mc-open-ticket');
    if (openQrBtn) {
      openQrModal(openQrBtn.dataset.code || `MC-${Math.random().toString(36).slice(2, 12)}`);
      return;
    }

    const payNowBtn = event.target.closest('.mc-pay-now');
    if (payNowBtn) {
      openCheckoutFromTicket(payNowBtn.dataset.reservation, payNowBtn.dataset.wheelchair === '1');
      return;
    }

    const cancelBtn = event.target.closest('.mc-cancel-ticket');
    if (cancelBtn) {
      openCancelModal(cancelBtn.dataset.id || '', cancelBtn.dataset.paid === '1');
    }
  });

  qrClose?.addEventListener('click', closeQrModal);
  qrModal?.addEventListener('click', (event) => {
    if (event.target === qrModal) {
      closeQrModal();
    }
  });

  closeCheckoutBtn?.addEventListener('click', closeCheckoutModal);
  checkoutOverlay?.addEventListener('click', closeCheckoutModal);

  cancelNo?.addEventListener('click', closeCancelModal);
  cancelModal?.addEventListener('click', (event) => {
    if (event.target === cancelModal) {
      closeCancelModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeQrModal();
      closeCancelModal();
      closeCheckoutModal();
    }
  });

  cancelYes?.addEventListener('click', async () => {
    if (!cancelReservationId) {
      return;
    }

    const original = cancelYes.textContent;
    cancelYes.disabled = true;
    cancelYes.textContent = 'Obrada...';

    try {
      const response = await fetch(mcAjax.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_cancel_reservation',
          id: cancelReservationId,
        }),
      }).then((res) => res.json());

      if (!response.success) {
        showToast(response.data || 'Greška pri otkazivanju.');
        return;
      }

      showToast(cancelWasPaid ? 'Karta je uspješno otkazana.' : 'Rezervacija je uspješno otkazana.');
      closeCancelModal();
      setTimeout(() => window.location.reload(), 700);
    } catch {
      showToast('Greška mreže. Pokušajte ponovo.');
    } finally {
      cancelYes.disabled = false;
      cancelYes.textContent = original;
    }
  });

  paySubmitBtn?.addEventListener('click', async () => {
    if (!payReservationId) {
      return;
    }

    if (!validateCardForm()) {
      return;
    }

    if (payNeedsProof && (!proofInput || !proofInput.files.length)) {
      showToast('Potrebno je uploadovati dokaz o invaliditetu.');
      return;
    }

    const original = paySubmitBtn.textContent;
    paySubmitBtn.disabled = true;
    paySubmitBtn.textContent = 'Obrada...';

    const formData = new FormData();
    formData.append('action', 'mc_pay_existing_reservation');
    formData.append('reservation_id', payReservationId);

    if (payNeedsProof && proofInput?.files[0]) {
      formData.append('disability_proof', proofInput.files[0]);
    }

    try {
      const response = await fetch(mcAjax.ajaxurl, {
        method: 'POST',
        body: formData,
      }).then((res) => res.json());

      if (!response.success) {
        showToast(response.data || 'Plaćanje nije uspjelo.');
        return;
      }

      showToast('Plaćanje je uspješno završeno.');
      closeCheckoutModal();
      setTimeout(() => window.location.reload(), 750);
    } catch {
      showToast('Greška mreže. Pokušajte ponovo.');
    } finally {
      paySubmitBtn.disabled = false;
      paySubmitBtn.textContent = original;
    }
  });
});
