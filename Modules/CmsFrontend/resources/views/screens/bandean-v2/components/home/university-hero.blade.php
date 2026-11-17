{{-- Banner hero của bandean-v2 (thiết kế bannerSBUT): ảnh campus phủ tông xanh, lối tắt theo đối tượng, các con số nổi bật nằm ngay trên banner.
     Slider dùng chung hook data-sbut-* (effects/hero-slider); số liệu lấy từ màn hình Con số nổi bật ($homeStatistics, cụm hero khai báo nhóm dữ liệu `statistics`). --}}
@php
    $slides = [
        ['bandean-v2/hero-campus.jpg',__('pages.khai_phong_tri_thuc'),__('pages.kien_tao_tuong_lai'),__('pages.mot_khong_gian_dai_hoc_mo_noi_tri_thuc_cong_nghe'),__('pages.phoi_canh_mat_tien_campus_sbut'),'center 45%'],
        ['bandean-v2/hero-icons.jpg',__('pages.hoc_de_kham_pha'),__('pages.lam_de_truong_thanh'),__('pages.ket_noi_nen_tang_hoc_thuat_voi_thuc_tien_mo_rong'),__('pages.phoi_canh_campus_goc_nhin_cong_nghe'),'72% center'],
        ['nam-binh-duong/campus-perspective.jpeg',__('pages.ket_noi_hom_nay'),__('pages.cong_hien_ngay_mai'),__('pages.nuoi_duong_tu_duy_mo_tinh_than_hoc_thuat_va_trac'),__('pages.phoi_canh_tong_the_khong_gian_campus'),'center 57%'],
    ];
    $home = route('cms.trang-chu');
    $audiences = ['sinh-vien' => __('frontend.ui.sinh_vien'), 'giang-vien' => __('frontend.ui.giang_vien'), 'tuyen-sinh' => __('frontend.ui.thi_sinh_phu_huynh')];
    $statRows = collect($homeStatistics ?? [])->take(4)->all();
@endphp
<section class="hero bd2-hero" id="trang-chu" aria-label="{{ __('frontend.ui.gioi_thieu_hinh_anh_va_dinh_huong_cua_truong') }}" data-sbut-hero>
@foreach($slides as [$image,$line,$accent,$description,$caption,$focus])
    <div class="sbut-reference-slide" data-sbut-slide @unless($loop->first) hidden @endunless>
        <img class="hero-image" src="{{ asset('assets/img/themes/'.$image) }}" alt="{{ $caption }}" width="1600" height="900" style="object-position:{{ $focus }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
        <div class="wrap hero-content">
            @if($loop->first)<h1 id="sbut-hero-title">{{ $line }}<br><em>{{ $accent }}</em></h1>@else<h2 class="sbut-hero-heading">{{ $line }}<br><em>{{ $accent }}</em></h2>@endif
            <p>{{ $description }}</p>
            <div class="hero-buttons">
                <a href="#dao-tao" class="btn gold">{{ __('frontend.ui.kham_pha_nganh_hoc') }}</a>
                <a href="#video-gioi-thieu" class="text-button hero-video"><span class="play-circle"><i class="ri-play-fill" aria-hidden="true"></i></span>{{ __('frontend.ui.bd2_xem_video_gioi_thieu') }}</a>
            </div>
            <div class="bd2-hero-side">
                <ul class="bd2-hero-chips">@foreach($audiences as $anchor => $label)<li><a href="{{ $home }}#{{ $anchor }}"><small>{{ __('frontend.ui.bd2_loi_tat_danh_cho') }}</small><b>{{ $label }}</b></a></li>@endforeach</ul>
            </div>
        </div>
    </div>
@endforeach
<div class="wrap sbut-hero-controls"><div class="slide-controls"><span class="slide-index"><b data-sbut-slide-index>01</b><span>/ {{ str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) }}</span></span><div class="slide-progress" aria-label="{{ __('frontend.ui.chon_banner') }}">@foreach($slides as $slide)<button type="button" data-sbut-slide-to="{{ $loop->index }}" @class(['active'=>$loop->first]) aria-label="Banner {{ $loop->iteration }}" aria-pressed="{{ $loop->first?'true':'false' }}"></button>@endforeach</div><div class="slide-arrows"><button type="button" class="bd2-chev bd2-chev--left" data-sbut-slide-move="-1" aria-label="{{ __('frontend.ui.banner_truoc') }}"></button><button type="button" class="bd2-chev" data-sbut-slide-move="1" aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"></button></div></div></div>
@if(count($statRows))
<div class="bd2-hero-stats" role="group" aria-label="{{ __('frontend.ui.cac_con_so_noi_bat_cua_truong') }}">
@foreach($statRows as $stat)
    @php($statValue = filled($stat['value'] ?? null) ? (string) $stat['value'] : '—')
    <div class="bd2-stat">
        @if(preg_match('/^(\d[\d.]*)(.*)$/u', $statValue, $statParts))<strong><span data-count="{{ (int) str_replace('.', '', $statParts[1]) }}"@if(str_starts_with($statParts[1], '0')) data-pad="{{ strlen($statParts[1]) }}"@endif>{{ $statParts[1] }}</span>{{ $statParts[2] }}</strong>@else<strong>{{ $statValue }}</strong>@endif
        <span>{{ $stat['label'] }}</span>
    </div>
@endforeach
</div>
@endif
</section>
