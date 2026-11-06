@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('page-style')
    @vite([
        'Modules/CmsFrontend/resources/assets/css/banner.css',
        'Modules/CmsFrontend/resources/assets/sass/app.scss',
    ])
    <style>
        /* Đảm bảo ảnh hiển thị đồng đều */
        .album-image {
            width: 100%;
            height: 220px;       /* chỉnh lại theo layout mong muốn */
            object-fit: cover;   /* crop ảnh để vừa khung */
            border-radius: 6px;
        }
    </style>
@endsection

@section('content')
 
    <section class="page-detail top-detail mt-5">
        <div class="container cms-page-container">
            <h2 class="mb-4  ">{{ $album->title }}</h2>

            <div class="row">
                @foreach ($album->image as $img)
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card h-100">
                            <img src="{{ $img['url'] }}" class="card-img-top album-image" alt="{{ $album->title }}">
                            @if ($img['description'])
                                <div class="card-body">
                                    <p class="card-text text-center">{{ $img['description'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
