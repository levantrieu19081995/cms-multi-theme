<div class="cms-screen-group cms-home-section cms-home-section--white">
{{-- "Chuyên mục nổi bật": mỗi chuyên mục chọn ở Cài đặt trang chủ là một hàng (tiêu đề + "Xem tất cả" + dải thẻ tự trượt ngang). --}}
@php $categoryTabs = collect($homeCategoryTabs ?? []); @endphp
<section class="cms-section cms-featured" aria-labelledby="cms-featured-title">
    <div class="cms-section-heading cms-featured-heading">
        <h2 id="cms-featured-title">{{ __('frontend.ui.chuyen_muc_noi_bat') }}</h2>
        <p class="cms-featured-sub">{{ __('frontend.ui.chuyen_muc_noi_bat_mo_ta') }}</p>
    </div>
    @forelse ($categoryTabs as $tab)
        <div class="cms-featured-row">
            <div class="cms-featured-head">
                <h3 id="cms-featured-row-{{ $tab['id'] }}"><a href="{{ $tab['url'] }}">{{ $tab['title'] }}</a></h3>
                <a class="cms-more" href="{{ $tab['url'] }}">{{ __('frontend.ui.xem_tat_ca') }} <span aria-hidden="true">→</span></a>
            </div>
            <div class="cms-featured-track" data-cms-slider role="group" aria-labelledby="cms-featured-row-{{ $tab['id'] }}">
                @foreach ($tab['stories'] as $story)
                    @include('cmsfrontend::screens.components.home.story-card', ['story' => $story, 'variant' => 'technical', 'categoryLabel' => null])
                @endforeach
            </div>
        </div>
    @empty
        <p class="cms-empty">{{ __('frontend.ui.chua_co_chuyen_muc_noi_bat_co_bai_viet_duoc_cong') }}</p>
    @endforelse
</section>
</div>

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

<div class="cms-screen-group cms-community-group cms-home-section cms-home-section--green">
<section class="cms-section cms-exemplars" aria-label="{{ __('pages.khuyen_nong_so') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.khuyen_nong_so'), 'moreUrl' => null])
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
                <a class="cms-more" href="{{ $exampleUrl }}">{{ __('frontend.ui.xem_them') }} <span aria-hidden="true">→</span></a>
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
