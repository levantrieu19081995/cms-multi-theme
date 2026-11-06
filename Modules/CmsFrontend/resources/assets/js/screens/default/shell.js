import { initSearchDialog } from '../../../shared/search-dialog.js';
import { initMissingImages } from '../../../shared/missing-image.js';


if (window.console) {
  console.log('%cDừng lại!', 'color:red;font-size:48px;font-weight:bold;');
  console.log('%cĐây là tính năng dành cho nhà phát triển. Không dán mã lạ vào đây vì có thể bị chiếm tài khoản.', 'font-size:16px;');
  console.log('%c© Trung tâm Khuyến nông và Dịch vụ Nông nghiệp thành phố Cần Thơ. Nghiêm cấm sao chép khi chưa được phép.', 'color:#1b7a34;font-size:14px;');
}

// Ảnh trong nội dung bài bị thiếu/hỏng: ảnh 1px + một dòng chữ, không giữ chỗ trống (mọi theme dùng shell.js).
initMissingImages();

const root = document.querySelector('.cms-public');
if (root) {
  root.classList.add('cms-js');
  root.querySelectorAll('.cms-home img').forEach(image => {
    const missing = () => {
      image.hidden = true;
      const container = image.parentElement;
      container?.classList.add('cms-image-empty');
      container?.setAttribute('title', 'Ảnh chưa khả dụng');
    };
    image.addEventListener('error', missing);
    if (image.complete && image.naturalWidth === 0) missing();
  });
  // Hộp tìm kiếm trên header (cùng hành vi với theme Nam Bình Dương: shared/search-dialog.js).
  const searchDialog = root.querySelector('#cms-site-search');
  initSearchDialog({ dialog: searchDialog, trigger: root.querySelector('#cms-search-open'), closeButton: searchDialog?.querySelector('.cms-search-close'), input: searchDialog?.querySelector('input[name="query"]') });
  const toggle = root.querySelector('.cms-menu-toggle');
  const menu = root.querySelector('#cms-main-menu');
  const closeMenu = () => {
    menu?.classList.remove('cms-menu-open');
    toggle?.setAttribute('aria-expanded', 'false');
  };
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    menu?.classList.toggle('cms-menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
  });
  root.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    const openDetails = root.querySelector('.cms-nav details[open]');
    if (openDetails) {
      openDetails.removeAttribute('open');
      openDetails.querySelector('summary')?.focus();
    } else if (toggle?.getAttribute('aria-expanded') === 'true') {
      closeMenu();
      toggle.focus();
    }
  });
  root.querySelectorAll('.cms-submenu').forEach(details => {
    details.addEventListener('toggle', () => {
      if (!details.open) return;
      // Keep the popup panel inside the menu bar so wide panels near the edges do not overflow.
      const panel = details.querySelector(':scope > .cms-submenu-panel');
      const bounds = details.closest('.cms-nav-inner')?.getBoundingClientRect();
      if (panel && bounds) {
        panel.style.setProperty('--cms-submenu-shift', '0px');
        if (getComputedStyle(panel).position === 'absolute') {
          const rect = panel.getBoundingClientRect();
          const shift = rect.left < bounds.left ? bounds.left - rect.left : (rect.right > bounds.right ? bounds.right - rect.right : 0);
          panel.style.setProperty('--cms-submenu-shift', `${Math.round(shift)}px`);
        }
      }
      root.querySelectorAll('.cms-submenu').forEach(other => {
        if (other !== details) other.removeAttribute('open');
      });
    });
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.cms-nav')) {
      root.querySelectorAll('.cms-submenu[open]').forEach(details => details.removeAttribute('open'));
      closeMenu();
    }
  });
  // Category tabs ("Chuyên mục nổi bật"): links to category pages, upgraded to an ARIA tablist when JS runs.
  root.querySelectorAll('[data-cms-tabs]').forEach(block => {
    const list = block.querySelector('[data-cms-tabs-list]');
    const tabs = [...block.querySelectorAll('[data-cms-tab]')];
    const more = block.querySelector('[data-cms-tabs-more]');
    if (!list || !tabs.length) return;
    list.setAttribute('role', 'tablist');
    const activate = (tab, focus = false) => {
      tabs.forEach(item => {
        const active = item === tab;
        const panel = document.getElementById(item.dataset.cmsTab);
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', String(active));
        item.setAttribute('tabindex', active ? '0' : '-1');
        item.removeAttribute('aria-current');
        if (panel) panel.hidden = !active;
      });
      if (more) more.href = tab.href;
      if (focus) tab.focus();
      // Bring the active tab into view inside the row only (no vertical page jump).
      const listBox = list.getBoundingClientRect();
      const tabBox = tab.getBoundingClientRect();
      if (tabBox.left < listBox.left + 40) list.scrollBy({ left: tabBox.left - listBox.left - 40, behavior: 'smooth' });
      else if (tabBox.right > listBox.right - 40) list.scrollBy({ left: tabBox.right - listBox.right + 40, behavior: 'smooth' });
    };
    tabs.forEach((tab, index) => {
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-controls', tab.dataset.cmsTab);
      document.getElementById(tab.dataset.cmsTab)?.setAttribute('role', 'tabpanel');
      tab.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
        event.preventDefault();
        activate(tab);
      });
      tab.addEventListener('keydown', event => {
        const target = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 }[event.key];
        if (target === undefined) return;
        event.preventDefault();
        activate(tabs[(target + tabs.length) % tabs.length], true);
      });
    });
    tabs.forEach(item => item.setAttribute('tabindex', item.classList.contains('is-active') ? '0' : '-1'));
    tabs.forEach(item => item.setAttribute('aria-selected', String(item.classList.contains('is-active'))));

    // Overflowing tab row: arrow buttons, mouse wheel → horizontal scroll, edge fades.
    const scroller = block.querySelector('[data-cms-tabs-scroller]');
    const buttons = [...block.querySelectorAll('[data-cms-tabs-scroll]')];
    const update = () => {
      const max = list.scrollWidth - list.clientWidth;
      const atStart = list.scrollLeft <= 1;
      const atEnd = list.scrollLeft >= max - 1;
      buttons.forEach(button => { button.hidden = max <= 1 || (button.dataset.cmsTabsScroll === '-1' ? atStart : atEnd); });
      scroller?.classList.toggle('has-prev', max > 1 && !atStart);
      scroller?.classList.toggle('has-next', max > 1 && !atEnd);
    };
    buttons.forEach(button => button.addEventListener('click', () => {
      list.scrollBy({ left: Number(button.dataset.cmsTabsScroll) * list.clientWidth * 0.7, behavior: 'smooth' });
      setTimeout(update, 450); // Fallback when the smooth scroll ends without a final scroll event.
    }));
    list.addEventListener('wheel', event => {
      const max = list.scrollWidth - list.clientWidth;
      if (max <= 1 || Math.abs(event.deltaX) > Math.abs(event.deltaY)) return;
      const next = Math.min(max, Math.max(0, list.scrollLeft + event.deltaY));
      if (next === list.scrollLeft) return; // At an edge: let the page scroll normally.
      event.preventDefault();
      list.scrollLeft = next;
      update();
    }, { passive: false });
    list.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
  });
  // "Chuyên mục nổi bật" (theme mặc định): mỗi dải thẻ tự trượt từng thẻ khi dải tràn; dừng khi rê chuột, focus, chạm, tab ẩn, hoặc khi người dùng chọn giảm chuyển động.
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  root.querySelectorAll('[data-cms-slider]').forEach((track, index) => {
    let held = false;
    let timer = null;
    const advance = () => {
      const max = track.scrollWidth - track.clientWidth;
      const first = track.firstElementChild;
      if (max <= 1 || held || document.hidden || !first) return;
      const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      track.scrollTo({ left: track.scrollLeft >= max - 2 ? 0 : Math.min(max, track.scrollLeft + first.getBoundingClientRect().width + gap), behavior: 'smooth' });
    };
    const start = () => { if (!timer && !reduceMotion.matches) timer = setInterval(advance, 4500 + index * 700); };
    const stop = () => { clearInterval(timer); timer = null; };
    const hold = value => () => { held = value; };
    track.addEventListener('mouseenter', hold(true));
    track.addEventListener('mouseleave', hold(false));
    track.addEventListener('focusin', hold(true));
    track.addEventListener('focusout', hold(false));
    track.addEventListener('touchstart', hold(true), { passive: true });
    track.addEventListener('touchend', () => setTimeout(hold(false), 6000), { passive: true });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(entries => entries.forEach(entry => (entry.isIntersecting ? start() : stop()))).observe(track);
    } else {
      start();
    }
  });
}

// Thanh cuộn riêng của trang chủ: mảnh, cao ~300px, nằm giữa mép phải. Thanh cuộn gốc của trình duyệt vẫn giữ nguyên.
(function initHomeScrollbar() {
  const home = document.querySelector('.cms-public .cms-home');
  if (!home || document.querySelector('.cms-home-scrollbar')) return;
  const bar = document.createElement('div');
  bar.className = 'cms-home-scrollbar';
  bar.setAttribute('aria-hidden', 'true');
  const thumb = document.createElement('div');
  thumb.className = 'cms-home-scrollbar-thumb';
  bar.appendChild(thumb);
  // Mũi tên lên đầu trang: chỉ hiện khi đã cuộn hết trang, nằm ngay dưới thanh.
  const top = document.createElement('button');
  top.type = 'button';
  top.className = 'cms-home-scrollbar-top';
  top.setAttribute('aria-label', document.documentElement.lang === 'en' ? 'Back to top' : 'Lên đầu trang');
  top.innerHTML = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M3 10l5-5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  top.addEventListener('pointerdown', event => event.stopPropagation());
  top.addEventListener('click', () => window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }));
  bar.appendChild(top);
  document.body.appendChild(bar);
  // Theme khác (nam-binh-duong, ban-de-an) cũng nạp shell.js nhưng không có CSS này: không có kiểu thì bỏ.
  if (getComputedStyle(bar).position !== 'fixed') { bar.remove(); return; }

  const doc = document.documentElement;
  const update = () => {
    const max = doc.scrollHeight - window.innerHeight;
    bar.hidden = max <= 40;
    if (bar.hidden) return;
    const track = bar.clientHeight;
    const size = Math.max(36, Math.round(track * window.innerHeight / doc.scrollHeight));
    top.classList.toggle('is-visible', window.scrollY >= max - 2);
    thumb.style.height = size + 'px';
    thumb.style.transform = 'translateY(' + Math.round((track - size) * Math.min(1, window.scrollY / max)) + 'px)';
  };
  const scrollToPointer = clientY => {
    const rect = bar.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (clientY - rect.top - thumb.offsetHeight / 2) / (rect.height - thumb.offsetHeight)));
    window.scrollTo({ top: ratio * (doc.scrollHeight - window.innerHeight) });
  };
  bar.addEventListener('pointerdown', event => {
    event.preventDefault();
    bar.setPointerCapture(event.pointerId);
    bar.classList.add('is-dragging');
    scrollToPointer(event.clientY);
    const move = e => scrollToPointer(e.clientY);
    const up = () => {
      bar.classList.remove('is-dragging');
      bar.removeEventListener('pointermove', move);
      bar.removeEventListener('pointerup', up);
      bar.removeEventListener('pointercancel', up);
    };
    bar.addEventListener('pointermove', move);
    bar.addEventListener('pointerup', up);
    bar.addEventListener('pointercancel', up);
  });
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  if ('ResizeObserver' in window) new ResizeObserver(update).observe(document.body);
  update();
})();
