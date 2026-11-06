{{-- Keep inherited controls and AJAX targets while adopting the common list components. --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.cms-editorial-admin .cms-list-page');
    if (!main) return;
    main.querySelectorAll('.breadcrumb').forEach(crumb => (crumb.closest('nav') || crumb).remove());
    const table = Array.from(main.querySelectorAll('.table')).find(item => !item.closest('.modal,dialog'));
    if (!table) return;
    const card = table.closest('.card');
    if (!card) return;
    let panel = card;
    while (panel.parentElement.closest('.card')) panel = panel.parentElement.closest('.card');
    panel.classList.add('cms-index-card');
    const heading = main.querySelector('.cms-page-heading'), oldHeading = panel.querySelector('.card-header');
    if (oldHeading && !oldHeading.querySelector('a,button,input,select')) oldHeading.remove();
    const discardEmptyGroup = node => {
        if (node?.matches('.d-grid,.d-flex,.row') && !node.children.length && !node.textContent.trim()) node.remove();
    };
    if (heading) {
        const create = Array.from(panel.querySelectorAll('a.btn[href]')).find(link =>
            !link.closest('tbody,.modal,dialog') && /\/create\/?$/.test(new URL(link.href, location.href).pathname));
        if (create) { const oldGroup = create.parentElement; heading.append(create); discardEmptyGroup(oldGroup); }
    }
    panel.querySelectorAll('form').forEach(form => {
        if (form.closest('.modal,dialog,tbody') || form.method.toLowerCase() === 'post' || form.querySelector('input[name="_method"],input[type="password"]') || form.classList.contains('cms-filter-panel')) return;
        const input = form.querySelector('input[type="search"],input[name="filter"]');
        const group = input?.closest('.input-group');
        if (!group) {
            if (form.querySelector('input:not([type="hidden"]),select')) form.classList.add('cms-filter-panel', 'cms-legacy-filter');
            return;
        }
        form.classList.add('cms-filter-panel', 'cms-legacy-filter');
        const oldParent = group.parentElement, row = document.createElement('div'), field = document.createElement('div'), label = document.createElement('label');
        row.className = 'cms-search-row'; field.className = 'cms-search-field'; label.className = 'cms-search-label';
        label.textContent = @json(__('admin.ui.search')); if (input.id) label.htmlFor = input.id;
        // Move the existing nodes so search handlers, field values and the advanced filter remain intact.
        form.prepend(row); field.append(label, group); row.append(field);
        // Filters that sat next to the search box (status, category, dates) join the row, labelled like the search field.
        Array.from(oldParent.children).filter(el => el.matches('.form-floating')).forEach(floating => {
            const control = floating.querySelector('select,input'), inner = floating.querySelector(':scope > label');
            const extra = document.createElement('div'), extraLabel = document.createElement('label');
            extra.className = 'cms-search-field cms-filter-field'; extraLabel.className = 'cms-search-label';
            extraLabel.textContent = inner?.textContent.trim() || ''; if (control?.id) extraLabel.htmlFor = control.id;
            inner?.remove(); extra.append(extraLabel, floating); row.append(extra);
        });
        const submit = form.querySelector('button[type="submit"],button:not([type]),input[type="submit"]');
        if (submit) {
            const oldSubmitParent = submit.parentElement;
            row.append(submit); submit.classList.remove('btn-outline-secondary', 'bg-white', 'w-100', 'h-100'); submit.classList.add('btn-outline-primary');
            discardEmptyGroup(oldSubmitParent);
        }
        discardEmptyGroup(oldParent);
    });
});
</script>
