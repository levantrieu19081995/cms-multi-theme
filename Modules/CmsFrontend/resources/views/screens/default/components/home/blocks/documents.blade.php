{{-- Văn bản, tài liệu, biểu mẫu (tách từ cụm Thông báo; cùng dải màu nên hai cụm liền nhau gộp thành một dải). --}}
<div class="cms-home-section cms-home-section--green cms-documents-band">
    <section class="cms-section" aria-labelledby="cms-documents-title">
        <div class="cms-section-heading">
            <h2 id="cms-documents-title">{{ __('frontend.ui.van_ban_tai_lieu_bieu_mau') }}</h2>
            <a class="cms-more" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.index') }}">{{ __('frontend.ui.xem_tat_ca') }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="cms-document-list">
            @forelse ($homeDocuments as $document)
                <a class="cms-document" href="{{ $document->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.show', ['id' => $document->vb_id]) }}">
                    <span class="cms-document-icon cms-document-icon--{{ $loop->index % 4 }}"><i class="ri-{{ $loop->index % 4 === 3 ? 'folder-open-line' : 'file-text-line' }}" aria-hidden="true"></i></span>
                    <div class="cms-document-content">
                        <h3>{{ $document->vb_trich_yeu }}</h3>
                        @if(filled($document->excerpt ?? null))<span>{{ $document->excerpt }}</span>@endif
                        @if (filled($document->vb_so_hieu))<span>Số hiệu: {{ $document->vb_so_hieu }}</span>@endif
                        @if ($document->vb_ngay_ban_hanh)<time datetime="{{ $document->vb_ngay_ban_hanh->format('Y-m-d') }}">Ban hành: {{ $document->vb_ngay_ban_hanh->format('d/m/Y') }}</time>@endif
                    </div>
                    <span class="cms-chevron" aria-hidden="true">›</span>
                </a>
            @empty
                <p class="cms-empty">{{ __('frontend.ui.chua_co_van_ban_duoc_cong_bo') }}</p>
            @endforelse
        </div>
    </section>
</div>
