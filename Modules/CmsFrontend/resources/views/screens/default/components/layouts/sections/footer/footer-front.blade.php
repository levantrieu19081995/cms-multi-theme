<footer class="cms-footer">
    <div class="cms-shell-container cms-footer-grid">
        <div class="cms-footer-brand"><a class="cms-brandmark" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}" aria-label="{{ __('frontend.ui.khuyen_nong_can_tho') }}">@if ($siteLogo = \Modules\CmsBackend\Support\SiteLogo::url())<img class="cms-brandmark-site" src="{{ $siteLogo }}" alt="" loading="lazy" decoding="async">@endif</a>
            <div><strong>{{ \Modules\CmsFrontend\Support\Brand::unit() }}</strong><p>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_description') ?? __('frontend.ui.trang_thong_tin_dien_tu') }}</p></div>
        </div>
        <div><h2>{{ __('frontend.ui.gioi_thieu') }}</h2>
            @if(!empty($cmsNavigation[1]['children']))
                <ul>@include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item', ['items' => array_slice($cmsNavigation[1]['children'], 0, 4)])</ul>
            @else
                @if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he') }}">{{ __('frontend.ui.lien_he_don_vi') }}</a>@endif
            @endif
        </div>
        <div><h2>{{ __('frontend.ui.thong_tin') }}</h2>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}">{{ __('frontend.ui.tin_tuc') }}</a>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.index') }}">{{ __('frontend.ui.van_ban') }}</a>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien') }}">{{ __('frontend.ui.su_kien') }}</a>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.dichvu.index') }}">{{ __('frontend.ui.dich_vu') }}</a>
        </div>
        <div><h2>{{ __('frontend.ui.thu_vien') }}</h2><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach') }}">{{ __('frontend.ui.hinh_anh') }}</a><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.danh-sach') }}">Video</a></div>
        <div><h2>{{ __('frontend.ui.lien_he') }}</h2><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.gui') }}">{{ __('frontend.ui.gui_cau_hoi') }}</a>@if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he') }}">{{ __('frontend.ui.lien_he_tu_van') }}</a>@endif
            @if($cmsWebsiteLinks->isNotEmpty())<details><summary>{{ __('frontend.ui.lien_ket_website') }}</summary><ul class="cms-website-links">@foreach($cmsWebsiteLinks as $link)
                <li><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::url($link->redirect_url) }}" target="_blank" rel="noopener noreferrer">@if($link->getFirstMediaUrl('anhQuangCaos'))<img src="{{ $link->getFirstMediaUrl('anhQuangCaos') }}" alt="" loading="lazy">@endif{{ $link->title }}</a></li>
            @endforeach</ul></details>@endif
        </div>
        <div><h2>{{ __('frontend.ui.theo_doi_chung_toi') }}</h2><div class="cms-social">
            @foreach(['facebook' => 'facebook-fill', 'youtube' => 'youtube-fill', 'messenger' => 'messenger-fill'] as $network => $label)
                @php $socialUrl = \Modules\CmsFrontend\Support\Brand::setting('web_' . $network) ?: config('cmsfrontend.brand.' . $network); @endphp
                @if($socialUrl)<a href="{{ $socialUrl }}" aria-label="{{ ucfirst($network) }}" target="_blank" rel="noopener noreferrer" class="cms-social-{{ $network }}"><i class="ri-{{ $label }}" aria-hidden="true"></i></a>
                @else<span class="cms-social-{{ $network }}" aria-label="{{ ucfirst($network) }}: chưa cấu hình liên kết" title="Chưa cấu hình liên kết {{ ucfirst($network) }}"><i class="ri-{{ $label }}" aria-hidden="true"></i></span>@endif
            @endforeach
        </div></div>
    </div>
    <div class="cms-shell-container cms-footer-information">
        <div class="cms-footer-publisher">
            <h2>{{ __('frontend.ui.trang_thong_tin_dien_tu_cua') }}</h2>
            <p><strong>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_unit_name') ?? __('frontend.ui.trung_tam_khuyen_nong_va_dich_vu_nong_nghiep_tp_') }}</strong><br>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_unit_short') ?? __('frontend.ui.ttkn_dvnn_can_tho') }}</p>
            @if($footerNameEn = \Modules\CmsFrontend\Support\Brand::setting('footer_unit_name_en'))<p lang="en">{{ $footerNameEn }}</p>@else<p lang="en">Can Tho Center for Agricultural Extension and Services<br>(Can Tho CAES)</p>@endif
            @php $footerParent = \Modules\CmsFrontend\Support\Brand::setting('footer_parent_unit') ?: config('cmsfrontend.brand.parent_unit'); @endphp
            @if(filled($footerParent))<p>{{ __('frontend.ui.thuoc') }} <strong>{{ $footerParent }}</strong></p>@endif
            <p class="cms-footer-copyright">© {{ now()->year }}. {{ \Modules\CmsFrontend\Support\Brand::setting('footer_copyright') ?? __('frontend.ui.khuyen_nong_can_tho') }}</p>
        </div>
        <div class="cms-footer-contact">
            <h2>{{ __('frontend.ui.thong_tin_lien_he') }}</h2>
            <address>
                @php
                    $footerAddress = \Modules\CmsFrontend\Support\Brand::address();
                    $footerPhone = \Modules\CmsFrontend\Support\Brand::setting('web_phone');
                    $footerEmail = \Modules\CmsFrontend\Support\Brand::setting('web_email');
                    $footerAdminEmail = \Modules\CmsFrontend\Support\Brand::setting('web_email_admin');
                @endphp
                <p>{{ $footerAddress ? __('frontend.ui.dia_chi').': '.$footerAddress : __('frontend.ui.dia_chi_so_38_nguyen_thai_hoc_p_ninh_kieu_tp_can') }}</p>
                <p>{{ __('frontend.ui.dien_thoai_2') }} @if($footerPhone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $footerPhone) }}">{{ $footerPhone }}</a>@else<a href="tel:02923820783">02923.820.783.</a>@endif</p>
                <p>{{ __('frontend.ui.email_van_phong') }} @if($footerEmail)<a href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a>@else<a href="mailto:ttkndvnnct@cantho.gov.vn">ttkndvnnct@cantho.gov.vn.</a>@endif</p>
                <p>Email Web Admin: @if($footerAdminEmail)<a href="mailto:{{ $footerAdminEmail }}">{{ $footerAdminEmail }}</a>@else<a href="mailto:kndvnncantho@gmail.com">kndvnncantho@gmail.com.</a>@endif</p>
            </address>
        </div>
        <div class="cms-footer-publication">
            <h2>{{ __('frontend.ui.thong_tin_xuat_ban') }}</h2>
            <p>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_publisher') ?? __('frontend.ui.chiu_tnxb_truong_bbt_gd_trung_tam') }}</p>
            <p>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_source_note') ?? __('frontend.ui.ghi_nguon_khuyennongcantho_vn_hoac_kndvnn_cantho') }}</p>
            <p>{{ __('frontend.ui.thiet_ke_va_phat_trien') }} <strong>{{ \Modules\CmsFrontend\Support\Brand::setting('footer_design_credit') ?? __('frontend.ui.cusc_software_dai_hoc_can_tho') }}</strong></p>
        </div>
    </div>
    {{-- Quick links (menu "Liên kết nhanh chân trang") and visit counters required by the PLHĐ. --}}
    <div class="cms-shell-container cms-footer-strip">
        @if(!empty($cmsFooterQuickLinks))
            <nav class="cms-footer-quick" aria-label="{{ __('frontend.ui.lien_ket_nhanh') }}">
                @foreach($cmsFooterQuickLinks as $link)
                    <a href="{{ $link['url'] }}" @if(($link['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif>
                        <span class="cms-footer-quick-icon" aria-hidden="true"><i class="{{ $link['icon'] }}"></i></span>
                        <span>{{ $link['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
        @php($cmsStats = $cmsVisitStats ?? [])
        @if(isset($cmsStats['total']) || isset($cmsStats['online']))
            <dl class="cms-footer-stats">
                @isset($cmsStats['total'])<div><dt><i class="ri-bar-chart-2-line" aria-hidden="true"></i> {{ __('frontend.ui.luot_truy_cap') }}</dt><dd>{{ number_format($cmsStats['total'], 0, ',', '.') }}</dd></div>@endisset
                @isset($cmsStats['online'])<div><dt><i class="ri-user-follow-line" aria-hidden="true"></i> {{ __('frontend.ui.truc_tuyen') }}</dt><dd>{{ number_format(max(1, $cmsStats['online']), 0, ',', '.') }}</dd></div>@endisset
            </dl>
        @endif
    </div>
    <div class="cms-shell-container cms-footer-bottom">
        @if(count($cmsConfiguredNavigation))<details><summary>{{ __('frontend.ui.danh_muc_thong_tin') }}</summary><ul>@include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item', ['items' => $cmsConfiguredNavigation])</ul></details>@endif
    </div>
</footer>
