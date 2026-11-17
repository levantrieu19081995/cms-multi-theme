{{-- Cụm "Chương trình đào tạo tiêu biểu" của bandean-v2 (thiết kế bannerSBUT): danh sách thẻ nghiêng, số thứ tự xếp lớp, ảnh cắt hình bình hành.
     Dữ liệu như bản dùng chung (screens/_campus): $homeSections['programs'] = bài của chuyên mục "chuong-trinh-dao-tao" + địa chỉ "Xem tất cả". --}}
@php($sectionData = $homeSections['programs'] ?? ['items' => collect(), 'url' => null])
<section class="section bd2-list bd2-programs" id="dao-tao" data-sbut-group="programs">
    <div class="wrap" data-stagger-group=":scope > .bd2-list-head > *, :scope > .bd2-list-intro, :scope > .bd2-list-toolbar, .bd2-list-items > li">
        <div class="bd2-list-head">
            <div>
                <div class="bd2-list-eyebrow">{{ __('frontend.ui.dao_tao_tai_nbdut') }}</div>
                <h2 class="bd2-list-title">{{ __('frontend.ui.chuong_trinh_dao_tao_tieu_bieu') }}</h2>
            </div>
            @if($sectionData['url'])
                <a class="bd2-list-all" href="{{ $sectionData['url'] }}"><span>{{ __('frontend.ui.xem_tat_ca') }}</span></a>
            @endif
        </div>
        <p class="bd2-list-intro">{{ __('frontend.ui.kham_pha_nganh_hoc_phu_hop_voi_so_thich_nang_luc') }}</p>
        <div class="bd2-list-toolbar">
            <span class="bd2-list-label">{{ __('frontend.ui.danh_sach_chuong_trinh') }}</span>
            <span class="bd2-list-count"><i class="ri-stack-line" aria-hidden="true"></i>{{ __('frontend.ui.bd2_dang_hien_thi', ['count' => $sectionData['items']->count()]) }}</span>
        </div>
        <ul class="bd2-list-items" aria-label="{{ __('frontend.ui.chuong_trinh_dao_tao_tieu_bieu') }}">
        @forelse($sectionData['items'] as $story)
            @php($image = trim((string) $story->getFirstMediaUrl('avatars')))
            <li>
                <a class="bd2-card" href="{{ route('cms.tin-tuc.show', $story->slug) }}">
                    <span class="bd2-card-media">
                        <span class="bd2-card-thumb">@if($image !== '')<img src="{{ $image }}" alt="" width="320" height="200" loading="lazy">@else<i class="ri-image-line" aria-hidden="true"></i>@endif</span>
                        <span class="bd2-card-num" aria-hidden="true"><i></i><i></i><b>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</b></span>
                    </span>
                    <span class="bd2-card-copy">
                        <span class="bd2-card-meta"><span class="bd2-card-tag">{{ __('pages.chuong_trinh_dao_tao') }}</span>@if($story->published_at)<time datetime="{{ \Carbon\Carbon::parse($story->published_at)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($story->published_at)->format('d/m/Y') }}</time>@endif</span>
                        <h3>{{ $story->title }}</h3>
                        @if(filled($story->excerpt))<span class="bd2-card-text">{{ \Illuminate\Support\Str::limit(strip_tags($story->excerpt), 200) }}</span>@endif
                    </span>
                    <span class="bd2-card-action">{{ __('frontend.ui.xem_chi_tiet') }}</span>
                </a>
            </li>
        @empty
            <li class="bd2-empty"><i class="ri-book-open-line" aria-hidden="true"></i><span>{{ __('frontend.ui.thong_tin_dang_duoc_cap_nhat') }}</span></li>
        @endforelse
        </ul>
    </div>
</section>
