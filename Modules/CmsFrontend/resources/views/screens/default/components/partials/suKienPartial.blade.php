<section class="section-su-kien p-relative">
    <div class="section-shadow-top"></div>
    <div class="decor-spin-shape-su-kien">
        <img decoding="async" src="/assets/img/khuyen-nong-can-tho/dot_dot.webp"
            alt="Be Better">
    </div>
    <div class="container su-kien-swiper-hover">
        <div class="col-lg-6 col-12 su-kien-title">
            {{-- <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien') }}"> --}}
            <p class="text-subtitle fs-5 mt-3 mt-md-0">
                        <i class="ri-newspaper-line"></i>{{ __('frontend.ui.cac_su_kien_noi_bat') }}
                    </p>
            {{-- </a> --}}
            <h2 class="">{{ __('frontend.ui.su_kien_noi_bat_cua_chung_toi') }}</h2>
        </div>
        <div class="col-lg-12 col-12 p-relative">
            <div class="swiper-button-prev suKien-prev suKien-prev-custom">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
            </div>
            <div class="swiper-container-wrapper px-12"
                style=" padding-left: 0px !important; padding-right: 0px !important;">
                <div class="swiper suKienSwiper" style="padding-left: 10px !important;">
                    <div class="swiper-wrapper" style=" height: 500px;  align-items: center;">
                        @foreach ($events as $event)
                            <div class="swiper-slide section-su-kien-item" style="position: relative;">
                                <div class="event-card_img bg-mask"
                                    style="mask-image: url('/assets/img/khuyen-nong-can-tho/mask-image-su-kien.png'); top: -60px;width: 250px; height: 250px;">
                                    <img style="height: 250px;width: 250px; object-fit: cover;"
                                        src={{ $event->getFirstMediaUrl('avatars') ? $event->getFirstMediaUrl('avatars') : '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}
                                        alt="hinh 1" />
                                </div>
                                <div class="swiper-item" style="height: 360px;">
                                    <div class="item-slider">

                                        <div style="width: 100%; display: flex;">
                                            <div style="width: 50%;"></div>
                                            <div class="div-info">
                                                <div style="margin-right: 10px">
                                                    <x-theme::avatar alt class="rounded-circle rounded-full mr-3"
                                                        style="height: 60px;" />
                                                </div>
                                                <div>
                                                    <p class="text-speaker">{{ $event->author->name ?? '' }}</p>
                                                    <p class="text-role"
                                                        style="margin: 0; font-size: 13px; color: #6c757d;">Admin</p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div style="margin-top: 30px; text-align: left;">
                                        <p class="text-sm text-gray-600 text-local-time"><i
                                                class="fas fa-map-marker-alt text-blue-600"
                                                style="color:#0D5EF4; margin-right: 10px;"></i>{{ $event->excerpt }}
                                        </p>
                                        <p class="text-xs text-gray-400 text-local-time"><i
                                                class="fas fa-clock text-blue-600"
                                                style="color:#0D5EF4; margin-right: 10px;"></i>
                                            {{ $event->published_at ? \Carbon\Carbon::parse($event->published_at)->format('H:i d/m/Y') : '' }}
                                            -
                                            {{ $event->publication_date_expires ? \Carbon\Carbon::parse($event->publication_date_expires)->format('H:i d/m/Y') : '' }}
                                        </p>
                                        <h3 class="text-lg font-bold text-tile" style="line-height: 1.2;">{{ $event->title }}</h3>
                                    </div>
                                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien.chi-tiet', $event->slug) }}">
                                        <div class="div-button-xemthem">
                                            <p class="text-button-xemthem">{{ __('frontend.ui.xem_them_2') }} <i class="fa fa-arrow-right"
                                                    aria-hidden="true"></i></p>
                                        </div>
                                    </a>


                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="swiper-button-next suKien-next suKien-next-custom">
                <i class="fa fa-arrow-right" aria-hidden="true"></i>
            </div>
        </div>
    </div>
    <div class="section-shadow-bottom"></div>
</section>
