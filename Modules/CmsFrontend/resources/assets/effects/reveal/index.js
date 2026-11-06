// Scroll reveal: chữ hiện theo dòng (data-reveal-lines) + hiện lần lượt (stagger) + số chạy (count-up), cùng một nhịp cập nhật.
// Gọi lại window.ScrollReveal.init(el) sau khi chèn HTML động. Khởi tạo nhiều lần chỉ dùng lại một instance (một bộ listener).
import { clamp, markOnce, onScrollFrame } from '../core/effect-utils.js';
import { createStagger } from '../stagger/index.js';
import { countUp } from '../count-up/index.js';

let instance = null;

export function initReveal({ stagger = .08, line = .92, pumpMs = 1600, countSelector = '[data-count]', countOptions } = {}) {
  if (instance) { instance.init(); return instance; }
  if (!('DOMMatrixReadOnly' in window)) return null;

  const headings = [], group = createStagger({ stagger, line, onShow: el => el.querySelectorAll(countSelector).forEach(target => countUp(target, countOptions)) });
  let pumpUntil = 0;

  const updateHeadings = () => {
    const vh = window.innerHeight;
    headings.forEach(({ el, lines }) => {
      const p = clamp((vh * .95 - el.getBoundingClientRect().top) / (vh * .55)), n = Math.floor(p * lines.length + .001);
      lines.forEach((node, i) => node.classList.toggle('on', i < n));
    });
  };
  // Tiêu đề nằm trong khối đang trượt nên vị trí đổi cả khi không cuộn: tính lại mỗi khung hình trong lúc chuyển động.
  const pump = ms => {
    const now = performance.now(), running = pumpUntil > now;
    pumpUntil = Math.max(pumpUntil, now + ms);
    if (!running) (function loop() { updateHeadings(); if (performance.now() < pumpUntil) requestAnimationFrame(loop); })();
  };
  const update = () => { if (group.update()) pump(pumpMs); updateHeadings(); };

  // Chữ luôn hiện theo dòng (không tách từng chữ cho đỡ rối mắt): mỗi đoạn giữa các <br> là một dòng.
  const splitLines = el => {
    if (!markOnce(el, 'revealLinesBound')) return;
    el.setAttribute('aria-label', [...el.childNodes].map(node => node.textContent).join(' ').replace(/\s+/g, ' ').trim());
    [...el.childNodes].forEach(node => {
      if (node.nodeType !== Node.TEXT_NODE) return;
      const text = node.textContent.replace(/\s+/g, ' ').trim();
      if (!text) return;
      const span = document.createElement('span');
      span.className = 'reveal-line';
      span.setAttribute('aria-hidden', 'true');
      span.textContent = text;
      node.replaceWith(span);
    });
    headings.push({ el, lines: [...el.querySelectorAll('.reveal-line')] });
  };

  const init = (root = document) => {
    root.querySelectorAll('[data-reveal-lines]').forEach(splitLines);
    group.bind(root);
    update();
  };

  document.documentElement.classList.add('reveal-ready');
  onScrollFrame(update);
  window.addEventListener('resize', update);
  instance = window.ScrollReveal = { init, refresh: update };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init()); else init();
  return instance;
}
