{{-- Sự kiện nổi bật (tách từ cụm Chuyên mục nổi bật). --}}
<div class="cms-screen-group cms-production-group cms-production-group--single cms-home-section cms-home-section--white">
<section class="cms-section cms-events" aria-label="{{ __('frontend.ui.su_kien_noi_bat') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.su_kien_noi_bat'), 'moreUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien')])
    @if ($homeEvents->isNotEmpty())
        <div class="cms-events-grid">
            @foreach ($homeEvents as $story)
                @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'event', 'storyUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien.chi-tiet', ['slug' => $story->slug])])
            @endforeach
        </div>
    @else
        <p class="cms-empty">{{ __('frontend.ui.chua_co_su_kien_duoc_cong_bo') }}</p>
    @endif
</section>

</div>
