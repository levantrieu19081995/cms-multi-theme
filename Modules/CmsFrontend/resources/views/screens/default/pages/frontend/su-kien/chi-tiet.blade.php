{{-- @extends('cmsfrontend::screens.components.layouts.blankLayout')
<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])

    <style>
        /* Giới hạn kích thước icon SVG trong elementor */
        .blog-content .elementor-icon-list-icon svg {
            width: 20px !important;
            height: 20px !important;
            flex-shrink: 0;
            display: inline-block;
        }

        /* Tăng khoảng cách giữa icon và text */
        .blog-content .elementor-icon-list-icon {
            margin-right: 10px;
            display: flex;
            align-items: center;
        }

        /* Đảm bảo chữ không bị ép ngang, được xuống dòng */
        .blog-content .elementor-icon-list-text {
            flex: 1;
            word-wrap: break-word;
            word-break: break-word;
            font-size: 1rem;
            line-height: 1.6;
            display: inline-block;
        }

        /* Cấu trúc mỗi dòng item: icon + chữ */
        .blog-content .elementor-icon-list-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 10px;
            flex-wrap: wrap;
        }

        .blog-content .elementor-element {
            width: 100% !important;
        }

        /* Tránh chữ đè khi khung hẹp */
        .blog-content p {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .blog-content {
            overflow-x: hidden;
        }
        
        .blog-content .image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
    </style>


@endsection

@section('title', $post->title ?? 'Chi tiết sự kiện')
@section('date', \Carbon\Carbon::parse($post->created_at)->format('Y-m-d\TH:i:s'))
@section('description', __('pages.chi_tiet_su_kien'))
@section('keywords', 'su kien, su kien cao dang soc trang, su kien truong soc trang, ' . $post->title ?? '')
@section('content')
    <div class="container py-5">
        <div class="row gx-30">
            <div class="col-xxl-8 col-lg-7">
                <div
                    class="post-35 post type-post status-publish format-gallery has-post-thumbnail hentry category-finance category-health category-technology tag-solution tag-students post_format-post-format-gallery th-blog blog-single">
                    <div class="w-100 rounded-3 border overflow-hidden" style="height: 300px">
                        <img src={{ $post->getFirstMediaUrl('avatars') ? $post->getFirstMediaUrl('avatars') : '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}
                            alt="blog s 1 1" class="img-fluid w-100 h-100 object-fit-cover">
                    </div>

                    <div class="blog-title">
                        <h2 class="fw-bold mt-4">{{ $post->title }}</h2>
                        <div class="blog-meta d-flex align-items-center gap-3 flex-wrap">
                            <span class="meta-item">
                                <i class="fas fa-clock" style="color: #0d5ef4;"></i>
                                Thời gian:
                                <span class="fw-bold">
                                    {{ \Carbon\Carbon::parse(($post->event_starts_at ?? $post->published_at))->format('H:i d/m/Y') }}
                                    -
                                    {{ \Carbon\Carbon::parse(($post->event_ends_at ?? $post->publication_date_expires))->format('H:i d/m/Y') }}
                                </span>
                            </span>
                            <span class="separator">|</span>
                            <span class="meta-item">
                                <i class="ri-map-pin-line" style="color: #0d5ef4;"></i>
                            {{ __('frontend.ui.dia_diem_2') }} <span class="fw-bold">{{ $post->excerpt }}</span>
                            </span>
                        </div>
                    </div>
                    <!-- Blog Meta -->
                    <div class="blog-content">
                        {!! $post->content !!}
                    </div>

                    <!-- Comments -->
                    <div class="th-comments-wrap">
                        @include('cmsfrontend::partials/commentsDetailPartial', [
                            'comments' => $comments,
                            'post' => $post,
                        ])
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-lg-5">
                @include('cmsfrontend::screens.pages.frontend.su-kien.partials.danh-sach', [
                    'eventsToday' => $eventsToday,
                ])
            </div>
        </div>
    </div>
@endsection --}}

@extends('cmsfrontend::screens.components.layouts.blankLayout')

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
    :root{
      /* đổi nhanh tại đây nếu muốn */
      --ck-center-max: 700px;   /* trần ảnh “giữa” chưa resize */
      --ck-side-max:   320px;   /* trần ảnh trái/phải chưa resize */
    }

    /* ========= Icon list (Elementor) ========= */
    .blog-content .elementor-icon-list-icon svg{
      width:20px!important;height:20px!important;flex-shrink:0;display:inline-block;
    }
    .blog-content .elementor-icon-list-icon{margin-right:10px;display:flex;align-items:center;}
    .blog-content .elementor-icon-list-text{
      flex:1;word-wrap:break-word;word-break:break-word;font-size:1rem;line-height:1.6;display:inline-block;
    }
    .blog-content .elementor-icon-list-item{
      display:flex;align-items:flex-start;margin-bottom:1rem;gap:10px;flex-wrap:wrap;
    }
    .blog-content .elementor-element{width:100%!important;}
    .blog-content p{margin:0;padding:0;width:100%;}

    /* ========= Khung content an toàn ========= */
    .post-content{max-width:100%;overflow-wrap:anywhere;word-break:break-word;}
    .post-content img,
    .post-content figure img,
    .post-content video,
    .post-content iframe,
    .post-content canvas,
    .post-content svg{display:block;max-width:100%;height:auto;}
    .post-content figure{max-width:100%;}
    .post-content table{display:block;width:100%;overflow-x:auto;}
    .post-content pre{max-width:100%;overflow-x:auto;white-space:pre-wrap;}
    .blog-content{overflow-x:hidden;}

    /* ========= CKEditor content (ảnh & figure) ========= */
    /* Ảnh trong figure mặc định fill theo figure */
    .blog-content.ck-content .image > img{display:block;width:100%;height:auto;}

    /* Tôn trọng ảnh đã resize trong CKEditor */
    .blog-content.ck-content .image.image_resized{max-width:100%;}
    .blog-content.ck-content .image.image_resized > img{width:auto;max-width:100%;height:auto;}

    /* Ảnh block CHƯA resize & không align trái/phải -> hạn chế bự, căn giữa */
    .blog-content.ck-content .image:not(.image_resized):not(.image-style-block-align-left):not(.image-style-block-align-right){
      width:auto;max-width:var(--ck-center-max);margin:1rem auto;box-sizing:border-box;
    }

    /* Align center */
    .blog-content.ck-content .image.image-style-align-center{margin-left:auto;margin-right:auto;}

    /* Align trái/phải (mặc định KHÔNG float để tránh chữ ôm) */
    .blog-content.ck-content .image.image-style-block-align-left,
    .blog-content.ck-content .image.image-style-block-align-right{
      width:auto;
      max-width:min(45%, var(--ck-side-max));
      display:block;float:none;           /* muốn chữ ôm, thay bằng float:left/right (ghi chú bên dưới) */
      margin:.5rem auto .75rem;
    }
    .blog-content.ck-content .image.image-style-block-align-left{margin-left:0;margin-right:auto;}
    .blog-content.ck-content .image.image-style-block-align-right{margin-left:auto;margin-right:0;}

    /* Caption gọn */
    .blog-content.ck-content .image figcaption{
      text-align:center;font-size:.875rem;color:#6c757d;margin-top:.25rem;
    }

    /* Mobile: luôn vừa khung */
    @media (max-width:768px){
      .blog-content.ck-content .image,
      .blog-content.ck-content .image.image-style-align-center,
      .blog-content.ck-content .image.image-style-block-align-left,
      .blog-content.ck-content .image.image-style-block-align-right{
        max-width:100%;margin-left:auto;margin-right:auto;float:none;
      }
    }

    /* --- Tuỳ chọn: nếu muốn chữ ôm quanh ảnh trái/phải trên desktop, dùng block sau:
    @media (min-width:769px){
      .blog-content.ck-content .image.image-style-block-align-left{float:left;margin:.25rem 1rem .5rem 0;}
      .blog-content.ck-content .image.image-style-block-align-right{float:right;margin:.25rem 0 .5rem 1rem;}
    }
    --- */
  </style>
@endsection

@php
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

@section('title', $post->title ?? __('pages.chi_tiet_su_kien'))
@section('date', \Carbon\Carbon::parse($post->created_at)->format('Y-m-d\TH:i:s'))
@section('description', $cmsShareDescription)
@section('keywords', __('pages.su_kien_khuyen_nong_can_tho_nong_nghiep') . ($post->title ?? ''))
@section('meta-image', $cmsShareImage)
@if ($cmsShareMedia)
  @section('meta-image-width', '1200')
  @section('meta-image-height', '630')
  @section('meta-image-type', 'image/jpeg')
@endif
@section('meta-type', 'article')
@section('article-published-time', optional($post->created_at)->toIso8601String())
@section('article-modified-time', optional($post->updated_at)->toIso8601String())

@section('content')

  <div class="container py-5">
    <div class="row gx-30">
      <div class="col-xxl-8 col-lg-7">
        <div class="post-35 post type-post status-publish format-gallery has-post-thumbnail hentry th-blog blog-single">

          <div class="w-100 rounded-3 border overflow-hidden" style="height:300px">
            <img
              src="{{ $post->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
              alt="cover"
              class="img-fluid w-100 h-100 object-fit-cover"
              onerror="this.src='/assets/img/khuyen-nong-can-tho/default-image.jpg'">
          </div>

          <div class="blog-title">
            <h2 class="fw-bold mt-4">{{ $post->title }}</h2>
            <div class="blog-meta d-flex align-items-center gap-3 flex-wrap">
              <span class="meta-item">
                <i class="fas fa-clock" style="color:#0d5ef4;"></i>
                {{ __('frontend.ui.thoi_gian') }}
                <span class="fw-bold">
                  {{ \Carbon\Carbon::parse(($post->event_starts_at ?? $post->published_at))->format('H:i d/m/Y') }}
                  -
                  {{ \Carbon\Carbon::parse(($post->event_ends_at ?? $post->publication_date_expires))->format('H:i d/m/Y') }}
                </span>
              </span>
              <span class="separator">|</span>
              <span class="meta-item">
                <i class="ri-map-pin-line" style="color:#0d5ef4;"></i>
                {{ __('frontend.ui.dia_diem_2') }} <span class="fw-bold">{{ $post->excerpt }}</span>
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="blog-content ck-content post-content">
            {!! \Modules\CmsFrontend\Support\PublicContent::forDisplay($post->content) !!}
          </div>

          <!-- Comments -->
          <div class="th-comments-wrap">
            @include('cmsfrontend::partials/commentsDetailPartial', [
              'comments' => $comments,
              'post'     => $post,
            ])
          </div>
        </div>
      </div>

      <div class="col-xxl-4 col-lg-5">
        @include('cmsfrontend::screens.pages.frontend.su-kien.partials.danh-sach', [
          'eventsToday' => $eventsToday,
        ])
      </div>
    </div>
  </div>
@endsection

