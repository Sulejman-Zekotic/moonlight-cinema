document.addEventListener('DOMContentLoaded', () => {
  const panels = {
    login: document.getElementById('mc-loginBox'),
    register: document.getElementById('mc-registerBox'),
    forgot: document.getElementById('mc-forgotBox'),
  };

  const loginForm = document.getElementById('mc-loginForm');
  const registerForm = document.getElementById('mc-registerForm');
  const forgotForm = document.getElementById('mc-forgotForm');
  const resetForm = document.getElementById('mc-resetForm');

  const loginError = document.getElementById('mc-loginError');
  const registerError = document.getElementById('mc-registerError');
  const forgotError = document.getElementById('mc-forgotError');
  const resetError = document.getElementById('mc-resetError');

  const loginEmail = document.getElementById('mc-loginEmail');
  const loginPassword = document.getElementById('mc-loginPassword');
  const regName = document.getElementById('mc-regName');
  const regEmail = document.getElementById('mc-regEmail');
  const regPassword = document.getElementById('mc-regPassword');
  const forgotEmail = document.getElementById('mc-forgotEmail');
  const resetToken = document.getElementById('mc-resetToken');
  const resetPassword = document.getElementById('mc-resetPassword');
  const resetPasswordConfirm = document.getElementById('mc-resetPasswordConfirm');

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function showPanel(name) {
    Object.entries(panels).forEach(([key, element]) => {
      if (element) {
        element.classList.toggle('mc-hidden', key !== name);
      }
    });
  }

  function setError(box, message) {
    if (!box) {
      return;
    }

    box.style.color = '#ff9c93';
    box.textContent = message;
  }

  function setSuccess(box, message) {
    if (!box) {
      return;
    }

    box.style.color = '#6ce8ab';
    box.textContent = message;
  }

  function clearState(form, box) {
    form?.querySelectorAll('.mc-field').forEach((field) => {
      field.style.borderColor = 'rgba(78, 168, 222, 0.22)';
    });

    if (box) {
      box.textContent = '';
    }
  }

  function flagField(input, isValid) {
    const field = input?.closest('.mc-field');
    if (!field) {
      return;
    }

    field.style.borderColor = isValid ? '#64dca0' : '#ff9c93';
  }

  function validateEmail(input, box) {
    const value = (input?.value || '').trim();

    if (!value) {
      flagField(input, false);
      setError(box, 'Email je obavezan.');
      return false;
    }

    if (!emailRegex.test(value)) {
      flagField(input, false);
      setError(box, 'Unesite ispravan email.');
      return false;
    }

    flagField(input, true);
    return true;
  }

  function validateName() {
    const value = (regName?.value || '').trim();

    if (!value) {
      flagField(regName, false);
      setError(registerError, 'Ime je obavezno.');
      return false;
    }

    if (value.length < 2) {
      flagField(regName, false);
      setError(registerError, 'Ime mora imati najmanje 2 slova.');
      return false;
    }

    flagField(regName, true);
    return true;
  }

  function validatePassword(input, box) {
    const value = input?.value || '';
    const rules = [];

    if (value.length < 8) rules.push('8 karaktera');
    if (!/[a-z]/.test(value)) rules.push('malo slovo');
    if (!/[A-Z]/.test(value)) rules.push('veliko slovo');
    if (!/[0-9]/.test(value)) rules.push('broj');
    if (!/[!@#$%^&*]/.test(value)) rules.push('specijalni znak');
    if (/\s/.test(value)) rules.push('bez razmaka');

    if (rules.length) {
      flagField(input, false);
      setError(box, `Lozinka mora imati: ${rules.join(', ')}.`);
      return false;
    }

    flagField(input, true);
    return true;
  }

  document.querySelectorAll('[data-auth-target]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      const target = trigger.getAttribute('data-auth-target');

      if (!target || !panels[target]) {
        return;
      }

      showPanel(target);
      clearState(loginForm, loginError);
      clearState(registerForm, registerError);
      clearState(forgotForm, forgotError);
    });
  });

  regName?.addEventListener('input', validateName);
  regEmail?.addEventListener('input', () => validateEmail(regEmail, registerError));
  regPassword?.addEventListener('input', () => validatePassword(regPassword, registerError));
  forgotEmail?.addEventListener('input', () => validateEmail(forgotEmail, forgotError));

  loginForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearState(loginForm, loginError);

    const emailOk = validateEmail(loginEmail, loginError);
    const passwordOk = validatePassword(loginPassword, loginError);
    if (!emailOk || !passwordOk) {
      return;
    }

    setSuccess(loginError, 'Prijava u toku...');

    try {
      const response = await fetch(mcAuth.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_app_login',
          email: loginEmail.value.trim(),
          password: loginPassword.value,
        }),
      }).then((res) => res.json());

      if (!response.success) {
        setError(loginError, response.data || 'Greška pri prijavi.');
        return;
      }

      setSuccess(loginError, 'Prijava uspješna.');
      setTimeout(() => {
        window.location.href = `${window.MC_BASE_URL}/`;
      }, 650);
    } catch {
      setError(loginError, 'Greška mreže. Pokušajte ponovo.');
    }
  });

  registerForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearState(registerForm, registerError);

    const nameOk = validateName();
    const emailOk = validateEmail(regEmail, registerError);
    const passwordOk = validatePassword(regPassword, registerError);

    if (!nameOk || !emailOk || !passwordOk) {
      return;
    }

    setSuccess(registerError, 'Registracija u toku...');

    try {
      const response = await fetch(mcAuth.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_app_register',
          name: regName.value.trim(),
          email: regEmail.value.trim(),
          password: regPassword.value,
        }),
      }).then((res) => res.json());

      if (!response.success) {
        setError(registerError, response.data || 'Greška pri registraciji.');
        return;
      }

      setSuccess(registerError, 'Registracija uspješna. Sada se prijavite.');
      setTimeout(() => {
        showPanel('login');
        clearState(registerForm, registerError);
      }, 750);
    } catch {
      setError(registerError, 'Greška mreže. Pokušajte ponovo.');
    }
  });

  forgotForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearState(forgotForm, forgotError);

    if (!validateEmail(forgotEmail, forgotError)) {
      return;
    }

    setSuccess(forgotError, 'Slanje linka u toku...');

    try {
      const response = await fetch(mcAuth.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_request_password_reset',
          email: forgotEmail.value.trim(),
        }),
      }).then((res) => res.json());

      if (!response.success) {
        setError(forgotError, response.data || 'Link nije poslan.');
        return;
      }

      setSuccess(forgotError, response.data?.message || 'Reset link je poslan.');
    } catch {
      setError(forgotError, 'Greška mreže. Pokušajte ponovo.');
    }
  });

  resetForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearState(resetForm, resetError);

    const passwordOk = validatePassword(resetPassword, resetError);
    if (!passwordOk) {
      return;
    }

    if ((resetPasswordConfirm?.value || '') !== (resetPassword?.value || '')) {
      flagField(resetPasswordConfirm, false);
      setError(resetError, 'Lozinke se ne podudaraju.');
      return;
    }

    flagField(resetPasswordConfirm, true);
    setSuccess(resetError, 'Spremanje nove lozinke...');

    try {
      const response = await fetch(mcAuth.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'mc_reset_password',
          token: resetToken?.value || '',
          password: resetPassword.value,
        }),
      }).then((res) => res.json());

      if (!response.success) {
        setError(resetError, response.data || 'Lozinka nije promijenjena.');
        return;
      }

      setSuccess(resetError, response.data?.message || 'Lozinka je promijenjena.');
      setTimeout(() => {
        window.location.href = `${window.MC_BASE_URL}/prijava`;
      }, 1100);
    } catch {
      setError(resetError, 'Greška mreže. Pokušajte ponovo.');
    }
  });

  document.querySelectorAll('.mc-toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target || '');
      const img = button.querySelector('img');

      if (!input || !img) {
        return;
      }

      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.setAttribute('aria-label', visible ? 'Prikaži lozinku' : 'Sakrij lozinku');
      img.src = mcAsset(visible ? '2026/01/eye-closed.png' : '2026/01/eye.png');
    });
  });
});
