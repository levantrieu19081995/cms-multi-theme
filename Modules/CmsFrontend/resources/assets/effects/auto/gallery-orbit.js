// Entry tự khởi chạy: vòng ảnh 3D ([data-cms-ring]). Chỉ được nạp khi một cụm đang bật khai báo preset gallery-orbit.
// Nhãn đã dịch do Blade khai báo trên khung vòng ảnh bằng data-label-*; status dùng {index} {total} {title}.
import { initGalleryOrbit } from '../gallery-orbit/index.js';

const scope = document.querySelector('.cms-public .cms-home');
const ring = scope?.querySelector('[data-cms-ring]');
if (scope && ring) {
  const text = ring.dataset ?? {};
  const labels = {};
  if (text.labelPhotos) labels.albumPhotos = (title, count) => `${title} – ${count} ${text.labelPhotos}`;
  if (text.labelStatus) labels.status = (index, total, title) => text.labelStatus.replace('{index}', index).replace('{total}', total).replace('{title}', title);
  if (text.labelPause) labels.pause = text.labelPause;
  if (text.labelResume) labels.resume = text.labelResume;
  initGalleryOrbit(scope, { labels });
}
