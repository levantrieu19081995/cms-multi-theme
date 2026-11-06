{{-- Video cards (VideoController::card): poster + play badge + title, linking to the video page. --}}
<div class="cms-video-cards">
    @foreach ($cards as $card)
        <a class="cms-video-tile" href="{{ $card['url'] }}">
            <span class="cms-video-tile-thumb {{ $card['poster'] ? '' : 'is-empty' }}">
                @if ($card['poster'])<img src="{{ $card['poster'] }}" alt="" loading="lazy" decoding="async" width="480" height="270">@endif
                <span class="cms-video-tile-play"><i class="ri-play-fill" aria-hidden="true"></i></span>
                @if ($card['type'] === 'youtube')<span class="cms-video-tile-badge">YouTube</span>@endif
            </span>
            <span class="cms-video-tile-title">{{ $card['title'] }}</span>
            @if ($card['date'])<span class="cms-video-tile-date">{{ $card['date'] }}</span>@endif
        </a>
    @endforeach
</div>
