// Đăng nhập thành công: hai nửa trang Login tách sang hai bên, Dashboard mở ra từ khe giữa.
// Phía Login (file này + effects/auth/style.css) đo hình học và điều hướng; phía Dashboard vẽ hiệu ứng bằng cross-document View Transition
// (CmsBackend login-transition.blade.php). Hợp đồng giữa hai phía: khoá sessionStorage 'cms-login-transition' {mode, t, centerX, ...},
// tên view-transition cms-login-left / cms-login-right. Không đổi các tên này nếu chưa đổi cả phía Dashboard.
import { prefersReducedMotion } from '../core/reduced-motion.js';

const FLAG = 'cms-login-transition';

// Trả về openAndGo(url): chạy hiệu ứng rồi điều hướng tới url. Không đụng tới form, fetch hay xác thực.
export function createLoginDashboardTransition({ frame, panes, debug = false }) {
  const reduced = prefersReducedMotion();
  const supportsViewTransitions = typeof window.CSSViewTransitionRule !== 'undefined' && 'onpagereveal' in window;
  const OPEN_MS = window.matchMedia('(max-width: 767px)').matches ? 600 : 800; // keep equal to the CSS durations

  const setFlag = (mode, geometry) => {
    try { sessionStorage.setItem(FLAG, JSON.stringify({ mode, t: Date.now(), ...geometry })); } catch (e) { /* storage blocked: the dashboard simply appears without the transition */ }
  };

  // Measures the real login geometry right before leaving: the frame (max 1480px, not the viewport) and the gap between the two doors.
  const measure = () => {
    const rect = frame.getBoundingClientRect();
    const named = name => Array.from(frame.querySelectorAll('.cms-login-visual, .cms-login-pane--login, .cms-login-curtain'))
      .find(el => getComputedStyle(el).viewTransitionName === name);
    const left = named('cms-login-left'), right = named('cms-login-right');
    // Inner edges of the doors meet at the centre; fall back to the frame centre if the named elements cannot be found.
    const centerX = left && right
      ? (left.getBoundingClientRect().right + right.getBoundingClientRect().left) / 2
      : rect.left + rect.width / 2;
    return { frameLeft: rect.left, frameRight: rect.right, frameWidth: rect.width, centerX, innerWidth: window.innerWidth };
  };
  const nextFrame = callback => requestAnimationFrame(() => requestAnimationFrame(callback));

  // Kiểu chuyển do theme khai báo trên khung (data-auth-transition); 'push-horizontal' chạy nối đuôi (push-horizontal-transition.js), còn lại là cửa tách đôi.
  const push = frame.dataset.authTransition === 'push-horizontal' && !!window.CmsAuthPush;

  return function openAndGo(url) {
    frame.dataset.busy = 'true';
    panes.forEach(pane => pane.setAttribute('inert', ''));
    if (reduced) { window.location.assign(url); return; }

    if (push) {
      if (supportsViewTransitions) {
        const optIn = document.createElement('style');
        optIn.textContent = '@view-transition { navigation: auto; }';
        document.head.appendChild(optIn);
        setFlag('vt', { transition: 'push-horizontal' });
        nextFrame(() => window.location.assign(url));
      } else {
        setFlag('fb', { transition: 'push-horizontal' });
        window.CmsAuthPush.leave(1, () => window.location.assign(url));
      }
      return;
    }

    if (supportsViewTransitions) {
      // Cross-document View Transition: the named halves are animated by the dashboard page.
      const optIn = document.createElement('style');
      optIn.textContent = '@view-transition { navigation: auto; }';
      document.head.appendChild(optIn);
      frame.classList.add('is-login-success');
      const geometry = measure(); // after the class: on mobile the 50% doors only exist from here on
      setFlag('vt', geometry);
      if (debug) console.info('[cms-login-transition] branch = view-transition', geometry);
      nextFrame(() => window.location.assign(url));
      return;
    }

    // Fallback: play the split here, then navigate; the dashboard fades in when it sees the flag.
    frame.classList.add('is-login-success', 'is-login-fallback');
    frame.parentElement.classList.add('is-login-success-wrap');
    setFlag('fb', {});
    if (debug) console.warn('[cms-login-transition] branch = fallback: View Transitions unsupported, only the split + fade-in is possible (no slit reveal)');
    setTimeout(() => window.location.assign(url), OPEN_MS - 50);
  };
}
