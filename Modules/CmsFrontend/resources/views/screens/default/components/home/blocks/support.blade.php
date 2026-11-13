{{-- Hỗ trợ và tư vấn: Gửi câu hỏi (Hỏi đáp) và Liên hệ tư vấn khi chức năng Liên hệ bật (tách từ cụm Đối tác và hỗ trợ). --}}
<section class="cms-support-grid cms-home-section cms-home-section--white" aria-label="{{ __('frontend.ui.ho_tro_va_tu_van') }}">
    <a class="cms-support" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.gui') }}">
        <span class="cms-support-icon"><i class="ri-chat-3-line" aria-hidden="true"></i></span>
        <div><h2>{{ __('frontend.ui.gui_cau_hoi') }}</h2><p>{{ __('frontend.ui.dat_cau_hoi_ve_ky_thuat_mo_hinh_san_xuat_hoac_ca') }}</p></div>
        <span class="cms-chevron" aria-hidden="true">›</span>
    </a>
    @if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<a class="cms-support" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he') }}">
        <span class="cms-support-icon"><i class="ri-customer-service-2-line" aria-hidden="true"></i></span>
        <div><h2>{{ __('frontend.ui.lien_he_tu_van') }}</h2><p>{{ __('frontend.ui.lien_he_voi_doi_ngu_khuyen_nong_de_duoc_tu_van_v') }}</p></div>
        <span class="cms-chevron" aria-hidden="true">›</span>
    </a>@endif
</section>
