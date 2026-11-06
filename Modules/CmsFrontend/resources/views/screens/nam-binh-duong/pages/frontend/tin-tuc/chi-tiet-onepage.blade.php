{{-- @php
    $configData = Helper::appClasses();
@endphp



@section('title', $post->title ?? 'Giới thiệu')
@section('date', $post->created_at ? \Carbon\Carbon::parse($post->created_at)->format('Y-m-d\TH:i:s') : '')
@section('description', __('pages.chi_tiet_tin_tuc'))
@section('keywords', 'tin tuc, tin tuc NBDUT, tin tuc soc trang, ' . $post->title ?? '')

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

        .blog-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
    </style>

@endsection


@section('content')
    <section class="page-detail top-detail mt-5">
        <div class="container py-5">
            <div class="row gx-30">
                <div class="sidebar-1">
                    <div class="blog-content">{!! $post->content !!}</div>
                </div>
            </div>
        </div>
    </section>
@endsection --}}
{{-- Luôn đặt extends lên đầu --}}
{{-- Luôn đặt extends lên đầu --}}


@extends('cmsfrontend::screens.components.layouts.blankLayout')

@php
  $configData = Helper::appClasses();
  $cmsShareDescriptionSource = $post->excerpt ?: $post->content ?: \Modules\CmsFrontend\Support\Brand::unit();
  $cmsShareDescription = trim((string) preg_replace(
    '/\s+/u',
    ' ',
    html_entity_decode(strip_tags((string) $cmsShareDescriptionSource), ENT_QUOTES | ENT_HTML5, 'UTF-8')
  ));
  $cmsShareDescription = \Illuminate\Support\Str::limit(
    $cmsShareDescription ?: \Modules\CmsFrontend\Support\Brand::unit(),
    180
  );
  $cmsShareMedia = $post->getFirstMedia('avatars');
  $cmsShareImage = $cmsShareMedia
    ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.social-image', [
        'post' => $post->getKey(),
        'v' => $cmsShareMedia->updated_at?->getTimestamp() ?: $post->updated_at?->getTimestamp(),
      ])
    : asset(config('cmsfrontend.brand.banner'));
@endphp

{{-- Meta --}}
@section('title', $post->title ?? __('pages.gioi_thieu'))
@section('date', $post->created_at ? \Carbon\Carbon::parse($post->created_at)->format('Y-m-d\TH:i:s') : '')
@section('description', $cmsShareDescription)
@section('keywords', __('pages.nbdut_nam_binh_duong_giao_duc_cong_nghe') . ($post->title ?? ''))
@section('meta-image', $cmsShareImage)
@if ($cmsShareMedia)
  @section('meta-image-width', '1200')
  @section('meta-image-height', '630')
  @section('meta-image-type', 'image/jpeg')
@endif
@section('meta-type', 'article')
@section('article-published-time', optional($post->created_at)->toIso8601String())
@section('article-modified-time', optional($post->updated_at)->toIso8601String())

{{-- Page Styles --}}
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
    /* ======================= KHUNG NỘI DUNG ======================= */
    .post-content{
      max-width:100%;
      overflow-wrap:anywhere;
      word-break:break-word;
    }
    .post-content figure{max-width:100%;}
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
    .post-content table{display:block;width:100%;overflow-x:auto;}
    .post-content pre{max-width:100%;overflow-x:auto;white-space:pre-wrap;}

    /* ======================= CKEDITOR CONTENT ======================= */
    /* Tham số dễ chỉnh */
    :root{
      --ck-center-max: 700px;  /* trần ảnh canh giữa/chung */
      --ck-side-max:   320px;  /* trần ảnh canh trái/phải trên desktop */
    }

    /* Ảnh luôn fit theo khung figure (để không vỡ layout) */
    .post-content.ck-content .image > img{
      width:100%;
      height:auto;
    }

    /* A) Ảnh block CHƯA resize & KHÔNG align trái/phải -> giới hạn để ảnh giữa không bự */
    .post-content.ck-content .image:not(.image_resized):not(.image-style-block-align-left):not(.image-style-block-align-right){
      max-width: var(--ck-center-max);
      margin: 1rem auto; /* căn giữa */
    }

    /* B) Khi dùng nút Align center */
    .post-content.ck-content .image.image-style-align-center{
      margin-left:auto;
      margin-right:auto;
    }

    /* C) Ảnh canh TRÁI/PHẢI: nhỏ gọn có trần cố định, đứng riêng 1 hàng để tránh “chạy chữ” */
    .post-content.ck-content .image.image-style-block-align-left,
    .post-content.ck-content .image.image-style-block-align-right{
      max-width: var(--ck-side-max);
      float: none !important;   /* nếu muốn chữ ôm quanh hãy đổi sang float */
      display: block;
      margin-top: .5rem;
      margin-bottom: .75rem;
    }
    .post-content.ck-content .image.image-style-block-align-left  { margin-left: 0;    margin-right: auto; }
    .post-content.ck-content .image.image-style-block-align-right { margin-left: auto; margin-right: 0; }

    /* (Tùy chọn) Caption */
    .post-content.ck-content .image figcaption{
      text-align:center; font-size:.875rem; color:#6c757d; margin-top:.25rem;
    }

    /* D) Mobile: mọi ảnh full chiều ngang để đỡ chật */
    @media (max-width:768px){
      .post-content.ck-content .image,
      .post-content.ck-content .image.image-style-align-center,
      .post-content.ck-content .image.image-style-block-align-left,
      .post-content.ck-content .image.image-style-block-align-right{
        max-width:100%;
        margin-left:auto;
        margin-right:auto;
      }
    }
  </style>
@endsection

{{-- Content --}}
@section('content')

  <section class="page-detail top-detail mt-5">
    <div class="container py-5">
      <div class="row gx-30">
        <div class="sidebar-1">
          <div class="post-content ck-content">
            {!! \Modules\CmsFrontend\Support\PublicContent::forDisplay($post->content) !!}
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection


