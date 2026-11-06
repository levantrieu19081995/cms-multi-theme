{{-- Adapt inherited CMS forms without cloning controls, changing their form owner or changing business rules. --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.cms-editorial-admin .cms-record-page');
    if (!main || main.querySelector('.cms-news-heading,.cms-native-record')) return;
    const record = @json($record);
    // Legacy buttons are recognised by their label, so the words searched for are the ones of the current admin language.
    const words = list => new RegExp(list.map(word => String(word).trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|'), 'i');
    const backWords = words([@json(__('admin.ui.tro_ve')), @json(__('admin.ui.quay_lai')), @json(__('admin.ui.huy_bo'))]);
    const draftWords = words([@json(__('admin.ui.luu_nhap'))]);
    const saveWords = words([@json(__('admin.ui.luu')), @json(__('admin.ui.cap_nhat')), @json(__('admin.ui.them'))]);
    const attachmentWords = words([@json(__('admin.ui.tap_tin_dinh_kem'))]);
    const forms = Array.from(main.querySelectorAll('form')).filter(form => !form.closest('.modal,dialog') && form.method.toLowerCase() === 'post');
    const form = forms.find(form => form.querySelector('input:not([type="hidden"]),textarea,select')) || forms[0];
    const host = form || main;
    const heading = document.createElement('div'), info = document.createElement('div'), title = document.createElement('h1'), note = document.createElement('p'), actions = document.createElement('div');
    heading.className = 'cms-page-heading cms-record-heading'; actions.className = 'cms-form-actions'; note.className = 'cms-heading-note';
    title.textContent = record.title;
    note.textContent = record.mode === 'show' ? @json(__('admin.js.thong_tin_chi_tiet_va_noi_dung_da_luu')) : @json(__('admin.js.cac_truong_co_dau_can_dien_day_du_truoc_khi_luu'));
    info.append(title, note); heading.append(info, actions);
    // Retain old wrappers containing interactive controls and remove only the duplicated main title.
    const oldTitle = Array.from(main.querySelectorAll('.card-header h4,.card-header>h5,h1')).find(el => !el.closest('.modal,dialog'));
    if (oldTitle) {
        const header = oldTitle.closest('.card-header'); oldTitle.remove();
        if (header && !header.querySelector('a,button,input,select,textarea') && !header.textContent.trim()) header.remove();
    }
    const returnLink = Array.from(main.querySelectorAll('a.btn[href]')).find(link => !link.closest('.modal,dialog') && backWords.test(link.textContent));
    const formerBackParent = returnLink?.parentElement;
    const back = returnLink || document.createElement('a');
    back.href = record.index; back.className = 'btn btn-outline-secondary cms-record-back';
    back.replaceChildren(); const arrow = document.createElement('i'); arrow.className='ri-arrow-left-line'; arrow.setAttribute('aria-hidden','true');
    back.append(arrow, document.createTextNode(@json(__('admin.ui.tro_ve_danh_sach')))); actions.append(back);
    if (form) {
        const buttons = Array.from(form.querySelectorAll('button[type="submit"],button[name="action"],button:not([type]),input[type="submit"]')).filter(button => !button.closest('.modal,dialog,.accordion-header,.input-group') && !button.matches('#lfm,[data-input],[data-preview]'));
        const oldParents = new Set(formerBackParent ? [formerBackParent] : []);
        buttons.forEach(button => {
            oldParents.add(button.parentElement);
            if (button.value === 'save_draft' || draftWords.test(button.textContent)) button.classList.add('cms-save');
            actions.append(button); // Stays inside the same form: legacy closest('form') handlers still work.
        });
        if (record.mode === 'edit' && buttons.some(button => saveWords.test(button.textContent))) {
            const cancel=document.createElement('a');cancel.className='btn btn-outline-secondary cms-cancel';cancel.href=record.index;cancel.textContent=@json(__('admin.ui.huy_bo'));actions.append(cancel);
        }
        oldParents.forEach(parent => {
            while (parent && parent !== form && parent !== main && !parent.id && !parent.children.length && !parent.textContent.trim()) {
                const ancestor = parent.parentElement; parent.remove(); parent = ancestor;
            }
        });
    }
    host.prepend(heading);
    if (host !== main) main.querySelectorAll('.breadcrumb').forEach(crumb => crumb.classList.add('cms-breadcrumb'));
    if (!main.querySelector('.breadcrumb,.cms-breadcrumb')) {
        const crumb = document.createElement('nav'); crumb.className='cms-breadcrumb'; crumb.setAttribute('aria-label',@json(__('admin.ui.duong_dan_trang')));
        const home=document.createElement('a'); home.href=@json(\Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard')); home.textContent=@json(__('admin.ui.quan_tri'));
        const list=document.createElement('a'); list.href=record.index; list.textContent=record.label || @json(__('admin.js.danh_sach'));
        const current=document.createElement('span'); current.textContent=record.title;
        crumb.append(home,document.createTextNode(' › '),list,document.createTextNode(' › '),current); main.prepend(crumb);
    }
    main.querySelectorAll('.form-floating').forEach(group => {
        if (group.closest('.modal,dialog') || !group.querySelector('input:not([type="hidden"]),textarea,select')) return;
        group.classList.add('cms-record-field');
        const label = Array.from(group.children).find(el => el.tagName === 'LABEL');
        if (label) {
            group.prepend(label);
            if (group.querySelector('[required]') && !label.textContent.includes('*')) {
                const mark=document.createElement('span'); mark.className='cms-required'; mark.textContent=' *'; label.append(mark);
            }
        }
        // Move the input-group together; IDs, validation errors and Select2 initialization remain intact.
        const panel = group.closest('.input-group') || group;
        panel.classList.add('cms-record-field-card');
    });
    // Plain textarea editors have no floating wrapper. Keep their original label with the editor.
    main.querySelectorAll('textarea#ckeditor5').forEach(input => {
        if (input.closest('.cms-record-field-card')) return;
        const panel = document.createElement('section'); panel.className='cms-record-field-card cms-record-editor-card';
        input.before(panel);
        const previous = panel.previousElementSibling;
        if (previous?.matches('h5,label')) panel.append(previous);
        else {
            const card = panel.closest('.card'), header = card?.querySelector(':scope > .card-header');
            const label = header?.querySelector('h5');
            if (label && !header.querySelector('a,button,input,select')) { panel.append(label); if (!header.textContent.trim()) header.remove(); }
        }
        panel.append(input);
    });
    main.querySelectorAll('.card-body>h5').forEach(label => {
        if (!attachmentWords.test(label.textContent)) return;
        const content=label.nextElementSibling;
        if (!content || !content.querySelector('button,input,a')) return;
        const panel=document.createElement('section');panel.className='cms-record-field-card';label.before(panel);panel.append(label,content);
        label.classList.add('cms-card-label');
    });
    // Preserve the existing responsive columns; replace their proportions through scoped CSS.
    main.querySelectorAll('.row').forEach(row => {
        const cols = Array.from(row.children);
        if (cols.some(col => col.matches('.col-lg-8,.col-12.col-lg-8')) && cols.some(col => col.matches('.col-lg-4,.col-12.col-lg-4'))) row.classList.add('cms-record-grid');
    });
    main.querySelectorAll('#holder img').forEach(img => { if (!img.getAttribute('src')) img.hidden=true; });
    main.dataset.cmsRecordReady='true';
    main.dispatchEvent(new Event('cms:record-ready'));
});
</script>
