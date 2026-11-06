{{--  
<section class="section-hinh-anh">


    <style>
  
    </style>

  <div class="container position-relative">

  
    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-start align-items-center mt-4">
      <a  href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach') }}">
            <p class="text-subtitle fs-5"><i class="ri-newspaper-line"></i>{{ __('frontend.ui.thu_vien_hinh_anh') }}</p>
      </a>
    </div>

    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-start align-items-center mb-4">
                    <h2 class="fw-bold text-uppercase mb-0">{{ __('frontend.ui.hinh_anh_noi_bat_cua_svc') }}</h2>
      <a class="btn fw-bold fancy-hover-btn text-uppercase p-5" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach') }}">
                        <span>{{ __('frontend.ui.xem_tat_ca_hinh_anh') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
      </a>
    </div>

    <div class="col-lg-12 col-12 p-relative">
        <div class="swiper-button-prev suKien-prev suKien-prev-custom">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </div>
        <div class="swiper-container-wrapper px-12"
            style=" padding-left: 0px !important; padding-right: 0px !important;">
            <div class="swiper hinhAnhSwiper" style="padding-left: 10px !important;">
                <div class="swiper-wrapper" style=" height: 500px;  align-items: center;"> 
                        
                   @foreach ($libraryImage->media->chunk(10) as $index => $image6)
                        @foreach ($image6 as $i => $image) 
                            <div class="swiper-slide section-hinh-anh-item" style="position: relative;"> 
                            <div style="width: 300px;height: 300px;">
                    <a href="{{ $image->getUrl() }}" data-lightbox="image-library" data-title="{{ __('frontend.ui.hinh_anh') }} {{ $index+1 }}">
                                    <img src="{{$image->getUrl()}}" class="d-block w-100 img-fluid" style="width: 300px;height: 300px;">
                                </a> 
                            </div> 
                        </div> 
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <div class="swiper-button-next suKien-next suKien-next-custom">
            <i class="fa fa-arrow-right" aria-hidden="true"></i>
        </div>
    </div>

  </div>
</section>

 --}}
<section class="section-hinh-anh">
  <style>
    /* optional: cho slide đẹp hơn */
    .section-hinh-anh-item img { border-radius: 6px; }
  </style>

  <div class="container position-relative">

    {{-- Header --}}
    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-start align-items-center mt-4">
      <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach') }}">
        <p class="text-subtitle fs-5"><i class="ri-newspaper-line"></i>{{ __('frontend.ui.thu_vien_hinh_anh') }}</p>
      </a>
    </div>

    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-start align-items-center mb-4">
      <h2 class="fw-bold text-uppercase mb-0">{{ __('frontend.ui.hinh_anh_noi_bat_cua_svc') }}</h2>
      <a class="btn fw-bold fancy-hover-btn text-uppercase p-5" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach') }}">
        <span>{{ __('frontend.ui.xem_tat_ca_hinh_anh') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
      </a>
    </div>

    {{-- Mỗi thư viện = 1 dòng swiper --}}
    @foreach ($libraryImages as $libIndex => $libraryImage)

      {{-- Tên thư viện (nếu có) --}}
      <h5 class="fw-bold mt-4 mb-3">
        {{ $libraryImage->title ?? __('pages.thu_vien') . ($libIndex + 1) }}
      </h5>

      <div class="col-lg-12 col-12 p-relative mb-5">

        {{-- Prev --}}
        <div class="swiper-button-prev suKien-prev suKien-prev-{{ $libIndex }}">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </div>

        <div class="swiper-container-wrapper px-12"
             style="padding-left:0 !important;padding-right:0 !important;">

          {{-- Swiper instance riêng --}}
          <div class="swiper hinhAnhSwiper hinhAnhSwiper-{{ $libIndex }}"
               style="padding-left:10px !important;">

            <div class="swiper-wrapper" style="height: 320px; align-items: center;">

              @foreach ($libraryImage->media as $image)
                <div class="swiper-slide section-hinh-anh-item" style="position: relative;">
                  <div style="width: 300px; height: 300px;">
                    <a href="{{ $image->getUrl() }}"
                       data-lightbox="image-library"
                       data-title="{{ $libraryImage->title ?? '' }}">
                      <img src="{{ $image->getUrl() }}"
                           class="d-block w-100 img-fluid"
                           style="width:300px;height:300px;object-fit:cover;">
                    </a>
                  </div>
                </div>
              @endforeach

            </div>
          </div>
        </div>

        {{-- Next --}}
        <div class="swiper-button-next suKien-next suKien-next-{{ $libIndex }}">
          <i class="fa fa-arrow-right" aria-hidden="true"></i>
        </div>

      </div>
    @endforeach

  </div>
</section>
