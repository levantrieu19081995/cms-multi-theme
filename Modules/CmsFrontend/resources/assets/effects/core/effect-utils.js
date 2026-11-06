// Tiện ích nhỏ dùng chung cho các effect. Không phụ thuộc theme.
export const clamp = (v, min = 0, max = 1) => Math.min(max, Math.max(min, v));
export const easeOutCubic = t => 1 - Math.pow(1 - t, 3);

// Đánh dấu một phần tử đã gắn effect (data-<key>) để khởi tạo lại không tạo trùng listener. Trả về true nếu đây là lần đầu.
export const markOnce = (el, key) => {
  if (el.dataset[key]) return false;
  el.dataset[key] = '1';
  return true;
};

// Gom nhiều sự kiện scroll trong một khung hình thành một lần gọi.
export const onScrollFrame = callback => {
  let queued = false;
  window.addEventListener('scroll', () => {
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => { queued = false; callback(); });
  }, { passive: true });
};
