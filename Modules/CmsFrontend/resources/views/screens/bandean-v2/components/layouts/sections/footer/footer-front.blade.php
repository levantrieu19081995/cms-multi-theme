{{-- Footer của bandean-v2 (thiết kế "SBUT xanh"): nền xanh đậm có sóng sáng, cột thương hiệu bên trái + ba cột liên kết có biểu tượng, thanh liên hệ dưới cùng.
     Chữ thương hiệu/liên hệ lấy từ "Cài đặt chung" (Brand::setting / Brand::address); trống thì ẩn mục đó, không bịa dữ liệu.
     Ảnh nền thật (nếu có): đặt tệp public/assets/img/themes/bandean-v2/footer-art.webp (hoặc .jpg) — tự được phủ lên lớp sóng vẽ sẵn bằng SVG/CSS.
     CSS: css/screens/bandean-v2/footer.css. Không dùng class .proposal-footer (kiểu cũ của Ban Đề án). --}}
@php
    $ftHome = \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu');
    $ftRegistry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);
    $ftArt = collect(['webp', 'jpg', 'png'])->first(fn ($ext) => is_file(public_path('assets/img/themes/bandean-v2/footer-art.'.$ext)));
    $ftAddress = \Modules\CmsFrontend\Support\Brand::address();
    $ftPhone = \Modules\CmsFrontend\Support\Brand::setting('web_phone');
    $ftEmail = \Modules\CmsFrontend\Support\Brand::setting('web_email');
@endphp
<footer class="bd2-footer @if($ftArt) has-art @endif" id="footer" style="--bd2-footer-campus: url('{{ asset('assets/img/themes/bandean-v2/hero-campus.jpg') }}')@if($ftArt); --bd2-footer-art: url('{{ asset('assets/img/themes/bandean-v2/footer-art.'.$ftArt) }}')@endif">
    <div class="bd2-ft-art" aria-hidden="true">
        <svg class="bd2-ft-wave bd2-ft-wave--left" viewBox="0 0 900 360" preserveAspectRatio="xMinYMin slice" focusable="false"><defs><linearGradient id="bd2FtG1" x1="0" x2="1"><stop offset="0" stop-color="#8EF0B4" stop-opacity=".0"/><stop offset=".55" stop-color="#8EF0B4" stop-opacity=".75"/><stop offset="1" stop-color="#D4F380" stop-opacity=".0"/></linearGradient></defs><g fill="none" stroke="url(#bd2FtG1)" stroke-width="1.4"><path d="M0 70 C 180 150, 420 -40, 860 8"/><path d="M0 92 C 200 170, 430 -10, 880 26"/><path d="M0 114 C 220 190, 440 20, 900 46"/><path d="M0 136 C 240 210, 450 50, 900 68"/></g></svg>
        <svg class="bd2-ft-wave bd2-ft-wave--right" viewBox="0 0 900 360" preserveAspectRatio="xMaxYMin slice" focusable="false"><defs><linearGradient id="bd2FtG2" x1="1" x2="0"><stop offset="0" stop-color="#8EF0B4" stop-opacity=".0"/><stop offset=".5" stop-color="#8EF0B4" stop-opacity=".8"/><stop offset="1" stop-color="#8EF0B4" stop-opacity=".0"/></linearGradient></defs><g fill="none" stroke="url(#bd2FtG2)" stroke-width="1.4"><path d="M900 0 C 760 40, 640 110, 520 250"/><path d="M900 22 C 770 62, 660 130, 560 270"/><path d="M900 44 C 780 84, 680 150, 600 290"/><path d="M900 66 C 790 106, 700 170, 640 310"/><path d="M900 88 C 800 128, 720 190, 680 330"/></g></svg>
        <span class="bd2-ft-campus"></span>
        <span class="bd2-ft-image"></span>
    </div>
    <div class="wrap bd2-ft-wrap">
        <div class="bd2-ft-top">
            <div class="bd2-ft-brand">
                <a class="bd2-ft-logo" href="{{ $ftHome }}" aria-label="{{ __('frontend.ui.ve_trang_chu_nbdut') }}"><img src="{{ asset('assets/img/themes/bandean-v2/brand-logo.png') }}" alt="{{ __('frontend.ui.logo_nbdut_truong_dai_hoc_cong_nghe_nam_binh_duo') }}" width="250" height="283" loading="lazy" decoding="async"></a>
                <h3 class="bd2-ft-motto">{{ __('frontend.ui.tri_thuc_mo') }}<br>{{ __('frontend.ui.tuong_lai_rong') }}</h3>
                <p class="bd2-ft-slogan">{{ \Modules\CmsFrontend\Support\Brand::setting('footer_slogan') ?? __('frontend.ui.khai_phong_uu_tu_cong_hien') }}</p>
            </div>

            <nav class="bd2-ft-col" aria-labelledby="bd2FtExplore">
                <h4 id="bd2FtExplore"><i class="ri-compass-3-line" aria-hidden="true"></i><span>{{ __('frontend.ui.kham_pha_nbdut') }}</span></h4>
                <ul>
                    @foreach(['gioi-thieu' => __('pages.ve_nha_truong'), 'dao-tao' => __('pages.chuong_trinh_dao_tao'), 'nghien-cuu' => __('pages.nghien_cuu_doi_moi'), 'tin-tuc' => __('pages.tin_tuc_su_kien')] as $anchor => $label)
                        <li><a href="{{ $ftHome }}#{{ $anchor }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav class="bd2-ft-col" aria-labelledby="bd2FtSupport">
                <h4 id="bd2FtSupport"><i class="ri-briefcase-line" aria-hidden="true"></i><span>{{ __('frontend.ui.dong_hanh_cung_ban') }}</span></h4>
                <ul>
                    <li><a href="{{ $ftHome }}#tuyen-sinh">{{ __('frontend.ui.thong_tin_tuyen_sinh') }}</a></li>
                    <li><a href="{{ $ftHome }}#lien-ket-nhanh">{{ __('frontend.ui.danh_cho_sinh_vien') }}</a></li>
                    <li><a href="{{ $ftHome }}#lien-ket-nhanh">{{ __('frontend.ui.danh_cho_giang_vien') }}</a></li>
                    <li><a href="{{ route('cms.frontend.hoidap.index') }}">{{ __('frontend.ui.hoi_dap') }}</a></li>
                    <li><a href="{{ route('cms.frontend.hoidap.gui') }}">{{ __('frontend.ui.gui_cau_hoi') }}</a></li>
                </ul>
            </nav>

            <nav class="bd2-ft-col" aria-labelledby="bd2FtConnect">
                <h4 id="bd2FtConnect"><i class="ri-share-circle-line" aria-hidden="true"></i><span>{{ __('frontend.ui.ket_noi_voi_nha_truong') }}</span></h4>
                <ul>
                    <li><a href="{{ $ftHome }}">{{ __('frontend.ui.truong_dai_hoc_cong_nghe') }} {{ __('frontend.ui.nam_binh_duong') }}</a></li>
                    @if ($ftRegistry->isEnabled('contact'))<li><a href="{{ route('cms.lien-he') }}">{{ __('frontend.ui.lien_he_gop_y') }}</a></li>@endif
                    <li><a href="{{ route('cms.frontend.vanban.index') }}">{{ __('frontend.ui.van_ban_tai_lieu') }}</a></li>
                    <li><a href="{{ route('cms.hinh-anh.danh-sach') }}">{{ __('frontend.ui.thu_vien_hinh_anh') }}</a></li>
                    <li><a href="{{ route('cms.video.danh-sach') }}">{{ __('frontend.ui.thu_vien_video') }}</a></li>
                    <li><a href="{{ route('cms.frontend.dichvu.index') }}">{{ __('frontend.ui.dich_vu') }}</a></li>
                    @if(count($cmsConfiguredNavigation))
                        <li><details class="bd2-ft-dir"><summary>{{ __('frontend.ui.danh_muc_thong_tin') }}</summary><ul>@include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item', ['items' => $cmsConfiguredNavigation])</ul></details></li>
                    @endif
                </ul>
            </nav>
        </div>

        @if($cmsWebsiteLinks->isNotEmpty())
            <div class="bd2-ft-sites">@foreach($cmsWebsiteLinks as $link)<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::url($link->redirect_url) }}" target="_blank" rel="noopener noreferrer">{{ $link->title }}</a>@endforeach</div>
        @endif

        <div class="bd2-ft-bar">
            @if(filled($ftAddress))<p class="bd2-ft-info bd2-ft-address"><b>{{ __('frontend.ui.dia_chi') }}:</b> {{ $ftAddress }}</p>@endif
            @if(filled($ftPhone))<p class="bd2-ft-info"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $ftPhone) }}">{{ $ftPhone }}</a></p>@endif
            @if(filled($ftEmail))<p class="bd2-ft-info"><i class="ri-mail-line" aria-hidden="true"></i><a href="mailto:{{ $ftEmail }}">{{ $ftEmail }}</a></p>@endif
            <p class="bd2-ft-info bd2-ft-copy">
                <span>© {{ now()->year }} {{ \Modules\CmsFrontend\Support\Brand::setting('footer_copyright') ?? __('frontend.brand.name_ban_de_an') }}</span>
                @if(isset($cmsVisitStats['total']))<span>{{ __('frontend.misc.visits') }}: {{ number_format($cmsVisitStats['total'], 0, ',', '.') }}</span>@endif
                <a href="{{ $ftHome }}#trang-chu">{{ __('frontend.ui.ve_dau_trang') }}</a>
            </p>
        </div>
    </div>
</footer>
