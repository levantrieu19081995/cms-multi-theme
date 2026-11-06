{{-- @php
    $configData = Helper::appClasses();
@endphp

 @extends('cmsfrontend::screens.components.layouts.blankLayout')
 @section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
    <style>
        .post-content {
            max-width: 100%;
            overflow-wrap: break-word;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .post-content table {
            width: 100%;
            overflow-x: auto;
            display: block;
        }
    </style>

@endsection

@section('title', $guongtieubieu->title ?? 'Chi tiết gương tiêu biểu')
@section('date', \Carbon\Carbon::parse($guongtieubieu->created_at)->format('Y-m-d\TH:i:s'))
@section('description', $guongtieubieu->excerpt ?? 'Trang thông tin chi tiết về gương tiêu biểu')
 @section('content')


    <section class="page-detail top-detail mt-5">
        <div class="container d-flex">
            <div class="sidebar-1">
                <div class="post-content">{!! $guongtieubieu->content !!}</div>
             </div>
        </div>
    </section>
@endsection --}}


@php
  $cmsShareMedia = $guongtieubieu->getFirstMedia('avatars');
  $cmsShareImage = $cmsShareMedia
    ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.social-image.guong-tieu-bieu', [
        'id' => $guongtieubieu->getKey(),
        'v' => $cmsShareMedia->updated_at?->getTimestamp() ?: $guongtieubieu->updated_at?->getTimestamp(),
      ])
    : asset(config('cmsfrontend.brand.banner'));
@endphp

@php
    $configData = Helper::appClasses();
@endphp

@extends('cmsfrontend::screens.components.layouts.blankLayout')

 
@section('title', $guongtieubieu->title ?? __('pages.chi_tiet_guong_tieu_bieu'))
@section('date', \Carbon\Carbon::parse($guongtieubieu->created_at)->format('Y-m-d\TH:i:s'))
@section('description', $guongtieubieu->excerpt ?? __('pages.trang_thong_tin_chi_tiet_ve_guong_tieu_bieu'))
@section('keywords', 'guong tieu bieu, truong cao dang nghe soc trang, ' . ($guongtieubieu->title ?? ''))
@section('meta-image', $cmsShareImage)
@if ($cmsShareMedia)
  @section('meta-image-width', '1200')
  @section('meta-image-height', '630')
  @section('meta-image-type', 'image/jpeg')
@endif

 
@section('page-style')
  @vite(Theme::asset([
    'assets/vendor/scss/pages/front-page-landing.scss',
    'assets/vendor/scss/pages/ui-carousel.scss',
    'assets/css/main.css'
  ]))
  @vite([
    'Modules/CmsFrontend/resources/assets/css/banner.css',
    'Modules/CmsFrontend/resources/assets/sass/app.scss',
    'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css',
    'resources/assets/css/slick/slick.css',
    'resources/assets/css/slick/slick-theme.css'
  ])

  <style>
    :root{
       --ck-center-max: 700px;  
      --ck-side-max:   320px;  
    }

     .post-content{
      max-width:100%;
      overflow-wrap:anywhere;
      word-break:break-word;
    }
    .post-content img,
    .post-content figure img,
    .post-content video,
    .post-content iframe,
    .post-content canvas,
    .post-content svg{
      display:block;
      max-width:100%;
      height:auto;
    }
    .post-content figure{ max-width:100%; }
    .post-content table{ display:block; width:100%; overflow-x:auto; }
    .post-content pre  { max-width:100%; overflow-x:auto; white-space:pre-wrap; }

 
     .post-content.ck-content .image > img{ display:block; width:100%; height:auto; }

     .post-content.ck-content .image.image_resized{ max-width:100%; }
    .post-content.ck-content .image.image_resized > img{
      width:auto; max-width:100%; height:auto;
    }

     .post-content.ck-content .image:not(.image_resized):not(.image-style-block-align-left):not(.image-style-block-align-right){
      width:auto;
      max-width:var(--ck-center-max);
      margin:1rem auto;
      box-sizing:border-box;
    }

     .post-content.ck-content .image.image-style-align-center{
      margin-left:auto; margin-right:auto;
    }

     .post-content.ck-content .image.image-style-block-align-left:not(.image_resized),
    .post-content.ck-content .image.image-style-block-align-right:not(.image_resized){
      width:auto;
      max-width:var(--ck-side-max);
      float:none !important;
      display:block;
      margin-top:.5rem; margin-bottom:.75rem;
    }
    .post-content.ck-content .image.image-style-block-align-left { margin-left:0;    margin-right:auto; }
    .post-content.ck-content .image.image-style-block-align-right{ margin-left:auto; margin-right:0;   }

 
    .post-content.ck-content .image figcaption{
      text-align:center; font-size:.875rem; color:#6c757d; margin-top:.25rem;
    }

 
    @media (max-width:768px){
      .post-content.ck-content .image,
      .post-content.ck-content .image.image-style-align-center,
      .post-content.ck-content .image.image-style-block-align-left,
      .post-content.ck-content .image.image-style-block-align-right{
        max-width:100%;
        margin-left:auto; margin-right:auto;
      }
    }
  </style>
@endsection

 
@section('content')

  <section class="page-detail top-detail mt-5">
    <div class="container py-5">
      <div class="sidebar-1">
         <div class="post-content ck-content">
          {!! $guongtieubieu->content !!}
        </div>
      </div>
    </div>
  </section>
@endsection

