{{-- Homepage hero carousel; slides come from the Banner admin screen (HomeBlocks::banners). Hidden when none. --}}
@if (!empty($homeBanners))
    {{-- Khung theo tỉ lệ ảnh banner đầu tiên; ảnh khác tỉ lệ vẫn hiện nguyên (không cắt), phần dư lấp bằng chính ảnh làm mờ. --}}
    <section class="cms-hero" aria-roledescription="carousel" aria-label="{{ __('frontend.ui.banner_trang_chu') }}" data-cms-hero style="--cms-hero-ratio: {{ (int) $homeBanners[0]['width'] }} / {{ (int) $homeBanners[0]['height'] }}">
        <div class="cms-hero-track">
            @foreach ($homeBanners as $banner)
                <article class="cms-hero-slide {{ $loop->first ? 'is-active' : '' }}" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}" @unless($loop->first) aria-hidden="true" @endunless>
                    @include('cmsfrontend::screens.components.home.frame-image', ['src' => $banner['image'], 'width' => (int) $banner['width'], 'height' => (int) $banner['height'], 'priority' => $loop->first])
                    @if ($banner['show_text'] ?? true)
                        <div class="cms-hero-copy">
                            <h2>{{ $banner['title'] }}</h2>
                            @if ($banner['description'])<p>{{ $banner['description'] }}</p>@endif
                            @if ($banner['url'])<a class="cms-hero-button" href="{{ $banner['url'] }}" @unless($loop->first) tabindex="-1" @endunless>{{ $banner['button'] }} <i class="ri-arrow-right-line" aria-hidden="true"></i></a>@endif
                        </div>
                    @elseif ($banner['url'])
                        {{-- Chỉ hiện hình: cả banner bấm được nếu có liên kết. --}}
                        <a class="cms-hero-cover-link" href="{{ $banner['url'] }}" aria-label="{{ $banner['title'] }}" @unless($loop->first) tabindex="-1" @endunless></a>
                    @endif
                </article>
            @endforeach
        </div>
        @if (count($homeBanners) > 1)
            <button class="cms-hero-arrow cms-hero-arrow--prev" type="button" data-cms-hero-prev aria-label="{{ __('frontend.ui.banner_truoc') }}"><i class="ri-arrow-left-s-line" aria-hidden="true"></i></button>
            <button class="cms-hero-arrow cms-hero-arrow--next" type="button" data-cms-hero-next aria-label="{{ __('frontend.ui.banner_tiep_theo') }}"><i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
            <div class="cms-hero-dots">
                @foreach ($homeBanners as $banner)
                    <button type="button" data-cms-hero-dot="{{ $loop->index }}" aria-label="Xem banner {{ $loop->iteration }}" @if($loop->first) aria-current="true" @endif></button>
                @endforeach
            </div>
        @endif
    </section>
@endif
