{{-- Thư viện hình ảnh – theme "Hiện đại": 360° ring carousel. Data: $homeAlbums. --}}
@if ($homeAlbums->isNotEmpty())
    {{-- 360° ring carousel (ported from 21st.dev "Image Fan Carousel" / Carousel360 to CSS 3D + plain JS):
         album thumbnails orbit on a tilted ring and auto-rotate; the album at the front of the ring is shown
         large in the centre. Without JS the thumbnails are a scrollable row of links. --}}
    @php $ringFirst = null; @endphp
    <div class="cms-ring" data-cms-ring data-label-photos="{{ __('frontend.ui.anh') }}" data-label-pause="{{ __('frontend.ui.tam_dung_tu_xoay') }}" data-label-resume="{{ __('frontend.ui.tiep_tuc_tu_xoay') }}" data-label-status="{{ __('frontend.ui.album_vi_tri') }}">
        <div class="cms-ring-stage" role="group" aria-roledescription="carousel" aria-label="{{ __('frontend.ui.thu_vien_hinh_anh') }}">
            <div class="cms-ring-orbit" data-cms-ring-orbit>
                @foreach ($homeAlbums as $album)
                    @php
                        $albumMedia = $album->media->where('collection_name', 'images')->filter(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'));
                        $albumImage = $album->getFirstMediaUrl('avatars') ?: $albumMedia->first()?->getUrl();
                        $albumUrl = $album->url ?? \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.show', ['slug' => $album->slug]);
                        $ringFirst ??= ['title' => $album->title, 'count' => $albumMedia->count(), 'image' => $albumImage, 'url' => $albumUrl];
                    @endphp
                    <a class="cms-ring-item" data-cms-ring-item href="{{ $albumUrl }}" data-title="{{ $album->title }}" data-count="{{ $albumMedia->count() }}" data-image="{{ $albumImage }}">
                        <span class="cms-ring-thumb {{ empty($albumImage) ? 'cms-image-empty' : '' }}">
                            @if (!empty($albumImage))<img src="{{ $albumImage }}" alt="" loading="lazy" width="96" height="96" draggable="false">@else<span class="cms-image-placeholder"><i class="ri-image-line" aria-hidden="true"></i></span>@endif
                            <span class="cms-ring-thumb-title">{{ $album->title }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
            <a class="cms-ring-center" data-cms-ring-center href="{{ $ringFirst['url'] }}" hidden>
                <span class="cms-ring-center-media">@if (!empty($ringFirst['image']))<img src="{{ $ringFirst['image'] }}" alt="" width="320" height="320" loading="lazy" data-cms-ring-image>@else<img alt="" width="320" height="320" data-cms-ring-image hidden>@endif</span>
                <span class="cms-ring-caption"><strong data-cms-ring-title>{{ $ringFirst['title'] }}</strong><span><span data-cms-ring-count>{{ $ringFirst['count'] }}</span> {{ __('frontend.ui.anh') }}</span></span>
            </a>
        </div>
        @if ($homeAlbums->count() > 1)
            {{-- Albums change by dragging, clicking a thumbnail, ←/→ keys or auto-rotation; only pause/play is a button. --}}
            <div class="cms-ring-controls" hidden>
                <button type="button" class="cms-ring-button cms-ring-toggle" data-cms-ring-toggle aria-label="{{ __('frontend.ui.tam_dung_tu_xoay') }}" aria-pressed="false"><i class="ri-pause-line" aria-hidden="true"></i></button>
                <span class="cms-sr-only" aria-live="polite" data-cms-ring-status></span>
            </div>
        @endif
    </div>
@else
    <p class="cms-empty">{{ __('frontend.ui.chua_co_album_hinh_anh_duoc_cong_bo') }}</p>
@endif
