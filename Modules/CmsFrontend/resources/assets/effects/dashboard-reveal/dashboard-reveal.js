/*
 * Dashboard reveal: blocks appear one after another in reading order, numbers count up, the side menu drops in from the top once per session.
 * Loaded by CmsBackend as a plain blocking script (layout) so .cms-motion is on <html> before the first paint; no import/export here.
 * Styles: dashboard-reveal.css. Hooks: .cms-reveal (+ data-reveal-col), [data-countup], .cms-admin-links > li. Respects prefers-reduced-motion.
 */
(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;

    const html = document.documentElement, menuKey = 'cms.admin.menu.revealed';
    const openedAt = Date.now(), STEP = .14, OPEN_WINDOW_MS = 2500;
    let menuSeen = false;
    try { menuSeen = sessionStorage.getItem(menuKey) === '1'; } catch {}
    html.classList.add('cms-motion');
    if (menuSeen) html.classList.add('cms-menu-static');

    // Animates the first number inside the node and keeps the text around it ("12 bài viết").
    const countUp = (node, delayMs) => {
        const match = node.textContent.match(/\d[\d.,]*/);
        const target = match ? parseInt(match[0].replace(/\D/g, ''), 10) : NaN;
        if (!Number.isFinite(target) || target <= 0) return;
        const before = node.textContent.slice(0, match.index), after = node.textContent.slice(match.index + match[0].length);
        const width = node.getBoundingClientRect().width;
        if (width) node.style.minWidth = width + 'px';
        node.textContent = before + '0' + after;
        const duration = 1000;
        setTimeout(() => {
            const start = performance.now();
            const tick = now => {
                const t = Math.min(1, (now - start) / duration);
                node.textContent = before + Math.round(target * (1 - Math.pow(1 - t, 4))).toLocaleString('vi-VN') + after;
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }, delayMs);
    };

    const start = () => {
        if (!menuSeen) {
            const links = [...document.querySelectorAll('.cms-admin-links > li')];
            links.forEach((item, index) => item.style.setProperty('--cms-d', (.12 + Math.min(index, 12) * .07) + 's'));
            requestAnimationFrame(() => requestAnimationFrame(() => links.forEach(item => item.classList.add('is-in'))));
            try { sessionStorage.setItem(menuKey, '1'); } catch {}
        }

        // The CSS already hides the top-level blocks of the content area (so nothing flashes before this runs); here they are marked so they get revealed.
        // Pages that mark their own blocks (dashboard) keep them; every other top-level child is shown as is.
        const main = document.querySelector('.cms-admin-main');
        if (main && !main.hasAttribute('data-no-reveal')) {
            const skip = /^(SCRIPT|STYLE|TEMPLATE|LINK|NOSCRIPT)$/;
            const blocksOf = root => [...root.children].filter(el => !skip.test(el.tagName) && !el.hidden && !el.matches('.modal,.offcanvas,.toast,.cms-no-reveal,[aria-hidden="true"]'));
            let top = blocksOf(main);
            if (main.querySelector('.cms-reveal')) {
                top.filter(el => !el.classList.contains('cms-reveal')).forEach(el => el.classList.add('cms-reveal-skip'));
            } else {
                // A single wrapper (container / form) around everything: reveal its children instead.
                if (top.length === 1 && blocksOf(top[0]).length > 1) { top[0].classList.add('cms-reveal-skip'); top = blocksOf(top[0]); }
                top.forEach(el => el.classList.add('cms-reveal'));
            }
        }

        const blocks = [...document.querySelectorAll('.cms-reveal')];
        if (!blocks.length) return;
        const order = new Map(blocks.map((block, index) => [block, index]));
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const block = entry.target;
            observer.unobserve(block);
            const early = Date.now() - openedAt <= OPEN_WINDOW_MS;
            const delay = early ? order.get(block) * STEP : (parseInt(block.dataset.revealCol || '0', 10) || 0) * .12;
            block.style.setProperty('--cms-d', delay + 's');
            block.classList.add('is-in');
            block.querySelectorAll('[data-countup]').forEach(node => countUp(node, delay * 1000 + 250));
        }), { threshold: 0, rootMargin: '0px 0px -8% 0px' });
        blocks.forEach(block => observer.observe(block));
    };
    // Whatever goes wrong, never leave content hidden: dropping .cms-motion shows everything again.
    document.addEventListener('DOMContentLoaded', () => {
        try { start(); } catch (error) { html.classList.remove('cms-motion'); console.error(error); }
    });
})();
