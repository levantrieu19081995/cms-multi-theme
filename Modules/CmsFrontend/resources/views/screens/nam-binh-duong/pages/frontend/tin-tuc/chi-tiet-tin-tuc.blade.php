 @extends('cmsfrontend::screens.components.layouts.blankLayout')

{{-- Page Styles --}}
@section('page-style')
@vite([
  'Modules/CmsFrontend/resources/assets/css/banner.css',
  'Modules/CmsFrontend/resources/assets/sass/app.scss',
  'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css',
  'resources/assets/css/slick/slick.css',
  'resources/assets/css/slick/slick-theme.css'
])

<style>
  /* ================== Common ================== */
  @media (max-width: 767px) {
    .tintuc-title{
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
      min-height:3em;
    }
  }

  /* Khối nội dung không tràn */
  .blog-content{
    overflow-wrap:anywhere;
    word-break:break-word;
  }

  /* Media trong nội dung: không vượt khung */
  .blog-content img,
  .blog-content figure img,
  .blog-content video,
  .blog-content iframe,
  .blog-content canvas,
  .blog-content svg{
    display:block;
    max-width:100%;
    height:auto;
  }
  .blog-content figure{max-width:100%;}
  .blog-content table{display:block;max-width:100%;overflow-x:auto;}
  .blog-content pre{max-width:100%;overflow-x:auto;white-space:pre-wrap;}

  /* ================== CKEditor content ================== */

  /* Ảnh bên trong figure mặc định fill theo figure (trừ khi là ảnh đã resize) */
  .ck-content .image > img{
    display:block;
    width:100%;
    height:auto;
  }

  /* TÔN TRỌNG ảnh đã resize trong CKEditor (image_resized) */
  .ck-content .image.image_resized{max-width:100%;}
  .ck-content .image.image_resized > img{
    width:auto;       /* giữ width inline của <img> */
    max-width:100%;
    height:auto;
  }

  /* Ảnh block CHƯA resize & không align trái/phải:
     - Không full trang để ảnh giữa đỡ bự
     - Căn giữa */
  .ck-content .image:not(.image_resized):not(.image-style-block-align-left):not(.image-style-block-align-right){
    max-width:700px;         /* chỉnh 600/800 tuỳ ý */
    margin:1rem auto;
  }

  /* Nút Align center */
  .ck-content .image.image-style-align-center{
    margin-left:auto;
    margin-right:auto;
  }

  /* Align trái/phải: KHÔNG float để chữ không ôm; mỗi ảnh đứng một hàng */
  .ck-content .image.image-style-block-align-left,
  .ck-content .image.image-style-block-align-right{
    max-width:45%;
    float:none !important;
    display:block;
    margin-top:.5rem;
    margin-bottom:.75rem;
  }
  .ck-content .image.image-style-block-align-left{
    margin-left:0;
    margin-right:auto;   /* tựa trái */
  }
  .ck-content .image.image-style-block-align-right{
    margin-left:auto;    /* tựa phải */
    margin-right:0;
  }

  /* Caption gọn gàng */
  .ck-content .image figcaption{
    text-align:center;
    font-size:.875rem;
    color:#6c757d;
    margin-top:.25rem;
  }

  /* Mobile: luôn full width, căn giữa */
  @media (max-width:768px){
    .ck-content .image,
    .ck-content .image.image-style-align-center,
    .ck-content .image.image-style-block-align-left,
    .ck-content .image.image-style-block-align-right{
      max-width:100%;
      margin-left:auto;
      margin-right:auto;
    }
  }
</style>
@endsection

{{-- Page Scripts --}}
@section('page-script')
@vite([
  'Modules/CmsFrontend/resources/assets/js/pdf-viewer.js'
])
@endsection

@section('vendor-style')
@vite(Theme::asset([
  'assets/vendor/libs/select2/select2.scss',
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
  'assets/vendor/libs/select2/select2.js',
  'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
]))
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

@section('title', $post->title ?? __('pages.chi_tiet_tin_tuc'))
@section('date', \Carbon\Carbon::parse($post->created_at)->format('Y-m-d\TH:i:s'))
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

@section('content')

<div class="container cms-page-container py-5">
  <div class="row gx-30">

    <div class="col-xxl-8 col-lg-7">
      <div class="post-35 post type-post status-publish format-gallery has-post-thumbnail hentry th-blog blog-single">
        @if($post->excerpt)
          <p class="fw-bolder fs-5">{{ $post->excerpt }}</p>
        @endif
        {{-- Ảnh đầu bài --}}
        {{-- <div class="blog-img th-blog-carousel slick-initialized slick-slider">
          <button type="button" class="slick-prev slick-arrow"><i class="fal fa-arrow-left"></i></button>
          <div class="slick-list draggable">
            <div class="slick-track" style="opacity:1;">
              <img
                src="{{ $post->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
                alt="thumbnail"
                class="slick-slide slick-current slick-active"
                onerror='this.src="/assets/img/khuyen-nong-can-tho/default-image.jpg"'>
            </div>
          </div>
          <button type="button" class="slick-next slick-arrow"><i class="fal fa-arrow-right"></i></button>
        </div> --}}

        <div class="blog-img">
          <img
            src="{{ $post->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
            alt="thumbnail"
            class="blog-thumbnail"
            onerror='this.src="/assets/img/khuyen-nong-can-tho/default-image.jpg"'>
        </div>

        {{-- Meta + Tóm tắt --}}
        <div class="blog-content">
          <div class="blog-meta d-flex align-items-center gap-3 flex-wrap">
            {{-- @if ($post->author->name)
              <span class="meta-item">
                <i class="fas fa-user" style="color:#0d5ef4;"></i>
                            {{ __('frontend.ui.tac_gia') }} <span>{{ $post->author->name }}</span>
              </span>
            @endif --}}

            @if ($post->author?->name)
              <span class="meta-item">
                <i class="fas fa-user" style="color:#0d5ef4;"></i>
                {{ __('frontend.ui.nguoi_dang') }} <span>{{ $post->author->name }}</span>
              </span>
            @endif

            @if (filled($post->author_names))
              <span class="separator">|</span>
              <span class="meta-item">
                <i class="fas fa-pen-nib" style="color:#0d5ef4;"></i>
                {{ __('frontend.ui.tac_gia') }} <span>{{ $post->author_names }}</span>
              </span>
            @endif
 
            <span class="separator">|</span>
            <span class="meta-item">
              <i class="fas fa-clock" style="color:#0d5ef4;"></i>
              {{ \Carbon\Carbon::parse($post->published_at ?? $post->created_at)->format('d/m/Y H:i') }}
            </span>
          </div>

          @if($post->excerpt)
            <p class="fw-bolder fs-5">{{ $post->excerpt }}</p>
          @endif
        </div>

        {{-- Nội dung bài viết từ CKEditor --}}
        {{-- <div class="blog-content ck-content">
           {!! $post->content !!}  

          <h5>DEBUG: Raw HTML trong DB</h5>
          <pre style="white-space:pre-wrap;word-break:break-word;border:1px solid #ddd;padding:12px;background:#fff;color:#000">
          {{ $post->content }}
          </pre>
        </div> --}}

        @php
          $content = \Modules\CmsFrontend\Support\PublicContent::forDisplay($post->content ?? '');

          // Convert <oembed url="..."></oembed> (CKEditor) => iframe (YouTube)
          $content = preg_replace_callback(
            '/<oembed[^>]*url="([^"]+)"[^>]*><\/oembed>/i',
            function ($m) {
              $url = html_entity_decode($m[1]);

              // Extract YouTube video id
              $id = null;
              if (preg_match('~youtu\.be/([^\?\&\/]+)~', $url, $mm)) $id = $mm[1];
              if (!$id && preg_match('~youtube\.com/watch\?v=([^\&]+)~', $url, $mm)) $id = $mm[1];
              if (!$id && preg_match('~youtube\.com/shorts/([^\?\&\/]+)~', $url, $mm)) $id = $mm[1];

              if (!$id) return $m[0]; // không phải youtube thì giữ nguyên

              $embed = "https://www.youtube.com/embed/{$id}";

              return <<<HTML
        <div class="ratio ratio-16x9 my-3">
          <iframe
            src="{$embed}"
            title="YouTube video"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            loading="lazy"
          ></iframe>
        </div>
        HTML;
            },
            $content
          );
        @endphp

        <div class="blog-content ck-content">
          {!! $content !!}
        </div>


        {{-- Tập tin đính kèm: chỉ mở trình xem khi người dùng chọn tập tin. --}}
        @if($attachmentFiles->isNotEmpty())
          <section class="cms-attachments" aria-labelledby="cms-attachments-title">
            <h5 id="cms-attachments-title">{{ __('frontend.ui.tap_tin_dinh_kem') }}</h5>
            <ul class="cms-attachment-list">
              @foreach($attachmentFiles as $file)
                @php
                  $mime = strtolower($file['type'] ?? '');
                  $fileIcon = str_contains($mime, 'pdf') ? 'ri-file-pdf-2-line'
                    : (str_contains($mime, 'word') || str_contains($mime, 'msword') ? 'ri-file-word-2-line'
                    : (str_contains($mime, 'sheet') || str_contains($mime, 'excel') ? 'ri-file-excel-2-line'
                    : (str_starts_with($mime, 'image/') ? 'ri-file-image-line' : 'ri-file-3-line')));
                @endphp
                <li>
                  <button type="button" class="cms-attachment-link" data-file-url="{{ $file['url'] }}" data-file-name="{{ $file['name'] }}" data-file-type="{{ $file['type'] }}">
                    <i class="{{ $fileIcon }}" aria-hidden="true"></i>
                    <span>{{ $file['name'] }}</span>
                  </button>
                </li>
              @endforeach
            </ul>
          </section>

          <div class="modal fade cms-document-modal" id="cmsDocumentModal" tabindex="-1" aria-labelledby="cmsDocumentModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="cmsDocumentModalTitle">{{ __('frontend.ui.xem_tap_tin') }}</h5>
                  <div class="cms-document-actions">
                    <a class="btn cms-document-download" id="cmsDocumentDownload" href="#" target="_blank" rel="noopener" download>
                      <i class="ri-download-2-line" aria-hidden="true"></i>{{ __('frontend.ui.tai_xuong') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('frontend.ui.dong') }}"></button>
                  </div>
                </div>
                <div class="modal-body">
                  <div id="filePreviewContainer" class="cms-pdf-pages" role="document" aria-live="polite"></div>
                </div>
              </div>
            </div>
          </div>
        @endif

        {{-- Chuyên mục --}}
        <div class="share-links clearfix">
          <div class="row justify-content-between">
            <div class="col-sm-auto d-flex align-items-center gap-2">
              <span class="share-links-title fw-bold">{{ __('frontend.ui.chuyen_muc_2') }}</span>
              <div class="tagcloud d-flex flex-wrap gap-2 mb-0">
                @foreach ($post->taxonomies as $item)
                  <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index', ['category' => $item->term->slug]) }}">{{ $item->term->name }}</a>
                @endforeach
              </div>
            </div>
          </div>
        </div>

      </div>

      {{-- Comments --}}
      <div class="th-comments-wrap">
        @include('cmsfrontend::partials/commentsDetailPartial', [
          'comments' => $comments,
          'post'     => $post,
        ])
      </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-xxl-4 col-lg-5">
      <aside class="sidebar-area">

        <div class="widget">
          <h3 class="widget_title">{{ __('frontend.ui.bai_viet_gan_day') }}</h3>
          <div class="recent-post-wrap mb-0">
            <div class="recent-tintuc">
              @foreach ($posts_lienquan as $item)
                <div class="swiper-slide">
                  <div class="card mb-3">
                    <div class="row g-0">
                      <div class="col-md-4 col-12 image-container">
                        <img src="{{ $item->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
                             class="img-tintuc"
                             style="width:100%;height:100px;object-fit:cover;border-radius:6px;"
                             alt="tintuc">
                        <div class="image-gradient-overlay"></div>
                      </div>
                      <div class="col-md-8 col-12">
                        <div class="card-body">
                          <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $item->slug]) }}"
                             class="fs-5 fw-semibold text-tile text-dark tintuc-title">
                            {{ $item['title'] }}
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="widget blog-widget-cta" id="edura_cta_widget-3">
          <div class="widget widget_banner background-image"
               style="background:var(--brand-gradient);margin-bottom:0;">
            <div class="widget-banner">
              <h4 class="title">{{ __('frontend.ui.can_giup_do_chung_toi_o_day_de_giup_ban') }}</h4>
              <span class="sbut-wordmark" aria-hidden="true" style="color:var(--text-on-dark)">SB<span>U</span>T<i></i></span>
              <h5 class="subtitle">NBDUT</h5>
              @if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he') }}" class="th-btn style7">{{ __('frontend.ui.lien_he_tu_van') }}</a>@endif
            </div>
          </div>
        </div>

      </aside>
    </div>

  </div>
</div>
@endsection
