@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', $video->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($video->excerpt ?? '')), 160) ?: $video->title)

@section('content')
    {{-- Video page: player from VideoSource (uploaded file, video URL or YouTube) and other videos. --}}
    <main class="cms-page-container cms-video-page" id="main-content">
        <nav class="cms-video-crumb" aria-label="Breadcrumb">
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}">{{ __('frontend.ui.trang_chu') }}</a><span aria-hidden="true">›</span>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.danh-sach') }}">{{ __('frontend.ui.video_khuyen_nong') }}</a>
        </nav>
        <article class="cms-video-main">
            <div class="cms-video-frame">
                @if ($source['type'] === 'file')
                    <video controls preload="metadata" playsinline @if($source['poster']) poster="{{ $source['poster'] }}" @endif>
                        <source src="{{ $source['url'] }}">
                        {{ __('frontend.ui.trinh_duyet_khong_ho_tro_phat_video') }} <a href="{{ $source['url'] }}">{{ __('frontend.ui.tai_video') }}</a>.
                    </video>
                @elseif ($source['type'] === 'youtube')
                    <iframe src="{{ $source['url'] }}" title="{{ $video->title }}" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                @else
                    <div class="cms-video-missing" @if($source['poster']) style="background-image:url('{{ $source['poster'] }}')" @endif>
        <p><i class="ri-video-off-line" aria-hidden="true"></i> {{ __('frontend.ui.chua_co_video') }}
                            @if ($source['type'] === 'link')<a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ __('frontend.ui.mo_lien_ket_video') }}</a>@endif
                        </p>
                    </div>
                @endif
            </div>
            <h1>{{ $video->title }}</h1>
            @if ($video->published_at)<time datetime="{{ \Illuminate\Support\Carbon::parse($video->published_at)->format('Y-m-d') }}"><i class="ri-calendar-line" aria-hidden="true"></i> {{ \Illuminate\Support\Carbon::parse($video->published_at)->format('d/m/Y') }}</time>@endif
            @if (filled($video->excerpt))<p class="cms-video-excerpt">{{ $video->excerpt }}</p>@endif
        </article>

        @if ($related->isNotEmpty())
            <section class="cms-video-related" aria-labelledby="cms-video-related-title">
                <h2 id="cms-video-related-title">{{ __('frontend.ui.video_khac') }}</h2>
                @include('cmsfrontend::screens.pages.frontend.video.cards', ['cards' => $related])
            </section>
        @endif
    </main>
@endsection
