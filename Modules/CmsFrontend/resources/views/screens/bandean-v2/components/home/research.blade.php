{{-- Cụm "Thành tựu nghiên cứu" của bandean-v2 (thiết kế bannerSBUT): lưới thẻ có ảnh phía trên, mép trên của phần chữ vát chéo.
     Dữ liệu như bản dùng chung (screens/_campus): $homeSections['research'] = bài của chuyên mục "thanh-tuu-nghien-cuu" + địa chỉ "Xem tất cả". --}}
@php($sectionData = $homeSections['research'] ?? ['items' => collect(), 'url' => null])
<section class="section bd2-list bd2-research" id="nghien-cuu" data-sbut-group="activities">
    <div class="wrap" data-stagger-group=":scope > .bd2-list-head > *, :scope > .bd2-list-intro, :scope > .bd2-list-toolbar, .bd2-carousel">
        <div class="bd2-list-head">
            <div>
                <div class="bd2-list-eyebrow">{{ __('frontend.ui.nghien_cuu_doi_moi_sang_tao') }}</div>
                <h2 class="bd2-list-title">{{ __('frontend.ui.thanh_tuu_nghien_cuu') }}</h2>
            </div>
            @if($sectionData['url'])
                <a class="bd2-list-all" href="{{ $sectionData['url'] }}"><span>{{ __('frontend.ui.xem_tat_ca') }}</span></a>
            @endif
        </div>
        <p class="bd2-list-intro">{{ __('frontend.ui.khong_gian_gioi_thieu_de_tai_cong_bo_va_nhung_ke') }}</p>
        <div class="bd2-list-toolbar">
            <span class="bd2-list-label">{{ __('frontend.ui.ho_so_nghien_cuu_noi_bat') }}</span>
            <span class="bd2-list-count"><i class="ri-stack-line" aria-hidden="true"></i>{{ __('frontend.ui.bd2_dang_hien_thi', ['count' => $sectionData['items']->count()]) }}</span>
        </div>
        <div class="bd2-carousel" data-bd2-carousel>
        <ul class="bd2-grid" data-bd2-track aria-label="{{ __('frontend.ui.thanh_tuu_nghien_cuu') }}">
        @forelse($sectionData['items'] as $story)
            @php($image = trim((string) $story->getFirstMediaUrl('avatars')))
            <li>
                <a class="bd2-tile" href="{{ route('cms.tin-tuc.show', $story->slug) }}">
                    <span class="bd2-tile-thumb">@if($image !== '')<img src="{{ $image }}" alt="" width="480" height="320" loading="lazy">@else<i class="ri-flask-line" aria-hidden="true"></i>@endif</span>
                    <span class="bd2-tile-body">
                        <span class="bd2-card-tag">{{ __('pages.nghien_cuu') }}</span>
                        <h3>{{ $story->title }}</h3>
                        @if(filled($story->excerpt))<span class="bd2-card-text">{{ \Illuminate\Support\Str::limit(strip_tags($story->excerpt), 200) }}</span>@endif
                        <span class="bd2-card-action">{{ __('frontend.ui.xem_chi_tiet') }}</span>
                    </span>
                </a>
            </li>
        @empty
            <li class="bd2-empty"><i class="ri-flask-line" aria-hidden="true"></i><span>{{ __('frontend.ui.thong_tin_dang_duoc_cap_nhat') }}</span></li>
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
