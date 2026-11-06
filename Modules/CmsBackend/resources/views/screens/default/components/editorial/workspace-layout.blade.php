<script>
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.cms-admin-main.cms-workspace-page');
    if (!main) return;

    const workspace = @json($workspace);
    const directColumns = main.querySelectorAll(':scope > .row > [class*="col-"]');
    directColumns.forEach(column => column.classList.add('cms-workspace-column'));

    const prepareSettingForms = () => {
        main.querySelectorAll('.card-body form').forEach(form => {
            form.classList.add('cms-settings-form');
            Array.from(form.children).forEach(row => {
                if (!row.classList.contains('row')) return;
                row.classList.add('cms-setting-field');
                if (row.querySelector('textarea')) row.classList.add('cms-setting-wide');
                const hasControl = row.querySelector('input:not([type="hidden"]), select, textarea');
                const hasAction = row.querySelector('button[type="submit"], a.btn');
                if (!hasControl && hasAction) row.classList.add('cms-setting-actions');
            });
        });
    };

    if (workspace.startsWith('settings-')) {
        prepareSettingForms();
        main.dataset.cmsWorkspaceReady = 'true';
        return;
    }

    const form = workspace === 'role'
        ? main.querySelector('#role-form')
        : main.querySelector(':scope > .row form');
    const card = form?.querySelector(':scope > .card');
    const bodies = card ? Array.from(card.querySelectorAll(':scope > .card-body')) : [];
    const body = bodies[0];
    if (!form || !card || !body) return;

    form.classList.add('cms-workspace-form');
    form.closest('[class*="col-"]')?.classList.add('cms-workspace-column');

    const cardHeader = card.querySelector(':scope > .card-header');
    const title = cardHeader?.textContent.trim() || (workspace === 'role' ? @json(__('admin.menu.vai_tro')) : @json(__('admin.menu.nguoi_dung')));
    const heading = document.createElement('div');
    heading.className = 'cms-workspace-heading';
    const intro = document.createElement('div');
    const headingTitle = document.createElement('h1');
    headingTitle.textContent = title;
    const note = document.createElement('p');
    note.textContent = workspace === 'role'
        ? @json(__('admin.js.thiet_lap_thong_tin_vai_tro_va_cac_quyen_duoc_ph'))
        : @json(__('admin.js.cap_nhat_tai_khoan_vai_tro_va_pham_vi_chuyen_muc'));
    intro.append(headingTitle, note);
    const actions = document.createElement('div');
    actions.className = 'cms-workspace-actions';

    const footer = bodies.length > 1 ? bodies[bodies.length - 1] : null;
    if (footer && footer !== body) {
        footer.querySelectorAll('a.btn, button.btn').forEach(button => actions.append(button));
        const separator = footer.previousElementSibling;
        footer.remove();
        if (separator?.tagName === 'HR') separator.remove();
    }
    heading.append(intro, actions);
    form.insertBefore(heading, card);
    cardHeader?.remove();

    const sections = document.createElement('div');
    sections.className = 'cms-workspace-sections';
    let section = null;
    Array.from(body.children).forEach(child => {
        if (child.tagName === 'H5') {
            section = document.createElement('section');
            section.className = `cms-workspace-section cms-workspace-section-${sections.children.length + 1}`;
            child.classList.add('cms-workspace-section-title');
            section.append(child);
            sections.append(section);
            return;
        }
        if (!section) return;
        if (child.tagName === 'HR' || child.tagName === 'BR') {
            child.remove();
            return;
        }
        section.append(child);
    });
    body.append(sections);
    main.dataset.cmsWorkspaceReady = 'true';
});
</script>
