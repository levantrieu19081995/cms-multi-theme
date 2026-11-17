// Ban Đề án: gọi trực tiếp hành vi nền (shared/campus-base.js) và các effect dùng chung (effects/); không import theme Nam Bình Dương.
// Hiệu ứng riêng của theme (nghiêng cảnh, thanh tiến trình cuộn, nút tạm dừng chuyển động) ở lại đây.
import { initCampusBase, campusHooks } from '../../../shared/campus-base.js';
import { prefersReducedMotion } from '../../../effects/core/reduced-motion.js';
import { onScrollFrame } from '../../../effects/core/effect-utils.js';
import { initReveal } from '../../../effects/reveal/index.js';
import { initScrollSpy, initObserverSpy, initSectionRail } from '../../../effects/active-section/index.js';
import { initHeroSlider } from '../../../effects/hero-slider/index.js';

const base = initCampusBase();
if (base) {
  const { root } = base;
  initScrollSpy(root.querySelectorAll(`[${campusHooks.section}]`), { sectionAttr: campusHooks.section, offset: () => base.headerOffset() + 100 });
  initHeroSlider(root, campusHooks.hero);
}
initSectionRail(document.querySelector(`${campusHooks.root} ${campusHooks.rail}`), {
  itemAttr: campusHooks.railItem,
  gate: document.querySelector(`${campusHooks.root} ${campusHooks.railGate}`)
});
initReveal();

const proposalRoot = document.querySelector('.proposal-public');
if (proposalRoot) {
  const progress = proposalRoot.querySelector('.proposal-progress');
  const visual = proposalRoot.querySelector('[data-proposal-visual]');
  const scene = proposalRoot.querySelector('[data-proposal-scene]');
  const motion = proposalRoot.querySelector('[data-proposal-motion]');
  let paused = prefersReducedMotion();
  const syncMotion = () => { proposalRoot.classList.toggle('motion-off', paused); if (motion) { motion.textContent = paused ? (window.cmsTranslations?.['motion_on'] || '▷ Bật hiệu ứng') : (window.cmsTranslations?.['motion_off'] || 'Ⅱ Tạm dừng hiệu ứng'); motion.setAttribute('aria-pressed', String(paused)); } };
  syncMotion();
  motion?.addEventListener('click', () => { paused = !paused; syncMotion(); });
  visual?.addEventListener('pointermove', event => { if (paused || event.pointerType === 'touch' || !scene) return; const box = visual.getBoundingClientRect(); scene.style.transform = `rotateY(${((event.clientX - box.left) / box.width - .5) * 12}deg) rotateX(${(.5 - (event.clientY - box.top) / box.height) * 8}deg)`; });
  visual?.addEventListener('pointerleave', () => { if (scene) scene.style.transform = ''; });
  // Thanh tiến trình cuộn: chỉ đổi transform (compositor), KHÔNG đổi width/không đọc scrollHeight mỗi khung hình, nếu không mỗi lần cuộn trình duyệt phải tính lại
  // toàn bộ bố cục (đã đo: 173 lần tính bố cục cho một lượt cuộn qua trang chủ) và thanh menu dính bị giật. Chiều cao trang đo lại khi đổi cỡ cửa sổ/nội dung.
  if (progress) {
    const page = document.documentElement;
    let range = 0;
    const measure = () => { range = page.scrollHeight - page.clientHeight; };
    measure();
    window.addEventListener('resize', measure);
    if ('ResizeObserver' in window) new ResizeObserver(measure).observe(document.body);
    let shown = -1;
    onScrollFrame(() => {
      const ratio = range > 0 ? Math.min(1, Math.max(0, page.scrollTop / range)) : 0;
      if (Math.abs(ratio - shown) < 0.0005) return;
      shown = ratio;
      progress.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
    });
  }
  initObserverSpy(proposalRoot.querySelectorAll('.cms-nav a[href*="#"]'), { activeClass: 'proposal-active' });
}

// Băng thẻ cuộn ngang (cụm Tin tức, sự kiện): chia trang theo số thẻ nhìn thấy cùng lúc, hiện "01 / 03", thanh đoạn và hai nút mũi tên.
// Thẻ bị bộ lọc ẩn (thuộc tính hidden, do shared/campus-base.js đặt) không được tính.
document.querySelectorAll('[data-bd2-carousel]').forEach(carousel => {
  const track = carousel.querySelector('[data-bd2-track]');
  const pager = carousel.querySelector('[data-bd2-pager]');
  if (!track || !pager) return;
  const pageEl = pager.querySelector('[data-bd2-page]'), pagesEl = pager.querySelector('[data-bd2-pages]'), bars = pager.querySelector('[data-bd2-bars]');
  const pad = n => String(n).padStart(2, '0');
  const visible = () => [...track.children].filter(item => !item.hidden);
  const geometry = () => {
    const items = visible();
    if (!items.length) return { items, perPage: 1, step: track.clientWidth, pages: 1 };
    const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
    const itemWidth = items[0].getBoundingClientRect().width + gap;
    const perPage = Math.max(1, Math.round((track.clientWidth + gap) / itemWidth));
    return { items, perPage, step: itemWidth * perPage, pages: Math.max(1, Math.ceil(items.length / perPage)) };
  };
  let lastPages = 0;
  const update = () => {
    const { step, pages } = geometry();
    const maxScroll = track.scrollWidth - track.clientWidth;
    const page = maxScroll - track.scrollLeft < 4 ? pages - 1 : Math.min(pages - 1, Math.round(track.scrollLeft / step));
    if (pages !== lastPages) { bars.replaceChildren(...Array.from({ length: pages }, () => document.createElement('i'))); lastPages = pages; }
    [...bars.children].forEach((bar, n) => bar.classList.toggle('active', n === page));
    pageEl.textContent = pad(page + 1);
    pagesEl.textContent = pad(pages);
    pager.hidden = pages < 2 && !visible().length;
    pager.classList.toggle('is-single', pages < 2);
    track.classList.toggle('is-centered', track.scrollWidth <= track.clientWidth + 1); // vừa khung (ít hơn hoặc đúng 3 thẻ): canh giữa
  };
  const reduced = prefersReducedMotion();
  pager.querySelectorAll('[data-bd2-move]').forEach(button => button.addEventListener('click', () => {
    const { step, pages } = geometry();
    const current = Math.round(track.scrollLeft / step);
    const target = Math.max(0, Math.min(pages - 1, current + Number(button.dataset.bd2Move)));
    track.scrollTo({ left: target * step, behavior: reduced ? 'auto' : 'smooth' });
  }));
  let queued = false;
  const queue = () => { if (queued) return; queued = true; requestAnimationFrame(() => { queued = false; update(); }); };
  track.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue);
  // Đổi bộ lọc: về đầu băng rồi tính lại số trang (campus-base.js ẩn/hiện thẻ trong cùng lượt click).
  document.querySelectorAll('[data-sbut-news-filter]').forEach(button => button.addEventListener('click', () => { track.scrollTo({ left: 0, behavior: 'auto' }); setTimeout(update, 0); }));
  update();
});

// Coverflow 3D (cụm Video giới thiệu), cùng cơ chế với effects/gallery-orbit (thư viện ảnh): vị trí là số THỰC `pos` (thẻ nào ở giữa = pos làm tròn),
// chạy theo lò xo từng khung hình về `target`; kéo chuột/cảm ứng thì thẻ đi theo tay rồi nhả vào thẻ gần nhất (vuốt ngắn vẫn sang một thẻ);
// tự chuyển thẻ (data-bd2-flow-autoplay = ms, 0 = tắt), dừng khi trỏ chuột/focus/đang kéo/tab bị ẩn; phím ←/→, nút và chấm.
// Mỗi thẻ nhận --o (vị trí so với giữa, âm = bên trái), --abs, --show (chữ trên thẻ giữa), --dim (độ tối của thẻ bên cạnh); CSS lo phần nghiêng/lùi/thu nhỏ.
// Bấm thẻ bên cạnh = đưa vào giữa; bấm thẻ giữa = xem video (liên kết thật); sau một lần kéo thì không tính là bấm.
document.querySelectorAll('[data-bd2-flow]').forEach(flow => {
  const stage = flow.querySelector('[data-bd2-flow-stage]');
  const items = [...flow.querySelectorAll('[data-bd2-flow-item]')];
  if (!stage || !items.length) return;
  const pager = flow.querySelector('[data-bd2-flow-pager]');
  const pageEl = flow.querySelector('[data-bd2-flow-page]');
  const dotsEl = flow.querySelector('[data-bd2-flow-dots]');
  const n = items.length;
  const reduced = prefersReducedMotion();
  const spring = { stiffness: 60, damping: 16, mass: 0.7 }; // như gallery-orbit
  const autoplay = reduced ? 0 : Number(flow.dataset.bd2FlowAutoplay ?? 5000);
  const mod = (value, size) => ((value % size) + size) % size;
  let pos = 0, target = 0, velocity = 0, frame = null, last = 0, shown = -1;
  let hovered = false, timer = null, drag = null, justDragged = false;

  const wrap = delta => { let w = mod(delta, n); if (w > n / 2) w -= n; return w; };
  const front = () => mod(Math.round(target), n);
  const clamp01 = value => Math.max(0, Math.min(1, value));
  const place = () => {
    items.forEach((item, i) => {
      const o = wrap(i - pos), abs = Math.abs(o);
      item.style.setProperty('--o', o.toFixed(3));
      item.style.setProperty('--abs', abs.toFixed(3));
      item.style.setProperty('--show', clamp01(1 - abs * 1.6).toFixed(3));
      item.style.setProperty('--dim', clamp01(abs * 1.2).toFixed(3));
      item.style.opacity = (clamp01((2.5 - abs) / 0.8) * (1 - abs * 0.08)).toFixed(3);
      item.style.zIndex = String(Math.round(100 - abs * 10));
      item.style.pointerEvents = abs > 2.4 ? 'none' : '';
      const active = abs < 0.5;
      item.classList.toggle('is-active', active);
      if (active) item.setAttribute('aria-current', 'true'); else item.removeAttribute('aria-current');
      item.tabIndex = abs > 2.4 ? -1 : 0;
    });
  };
  const status = () => {
    const index = front();
    if (index === shown) return;
    shown = index;
    dots.forEach((dot, i) => { dot.classList.toggle('is-active', i === index); dot.setAttribute('aria-pressed', String(i === index)); });
    if (dotsEl) dotsEl.style.setProperty('--p', n > 1 ? String(index / (n - 1)) : '0');
    if (pageEl) pageEl.textContent = String(index + 1).padStart(2, '0');
  };
  const tick = now => {
    const dt = Math.min(0.05, (now - last) / 1000 || 0.016);
    last = now;
    const force = -spring.stiffness * (pos - target) - spring.damping * velocity;
    velocity += (force / spring.mass) * dt;
    pos += velocity * dt;
    if (Math.abs(pos - target) < 0.001 && Math.abs(velocity) < 0.001) { pos = target; velocity = 0; frame = null; } else frame = requestAnimationFrame(tick);
    place();
  };
  const animate = () => {
    if (reduced) { pos = target; place(); return; }
    if (!frame) { last = performance.now(); frame = requestAnimationFrame(tick); }
  };
  const turnTo = next => { target = next; status(); animate(); };
  const go = index => { let delta = wrap(index - front()); turnTo(Math.round(target) + delta); schedule(); };
  const rotate = direction => { turnTo(Math.round(target) + direction); schedule(); };

  const stopTimer = () => { clearInterval(timer); timer = null; };
  const schedule = () => {
    stopTimer();
    if (autoplay > 0 && n > 1 && !hovered && !drag && !document.hidden) timer = setInterval(() => { if (flow.offsetParent !== null) rotate(1); }, autoplay); // không tự chuyển khi băng thẻ đang bị ẩn (đổi sang giao diện khác)
  };

  const dots = dotsEl ? items.map((item, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.setAttribute('aria-label', item.getAttribute('aria-label') || String(i + 1));
    dot.addEventListener('click', () => go(i));
    dotsEl.append(dot);
    return dot;
  }) : [];

  items.forEach((item, i) => item.addEventListener('click', event => {
    if (justDragged) { event.preventDefault(); return; }
    if (Math.abs(wrap(i - pos)) >= 0.5) { event.preventDefault(); go(i); }
  }));
  flow.querySelectorAll('[data-bd2-flow-move]').forEach(button => button.addEventListener('click', () => rotate(Number(button.dataset.bd2FlowMove))));
  flow.addEventListener('keydown', event => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    rotate(event.key === 'ArrowRight' ? 1 : -1);
  });
  flow.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') { hovered = true; schedule(); } });
  flow.addEventListener('pointerleave', () => { hovered = false; schedule(); });
  flow.addEventListener('focusin', () => { hovered = true; schedule(); });
  flow.addEventListener('focusout', event => { if (!flow.contains(event.relatedTarget)) { hovered = false; schedule(); } });
  document.addEventListener('visibilitychange', schedule);

  // Kéo: thẻ đi theo tay (khoảng cách hai thẻ liền nhau ≈ 60% bề rộng thẻ giữa), nhả ra thì vào thẻ gần nhất.
  const pxPerStep = () => Math.max(120, (items[0].offsetWidth || 400) * 0.6);
  stage.addEventListener('dragstart', event => event.preventDefault());
  stage.addEventListener('pointerdown', event => {
    if (event.button !== 0 || n < 2) return;
    drag = { id: event.pointerId, x: event.clientX, from: target, moved: false };
  });
  stage.addEventListener('pointermove', event => {
    if (!drag || event.pointerId !== drag.id) return;
    const dx = event.clientX - drag.x;
    if (!drag.moved) {
      if (Math.abs(dx) < 6) return;
      drag.moved = true;
      try { stage.setPointerCapture(event.pointerId); } catch { /* con trỏ đã nhả */ }
      flow.classList.add('is-dragging');
      stopTimer();
    }
    target = drag.from - dx / pxPerStep();
    status();
    animate();
  });
  const endDrag = event => {
    if (!drag || event.pointerId !== drag.id) return;
    const { moved, from, x } = drag;
    drag = null;
    flow.classList.remove('is-dragging');
    if (!moved) return;
    justDragged = true;
    setTimeout(() => { justDragged = false; }, 0);
    const dx = event.clientX - x;
    let next = Math.round(target);
    if (next === Math.round(from) && Math.abs(dx) > 24) next += dx < 0 ? 1 : -1; // vuốt ngắn vẫn sang một thẻ
    turnTo(next);
    schedule();
  };
  stage.addEventListener('pointerup', endDrag);
  stage.addEventListener('pointercancel', endDrag);

  if (pager && n < 2) pager.hidden = true;
  place();
  status();
  schedule();
});

// Thanh menu chính: đổi từ nằm trong luồng sang position: fixed khi cuộn qua đỉnh màn hình (CSS: .bd2-menu-slot.is-pinned). Chỉ bật/tắt một class, không đọc
// kích thước mỗi khung hình ngoài một lần getBoundingClientRect trong rAF khi có sự kiện cuộn. Điện thoại không ghim (menu chiếm nhiều hàng).
document.querySelectorAll('[data-bd2-menu-slot]').forEach(slot => {
  const bar = slot.querySelector('.quick-links');
  if (!bar) return;
  const phone = window.matchMedia('(max-width: 760px)');
  let pinned = false, queued = false;
  const measure = () => { if (!pinned) slot.style.minHeight = bar.offsetHeight + 'px'; };
  const update = () => {
    queued = false;
    const should = !phone.matches && slot.getBoundingClientRect().top <= 0;
    if (should !== pinned) { pinned = should; slot.classList.toggle('is-pinned', pinned); }
  };
  const queue = () => { if (!queued) { queued = true; requestAnimationFrame(update); } };
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', () => { if (pinned) { pinned = false; slot.classList.remove('is-pinned'); } measure(); queue(); });
  if ('ResizeObserver' in window) new ResizeObserver(measure).observe(bar);
  measure();
  update();
});

// Chuyển giao diện danh sách (cụm Đối tác): các nút [data-bd2-view-btn="tên"] trong cùng <section> bật panel [data-bd2-view-panel="tên"] của [data-bd2-views], ẩn các panel còn lại.
// Section được gắn data-bd2-view="<tên>" để CSS đổi kiểu theo. Lựa chọn được nhớ trong localStorage (khóa data-bd2-views-key); localStorage bị chặn thì vẫn chạy bình thường, chỉ không nhớ. Thêm giao diện mới = thêm nút + panel, không sửa đoạn này.
document.querySelectorAll('[data-bd2-views]').forEach(views => {
  const root = views.closest('section') || document;
  const buttons = [...root.querySelectorAll('[data-bd2-view-btn]')];
  const panels = [...views.querySelectorAll('[data-bd2-view-panel]')];
  if (!buttons.length || !panels.length) return;
  const key = views.dataset.bd2ViewsKey || '';
  const names = panels.map(panel => panel.dataset.bd2ViewPanel);
  const apply = (wanted, persist) => {
    const name = names.includes(wanted) ? wanted : names[0];
    panels.forEach(panel => { panel.hidden = panel.dataset.bd2ViewPanel !== name; });
    if (root.dataset) root.dataset.bd2View = name; // CSS đổi kiểu tiêu đề theo giao diện đang bật (section[data-bd2-view="grid"])
    buttons.forEach(button => {
      const on = button.dataset.bd2ViewBtn === name;
      button.classList.toggle('is-active', on);
      button.setAttribute('aria-pressed', String(on));
    });
    if (persist && key) { try { localStorage.setItem(key, name); } catch (error) { /* không nhớ được thì thôi */ } }
  };
  buttons.forEach(button => button.addEventListener('click', () => apply(button.dataset.bd2ViewBtn, true)));
  let saved = null;
  try { saved = key ? localStorage.getItem(key) : null; } catch (error) { saved = null; }
  apply(saved, false);
});

// Sơ đồ kết nối (cụm Đối tác, giao diện "grid"): mỗi [data-bd2-hub-node] ứng với một đường nối [data-bd2-hub-line] (<path> trong <svg class="bd2-hub-lines">) cùng thứ tự.
// Đường nối bẻ góc vuông, vẽ theo vị trí thật của nút và lõi (đo bằng getBoundingClientRect, vẽ lại khi đổi kích thước hoặc khi sơ đồ vừa hiện ra): nút lẻ ở cột trái, nút chẵn ở cột phải.
//  - nút nằm ngang tầm lõi: một đoạn thẳng từ mép nút tới mép lõi;
//  - nút nằm cao/thấp hơn lõi: đi ngang từ mép nút tới điểm nối (cách mép lõi 27% bề rộng lõi) rồi rẽ dọc vào cạnh trên/dưới của lõi.
// Nút đang sáng (is-active) tự chạy vòng mỗi ~3,2 giây, đường nối của nó chuyển vàng và được đưa lên trên cùng; trỏ chuột/focus vào nút nào thì nút đó sáng và dừng chạy.
// Không chạy khi sơ đồ đang ẩn, tab bị ẩn hoặc người dùng chọn giảm chuyển động (khi đó nút đầu tiên sáng cố định).
document.querySelectorAll('[data-bd2-hub]').forEach(hub => {
  const body = hub.querySelector('.bd2-hub-body');
  const svg = hub.querySelector('.bd2-hub-lines');
  const core = hub.querySelector('.bd2-hub-core');
  const nodes = [...hub.querySelectorAll('[data-bd2-hub-node]')];
  const lines = [...hub.querySelectorAll('[data-bd2-hub-line]')];
  if (!nodes.length || !body) return;
  const reduced = prefersReducedMotion();
  let index = 0, timer = null, held = false;

  const draw = () => {
    if (!svg || !core || getComputedStyle(svg).display === 'none') return;
    const base = body.getBoundingClientRect();
    if (!base.width) return;
    const c = core.getBoundingClientRect();
    const coreL = c.left - base.left, coreR = c.right - base.left, coreT = c.top - base.top, coreB = c.bottom - base.top;
    svg.setAttribute('viewBox', `0 0 ${Math.round(base.width)} ${Math.round(base.height)}`);
    nodes.forEach((node, i) => {
      const path = lines[i];
      if (!path) return;
      const r = node.getBoundingClientRect();
      const left = i % 2 === 0;
      const y = r.top - base.top + r.height / 2;
      const x = (left ? r.right : r.left) - base.left;
      let d;
      if (y >= coreT + 16 && y <= coreB - 16) {
        d = `M${x.toFixed(1)},${y.toFixed(1)}H${(left ? coreL : coreR).toFixed(1)}`;
      } else {
        const ax = left ? coreL + c.width * 0.27 : coreR - c.width * 0.27;
        d = `M${x.toFixed(1)},${y.toFixed(1)}H${ax.toFixed(1)}V${(y < coreT ? coreT : coreB).toFixed(1)}`;
      }
      path.setAttribute('d', d);
    });
  };

  const show = next => {
    index = next;
    nodes.forEach((node, i) => node.classList.toggle('is-active', i === next));
    lines.forEach((line, i) => line.classList.toggle('is-active', i === next));
    if (lines[next] && lines[next].parentNode) lines[next].parentNode.appendChild(lines[next]); // đường đang sáng vẽ sau cùng = nằm trên các đường khác
  };
  const stop = () => { clearInterval(timer); timer = null; };
  const start = () => {
    stop();
    if (reduced || nodes.length < 2 || held || document.hidden) return;
    timer = setInterval(() => { if (hub.offsetParent !== null) show((index + 1) % nodes.length); }, 3200);
  };
  nodes.forEach((node, i) => {
    const hold = () => { held = true; show(i); stop(); };
    const release = () => { held = false; start(); };
    node.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') hold(); });
    node.addEventListener('pointerleave', release);
    node.addEventListener('focus', hold);
    node.addEventListener('blur', release);
  });
  document.addEventListener('visibilitychange', start);
  if ('ResizeObserver' in window) new ResizeObserver(draw).observe(body); else window.addEventListener('resize', draw);
  document.fonts && document.fonts.ready && document.fonts.ready.then(draw);
  draw();
  show(0);
  start();
});
