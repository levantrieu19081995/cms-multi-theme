 


@php
  // Chuẩn hoá về collection, reindex 0..n-1 để get() an toàn
  $list  = collect($videos)->values();
  $count = $list->count();
@endphp

<section class="section-video">
  <div class="container position-relative">
    <div class="col-lg-6 col-12 su-kien-title"> 
        <a  href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.danh-sach') }}">
            <p class="text-subtitle fs-5 mt-3 mt-md-0">
                <i class="ri-newspaper-line"></i>{{ __('frontend.ui.thu_vien_video') }}
            </p>
        </a>
            <h2 class="">{{ __('frontend.ui.video_noi_bat_cua_chung_toi') }}</h2>
        </div>

    @if($count >= 3)
      {{-- Hàng 1: 1 lớn + 2 nhỏ --}}
      <div class="row g-3 align-items-stretch">
        <div class="col-12 col-lg-8">
          @include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(0), 'large' => true, 'class' => 'h-100'])
        </div>
        <div class="col-12 col-lg-4">
          <div class="d-grid gap-3">
            @include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(1)])
            @include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(2)])
          </div>
        </div>
      </div>

      {{-- CÁC VIDEO CÒN LẠI (bắt buộc hiển thị) --}}
      @if($count > 3)
        <div class="row g-3 mt-3">
          @foreach($list->slice(3) as $v)
            <div class="col-12 col-md-6 col-lg-4">
              @include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $v])
            </div>
          @endforeach
        </div>
      @endif

    @elseif($count === 2)
      <div class="row g-3">
        <div class="col-12 col-md-6">@include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(0)])</div>
        <div class="col-12 col-md-6">@include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(1)])</div>
      </div>

    @elseif($count === 1)
      <div class="row g-3">
        <div class="col-12">@include('cmsfrontend::screens.components.partials.videoCard', ['cms.video' => $list->get(0), 'large' => true])</div>
      </div>

    @else
      <p class="text-muted mb-0">{{ __('frontend.ui.chua_co_video') }}</p>
    @endif
  </div>
</section>

