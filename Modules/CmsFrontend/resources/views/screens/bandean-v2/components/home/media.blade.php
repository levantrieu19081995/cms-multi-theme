{{-- Cụm "Video giới thiệu trường" của bandean-v2 (thiết kế bannerSBUT): băng thẻ 3D (coverflow) trên nền campus phủ xanh. Thẻ giữa to, hai thẻ mỗi bên
     nghiêng và lùi ra sau; bấm thẻ bên cạnh để đưa vào giữa, bấm thẻ giữa để xem video. Dữ liệu: $homeVideos (slug, title, poster).
     Chuyển thẻ + chỉ số 01 / 05: js/screens/bandean-v2/theme.js (data-bd2-flow). --}}
@php($videos = $homeVideos->filter(fn ($video) => ! empty($video['slug']))->values())
<section class="section bd2-media" id="video-gioi-thieu" aria-labelledby="videoHeading" data-sbut-group="media" style="--bd2-media-bg: url('{{ asset('assets/img/themes/bandean-v2/hero-campus.jpg') }}')">
    <div class="wrap" data-stagger-group=":scope > .bd2-list-head > *, :scope > .bd2-list-intro">
        <div class="bd2-list-head">
            <div>
                <div class="bd2-list-eyebrow">{{ __('frontend.ui.thu_vien_video_nbdut') }}</div>
                <h2 class="bd2-list-title" id="videoHeading">{{ __('frontend.ui.video_gioi_thieu_truong') }}</h2>
            </div>
            <a class="bd2-list-all" href="{{ route('cms.video.danh-sach') }}"><span>{{ __('frontend.ui.xem_tat_ca') }}</span></a>
        </div>
        <p class="bd2-list-intro">{{ __('frontend.ui.nhung_goc_nhin_ve_campus_kien_truc_va_tinh_than_') }}</p>
        @if($videos->isNotEmpty())
        <div class="bd2-flow" data-bd2-flow data-bd2-flow-autoplay="5000" aria-roledescription="carousel" aria-label="{{ __('frontend.ui.danh_sach_video_gioi_thieu_truong') }}">
            <div class="bd2-flow-stage" data-bd2-flow-stage>
            @foreach($videos as $video)
                <a class="bd2-flow-card" data-bd2-flow-item href="{{ route('cms.video.show', $video['slug']) }}" aria-label="{{ $video['title'] }}">
                    @if(! empty($video['poster']))<img src="{{ $video['poster'] }}" alt="" width="640" height="360" loading="{{ $loop->first ? 'eager' : 'lazy' }}">@else<span class="bd2-flow-empty"><i class="ri-video-line" aria-hidden="true"></i></span>@endif
                    <span class="bd2-flow-tag">VIDEO</span>
                    <span class="bd2-flow-caption"><strong>{{ $video['title'] }}</strong><i class="bd2-chev" aria-hidden="true"></i></span>
                </a>
            @endforeach
            </div>
            <div class="bd2-flow-pager" data-bd2-flow-pager @if($videos->count() < 2) hidden @endif>
                <span class="bd2-flow-index"><b data-bd2-flow-page>01</b><span>/ {{ str_pad((string) $videos->count(), 2, '0', STR_PAD_LEFT) }}</span></span>
                <span class="bd2-flow-dots" data-bd2-flow-dots role="group" aria-label="{{ __('frontend.ui.danh_sach_video_gioi_thieu_truong') }}"></span>
                <span class="bd2-flow-arrows"><button type="button" class="bd2-chev bd2-chev--left" data-bd2-flow-move="-1" aria-label="{{ __('frontend.ui.banner_truoc') }}"></button><button type="button" class="bd2-chev" data-bd2-flow-move="1" aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"></button></span>
            </div>
        </div>
        @else
            <p class="bd2-empty bd2-media-empty">{{ __('frontend.ui.video_dang_duoc_cap_nhat') }}</p>
        @endif
    </div>
</section>
