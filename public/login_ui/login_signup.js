/* ================================================================
   DEORIS — Login / Sign-Up JS
   ================================================================ */
(function () {
  'use strict';

  const tabButtons         = document.querySelectorAll('.tab-btn');
  const loginFormElement   = document.getElementById('loginForm');
  const signupFormElement  = document.getElementById('signupForm');
  const authSwitchText     = document.getElementById('switchText');
  const authPage           = document.querySelector('.auth-page');

  const AUTH_MODE = { LOGIN: 'login', SIGNUP: 'signup' };

  const SWITCH_TEXT = {
    login:  { label: "Don't have an account?", action: 'Sign Up',  target: 'signup' },
    signup: { label: 'Already have an account?', action: 'Log In', target: 'login'  },
  };

  /* ── Tab / form switching ──────────────────────────────────── */
  function setAuthMode(mode) {
    if (!AUTH_MODE[mode.toUpperCase()]) return;

    const isLogin = mode === AUTH_MODE.LOGIN;

    tabButtons.forEach(btn =>
      btn.classList.toggle('is-active', btn.dataset.tab === mode)
    );

    loginFormElement.classList.toggle('is-visible', isLogin);
    signupFormElement.classList.toggle('is-visible', !isLogin);

    const sw = SWITCH_TEXT[mode];
    if (authSwitchText && sw) {
      authSwitchText.innerHTML =
        `${sw.label} <button type="button" class="switch-btn" data-switch="${sw.target}">${sw.action}</button>`;
    }
  }

  tabButtons.forEach(btn =>
    btn.addEventListener('click', () => setAuthMode(btn.dataset.tab))
  );

  authSwitchText && authSwitchText.addEventListener('click', e => {
    const btn = e.target.closest('.switch-btn');
    if (btn) setAuthMode(btn.dataset.switch);
  });

  /* ── Password confirmation check ──────────────────────────── */
  signupFormElement && signupFormElement.addEventListener('submit', e => {
    const pw  = document.getElementById('signupPassword')?.value;
    const cpw = document.getElementById('signupConfirm')?.value;
    if (pw && cpw && pw !== cpw) {
      e.preventDefault();
      // Show inline error instead of alert
      let err = signupFormElement.querySelector('.pw-mismatch-error');
      if (!err) {
        err = document.createElement('p');
        err.className = 'field-help pw-mismatch-error';
        err.style.color = '#DC2626';
        err.style.marginTop = '-.5rem';
        err.style.marginBottom = '.75rem';
        const confirmField = document.getElementById('signupConfirm')?.closest('.field-group');
        confirmField ? confirmField.appendChild(err) : signupFormElement.prepend(err);
      }
      err.textContent = 'Passwords do not match.';
      document.getElementById('signupConfirm')?.focus();
    }
  });

  /* ── Password visibility toggles ──────────────────────────── */
  document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', function () {
      const wrap  = this.closest('.input-wrap');
      const input = wrap?.querySelector('input');
      if (!input) return;
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      const icon = this.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye',      !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
      }
      this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  });

  /* ── Init ──────────────────────────────────────────────────── */
  setAuthMode(authPage?.dataset.initialAuthMode || AUTH_MODE.LOGIN);

})();
