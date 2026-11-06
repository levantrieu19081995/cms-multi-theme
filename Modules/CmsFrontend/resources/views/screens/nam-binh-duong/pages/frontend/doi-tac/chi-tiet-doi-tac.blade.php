{{-- @php
    $configData = Helper::appClasses();
@endphp

<!-- Vendor Styles -->
@extends('cmsfrontend::screens.components.layouts.blankLayout')
<!-- Page Styles -->
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

@section('title', $lienket->title ?? 'Chi tiết đôi tác')
@section('date', \Carbon\Carbon::parse($lienket->created_at)->format('Y-m-d\TH:i:s'))
@section('description', $lienket->excerpt ?? 'Trang thông tin chi tiết về đối tác')
@section('keywords', 'doi tac, doi tac NBDUT, doi tac truong nam binh duong, ' . $lienket->title ?? '')
@section('content')


    <section class="page-detail top-detail mt-5">
        <div class="container d-flex">
            <div class="sidebar-1">
                <div class="post-content">{!! $lienket->content !!}</div>
            </div>
        </div>
    </section>
@endsection --}}


@php
    $configData = Helper::appClasses();
@endphp

@extends('cmsfrontend::screens.components.layouts.blankLayout')

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
      /* bạn có thể chỉnh nhanh 2 biến này nếu muốn */
      --ck-center-max: 700px;   /* ảnh giữa (chưa resize) tối đa */
      --ck-side-max:   320px;   /* ảnh trái/phải (chưa resize) tối đa */
    }

    /* ===== Khung an toàn cho nội dung ===== */
    .post-content{
      max-width: 100%;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .post-content img,
    .post-content figure img,
    .post-content video,
    .post-content iframe,
    .post-content canvas,
    .post-content svg{
      display: block;
      max-width: 100%;
      height: auto;
    }
    .post-content figure{ max-width: 100%; }
    .post-content table{ display:block; width:100%; overflow-x:auto; }
    .post-content pre  { max-width:100%; overflow-x:auto; white-space:pre-wrap; }

    /* ===== Quy tắc CKEditor (đồng bộ với trang Tin tức/Sự kiện) ===== */

    /* Ảnh bên trong figure mặc định fill theo figure */
    .post-content.ck-content .image > img{
      display:block; width:100%; height:auto;
    }

    /* Tôn trọng ảnh đã resize trong CKEditor */
    .post-content.ck-content .image.image_resized{ max-width:100%; }
    .post-content.ck-content .image.image_resized > img{
      width:auto; max-width:100%; height:auto;
    }

    /* Ảnh block CHƯA resize & không align trái/phải: giới hạn để ảnh giữa không bự */
    .post-content.ck-content .image:not(.image_resized):not(.image-style-block-align-left):not(.image-style-block-align-right){
      width:auto;
      max-width: var(--ck-center-max);
      margin: 1rem auto;
      box-sizing: border-box;
    }

    /* Căn giữa khi dùng nút Align center */
    .post-content.ck-content .image.image-style-align-center{
      margin-left: auto; margin-right: auto;
    }

    /* Ảnh align trái/phải CHƯA resize: tự nhỏ gọn, không float để tránh “chạy chữ” */
    .post-content.ck-content .image.image-style-block-align-left:not(.image_resized),
    .post-content.ck-content .image.image-style-block-align-right:not(.image_resized){
      width:auto;
      max-width: var(--ck-side-max);
      float:none !important;           /* muốn chữ ôm quanh: thay bằng float: left/right; */
      display:block;
      margin-top:.5rem; margin-bottom:.75rem;
    }
    .post-content.ck-content .image.image-style-block-align-left{
      margin-left:0; margin-right:auto;           /* tựa trái */
    }
    .post-content.ck-content .image.image-style-block-align-right{
      margin-left:auto; margin-right:0;           /* tựa phải */
    }

    /* Caption gọn gàng */
    .post-content.ck-content .image figcaption{
      text-align:center; font-size:.875rem; color:#6c757d; margin-top:.25rem;
    }

    /* Mobile: luôn vừa khung */
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

@section('title', $lienket->title ?? __('pages.chi_tiet_doi_tac_2'))
@section('date', \Carbon\Carbon::parse($lienket->created_at)->format('Y-m-d\TH:i:s'))
@section('description', $lienket->excerpt ?? __('pages.trang_thong_tin_chi_tiet_ve_doi_tac'))
@section('keywords', 'doi tac, doi tac NBDUT, doi tac truong nam binh duong, ' . ($lienket->title ?? ''))

@section('content')

  <section class="page-detail top-detail mt-5">
    <div class="container py-5">
      <div class="sidebar-1">
        {{-- Thêm ck-content để áp CSS CKEditor --}}
        <div class="post-content ck-content">
          {!! $lienket->content !!}
        </div>
      </div>
    </div>
  </section>
@endsection
