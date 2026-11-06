<section class="cms-section cms-partners cms-home-section cms-home-section--white" aria-label="{{ __('frontend.ui.don_vi_phoi_hop') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.don_vi_phoi_hop'), 'moreUrl' => null])
    <div class="cms-partners-grid">
        @forelse (collect($links ?? [])->take(6) as $partner)
            @php $partnerImage = trim((string) $partner->getFirstMediaUrl('avatars')); @endphp
            <a class="cms-partner" href="{{ $partner->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.doi-tac.show', ['slug' => $partner->slug]) }}">
                @if ($partnerImage !== '')<img src="{{ $partnerImage }}" alt="" loading="lazy" width="160" height="72">@else<i class="ri-{{ $partner->icon ?: 'community-line' }}" aria-hidden="true"></i>@endif
                <span>{{ $partner->title }}</span>
            </a>
        @empty
            <p class="cms-empty">{{ __('frontend.ui.chua_co_don_vi_phoi_hop_duoc_dang') }}</p>
        @endforelse
    </div>
</section>
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
