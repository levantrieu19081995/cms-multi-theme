@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.thu_vien_hinh_anh'))
@section('description', __('pages.danh_sach_hinh_anh_duoc_cong_bo_ra_trang_chu'))
@section('keywords', __('pages.thu_vien_hinh_anh')  )
<!-- Vendor Styles -->
@section('vendor-style')
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 
    'Modules/CmsFrontend/resources/assets/sass/app.scss', 
    'Modules/CmsFrontend/resources/assets/css/su-kien/su-kien.css', 
    'Modules/CmsFrontend/resources/assets/css/guong-tieu-bieu/guong-tieu-bieu.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 'assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/js/mega-dropdown.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js']))
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['Modules/CmsFrontend/resources/assets/js/banner.js', 
    'Modules/CmsFrontend/resources/assets/js/listing.js'])
@endsection
@section('content')  
    <section class="mt-5 mb-5">
        <div class="container ">
        <div class="row">
            <div class="col-md-12">
            <h2 class="mini-title " style="font-size: 26px;">
                <a href="javascript::void(0)" class="btn btn-icon btn-label-primary waves-effect " style="border-bottom: 0px"
                onclick="history.back()">
                <span class="tf-icons ri-arrow-left-line ri-22px"></span>
                </a>
                @if($id != null && $albums[0] != null)
                <a href="#">
                {{ $albums[0]->title }}
                </a>
                @else
                <a href="#">
                {{ __('pages.thu_vien_hinh_anh') }}
                </a>
                @endif
            </h2>
            <div class="danhsachbaiviet">
                <div class="row">
                @foreach ($albums as $index => $album)
                @foreach ($album->children as $i => $album_children)
                <div class="col-md-3">
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach', ['id'=> $album_children->id]) }}">
                    <div class="card" style="background-color:#f0f4f9">
                        <div class="card-body" style="padding:6px 12px; ">
                        <h5 class="card-title"><i class="ri-folder-image-line"></i> {{ $album_children->title }}</h5>
                        </div>
                    </div>
                    </a>
                </div>
                @endforeach
                @endforeach
                </div>
                <div class="row">
                @foreach ($albums as $index => $album)
                @foreach ($album->image as $i => $image)
                <div class="col-md-3">
                    <div class="card" style="background-color:#f0f4f9">
                    <div class="card-body" style="padding:12px var(--bs-card-spacer-x); padding-bottom:0">
                        <h5 class="card-title">{{ $image['description'] }}</h5>
                    </div>
                    <div class="card-body" style="padding-top:6px">
                        <div class="image-container">
                        <a href="{{ $image['url'] }}" data-lightbox="image-library">
                            <img src="{{ $image['url'] }}" class="d-block w-100 img-fluid">
                        </a>
                        </div>
                    </div>
                    </div>
                </div>
                @endforeach
                @endforeach
                </div>
            </div>
            </div>
        </div>
        </div>
    </section>
@endsection
