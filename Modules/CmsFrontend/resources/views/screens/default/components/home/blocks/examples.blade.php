{{-- Gương tiêu biểu, tiêu đề "Khuyến nông số" (tách từ cụm Chuyên mục nổi bật). --}}
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
                @if ($exampleImage !== '')@include('cmsfrontend::screens.components.home.frame-image', ['src' => $exampleImage, 'width' => 480, 'height' => 480])@else<span class="cms-image-placeholder"><i class="ri-user-line" aria-hidden="true"></i></span>@endif
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
                            <span class="cms-exemplar-small-image {{ $exampleImage === '' ? 'cms-image-empty' : '' }}">@if ($exampleImage !== '')@include('cmsfrontend::screens.components.home.frame-image', ['src' => $exampleImage, 'width' => 240, 'height' => 160])@else<span class="cms-image-placeholder"><i class="ri-user-line" aria-hidden="true"></i></span>@endif</span>
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
</div>
