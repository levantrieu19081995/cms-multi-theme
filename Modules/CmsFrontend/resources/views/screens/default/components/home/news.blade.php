<section class="cms-news-section cms-home-section cms-home-section--white" aria-label="{{ __('frontend.ui.tin_tuc_noi_bat') }}">
    @if ($homeNews->isNotEmpty())
        <div class="cms-news-top {{ $homeNews->count() < 4 ? 'cms-news-top--compact' : '' }}">
            <div class="cms-news-lead">
                @include('cmsfrontend::screens.components.home.story-card', ['story' => $homeNews->first(), 'variant' => 'lead'])
            </div>
            @if ($homeNews->count() > 1)
                <div class="cms-news-secondary">
                    @foreach ($homeNews->slice(1, 2) as $story)
                        @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'secondary'])
                    @endforeach
                </div>
            @endif
            @if ($homeNews->count() > 6)
                <aside class="cms-news-latest" aria-label="{{ __('frontend.ui.tin_moi') }}">
                    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.tin_moi'), 'moreUrl' => $newsUrl])
                    <div class="cms-latest-list">
                        @foreach ($homeNews->slice(6, 5) as $story)
                            @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'latest'])
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
        @if ($homeNews->count() > 3)
            <div class="cms-news-bottom">
                @foreach ($homeNews->slice(3, 3) as $story)
                    @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'standard'])
                @endforeach
            </div>
        @endif
    @else
        <div class="cms-empty cms-empty--news">
            <h2>{{ __('frontend.ui.tin_tuc_khuyen_nong_can_tho') }}</h2>
            <p>{{ __('frontend.ui.chua_co_bai_viet_duoc_cong_bo') }}</p>
        </div>
    @endif
</section>
