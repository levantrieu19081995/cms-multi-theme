// Login / register screen. One page, two forms. Labels and URLs come from data-* attributes rendered by Blade (frontend.login.*, frontend.register.*, routes); nothing is hard-coded here.
// Form handling stays here; the animations live in effects/auth/.
import { initAuthPanelSwitch } from '../effects/auth/auth-panel-switch.js';
import { createLoginDashboardTransition } from '../effects/auth/login-dashboard-transition.js';

document.addEventListener('DOMContentLoaded', () => {
  const frame = document.querySelector('.cms-login-frame');
  if (!frame) return;

  // Login <-> Register switching (in place, URL via History API): effects/auth/auth-panel-switch.js
  const panelSwitch = initAuthPanelSwitch(frame);
  const panes = panelSwitch ? panelSwitch.panes : Array.from(frame.querySelectorAll('[data-auth-pane]'));

  // Show / hide password (login, register and confirmation fields).
  frame.querySelectorAll('.cms-password-toggle').forEach(button => {
    const input = document.getElementById(button.dataset.toggleTarget || '');
    if (!input) return;
    button.addEventListener('click', () => {
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.setAttribute('aria-pressed', visible ? 'false' : 'true');
      button.setAttribute('aria-label', (visible ? button.dataset.labelShow : button.dataset.labelHide) || '');
      input.focus();
    });
  });

  // Loading state on submit. Register is a plain form post; an inactive form can never be submitted.
  const startLoading = (form, submit) => {
    const text = submit.querySelector('span');
    if (text) { submit.dataset.label = text.textContent; if (form.dataset.labelSubmitting) text.textContent = form.dataset.labelSubmitting; }
    submit.disabled = true;
    submit.classList.add('is-loading');
    submit.setAttribute('aria-busy', 'true');
  };
  const stopLoading = submit => {
    const text = submit.querySelector('span');
    if (text && submit.dataset.label) text.textContent = submit.dataset.label;
    submit.disabled = false;
    submit.classList.remove('is-loading');
    submit.removeAttribute('aria-busy');
  };

  frame.querySelectorAll('form:not([data-login-form])').forEach(form => {
    form.addEventListener('submit', event => {
      if (form.closest('[inert]')) { event.preventDefault(); return; }
      const submit = form.querySelector('.cms-login-submit');
      // Disable on the next tick so the submission itself is not affected.
      if (submit) { const text = submit.querySelector('span'); if (text && form.dataset.labelSubmitting) text.textContent = form.dataset.labelSubmitting; setTimeout(() => { submit.disabled = true; }, 0); submit.classList.add('is-loading'); submit.setAttribute('aria-busy', 'true'); }
    });
  });

  // ---- Login: posted with fetch so the opening animation only runs once the server accepted the login ----
  const loginForm = frame.querySelector('form[data-login-form]');
  if (!loginForm) return;
  const loginSubmit = loginForm.querySelector('.cms-login-submit');
  const errorBox = loginForm.querySelector('[data-login-error]');
  const csrf = document.querySelector('meta[name="csrf-token"]');
  const showError = (message, fields) => {
    if (errorBox) { errorBox.textContent = message; errorBox.hidden = false; }
    loginForm.querySelectorAll('.cms-login-input').forEach(input => {
      const bad = fields.includes(input.name);
      input.classList.toggle('is-error', bad);
      if (bad) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    });
  };

  // Opening animation after a successful login (doors split, dashboard opens from the gap): effects/auth/login-dashboard-transition.js
  const openAndGo = createLoginDashboardTransition({ frame, panes, debug: frame.hasAttribute('data-debug') }); // data-debug only rendered when APP_DEBUG is on

  loginForm.addEventListener('submit', event => {
    event.preventDefault();
    if (loginForm.closest('[inert]') || frame.dataset.busy) return;
    frame.dataset.busy = 'true';
    frame.querySelectorAll('[data-auth-switch]').forEach(button => { button.disabled = true; });
    if (errorBox) errorBox.hidden = true;
    startLoading(loginForm, loginSubmit);

    const restore = () => {
      delete frame.dataset.busy;
      frame.querySelectorAll('[data-auth-switch]').forEach(button => { button.disabled = false; });
      stopLoading(loginSubmit);
    };

    fetch(loginForm.action, {
      method: 'POST',
      body: new FormData(loginForm),
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-TOKEN': csrf.content } : {}) },
    }).then(response => response.json().catch(() => null).then(data => ({ response, data }))).then(({ response, data }) => {
      if (response.ok && data && data.redirect) { openAndGo(data.redirect); return; }
      // Wrong credentials / validation (422), rate limit, expired session...: no animation, no navigation.
      const errors = (data && data.errors) || {};
      const fields = Object.keys(errors);
      const first = fields.length ? [].concat(errors[fields[0]])[0] : '';
      showError(first || loginForm.dataset.labelErrorGeneric || '', fields);
      restore();
      const target = loginForm.querySelector(fields.includes('password') && !fields.includes('login') ? '#cms-password' : '#cms-login');
      if (target) target.focus({ preventScroll: true });
    }).catch(() => {
      showError(loginForm.dataset.labelErrorNetwork || '', []);
      restore();
    });
  });
});
