import { clamp, easeOutCubic } from '../core/effect-utils.js';

// Số chạy từ 0 tới data-count (một lần). data-pad: số chữ số đệm 0. Thời gian chỉnh bằng options.duration (ms).
export function countUp(el, { duration = 1600, locale = 'vi-VN' } = {}) {
  if (el.dataset.countDone) return;
  el.dataset.countDone = '1';
  const end = parseInt(el.dataset.count, 10) || 0, pad = parseInt(el.dataset.pad, 10) || 0, t0 = performance.now();
  (function step(t) {
    const p = clamp((t - t0) / duration);
    el.textContent = Math.round(end * easeOutCubic(p)).toLocaleString(locale).padStart(pad, '0');
    if (p < 1) requestAnimationFrame(step);
  })(t0);
}
