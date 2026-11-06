// Hiện lần lượt các phần tử của một nhóm khi cuộn tới (hai chiều). Xem effects/stagger/style.css cho thuộc tính HTML.
// Mép trên (vị trí bố cục, đã trừ quãng dịch translateY) qua vạch `line` của màn hình thì trồi lên; lùi xuống dưới vạch thì chìm xuống.
// Độ trễ so le tính trong số phần tử cùng đổi trạng thái ở một lần cập nhật của mỗi nhóm (hiện theo thứ tự, ẩn theo thứ tự ngược lại),
// nên danh sách dài cuộn tới đâu hiện tới đó mà không bị trễ dồn.
import { markOnce } from '../core/effect-utils.js';

export function createStagger({ stagger = .08, line = .92, groupSelector = '[data-stagger-group]', onShow = null } = {}) {
  const items = [];

  const bind = (root = document) => root.querySelectorAll(groupSelector).forEach(group => {
    if (!markOnce(group, 'staggerBound')) return;
    // data-stagger-group rỗng: các con trực tiếp; có giá trị: selector chọn phần tử bên trong nhóm (theo thứ tự trong trang).
    const offset = parseFloat(group.dataset.staggerOffset) || 0, selector = group.dataset.staggerGroup.trim();
    (selector ? [...group.querySelectorAll(selector)] : [...group.children]).forEach(el => {
      el.classList.add('reveal-item');
      items.push({ el, group, offset });
    });
  });

  // Trả về true nếu có phần tử đổi trạng thái (để bên gọi giữ vòng cập nhật chạy trong lúc chuyển động).
  const update = () => {
    const edge = window.innerHeight * line, batches = new Map();
    items.forEach(item => {
      const { el } = item, shiftY = new DOMMatrixReadOnly(getComputedStyle(el).transform).m42, shown = el.getBoundingClientRect().top - shiftY < edge;
      if (el.classList.contains('is-in') === shown) return;
      if (!batches.has(item.group)) batches.set(item.group, { show: [], hide: [] });
      batches.get(item.group)[shown ? 'show' : 'hide'].push(item);
    });
    batches.forEach(({ show, hide }) => {
      show.forEach((item, k) => {
        item.el.style.setProperty('transition-delay', (item.offset + k * stagger) + 's', 'important');
        item.el.classList.add('is-in');
        if (onShow) onShow(item.el);
      });
      hide.forEach((item, k) => {
        item.el.style.setProperty('transition-delay', ((hide.length - 1 - k) * stagger) + 's', 'important');
        item.el.classList.remove('is-in');
      });
    });
    return batches.size > 0;
  };

  return { bind, update };
}
