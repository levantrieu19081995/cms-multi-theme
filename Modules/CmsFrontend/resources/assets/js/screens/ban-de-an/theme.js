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
  onScrollFrame(() => { if (progress) { const page = document.documentElement; const range = page.scrollHeight - page.clientHeight; progress.style.width = (range > 0 ? page.scrollTop / range * 100 : 0) + '%'; } });
  initObserverSpy(proposalRoot.querySelectorAll('.cms-nav a[href*="#"]'), { activeClass: 'proposal-active' });
}
