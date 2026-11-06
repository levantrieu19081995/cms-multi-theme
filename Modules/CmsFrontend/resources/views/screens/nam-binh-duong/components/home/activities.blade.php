<div class="cms-screen-group cms-production-group">
{{-- "Chuyên mục nổi bật": tabs = categories chosen in Settings; each panel keeps the 1 lead + 3 story layout.
     Without JS the tabs are plain links to the category pages and the first panel stays visible. --}}
@php $categoryTabs = collect($homeCategoryTabs ?? []); @endphp
<section class="cms-section cms-technical" aria-labelledby="cms-featured-title" data-cms-tabs>
    <div class="cms-section-heading">
        <h2 id="cms-featured-title">{{ __('frontend.ui.chuyen_muc_noi_bat') }}</h2>
        @if ($categoryTabs->isNotEmpty())<a class="cms-more" href="{{ $categoryTabs->first()['url'] }}" data-cms-tabs-more>{{ __('frontend.ui.xem_tat_ca') }} </a>@endif
    </div>
    @if ($categoryTabs->isNotEmpty())
        {{-- Scroll buttons appear (via JS) only when the tabs overflow the row. --}}
        <div class="cms-category-tabs-wrap" data-cms-tabs-scroller>
            <button type="button" class="cms-tabs-scroll cms-tabs-scroll--prev" data-cms-tabs-scroll="-1" aria-label="{{ __('frontend.ui.xem_chuyen_muc_truoc') }}" hidden><i class="ri-arrow-left-s-line" aria-hidden="true"></i></button>
            <div class="cms-category-tabs" data-cms-tabs-list aria-label="{{ __('frontend.ui.chuyen_muc_noi_bat') }}">
                @foreach ($categoryTabs as $tab)
                    <a class="cms-category-tab @if($loop->first) is-active @endif" href="{{ $tab['url'] }}" id="cms-featured-tab-{{ $tab['id'] }}"
                        data-cms-tab="cms-featured-panel-{{ $tab['id'] }}" @if($loop->first) aria-current="true" @endif>{{ $tab['title'] }}</a>
                @endforeach
            </div>
            <button type="button" class="cms-tabs-scroll cms-tabs-scroll--next" data-cms-tabs-scroll="1" aria-label="{{ __('frontend.ui.xem_chuyen_muc_tiep_theo') }}" hidden><i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
        </div>
        @foreach ($categoryTabs as $tab)
            <div class="cms-technical-grid" id="cms-featured-panel-{{ $tab['id'] }}" data-cms-tab-panel aria-labelledby="cms-featured-tab-{{ $tab['id'] }}" @unless($loop->first) hidden @endunless>
                @foreach ($tab['stories'] as $story)
                    @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => $loop->first ? 'technical-lead' : 'technical', 'categoryLabel' => $tab['title']])
                @endforeach
            </div>
        @endforeach
    @else
        <p class="cms-empty">{{ __('frontend.ui.chua_co_chuyen_muc_noi_bat_co_bai_viet_duoc_cong') }}</p>
    @endif
</section>

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

<div class="cms-screen-group cms-community-group">
<section class="cms-section cms-exemplars" aria-label="{{ __('frontend.ui.guong_tieu_bieu') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.guong_tieu_bieu'), 'moreUrl' => null])
    @if ($homeExamples->isNotEmpty())
        @php
            $example = $homeExamples->first();
            $exampleUrl = $example->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.guong-tieu-bieu.detail-by-id', ['id' => $example->url_key]);
            $exampleImage = trim((string) $example->getFirstMediaUrl('avatars'));
        @endphp
        <div class="cms-exemplar-grid {{ $homeExamples->count() === 1 ? 'cms-exemplar-grid--single' : '' }}">
            <a class="cms-exemplar-portrait {{ $exampleImage === '' ? 'cms-image-empty' : '' }}" href="{{ $exampleUrl }}" tabindex="-1" aria-hidden="true">
                @if ($exampleImage !== '')<img src="{{ $exampleImage }}" alt="" loading="lazy" width="480" height="480">@else<span class="cms-image-placeholder"><i class="ri-user-line" aria-hidden="true"></i></span>@endif
            </a>
            <div class="cms-exemplar-intro">
                @if (filled($example->role))<span class="cms-story-label cms-story-label--outline">{{ $example->role }}</span>@endif
                <h3><a href="{{ $exampleUrl }}">{{ $example->title }}</a></h3>
                @if (filled($example->excerpt))<p>{{ \Illuminate\Support\Str::limit(strip_tags($example->excerpt), 240) }}</p>@endif
                <a class="cms-more" href="{{ $exampleUrl }}">{{ __('frontend.ui.xem_them') }} </a>
            </div>
            @if ($homeExamples->count() > 1)
                <div class="cms-exemplar-related">
                    @foreach ($homeExamples->skip(1) as $example)
                        @php $exampleImage = trim((string) $example->getFirstMediaUrl('avatars')); @endphp
                        <a class="cms-exemplar-small" href="{{ $example->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.guong-tieu-bieu.detail-by-id', ['id' => $example->url_key]) }}">
                            <span class="cms-exemplar-small-image {{ $exampleImage === '' ? 'cms-image-empty' : '' }}">@if ($exampleImage !== '')<img src="{{ $exampleImage }}" alt="" loading="lazy" width="240" height="160">@else<span class="cms-image-placeholder"><i class="ri-user-line" aria-hidden="true"></i></span>@endif</span>
                            <div><h3>{{ $example->title }}</h3>@if (filled($example->excerpt))<p>{{ \Illuminate\Support\Str::limit(strip_tags($example->excerpt), 95) }}</p>@endif</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <p class="cms-empty">{{ __('frontend.ui.chua_co_guong_tieu_bieu_duoc_dang') }}</p>
    @endif
</section>
@include('cmsfrontend::screens.components.home.services')
</div>
