<section class="cms-section cms-services" aria-label="{{ __('frontend.ui.dich_vu_ho_tro') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.dich_vu_ho_tro'), 'moreUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.dichvu.index')])
    <div class="cms-services-grid">
        @forelse (collect($dichvus ?? [])->take(3) as $service)
            @php
                $serviceIcon = preg_match('/^[a-zA-Z0-9-]+$/', (string) $service->icon) ? $service->icon : 'customer-service-2-line';
                $serviceLink = trim((string) ($service->link ?? ''));
                $serviceScheme = strtolower((string) parse_url($serviceLink, PHP_URL_SCHEME));
                $serviceLink = $serviceLink !== '' && in_array($serviceScheme, ['', 'http', 'https'], true) ? \Modules\CmsBackend\Support\ModuleUrl::url($serviceLink) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.dichvu.index');
            @endphp
            <a class="cms-service" href="{{ $serviceLink }}">
                <span class="cms-service-icon"><i class="ri-{{ $serviceIcon }}" aria-hidden="true"></i></span>
                <div><h3>{{ $service->title }}</h3>@if(filled($service->excerpt ?? null))<p class="cms-story-excerpt">{{ $service->excerpt }}</p>@else<span class="cms-service-action">{{ __('frontend.ui.tim_hieu_dich_vu') }} </span>@endif</div>
            </a>
        @empty
            <p class="cms-empty">{{ __('frontend.ui.chua_co_dich_vu_duoc_dang') }}</p>
        @endforelse
    </div>
</section>
