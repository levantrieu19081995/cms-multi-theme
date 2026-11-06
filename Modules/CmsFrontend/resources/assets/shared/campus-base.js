// Hành vi nền dùng chung cho các theme trang chủ kiểu "campus" (Nam Bình Dương, Ban Đề án): menu, tìm kiếm nhanh, lối tắt, lọc tin.
// Đây KHÔNG phải effect (không nằm trong effects/): chuyển nguyên từ nam-binh-duong/theme.js, giữ nguyên hành vi.
// Trả về { headerOffset } để theme truyền cho các effect cần biết chiều cao thanh dính; null nếu trang không có gốc `.sbut-public`.
import { initSearchDialog } from './search-dialog.js';

if (window.console) {
  console.log('%cDừng lại!', 'color:red;font-size:48px;font-weight:bold;');
  console.log('%cĐây là tính năng dành cho nhà phát triển. Không dán mã lạ vào đây vì có thể bị chiếm tài khoản.', 'font-size:16px;');
  console.log('%c© Trường Đại học Công nghệ Nam Bình Dương. Nghiêm cấm sao chép khi chưa được phép.', 'color:#39789A;font-size:14px;');
}

// Hook HTML của các theme campus, để theme truyền cho effect (effect không biết các tên này).
export const campusHooks = {
  root: '.sbut-public',
  section: 'data-sbut-section',
  hero: { hero: 'data-sbut-hero', slide: 'data-sbut-slide', goTo: 'data-sbut-slide-to', move: 'data-sbut-slide-move', counter: 'data-sbut-slide-index' },
  rail: '[data-sbut-rail]',
  railItem: 'data-sbut-rail-target',
  railGate: '.section-shortcuts'
};

let started = null;

export function initCampusBase() {
  if (started) return started;
  const root = document.querySelector(campusHooks.root);
  if (!root) return null;

  const menu = root.querySelector('#mainNav'), toggle = root.querySelector('#menuBtn'), search = root.querySelector('#searchBtn'), form = root.querySelector('#sbut-site-search');
  const header = root.querySelector('.sbut-header');
  const shortcutBar = root.querySelector('.sbut-shortcut-inner')?.closest('.quick-links');
  const pinnedHeaderHeight = () => header && ['sticky', 'fixed'].includes(getComputedStyle(header).position) ? header.getBoundingClientRect().height : 0;
  const updateStickyOffsets = () => { root.style.setProperty('--sbut-header-height', pinnedHeaderHeight() + 'px'); root.style.setProperty('--sbut-shortcut-height', (shortcutBar?.getBoundingClientRect().height || 0) + 'px'); };
  updateStickyOffsets();
  const stickyObserver = new ResizeObserver(updateStickyOffsets); if (header) stickyObserver.observe(header); if (shortcutBar) stickyObserver.observe(shortcutBar);
  const closeMenu = () => { menu?.classList.remove('open'); toggle?.setAttribute('aria-expanded', 'false'); toggle?.setAttribute('aria-label', (window.cmsTranslations?.['menu_open'] || 'Mở menu')); };
  toggle?.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; menu?.classList.toggle('open', open); toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? (window.cmsTranslations?.['menu_close'] || 'Đóng menu') : (window.cmsTranslations?.['menu_open'] || 'Mở menu')); });
  const input = form?.querySelector('input'), results = form?.querySelector('.sbut-search-results'), empty = form?.querySelector('.sbut-search-empty'), all = form?.querySelector('.sbut-search-all');
  const normalize = value => value.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();
  const candidates = [...root.querySelectorAll('.home-list-row,.notice-item')].map(link => ({ url: link.href, title: link.querySelector('h3')?.textContent.trim(), label: link.querySelector('.card-eyebrow')?.textContent.trim() || link.querySelector('.home-row-category')?.childNodes[0]?.textContent.trim() || (window.cmsTranslations?.['news'] || 'Tin tức') })).filter(item => item.title);
  const unique = [...new Map(candidates.map(item => [item.url, item])).values()];
  const renderSuggestions = () => { if (!results || !input) return; const query = normalize(input.value); const matches = unique.filter(item => !query || normalize(item.title + ' ' + item.label).includes(query)).slice(0, 5); results.replaceChildren(); for (const item of matches) { const link = document.createElement('a'), label = document.createElement('span'), title = document.createElement('strong'); link.href = item.url; label.textContent = item.label; title.textContent = item.title; link.append(label, title); results.append(link); } empty.hidden = matches.length !== 0; all.hidden = !query; };
  input?.addEventListener('input', renderSuggestions);
  // Mở/đóng, focus, Escape, Ctrl+K, bấm nền: shared/search-dialog.js; gợi ý và đóng menu là của theme campus.
  initSearchDialog({ dialog: form, trigger: search, closeButton: form?.querySelector('.sbut-search-close'), input, onBeforeOpen: () => { closeMenu(); renderSuggestions(); }, onEscape: closeMenu });
  menu?.addEventListener('click', e => { if (e.target.closest('a')) closeMenu(); });
  document.addEventListener('click', e => { if (!e.target.closest('.header')) closeMenu(); });
  const shortcutMenus = [...root.querySelectorAll('.sbut-shortcut-dropdown')];
  shortcutMenus.forEach(details => details.addEventListener('toggle', () => { if (details.open) shortcutMenus.forEach(other => { if (other !== details) other.open = false; }); }));
  document.addEventListener('click', event => { if (!event.target.closest('.sbut-shortcut-item')) shortcutMenus.forEach(details => { details.open = false; }); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') shortcutMenus.forEach(details => { if (details.open) { details.open = false; details.querySelector('summary')?.focus(); } }); });

  // Lọc tin trên trang chủ theo loại.
  const filters = [...root.querySelectorAll('[data-sbut-news-filter]')], list = root.querySelector('[data-sbut-news-list]');
  filters.forEach(button => button.addEventListener('click', () => {
    const filter = button.dataset.sbutNewsFilter;
    filters.forEach(b => { const active = b === button; b.classList.toggle('active', active); b.setAttribute('aria-pressed', String(active)); });
    let count = 0;
    list?.querySelectorAll('[data-sbut-row-kind]').forEach(row => { row.hidden = filter !== 'all' && row.dataset.sbutRowKind !== filter; if (!row.hidden) count++; });
    const counter = root.querySelector('[data-sbut-news-count]');
    if (counter) counter.textContent = count + ' ' + (window.cmsTranslations?.['items'] || 'nội dung');
    const emptyState = root.querySelector('[data-sbut-news-empty]');
    if (emptyState) emptyState.hidden = count !== 0;
  }));

  started = { root, headerOffset: () => pinnedHeaderHeight() + (shortcutBar?.getBoundingClientRect().height || 0) };
  return started;
}
