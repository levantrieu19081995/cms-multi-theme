@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('page-style')
    @vite([
        'Modules/CmsFrontend/resources/assets/css/banner.css',
        'Modules/CmsFrontend/resources/assets/sass/app.scss',
    ])
    <style>
        /* Ảnh xếp theo hàng đều chiều cao, mỗi ảnh đúng tỉ lệ gốc (--r = rộng / cao), không cắt. Hàng cuối không bị kéo giãn. */
        .cms-album-rows { display: flex; flex-wrap: wrap; gap: 8px; }
        .cms-album-rows::after { content: ''; flex-grow: 1000000; }
        .cms-album-item { position: relative; display: block; min-width: 0; overflow: hidden; border-radius: 6px; background: #eef4ec; aspect-ratio: var(--r); flex: calc(var(--r) * 100) 1 calc(var(--r) * 200px); }
        .cms-album-item img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .cms-album-cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 22px 10px 8px; color: #fff; font-size: 13px; line-height: 1.4; text-align: center; background: linear-gradient(transparent, #000b); }
        @media (max-width: 575px) { .cms-album-item { flex-basis: calc(var(--r) * 120px); } }
    </style>
@endsection

@section('content')
 
    <section class="page-detail top-detail mt-5">
        <div class="container cms-page-container">
            <h2 class="mb-4  ">{{ $album->title }}</h2>

            <div class="cms-album-rows">
                @foreach ($album->image as $img)
                    <a class="cms-album-item" href="{{ $img['url'] }}" data-lightbox="album" target="_blank" rel="noopener" style="--r: {{ $img['ratio'] }}">
                        <img src="{{ $img['url'] }}" alt="{{ $img['description'] ?: $album->title }}" loading="lazy" decoding="async">
                        @if ($img['description'])
                            <span class="cms-album-cap">{{ $img['description'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
