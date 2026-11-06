
<section class="section-thongtin">
    <div class="container position-relative">
        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}">
            <p class="text-subtitle fs-5"><i class="ri-newspaper-line"></i>{{ __('frontend.ui.thong_tin_thong_bao') }}</p>
        </a>
        <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-start align-items-center mb-4">

             @if(isset($categoryInfo) && isset($categoryInfo->term))
                <h2 class="fw-bold text-uppercase mb-0">
                    {{ $categoryInfo->term->name }}
                </h2>
            @else
                <h2 class="fw-bold text-uppercase mb-0">
                    {{ __('frontend.ui.chua_co_danh_muc') }}
                </h2>
            @endif

                @if(isset($categoryInfo) && isset($categoryInfo->term))
                    <a class="btn fw-bold fancy-hover-btn text-uppercase p-5" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index', $categoryInfo->term->slug) }}">
                        <span>
                            {{ __('frontend.ui.xem_tat_ca_thong_tin') }} <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </span>
                    </a>
                @else
                    <a class="btn fw-bold fancy-hover-btn text-uppercase p-5" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}">
                        <span>
                            {{ __('frontend.ui.xem_tat_ca_thong_tin') }} <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </span>
                    </a>
                @endif
        </div>
        <!-- Swiper -->
        <div class="swiper thongtin py-5 pl-0 pr-3">
            <div class="swiper-wrapper">
                @foreach ($categoryPosts as $post) 
                    <div class="swiper-slide section-thongtin-item">
                        <div class="img-wrapper">
                            <img src="{{ $post->getFirstMediaUrl('avatars') ?: '/path/to/default.jpg' }}" alt="{{ $post->title }}">
                        </div>
                        <div class="card-body w-100">
                            <h4 class="card-title text-start fs-5">{{ $post->title }}</h4>
                            @if (filled($post->author_names)) 
                                <p class="card-text text-end text-muted fs-6 mt-1"><span> {{ __('frontend.ui.tac_gia') }} </span>{{ $post->author_names }}</p>
                            @endif  
                            <p class="card-text text-end text-muted fs-6 mt-1">
                                <span>{{ __('frontend.ui.nguoi_dang') }}</span> {{ optional($post->author)->name ?? __('pages.khong_xac_dinh') }}
                            </p>
                            <p class="card-text text-end text-muted fs-6 mt-1"><span> {{ __('frontend.ui.ngay_dang') }} </span>{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }}</p>
                            <div class="d-flex justify-content-center">
                                <a  class="btn text-center btn-xemchitiet text-none" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $post->slug]) }}">{{ __('frontend.ui.xem_chi_tiet') }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

        </div>
        <div class="swiper-button-next button-next-thongtin"><i class="ri-arrow-right-line"></i></div>
        <div class="swiper-button-prev button-prev-thongtin"><i class="ri-arrow-left-line"></i></div>
    </div>
</section>
