@php
    // 'self' also works on local hosts with underscores, which CSP host-source rejects.
    // allow-same-origin below preserves this origin; scripts/forms remain blocked.
    $previewPolicy = "default-src 'none'; script-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'";
    $previewBaseHeight = max(120, (int) ($previewHeight ?? 240));
    $previewMaxHeight = $previewBaseHeight * 4;
    $previewContent = \Modules\CmsBackend\Support\PublicContent::forEditor($previewSource ?? $post->content ?? "");
    $previewHtml = '<!doctype html><html><head><meta http-equiv="Content-Security-Policy" content="'.e($previewPolicy).'"><style>html,body{scrollbar-width:none;-ms-overflow-style:none}body{font:15px/1.6 Arial,sans-serif;color:#23416c;margin:16px;overflow-y:hidden}::-webkit-scrollbar{display:none;width:0;height:0}img{max-width:100%;height:auto}figure{margin:16px 0}table{max-width:100%}</style></head><body><main id="cms-preview-content">'.$previewContent.'</main></body></html>';
@endphp
<iframe title="{{ __('admin.ui.noi_dung_da_luu_chi_xem') }}" sandbox="allow-same-origin" srcdoc="{{ $previewHtml }}" class="form-control cms-content-preview" data-max-height="{{ $previewMaxHeight }}" style="width:100%;height:{{ $previewBaseHeight }}px"></iframe>
<script>
(() => {
    const preview = document.currentScript.previousElementSibling;
    const syncHeight = () => {
        const doc = preview.contentDocument;
        const content = doc?.getElementById('cms-preview-content');
        if (!content) return;

        const bodyStyle = doc.defaultView.getComputedStyle(doc.body);
        const margins = (parseFloat(bodyStyle.marginTop) || 0) + (parseFloat(bodyStyle.marginBottom) || 0);
        const contentHeight = Math.ceil(Math.max(content.scrollHeight, content.getBoundingClientRect().height) + margins);
        const maxHeight = Number(preview.dataset.maxHeight) || 960;
        const isOverflowing = contentHeight > maxHeight;

        preview.style.height = `${Math.min(maxHeight, Math.max(96, contentHeight))}px`;
        doc.documentElement.style.overflowY = isOverflowing ? 'auto' : 'hidden';
        doc.body.style.overflowY = isOverflowing ? 'auto' : 'hidden';
    };

    preview.addEventListener('load', () => {
        syncHeight();
        const content = preview.contentDocument?.getElementById('cms-preview-content');
        if (content && 'ResizeObserver' in window) new ResizeObserver(syncHeight).observe(content);
    }, { once: true });
})();
</script>
