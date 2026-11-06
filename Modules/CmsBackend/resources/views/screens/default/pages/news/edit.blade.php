@extends('cmsbackend::screens.layout')
@section('title', ($post->exists ? __('admin.ui.xu_ly_sp') : __('admin.ui.tao_sp')).mb_strtolower($heading).' – Khuyến nông')
@section('vendor-style')
@vite(Theme::asset(['assets/vendor/libs/ckeditor5/ckeditor5.css']))
@endsection
@section('page-script')
@if($editable)
@vite(['Modules/CmsBackend/resources/assets/js/screens/default/editor.js', 'resources/assets/js/form-input-group.js'])
<script>
document.addEventListener('DOMContentLoaded', () => {
    const imagePicker = document.getElementById('lfm');
    if (imagePicker) imagePicker.addEventListener('click', event => {
        event.preventDefault();

        const targetInput = document.getElementById(imagePicker.dataset.input);
        const targetPreview = document.getElementById(imagePicker.dataset.preview);
        const fileManagerUrl = new URL(@json(url('laravel-filemanager')), window.location.origin);
        fileManagerUrl.searchParams.set('type', 'image');

        window.SetUrl = items => {
            const selectedItems = Array.isArray(items) ? items : [];
            const selectedUrl = selectedItems.map(item => item.url).filter(Boolean).join(',');

            if (targetInput) {
                targetInput.value = selectedUrl;
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (targetPreview) {
                targetPreview.replaceChildren();
                selectedItems.forEach(item => {
                    const image = document.createElement('img');
                    image.src = item.thumb_url || item.url;
                    image.alt = @json(__('admin.js.anh_dai_dien_da_chon'));
                    image.style.height = '5rem';
                    targetPreview.appendChild(image);
                });
                targetPreview.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        window.open(fileManagerUrl.toString(), 'FileManager', 'width=1100,height=720,resizable=yes,scrollbars=yes');
    });

    const title = document.getElementById('title');
    const slug = document.getElementById('slug');
    if (!title || !slug || slug.disabled || slug.readOnly) return;
    const toSlug = value => value.toLowerCase().replace(/đ/g, 'd')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
        .slice(0, 255).replace(/-+$/g, '');
    // Preserve published URLs and manually supplied slugs, including after validation errors.
    let automatic = !slug.value.trim() || (slug.dataset.newPost === 'true' && slug.value === toSlug(title.value));
    const generate = () => {
        if (automatic) slug.value = toSlug(title.value);
    };
    title.addEventListener('input', generate);
    slug.addEventListener('input', () => { automatic = !slug.value.trim(); });
    slug.addEventListener('blur', generate);
    generate();
    document.querySelectorAll('[data-cms-counter]').forEach(input => {
        const counter = document.getElementById(input.dataset.cmsCounter);
        const update = () => { counter.textContent = `${input.value.length}/${input.maxLength}`; };
        input.addEventListener('input', update); update();
    });
    const fold = text => text.toLocaleLowerCase('vi').replace(/đ/g, 'd').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const filter = (input, selector, emptyId) => input?.addEventListener('input', () => {
        let count=0;document.querySelectorAll(selector).forEach(row => { row.hidden=!fold(row.textContent).includes(fold(input.value.trim()));if(!row.hidden)count++; });
        const empty=document.getElementById(emptyId);if(empty)empty.hidden=count>0;
    });
    filter(document.getElementById('cms-category-search'),'.cms-category-row','cms-category-empty');
    const tagSearch=document.getElementById('cms-tag-search');
    filter(tagSearch,'.cms-tag','cms-tag-empty');
    tagSearch?.addEventListener('keydown', event => {
        if(event.key!=='Enter')return;event.preventDefault();
        const tag=[...document.querySelectorAll('.cms-tag')].find(row=>fold(row.textContent.trim())===fold(tagSearch.value.trim()));
        if(tag){tag.querySelector('input').checked=true;tagSearch.value='';tagSearch.dispatchEvent(new Event('input'));}
    });
});
</script>
@endif
@endsection
@section('content')
@include('cmsbackend::screens.components.editorial.form')
@endsection
