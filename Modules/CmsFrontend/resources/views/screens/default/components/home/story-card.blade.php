@php
    $variant = $variant ?? 'standard';
    $storyUrl = $story->url ?? $storyUrl ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $story->slug]);
    $storyImage = trim((string) $story->getFirstMediaUrl('avatars'));
    $storyExcerpt = trim(strip_tags((string) ($story->excerpt ?? '')));
    $storyCategory = $categoryLabel ?? ($story->relationLoaded('taxonomies') ? $story->taxonomies->first()?->term?->name : null);
@endphp
<article class="cms-story cms-story--{{ $variant }}">
    <a class="cms-story-image {{ $storyImage === '' ? 'cms-image-empty' : '' }}" href="{{ $storyUrl }}" tabindex="-1" aria-hidden="true">
        @if ($storyImage !== '')
            @include('cmsfrontend::screens.components.home.frame-image', ['src' => $storyImage, 'width' => 640, 'height' => 360, 'priority' => $variant === 'lead'])
        @else
            <span class="cms-image-placeholder"><i class="ri-image-line" aria-hidden="true"></i></span>
        @endif
    </a>
    <div class="cms-story-content">
        @if ($variant === 'lead')
            <span class="cms-story-label cms-story-label--filled">{{ __('frontend.ui.tin_noi_bat') }}</span>
        @elseif (filled($storyCategory) && $variant !== 'latest' && $variant !== 'row')
            <span class="cms-story-label">{{ $storyCategory }}</span>
        @endif
        @if ($variant === 'lead')
            <h2 class="cms-story-title"><a href="{{ $storyUrl }}">@if(!empty($story->title_lines))@foreach($story->title_lines as $line)<span class="cms-title-line">{{ $line }}@if(!$loop->last) @endif</span>@endforeach @else{{ $story->title }}@endif</a></h2>
        @else
            <h3 class="cms-story-title"><a href="{{ $storyUrl }}">{{ $story->title }}</a></h3>
        @endif
        @if ($storyExcerpt !== '' && $variant !== 'latest')
            <p class="cms-story-excerpt">{{ \Illuminate\Support\Str::limit($storyExcerpt, $variant === 'lead' ? 220 : 125) }}</p>
        @endif
        @if ($variant === 'lead')
            <a class="cms-more cms-story-read" href="{{ $storyUrl }}">{{ __('frontend.ui.xem_chi_tiet') }} <span aria-hidden="true">→</span></a>
        @endif
    </div>
</article>
