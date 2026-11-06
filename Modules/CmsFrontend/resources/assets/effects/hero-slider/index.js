// Hero slider: chuyển slide bằng nút (không tự chạy). Hook HTML do theme truyền vào bằng TÊN thuộc tính (không đặt cứng ở đây):
//   hero (khung), slide (mỗi slide), goTo (nút tới slide, giá trị = số thứ tự), move (nút +1/-1), counter (nơi hiện số slide).
// Slide đang xem chỉ khác nhau ở thuộc tính hidden; giao diện do theme quyết định.
import { markOnce } from '../core/effect-utils.js';

export function initHeroSlider(scope = document, { hero, slide, goTo, move, counter } = {}) {
  const frame = hero && scope.querySelector(`[${hero}]`);
  if (!frame || !slide || !markOnce(frame, 'heroSliderBound')) return null;
  const slides = [...frame.querySelectorAll(`[${slide}]`)];
  if (!slides.length) return null;
  const goButtons = goTo ? [...frame.querySelectorAll(`[${goTo}]`)] : [], moveButtons = move ? [...frame.querySelectorAll(`[${move}]`)] : [];
  const counterEl = counter ? frame.querySelector(`[${counter}]`) : null;
  let index = 0;

  const show = i => {
    index = (i + slides.length) % slides.length;
    slides.forEach((s, n) => { s.hidden = n !== index; });
    goButtons.forEach(b => {
      const active = Number(b.getAttribute(goTo)) === index;
      b.classList.toggle('active', active);
      b.setAttribute('aria-pressed', String(active));
    });
    if (counterEl) counterEl.textContent = String(index + 1).padStart(2, '0');
  };
  goButtons.forEach(b => b.addEventListener('click', () => show(Number(b.getAttribute(goTo)))));
  moveButtons.forEach(b => b.addEventListener('click', () => show(index + Number(b.getAttribute(move)))));
  return { show };
}
