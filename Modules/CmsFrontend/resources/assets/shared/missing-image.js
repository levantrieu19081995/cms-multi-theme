// Ảnh trong nội dung bài viết (.ck-content, .blog-content) bị thiếu hoặc không tải được: thay bằng ảnh 1px và một dòng chữ,
// để khung ảnh không giữ chỗ trống lớn đẩy nội dung xuống. Dùng chung cho mọi theme (nạp qua default/shell.js).
// Không đụng ảnh ở nơi khác (banner, thẻ tin...) vì chúng đã có placeholder riêng.
const PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
const SCOPE = '.ck-content img, .blog-content img';

const label = () => (document.documentElement.lang || '').toLowerCase().startsWith('vi') || !document.documentElement.lang ? 'Không có ảnh' : 'Image unavailable';

function collapse(image) {
  if (image.dataset.cmsMissing) return;
  image.dataset.cmsMissing = '1';
  ['srcset', 'sizes', 'width', 'height'].forEach(name => image.removeAttribute(name));
  image.alt = '';
  image.classList.add('cms-missing-image-pixel');
  // Khung bọc (figure/.image của CKEditor) thường mang chiều rộng inline: để CSS thu gọn lại.
  image.closest('figure, .image')?.classList.add('cms-missing-image-holder');
  image.src = PIXEL;
  const note = document.createElement('span');
  note.className = 'cms-missing-image';
  note.textContent = label();
  image.after(note);
}

const empty = image => {
  const src = (image.getAttribute('src') || '').trim();
  return src === '' || src === '#' || (image.complete && image.naturalWidth === 0 && src !== PIXEL);
};

export function initMissingImages() {
  // `error` không nổi bọt: bắt ở pha capture để cũng xử lý ảnh tải chậm (loading="lazy").
  document.addEventListener('error', event => {
    if (event.target instanceof HTMLImageElement && event.target.matches(SCOPE)) collapse(event.target);
  }, true);
  const scan = () => document.querySelectorAll(SCOPE).forEach(image => { if (empty(image)) collapse(image); });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan, { once: true });
  else scan();
  window.addEventListener('load', scan, { once: true });
}
