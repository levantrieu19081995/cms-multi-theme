@php
    $videoUrl = (string) ($homeVideo['embed_url'] ?? '');
    $videoTitle = (string) ($homeVideo['title'] ?? __('pages.video_khuyen_nong'));
    $videoPoster = (string) ($homeVideo['poster'] ?? '');
    $videoDetail = (!empty($homeVideo['slug']) ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.show', ['slug' => $homeVideo['slug']]) : null);
    $embedHost = strtolower((string) parse_url($videoUrl, PHP_URL_HOST));
    $isTrustedEmbed = in_array($embedHost, ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com', 'player.vimeo.com'], true) && parse_url($videoUrl, PHP_URL_SCHEME) === 'https';
    $isSafeVideoUrl = in_array(strtolower((string) parse_url($videoUrl, PHP_URL_SCHEME)), ['', 'http', 'https'], true) && $videoUrl !== '';
@endphp
<article class="cms-video-card {{ $leadVideo ? 'cms-video-card--lead' : '' }}">
    @if (!$leadVideo && $videoDetail)
        <a class="cms-video-thumb {{ $videoPoster === '' ? 'cms-image-empty' : '' }}" href="{{ $videoDetail }}" tabindex="-1" aria-hidden="true">
            @if ($videoPoster !== '')@include('cmsfrontend::screens.components.home.frame-image', ['src' => $videoPoster, 'width' => 240, 'height' => 160])@endif
            <span class="cms-video-play"><i class="ri-play-fill" aria-hidden="true"></i></span>
        </a>
    @elseif (($homeVideo['type'] ?? '') === 'file' && $isSafeVideoUrl)
        <video class="cms-video-player" controls preload="none" @if ($videoPoster !== '') poster="{{ $videoPoster }}" @endif aria-label="{{ $videoTitle }}">
            <source src="{{ $videoUrl }}">
            {{ __('frontend.ui.trinh_duyet_khong_ho_tro_phat_video') }} <a href="{{ $videoUrl }}">{{ __('frontend.ui.mo_tap_tin_video') }}</a>.
        </video>
    @elseif ($isTrustedEmbed)
        <iframe class="cms-video-player" src="{{ $videoUrl }}" title="{{ $videoTitle }}" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
    @elseif ($videoDetail || $isSafeVideoUrl)
        <a class="cms-video-thumb cms-image-empty" href="{{ $videoDetail ?: $videoUrl }}"><span class="cms-video-play"><i class="ri-play-fill" aria-hidden="true"></i></span><span class="cms-sr-only">{{ __('frontend.misc.open_video') }} {{ $videoTitle }}</span></a>
    @else
        <div class="cms-video-thumb cms-image-empty"><span class="cms-video-play"><i class="ri-video-line" aria-hidden="true"></i></span></div>
    @endif
    <h3>@if ($videoDetail)<a href="{{ $videoDetail }}">{{ $videoTitle }}</a>@else{{ $videoTitle }}@endif</h3>
</article>
