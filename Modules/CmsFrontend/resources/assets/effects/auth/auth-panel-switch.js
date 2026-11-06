// Chuyển Login ↔ Register tại chỗ (không tải lại trang); URL đi theo bằng History API.
// Nhãn và URL lấy từ data-* do Blade render (frame.dataset.loginUrl, registerUrl, titleLogin, titleRegister); không đặt cứng ở đây.
// data-auth-mode trên khung điều khiển chuyển động CSS (effects/auth/style.css); form đang ẩn bị inert để không nhận focus, click, Enter.
import { markOnce } from '../core/effect-utils.js';

export function initAuthPanelSwitch(frame) {
  if (!frame || !markOnce(frame, 'authSwitchBound')) return null;

  const panes = Array.from(frame.querySelectorAll('[data-auth-pane]'));
  const panels = Array.from(frame.querySelectorAll('[data-auth-panel]'));
  const urls = { login: frame.dataset.loginUrl, register: frame.dataset.registerUrl };
  const titles = { login: frame.dataset.titleLogin, register: frame.dataset.titleRegister };
  const modes = panes.map(pane => pane.dataset.authPane);

  const pathOf = url => { try { return new URL(url, window.location.href).pathname.replace(/\/+$/, ''); } catch (e) { return null; } };
  const modeFromLocation = () => {
    const path = window.location.pathname.replace(/\/+$/, '');
    return modes.find(mode => urls[mode] && pathOf(urls[mode]) === path) || null;
  };

  // Shows one mode: data-auth-mode drives the CSS slide; the hidden form is made inert so it cannot take focus, clicks or Enter.
  const apply = (mode, focus) => {
    if (!modes.includes(mode)) return;
    frame.dataset.authMode = mode;
    panes.forEach(pane => {
      const active = pane.dataset.authPane === mode;
      pane.toggleAttribute('inert', !active);
      if (active) pane.removeAttribute('aria-hidden'); else pane.setAttribute('aria-hidden', 'true');
    });
    panels.forEach(panel => {
      const active = panel.dataset.authPanel === mode;
      panel.classList.toggle('is-visible', active);
      if (active) panel.removeAttribute('aria-hidden'); else panel.setAttribute('aria-hidden', 'true');
      panel.querySelectorAll('button').forEach(button => { if (active) button.removeAttribute('tabindex'); else button.setAttribute('tabindex', '-1'); });
    });
    if (titles[mode]) document.title = titles[mode];
    if (focus) {
      const active = panes.find(pane => pane.dataset.authPane === mode);
      const first = active && active.querySelector('input:not([type="hidden"]):not([type="checkbox"])');
      if (first) first.focus({ preventScroll: true });
    }
  };

  frame.querySelectorAll('[data-auth-switch]').forEach(button => {
    button.addEventListener('click', () => {
      const mode = button.dataset.authSwitch;
      if (!modes.includes(mode) || mode === frame.dataset.authMode || frame.dataset.busy) return;
      apply(mode, true);
      if (urls[mode] && window.history && window.history.pushState) window.history.pushState({ authMode: mode }, '', urls[mode]);
    });
  });

  // Back / Forward restore the matching form without reloading.
  if (window.history && window.history.replaceState) {
    window.history.replaceState({ authMode: frame.dataset.authMode }, '', window.location.href);
  }
  window.addEventListener('popstate', event => {
    const mode = (event.state && event.state.authMode) || modeFromLocation();
    if (mode && !frame.dataset.busy) apply(mode, false);
  });

  return { apply, panes };
}
