{{-- Cụm "Văn bản, tài liệu" của bandean-v2 (thiết kế bannerSBUT): mỗi văn bản là một "tờ giấy" có gáy xanh, góc gập, quốc hiệu, nhãn loại văn bản,
     trích yếu và số hiệu · ngày ban hành. Các dòng xám trên tờ giấy chỉ để trang trí (aria-hidden). Dữ liệu: $homeDocuments (khối documents). --}}
<section class="section bd2-list bd2-docs" id="van-ban" data-sbut-group="documents">
    <div class="wrap" data-stagger-group=":scope > .bd2-list-head > *, .bd2-carousel">
        <div class="bd2-list-head">
            <div>
                <div class="bd2-list-eyebrow">{{ __('frontend.ui.tra_cuu_khai_thac') }}</div>
                <h2 class="bd2-list-title">{{ __('frontend.ui.van_ban_tai_lieu') }}</h2>
            </div>
            <a class="bd2-list-all" href="{{ route('cms.frontend.vanban.index') }}"><span>{{ __('frontend.ui.xem_tat_ca') }}</span></a>
        </div>
        <div class="bd2-carousel" data-bd2-carousel>
        <ul class="bd2-docs-grid" data-bd2-track aria-label="{{ __('frontend.ui.van_ban_tai_lieu') }}">
        @forelse($homeDocuments->take(6) as $document)
            <li>
                <a class="bd2-doc" href="{{ route('cms.frontend.vanban.show', ['id' => $document->getKey()]) }}">
                    <span class="bd2-doc-head" aria-hidden="true">
                        <svg class="bd2-doc-seal" viewBox="0 0 48 48" focusable="false"><circle cx="24" cy="24" r="21" fill="none" stroke="currentColor" stroke-width="2.4"/><circle cx="24" cy="24" r="15" fill="none" stroke="currentColor" stroke-width="1.4" stroke-dasharray="2 3"/><path d="M24 12l3.5 8.2 8.8.7-6.7 5.8 2.1 8.6L24 30.7l-7.7 4.6 2.1-8.6-6.7-5.8 8.8-.7z" fill="currentColor"/></svg>
                        <span><b>{{ __('frontend.ui.bd2_van_ban_cong_hoa') }}</b><i>{{ __('frontend.ui.bd2_van_ban_doc_lap') }}</i></span>
                    </span>
                    <span class="bd2-doc-title">
                        <b class="bd2-doc-type">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($document->loaiVanBan?->lvb_ten ?: 'VB', 0, 2)) }}</b>
                        <span><strong>{{ $document->vb_trich_yeu }}</strong><small>{{ $document->vb_so_hieu ?: __('pages.van_ban') }}@if($document->vb_ngay_ban_hanh) · {{ $document->vb_ngay_ban_hanh->format('d/m/Y') }}@endif</small></span>
                    </span>
                    <span class="bd2-doc-lines bd2-doc-lines--{{ $loop->index % 3 }}" aria-hidden="true"><i></i><i></i><i></i><u></u><u></u><u></u><u></u><s></s></span>
                    <span class="bd2-doc-foot"><em>{{ __('frontend.ui.xem_chi_tiet') }}</em></span>
                </a>
            </li>
        @empty
            <li class="bd2-empty"><i class="ri-file-text-line" aria-hidden="true"></i><span>{{ __('frontend.ui.van_ban_va_tai_lieu_dang_duoc_cap_nhat') }}</span></li>
        @endforelse
        </ul>
        <div class="bd2-pager" data-bd2-pager>
            <span class="bd2-pager-index"><b data-bd2-page>01</b><span>/ <span data-bd2-pages>01</span></span></span>
            <span class="bd2-pager-bars" data-bd2-bars aria-hidden="true"></span>
            <span class="bd2-pager-arrows"><button type="button" class="bd2-chev bd2-chev--left" data-bd2-move="-1" aria-label="{{ __('frontend.ui.banner_truoc') }}"></button><button type="button" class="bd2-chev" data-bd2-move="1" aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"></button></span>
        </div>
        </div>
    </div>
</section>
