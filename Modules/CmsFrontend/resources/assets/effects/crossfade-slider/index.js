// Crossfade slider: các slide chồng lên nhau, chuyển bằng mờ dần; mũi tên, chấm, tự chạy (dừng khi rê chuột/focus/tab ẩn), vuốt trên cảm ứng.
// Không tự chạy khi máy bật giảm chuyển động (xét một lần lúc khởi tạo). CSS cơ chế: effects/crossfade-slider/style.css.
// Hook HTML mặc định là hook hiện có (đổi bằng options.hooks): khung [data-cms-hero], slide .cms-hero-slide,
// chấm [data-cms-hero-dot="<số>"], nút [data-cms-hero-prev] / [data-cms-hero-next]. Class slide đang xem: is-active.
import { markOnce } from '../core/effect-utils.js';
import { prefersReducedMotion } from '../core/reduced-motion.js';

const defaults = {
  interval: 6000,   // ms giữa hai lần tự chuyển
  swipe: 40,        // px tối thiểu để tính là một cú vuốt
  activeClass: 'is-active',
  hooks: { root: '[data-cms-hero]', slide: '.cms-hero-slide', dotAttr: 'data-cms-hero-dot', prevAttr: 'data-cms-hero-prev', nextAttr: 'data-cms-hero-next' }
};

// Trả về mảng { destroy } cho mỗi slider được khởi tạo (rỗng nếu không có). Gọi lại không tạo trùng listener/timer.
export function initCrossfadeSlider(scope = document, options = {}) {
  const o = { ...defaults, ...options, hooks: { ...defaults.hooks, ...options.hooks } };
  if (!scope) return [];
  const roots = scope.matches?.(o.hooks.root) ? [scope] : [...scope.querySelectorAll(o.hooks.root)];
  return roots.map(root => createSlider(root, o)).filter(Boolean);
}

function createSlider(hero, o) {
  const { hooks: h } = o;
  const slides = [...hero.querySelectorAll(h.slide)];
  const dots = [...hero.querySelectorAll(`[${h.dotAttr}]`)];
  if (slides.length < 2 || !markOnce(hero, 'crossfadeSliderBound')) return null;
  const reduced = prefersReducedMotion();
  let index = 0;
  let timer = null;
  const cleanups = [];
  const listen = (el, type, handler, opts) => { el.addEventListener(type, handler, opts); cleanups.push(() => el.removeEventListener(type, handler, opts)); };

  const show = next => {
    index = (next + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle(o.activeClass, active);
      slide.toggleAttribute('aria-hidden', !active);
      slide.querySelectorAll('a').forEach(link => active ? link.removeAttribute('tabindex') : link.setAttribute('tabindex', '-1'));
    });
    dots.forEach((dot, i) => i === index ? dot.setAttribute('aria-current', 'true') : dot.removeAttribute('aria-current'));
  };
  const stop = () => { clearInterval(timer); timer = null; };
  const start = () => { if (!reduced && !timer) timer = setInterval(() => show(index + 1), o.interval); };
  const go = next => { show(next); stop(); start(); };

  const prev = hero.querySelector(`[${h.prevAttr}]`), next = hero.querySelector(`[${h.nextAttr}]`);
  if (prev) listen(prev, 'click', () => go(index - 1));
  if (next) listen(next, 'click', () => go(index + 1));
  dots.forEach(dot => listen(dot, 'click', () => go(Number(dot.getAttribute(h.dotAttr)))));
  listen(hero, 'mouseenter', stop);
  listen(hero, 'mouseleave', start);
  listen(hero, 'focusin', stop);
  listen(hero, 'focusout', start);
  listen(document, 'visibilitychange', () => document.hidden ? stop() : start());
  let touchX = null;
  listen(hero, 'touchstart', event => { touchX = event.touches[0].clientX; }, { passive: true });
  listen(hero, 'touchend', event => {
    if (touchX === null) return;
    const dx = event.changedTouches[0].clientX - touchX;
    touchX = null;
    if (Math.abs(dx) > o.swipe) go(index + (dx < 0 ? 1 : -1));
  });
  start();

  return {
    destroy() {
      stop();
      cleanups.forEach(fn => fn());
      delete hero.dataset.crossfadeSliderBound;
    }
  };
}
