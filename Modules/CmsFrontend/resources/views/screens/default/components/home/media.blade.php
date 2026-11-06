    <section class="cms-section cms-screen-group cms-gallery cms-home-section cms-home-section--cream" aria-label="{{ __('frontend.ui.thu_vien_hinh_anh') }}">
        @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.thu_vien_hinh_anh'), 'moreUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach')])
        @include(($homeTheme ?? app(\Modules\CmsFrontend\Services\HomeTheme::class))->view('gallery'))
    </section>
    <section class="cms-section cms-screen-group cms-video cms-home-section cms-home-section--cream" aria-label="{{ __('frontend.ui.video_khuyen_nong') }}">
        @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.video_khuyen_nong'), 'moreUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.danh-sach')])
        @if ($homeVideos->isNotEmpty())
            <div class="cms-video-grid {{ $homeVideos->count() < 4 ? 'cms-video-grid--compact' : '' }}">
                @foreach ($homeVideos as $homeVideo)
                    @include('cmsfrontend::screens.components.home.video-card', ['homeVideo' => $homeVideo, 'leadVideo' => $loop->first])
                @endforeach
            </div>
        @else
            <p class="cms-empty">{{ __('frontend.ui.chua_co_video_duoc_cong_bo') }}</p>
        @endif
    </section>
