<section class="section-guong-tieu-bieu p-relative">
    <!-- Hình xoay trái trên -->
    <div class="decor-spin-shape">
        <img decoding="async"
            src="/assets/img/khuyen-nong-can-tho/slide_slide.webp" alt="SVN">
    </div>

    <!-- Hình nhảy bên phải -->
    <div class="decor-jump-shape">
        <img decoding="async"
            src="/assets/img/khuyen-nong-can-tho/draw_blue_pen.webp" alt="SVN">
    </div>

    <div class="container">
        <div class="col-lg-6 col-12 guong-tieu-bieu-title">
            {{-- <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}"> --}}
            <p class="text-subtitle fs-5 mt-3 mt-md-0">
                <i class="ri-newspaper-line" style="color:#0D5EF4"></i>
                {{ __('frontend.ui.tam_guong_hoc_tap') }}
            </p>
            {{-- </a> --}}
            <h2 class="">{{ __('frontend.ui.tam_guong_tieu_bieu_cua_chung_toi') }}</h2>
        </div>

        <div class="col-lg-12 col-12 p-relative">
            @php $count = count($guongTieuBieu); @endphp

            @if ($count === 1)
                @php $item = $guongTieuBieu[0]; @endphp
                <div class="d-flex justify-content-center">
                    <div class="section-guong-tieu-bieu-item guong-tieu-bieu-swiper-slide" style="max-width: 600px;">
                        <div style="width: 100%;">
                            <div class="blue-ear-bg">
                                <svg class="blue-ear-svg" viewBox="0 0 150 137" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M 0 9.99951 C 0 4.47666 4.47715 -0.000488281 10 -0.000488281 H 140 C 145.523 -0.000488281 150 4.47666 150 9.99951 V 10.5803 C 150 13.3951 148.814 16.0796 146.732 17.9747 L 18.8619 134.394 C 17.0205 136.07 14.6199 137 12.1297 137 H 10 C 4.47715 137 0 132.522 0 127 V 9.99951 Z"
                                        fill="#0D5EF4" />
                                </svg>
                            </div>
                            <div class="testimonial-header" style="padding: 50px;">
                                <div class="avatar-wrapper">
                                    <img src="{{ $item->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
                                        alt="hinh 1" class="testimonial-avatar" />
                                </div>
                                <div class="testimonial-text">
                                    <p class="text-sm text-gray-700 mb-0">
                                        {{ strip_tags($item->content) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="testimonial-content-wrapper">
                            <div class="testimonial-divider">
                                <hr class="border-t border-slate-200 my-4">
                            </div>

                            <div class="testimonial-footer">
                                <div class="testimonial-meta">
                                    <p class="testimonial-name">{{ $item->title }}</p>
                                    <p class="testimonial-role">{{ $item->role }}</p>
                                </div>
                                <div class="testimonial-rating">
                                    <p>
                                        @for ($i = 0; $i < 5; $i++)
                                            @if ($i < $item->rating)
                                                ★
                                            @else
                                                ☆
                                            @endif
                                        @endfor
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="swiper control-Swiper">
                    <div class="swiper-wrapper">
                        @foreach ($guongTieuBieu as $item)
                           <div class="swiper-slide section-guong-tieu-bieu-item guong-tieu-bieu-swiper-slide">
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.guong-tieu-bieu.detail-by-id', ['id' => $item->url_key]) }}">
                                <div style="width: 100%;">
                                    <div class="blue-ear-bg">
                                        <svg class="blue-ear-svg" viewBox="0 0 150 137" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M 0 9.99951 C 0 4.47666 4.47715 -0.000488281 10 -0.000488281 H 140 C 145.523 -0.000488281 150 4.47666 150 9.99951 V 10.5803 C 150 13.3951 148.814 16.0796 146.732 17.9747 L 18.8619 134.394 C 17.0205 136.07 14.6199 137 12.1297 137 H 10 C 4.47715 137 0 132.522 0 127 V 9.99951 Z"
                                                fill="#0D5EF4" />
                                        </svg>
                                    </div>
                                    <div class="testimonial-header" style="padding: 50px;">
                                        <div class="avatar-wrapper">
                                            <img src="{{ $item->getFirstMediaUrl('avatars') ?: '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"
                                                alt="hinh 1" class="testimonial-avatar" />
                                        </div>
                                        <div class="testimonial-text">
                                            <p class="text-sm text-gray-700 mb-0" style="font-size: 14px;">
                                                {{ strip_tags($item->excerpt) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="testimonial-content-wrapper">
                                    <div class="testimonial-divider">
                                        <hr class="border-t border-slate-200 my-4">
                                    </div>

                                    <div class="testimonial-footer">
                                        <div class="testimonial-meta">
                                            <p class="testimonial-name">{{ $item->title }}</p>
                                            <p class="testimonial-role">{{ $item->role }}</p>
                                        </div>
                                        <div class="testimonial-rating">
                                            <p>
                                                @for ($i = 0; $i < 5; $i++)
                                                    @if ($i < $item->rating)
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <div class="pagination-wrapper">
                        <div class="swiper-pagination custom-swiper-pagination"></div>
                    </div>
                </div>
            @endif

        </div>
        <div class="pagination-wrapper">
            <div class="swiper-pagination custom-swiper-pagination"></div>
        </div>
    </div>
</section>
