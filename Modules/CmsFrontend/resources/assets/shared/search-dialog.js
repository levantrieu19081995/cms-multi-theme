// Hộp tìm kiếm dạng <dialog> mở từ nút trên header: mở/đóng, tự focus ô nhập, trả focus về nút khi đóng, Escape, bấm ra ngoài hộp,
// Ctrl/Cmd+K, aria-expanded. Chỉ là hành vi: nội dung form (action, tên query) và giao diện do theme quyết định; gợi ý, đóng menu... theme
// gắn thêm qua callback. Dùng chung bởi shared/campus-base.js (Nam Bình Dương, Ban Đề án) và js/screens/default/shell.js (Khuyến nông).
// Kiểu dáng: shared/search-dialog.css (cơ chế) + CSS theme. Khởi tạo lại trên cùng hộp không tạo trùng listener.
const bound = new WeakSet();

// dialog, trigger: phần tử; closeButton, input: phần tử tuỳ chọn (mặc định null);
// onBeforeOpen(): gọi trước khi mở (vd. đóng menu, dựng gợi ý); onEscape(): gọi thêm khi nhấn Escape.
export function initSearchDialog({ dialog, trigger, closeButton = null, input = null, shortcut = true, onBeforeOpen = null, onEscape = null } = {}) {
  if (!dialog || !trigger || typeof dialog.showModal !== 'function' || bound.has(dialog)) return null;
  bound.add(dialog);
  dialog.dataset.searchDialog = '';

  const open = () => {
    if (dialog.open) return;
    onBeforeOpen?.();
    dialog.showModal();
    trigger.setAttribute('aria-expanded', 'true');
    input?.focus();
  };
  const close = () => { if (dialog.open) dialog.close(); };

  trigger.addEventListener('click', open);
  closeButton?.addEventListener('click', close);
  dialog.addEventListener('close', () => { trigger.setAttribute('aria-expanded', 'false'); trigger.focus(); });
  // Bấm vào vùng nền (backdrop) ngoài hộp thì đóng.
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
  });
  document.addEventListener('keydown', event => {
    if (shortcut && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      if (dialog.open) close(); else open();
    }
    if (event.key === 'Escape') { onEscape?.(); close(); }
  });

  return { open, close };
}
