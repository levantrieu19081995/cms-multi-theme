{{-- Thư viện hình ảnh – theme "Báo chí" (base theme): editorial grid, lead album 2×2 + smaller albums. Data: $homeAlbums. --}}
@if ($homeAlbums->isNotEmpty())
    <div class="cms-gallery-grid {{ $homeAlbums->count() < 4 ? 'cms-gallery-grid--compact' : '' }}">
        @foreach ($homeAlbums as $album)
            @php
                $albumMedia = $album->media->where('collection_name', 'images')->filter(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'));
                $albumImage = $album->getFirstMediaUrl('avatars') ?: $albumMedia->first()?->getUrl();
            @endphp
            <a class="cms-gallery-item {{ $loop->first ? 'cms-gallery-item--lead' : '' }} {{ empty($albumImage) ? 'cms-image-empty' : '' }}" href="{{ $album->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.show', ['slug' => $album->slug]) }}">
                @if (!empty($albumImage))@include('cmsfrontend::screens.components.home.frame-image', ['src' => $albumImage, 'width' => 480, 'height' => 360])@else<span class="cms-image-placeholder"><i class="ri-image-line" aria-hidden="true"></i></span>@endif
                <div class="cms-gallery-caption"><h3>{{ $album->title }}</h3><span>{{ $albumMedia->count() }} {{ __('frontend.ui.anh') }}</span></div>
            </a>
        @endforeach
    </div>
@else
    <p class="cms-empty">{{ __('frontend.ui.chua_co_album_hinh_anh_duoc_cong_bo') }}</p>
@endif
