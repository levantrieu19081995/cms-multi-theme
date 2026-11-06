// Đánh dấu mục đang xem theo vị trí cuộn. Mọi hook HTML/class do theme truyền vào; ở đây không có tên theme.
import { markOnce, onScrollFrame } from '../core/effect-utils.js';

// Mục menu nào có phần tử đích nằm trên "vạch" (đỉnh viewport + offset()) gần nhất thì active.
//   links: NodeList/array các liên kết; sectionAttr: tên thuộc tính chứa id phần tử đích; offset(): px (vd. chiều cao thanh dính + 100).
export function initScrollSpy(links, { sectionAttr, activeClass = 'active', offset = () => 100 } = {}) {
  const nav = [...links];
  if (!sectionAttr || !nav.length || !markOnce(nav[0], 'scrollSpyBound')) return null;
  const sections = nav.map(a => document.getElementById(a.getAttribute(sectionAttr))).filter(Boolean);
  const activate = () => {
    let id = sections[0]?.id;
    const edge = offset();
    for (const section of sections) { if (section.getBoundingClientRect().top <= edge) id = section.id; }
    nav.forEach(a => {
      const active = a.getAttribute(sectionAttr) === id && sections.length > 0;
      a.classList.toggle(activeClass, active);
      if (active) a.setAttribute('aria-current', 'location'); else a.removeAttribute('aria-current');
    });
  };
  onScrollFrame(activate);
  activate();
  return { refresh: activate };
}

// Như trên nhưng theo IntersectionObserver: liên kết trỏ tới #id của phần đang cắt dải giữa màn hình thì thêm activeClass.
export function initObserverSpy(links, { activeClass = 'active', rootMargin = '-25% 0px -65%' } = {}) {
  const nav = [...links];
  if (!nav.length || !('IntersectionObserver' in window) || !markOnce(nav[0], 'observerSpyBound')) return null;
  const hashOf = link => { try { return new URL(link.href, location.href).hash; } catch { return ''; } };
  const sections = nav.map(link => { const hash = hashOf(link); return hash ? document.querySelector(hash) : null; }).filter(Boolean);
  if (!sections.length) return null;
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue;
      nav.forEach(link => link.classList.toggle(activeClass, hashOf(link) === '#' + entry.target.id));
    }
  }, { rootMargin });
  sections.forEach(section => observer.observe(section));
  return { observer };
}

// Cột mục lục cố định: mục hiện lần lượt khi đỉnh cụm tới giữa màn hình, mục "đầu trang" hiện gần cuối trang.
//   itemAttr: thuộc tính đích của mỗi mục; topValue: giá trị nghĩa là "lên đầu trang"; gate: phần tử mà sau khi cuộn khỏi (bottom <= 0) cột mới bật.
// Class is-in / is-current và độ trễ so le do effect đặt; kiểu dáng và chuyển động thuộc CSS của theme.
export function initSectionRail(rail, { itemAttr, topValue = 'top', gate = null, stagger = .08 } = {}) {
  if (!rail || !itemAttr || !markOnce(rail, 'sectionRailBound')) return null;
  const items = [...rail.querySelectorAll(`[${itemAttr}]`)]
    .map(link => ({ link, target: link.getAttribute(itemAttr) === topValue ? null : document.getElementById(link.getAttribute(itemAttr)) }))
    .filter(item => item.link.getAttribute(itemAttr) === topValue || item.target);

  const update = () => {
    const vh = window.innerHeight, on = !gate || gate.getBoundingClientRect().bottom <= 0, atEnd = window.scrollY + vh >= document.documentElement.scrollHeight - vh * .25, show = [], hide = [];
    let current = null;
    items.forEach(item => {
      const shown = on && (item.target ? item.target.getBoundingClientRect().top <= vh * .5 : atEnd);
      if (shown && item.target) current = item;
      if (item.link.classList.contains('is-in') !== shown) (shown ? show : hide).push(item);
    });
    show.forEach((item, k) => {
      item.link.style.setProperty('transition-delay', (k * stagger) + 's', 'important');
      item.link.classList.add('is-in');
      item.link.removeAttribute('tabindex');
      item.link.removeAttribute('aria-hidden');
    });
    hide.forEach((item, k) => {
      item.link.style.setProperty('transition-delay', ((hide.length - 1 - k) * stagger) + 's', 'important');
      item.link.classList.remove('is-in');
      item.link.setAttribute('tabindex', '-1');
      item.link.setAttribute('aria-hidden', 'true');
    });
    items.forEach(item => {
      const active = item === current;
      item.link.classList.toggle('is-current', active);
      if (active) item.link.setAttribute('aria-current', 'location'); else item.link.removeAttribute('aria-current');
    });
  };

  items.forEach(item => { item.link.setAttribute('tabindex', '-1'); item.link.setAttribute('aria-hidden', 'true'); });
  onScrollFrame(update);
  window.addEventListener('resize', update);
  update();
  return { refresh: update };
}
