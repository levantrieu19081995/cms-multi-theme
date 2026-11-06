// Gallery orbit: ảnh thu nhỏ xếp trên một vòng nghiêng 3D (nhìn như hình elip), vòng quay bằng lò xo (tính từng khung hình trong JS,
// không dựa vào CSS transition); ảnh ở phía trước vòng được phóng to ở giữa (FLIP). Tự xoay, nút ‹ ›, phím mũi tên, kéo bằng chuột/cảm ứng.
// Port của 21st.dev Carousel360. CSS cơ chế: effects/gallery-orbit/style.css; kích thước/màu của ảnh thuộc theme.
//
// Hook HTML (mặc định là hook hiện có, theme có thể đổi bằng options.hooks): khung [data-cms-ring], sân khấu .cms-ring-stage,
// mỗi mục [data-cms-ring-item] (data-title, data-count, data-image + href) chứa .cms-ring-thumb, ảnh giữa [data-cms-ring-center]
// với [data-cms-ring-image|title|count], trạng thái đọc cho màn hình [data-cms-ring-status], nút tạm dừng [data-cms-ring-toggle],
// nhóm nút .cms-ring-controls, nút bước [data-cms-ring-step="1|-1"].
// Biến CSS do effect ghi: --cms-ring-perspective, --cms-ring-orbit-y, --cms-ring-center-top; theme đặt --cms-ring-thumb (cạnh ảnh nhỏ).
import { clamp, markOnce } from '../core/effect-utils.js';

const defaults = {
  interval: 2400,        // ms giữa hai lần tự xoay
  tilt: 24,              // độ nghiêng vòng về phía người xem (deg)
  thumbTilt: 18,         // ảnh nhỏ ngả ra sau một chút
  spring: { stiffness: 60, damping: 16, mass: 0.7 },
  pixelsPerStep: 140,    // quãng kéo làm vòng quay một album
  flip: { duration: 900, easing: 'cubic-bezier(.45, 0, .2, 1)' },
  hooks: {
    ring: '[data-cms-ring]', stage: '.cms-ring-stage', thumb: '.cms-ring-thumb', controls: '.cms-ring-controls',
    itemAttr: 'data-cms-ring-item', centerAttr: 'data-cms-ring-center', imageAttr: 'data-cms-ring-image', titleAttr: 'data-cms-ring-title',
    countAttr: 'data-cms-ring-count', statusAttr: 'data-cms-ring-status', toggleAttr: 'data-cms-ring-toggle', stepAttr: 'data-cms-ring-step'
  },
  classes: { ready: 'is-ready', dragging: 'is-dragging', ghost: 'cms-ring-ghost' },
  labels: {
    albumPhotos: (title, count) => `${title} – ${count} ảnh`,
    status: (index, total, title) => `Album ${index}/${total}: ${title}`,
    resume: 'Tiếp tục tự xoay',
    pause: 'Tạm dừng tự xoay'
  },
  icons: { play: 'ri-play-line', pause: 'ri-pause-line' }
};

// Trả về mảng { destroy } cho mỗi vòng được khởi tạo (rỗng nếu trang không có vòng nào). Gọi lại không tạo trùng listener/timer.
export function initGalleryOrbit(scope = document, options = {}) {
  const o = { ...defaults, ...options, hooks: { ...defaults.hooks, ...options.hooks }, classes: { ...defaults.classes, ...options.classes }, labels: { ...defaults.labels, ...options.labels }, icons: { ...defaults.icons, ...options.icons }, spring: { ...defaults.spring, ...options.spring }, flip: { ...defaults.flip, ...options.flip } };
  if (!scope) return [];
  const rings = scope.matches?.(o.hooks.ring) ? [scope] : [...scope.querySelectorAll(o.hooks.ring)];
  return rings.map(ring => createOrbit(ring, o)).filter(Boolean);
}

function createOrbit(ring, o) {
  const { hooks: h, classes: c } = o;
  const stage = ring.querySelector(h.stage);
  const items = [...ring.querySelectorAll(`[${h.itemAttr}]`)];
  const thumbs = items.map(item => item.querySelector(h.thumb));
  const center = ring.querySelector(`[${h.centerAttr}]`);
  const image = ring.querySelector(`[${h.imageAttr}]`);
  const title = ring.querySelector(`[${h.titleAttr}]`);
  const count = ring.querySelector(`[${h.countAttr}]`);
  const status = ring.querySelector(`[${h.statusAttr}]`);
  const toggle = ring.querySelector(`[${h.toggleAttr}]`);
  const controls = ring.querySelector(h.controls);
  if (!stage || !center || items.length < 2 || !markOnce(ring, 'galleryOrbitBound')) return null;

  const step = 360 / items.length;
  const { tilt: TILT, thumbTilt: THUMB_TILT } = o;
  let radius = 220;
  let target = 0; // ring rotation we are heading to (deg, unbounded)
  let angle = 0; // current animated rotation
  let velocity = 0;
  let frame = null;
  let last = 0;
  let shown = -1;
  let paused = false;
  let hovered = false;
  let timer = null;
  const cleanups = [];
  const listen = (el, type, handler, opts) => { el.addEventListener(type, handler, opts); cleanups.push(() => el.removeEventListener(type, handler, opts)); };

  const frontIndex = rotation => ((-Math.round(rotation / step) % items.length) + items.length) % items.length;
  const place = () => {
    thumbs.forEach((thumb, i) => {
      const theta = angle + i * step;
      const depth = (Math.cos(theta * Math.PI / 180) + 1) / 2; // 1 = front, 0 = back
      thumb.style.transform = `rotateX(${-TILT}deg) rotateY(${theta}deg) translateZ(${radius}px) rotateY(${-theta}deg) rotateX(${TILT}deg) rotateX(${THUMB_TILT}deg) scale(${(0.7 + 0.3 * depth).toFixed(3)})`;
      // The front album is the one shown large in the centre, so its thumbnail fades out near the front.
      const nearFront = clamp((1 - depth) / 0.1);
      thumb.style.opacity = ((0.35 + 0.65 * depth) * nearFront).toFixed(3);
      thumb.style.pointerEvents = nearFront < 0.2 ? 'none' : '';
      thumb.style.zIndex = String(Math.round(depth * 100));
    });
  };
  const showCenter = () => {
    const index = frontIndex(target);
    if (index === shown) return;
    const first = shown === -1;
    shown = index;
    const item = items[index];
    const apply = () => {
      center.href = item.href;
      if (title) title.textContent = item.dataset.title || '';
      if (count) count.textContent = item.dataset.count || '0';
      if (image) {
        image.hidden = !item.dataset.image;
        if (item.dataset.image) image.src = item.dataset.image;
      }
      center.setAttribute('aria-label', o.labels.albumPhotos(item.dataset.title, item.dataset.count));
      if (status) status.textContent = o.labels.status(index + 1, items.length, item.dataset.title);
    };
    if (first || !center.animate) { apply(); return; }

    // FLIP: the new album grows out of its ring thumbnail (position 1 or 3) into the centre, while the
    // previous centre image shrinks away towards the opposite side of the ring.
    const stageBox = stage.getBoundingClientRect();
    const from = thumbs[index].getBoundingClientRect();
    const oldBox = center.getBoundingClientRect();
    const ghost = center.cloneNode(true);
    ghost.removeAttribute(h.centerAttr);
    ghost.querySelectorAll(`[${h.imageAttr}],[${h.titleAttr}],[${h.countAttr}]`).forEach(node => {
      node.removeAttribute(h.imageAttr); node.removeAttribute(h.titleAttr); node.removeAttribute(h.countAttr);
    });
    ghost.setAttribute('aria-hidden', 'true');
    ghost.tabIndex = -1;
    ghost.classList.add(c.ghost);
    stage.append(ghost);
    apply();
    const to = center.getBoundingClientRect();
    const fromCenterX = from.left + from.width / 2;
    const mirrorX = 2 * (stageBox.left + stageBox.width / 2) - fromCenterX; // where the old album goes
    const frames = (box, x, y, scale) => `translate(${x - (box.left + box.width / 2)}px, ${y - (box.top + box.height / 2)}px) scale(${scale})`;
    // Ease in-out so the zoom keeps pace with the ring's spring instead of snapping in.
    const flip = { duration: o.flip.duration, easing: o.flip.easing };
    center.animate([
      { transform: frames(to, fromCenterX, from.top + from.height / 2, from.width / to.width), opacity: 0.6, borderRadius: '40px' },
      { transform: 'none', opacity: 1, borderRadius: getComputedStyle(center).borderRadius },
    ], flip);
    ghost.animate([
      { transform: 'none', opacity: 1 },
      { opacity: 0.85, offset: 0.5 },
      { transform: frames(oldBox, mirrorX, from.top + from.height / 2, from.width / oldBox.width), opacity: 0 },
    ], flip).finished.finally(() => ghost.remove());
  };
  const tick = now => {
    const dt = Math.min(0.05, (now - last) / 1000 || 0.016);
    last = now;
    // Spring towards the target angle.
    const force = -o.spring.stiffness * (angle - target) - o.spring.damping * velocity;
    velocity += (force / o.spring.mass) * dt;
    angle += velocity * dt;
    if (Math.abs(angle - target) < 0.02 && Math.abs(velocity) < 0.02) {
      angle = target;
      velocity = 0;
      frame = null;
    } else {
      frame = requestAnimationFrame(tick);
    }
    place();
  };
  const animate = () => {
    if (!frame) { last = performance.now(); frame = requestAnimationFrame(tick); }
  };
  const turnTo = nextTarget => { target = nextTarget; showCenter(); animate(); };
  const rotate = direction => turnTo(target + direction * step);
  const resize = () => {
    const width = stage.offsetWidth;
    radius = Math.max(120, Math.min(440, width * (width < 480 ? 0.4 : width < 860 ? 0.41 : 0.49)));
    ring.style.setProperty('--cms-ring-perspective', `${Math.round(radius * 2.4)}px`);
    // Drop the large image just below the far arc of the ring so the circle stays visible above it, then
    // centre the whole group in the stage. Positions are measured (perspective included), not estimated.
    ring.style.setProperty('--cms-ring-orbit-y', '0px');
    place();
    const stageBox = stage.getBoundingClientRect();
    const middle = stageBox.top + stageBox.height / 2;
    let farBottom = -Infinity;
    let groupTop = Infinity;
    thumbs.forEach((thumb, i) => {
      const box = thumb.getBoundingClientRect();
      const depth = (Math.cos((angle + i * step) * Math.PI / 180) + 1) / 2;
      if (depth < 0.35) farBottom = Math.max(farBottom, box.bottom - middle);
      groupTop = Math.min(groupTop, box.top - middle);
    });
    const centerTop = farBottom + 12;
    const groupBottom = centerTop + center.offsetHeight;
    const shift = -(groupTop + groupBottom) / 2;
    ring.style.setProperty('--cms-ring-orbit-y', `${Math.round(shift)}px`);
    ring.style.setProperty('--cms-ring-center-top', `${Math.round(centerTop + shift)}px`);
    stage.style.height = `${Math.round(groupBottom - groupTop + 32)}px`;
    place();
  };
  const stopTimer = () => { clearInterval(timer); timer = null; };
  const schedule = () => {
    stopTimer();
    if (!paused && !hovered && !document.hidden) timer = setInterval(() => rotate(1), o.interval);
  };

  items.forEach((item, index) => {
    item.tabIndex = -1;
    item.setAttribute('aria-hidden', 'true');
    // Clicking a thumbnail turns the ring the short way until that album is in front.
    listen(item, 'click', event => {
      event.preventDefault();
      let delta = (frontIndex(target) - index) % items.length;
      if (delta > items.length / 2) delta -= items.length;
      if (delta < -items.length / 2) delta += items.length;
      turnTo(target + delta * step);
      schedule();
    });
  });
  ring.classList.add(c.ready);
  center.hidden = false;
  if (controls) controls.hidden = false;
  ring.querySelectorAll(`[${h.stepAttr}]`).forEach(button => listen(button, 'click', () => {
    rotate(Number(button.getAttribute(h.stepAttr)));
    schedule();
  }));
  const setPaused = value => {
    paused = value;
    if (toggle) {
      toggle.setAttribute('aria-pressed', String(paused));
      toggle.setAttribute('aria-label', paused ? o.labels.resume : o.labels.pause);
      toggle.innerHTML = `<i class="${paused ? o.icons.play : o.icons.pause}" aria-hidden="true"></i>`;
    }
    schedule();
  };
  if (toggle) listen(toggle, 'click', () => setPaused(!paused));
  // Auto-rotation pauses while the visitor points at or focuses the carousel, and while the tab is hidden;
  // the pause button stops it for good.
  listen(ring, 'pointerenter', event => { if (event.pointerType === 'mouse') { hovered = true; schedule(); } });
  listen(ring, 'pointerleave', () => { hovered = false; schedule(); });
  listen(ring, 'focusin', () => { hovered = true; schedule(); });
  listen(ring, 'focusout', event => { if (!ring.contains(event.relatedTarget)) { hovered = false; schedule(); } });
  listen(document, 'visibilitychange', schedule);
  listen(ring, 'keydown', event => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    rotate(event.key === 'ArrowRight' ? 1 : -1);
  });
  // Direct drag (mouse or touch) anywhere on the stage, including the large image: the ring follows the
  // pointer, then snaps to the nearest album on release. A short flick still moves one album, like ‹ / ›.
  const PX_PER_STEP = o.pixelsPerStep;
  let drag = null;
  let justDragged = false;
  listen(stage, 'dragstart', event => event.preventDefault()); // no native image/link dragging
  listen(stage, 'pointerdown', event => {
    if (event.button !== 0) return;
    drag = { id: event.pointerId, x: event.clientX, from: target, moved: false };
  });
  listen(stage, 'pointermove', event => {
    if (!drag || event.pointerId !== drag.id) return;
    const dx = event.clientX - drag.x;
    if (!drag.moved) {
      if (Math.abs(dx) < 6) return;
      drag.moved = true;
      try { stage.setPointerCapture(event.pointerId); } catch { /* pointer already released */ }
      ring.classList.add(c.dragging);
      stopTimer();
    }
    target = drag.from + (dx / PX_PER_STEP) * step;
    animate();
  });
  const endDrag = event => {
    if (!drag || event.pointerId !== drag.id) return;
    const { moved, from, x } = drag;
    drag = null;
    ring.classList.remove(c.dragging);
    if (!moved) return;
    justDragged = true;
    setTimeout(() => { justDragged = false; }, 0);
    const dx = event.clientX - x;
    let steps = Math.round(dx / PX_PER_STEP);
    if (steps === 0 && Math.abs(dx) > 30) steps = dx > 0 ? 1 : -1; // flick
    turnTo(from + steps * step);
    schedule();
  };
  listen(stage, 'pointerup', endDrag);
  listen(stage, 'pointercancel', endDrag);
  // A drag must not also open the album link or trigger the thumbnail's click.
  listen(ring, 'click', event => {
    if (justDragged) { event.preventDefault(); event.stopPropagation(); }
  }, true);
  listen(window, 'resize', resize);
  resize();
  showCenter();
  setPaused(false);

  return {
    destroy() {
      stopTimer();
      if (frame) cancelAnimationFrame(frame);
      frame = null;
      cleanups.forEach(fn => fn());
      delete ring.dataset.galleryOrbitBound;
    }
  };
}
