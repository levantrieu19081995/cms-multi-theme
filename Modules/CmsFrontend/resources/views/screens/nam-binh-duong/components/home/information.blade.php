<div class="cms-screen-group cms-information-grid">
    <section class="cms-section" aria-labelledby="cms-information-title">
        <div class="cms-section-heading">
            <h2 id="cms-information-title">{{ __('frontend.ui.thong_tin_thong_bao_2') }}</h2>
            <a class="cms-more" href="{{ $noticeUrl }}">{{ __('frontend.ui.xem_tat_ca') }} </a>
        </div>
        <div class="cms-information-list">
            @forelse ($homeNotices as $story)
                @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'row'])
            @empty
                <p class="cms-empty">{{ __('frontend.ui.chua_co_thong_bao_duoc_cong_bo') }}</p>
            @endforelse
        </div>
    </section>
    <section class="cms-section" aria-labelledby="cms-documents-title">
        <div class="cms-section-heading">
            <h2 id="cms-documents-title">{{ __('frontend.ui.van_ban_tai_lieu_bieu_mau') }}</h2>
            <a class="cms-more" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.index') }}">{{ __('frontend.ui.xem_tat_ca') }} </a>
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
                    
                </a>
            @empty
                <p class="cms-empty">{{ __('frontend.ui.chua_co_van_ban_duoc_cong_bo') }}</p>
            @endforelse
        </div>
    </section>
</div>
