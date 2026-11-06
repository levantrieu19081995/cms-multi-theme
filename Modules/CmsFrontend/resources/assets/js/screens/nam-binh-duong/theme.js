// Nam Bình Dương: hành vi nền từ shared/campus-base.js, hiệu ứng từ effects/. Theme chỉ khai báo hook HTML và thông số của mình.
import { initCampusBase, campusHooks } from '../../../shared/campus-base.js';
import { initReveal } from '../../../effects/reveal/index.js';
import { initScrollSpy, initSectionRail } from '../../../effects/active-section/index.js';
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
