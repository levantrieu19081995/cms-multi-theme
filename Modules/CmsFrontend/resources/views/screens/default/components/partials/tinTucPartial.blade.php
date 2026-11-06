<section class="section-tintuc">
    <div class="container">
        <div class="row gx-10">
            @if (!empty($firstPost[0]))
                <div class="col-lg-6 col-12 section-tin-tuc-item d-none d-lg-block" style='max-height: 720px;'>
                    <div class="position-relative h-100 image-container rounded">
                        <img rel="preload" as="image" src="{{ $firstPost[0]->getFirstMediaUrl('avatars') ?? '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"   loading="lazy"
                            class="img-fluid w-100" alt="{{ __('frontend.ui.anh_chinh') }}" style='max-height: 720px;object-fit: cover;'>

                        <div class="image-gradient-overlay"></div>

                        <div class="text-overlay text-center text-white">
                            <h3 class="image-title text-white text-uppercase mb-2 p-5">{{ $firstPost[0]['title'] }}</h3>
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $firstPost[0]->slug]) }}"
                                class="btn btn-more text-uppercase fs-6">{{ __('frontend.ui.doc_them') }} <i
                                    class="ri-arrow-right-line"></i></a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-6 col-12 section-tin-tuc-item"> 
                    <p class="text-subtitle fs-5 mt-3 mt-md-0">
                        <i class="ri-newspaper-line"></i>{{ __('frontend.ui.tin_tuc_cua_chung_toi') }}
                    </p>  
                <div class="d-flex justify-content-between gap-1 ">
                    <h2 class="fw-bold text-uppercase">{{ __('frontend.ui.tin_tuc') }}</h2>
                    <div>
                        <button class="prev-tintuc mr-4 btn btn-primary rounded-circle"><i
                                class="ri-arrow-up-line"></i></button>
                        <button class="next-tintuc btn btn-primary rounded-circle"><i
                                class="ri-arrow-down-line"></i></button>
                    </div>
                </div>
                <div class="swiper swiper-tintuc p-5 py-md-10 pr-3">
                    <div class="swiper-wrapper" id="tin-tuc-swiper">
                        <div class="swiper-slide d-block d-lg-none">
                            <div class="card mb-3">
                                <div class="row g-0">
                                    <div class="col-md-4 col-12 image-container">
                                        <img src="{{ $firstPost[0]->getFirstMediaUrl('avatars') ?? '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}"   rel="preload" loading="lazy"
                                            class="img-tintuc" alt="tintuc">
                                        <div class="image-gradient-overlay"></div>
                                    </div>
                                    <div class="col-md-8 col-12">
                                        <div class="card-body">
                                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $firstPost[0]->slug]) }}"
                                                class="text-lg font-bold text-tile tintuc-title">
                                                {{ $firstPost[0]['title'] }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @foreach ($posts as $index => $post)
                            <div class="swiper-slide">
                                <div class="card mb-3">
                                    <div class="row g-0">
                                        <div class="col-md-4 col-12 image-container">
                                            <img src={{ $post->getFirstMediaUrl('avatars') ?? '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}  rel="preload" loading="lazy"
                                                class="img-tintuc"
                                                style="width: 100%;
                                                height: 120px;
                                                object-fit: cover;
                                                border-radius: 6px;"
                                                alt="tintuc">
                                            <div class="image-gradient-overlay"></div>
                                        </div>
                                        <div class="col-md-8 col-12">
                                            <br>
                                                <div class="card-body">
                                                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.show', ['slug' => $post->slug]) }}"
                                                        class='text-lg font-bold text-tile tintuc-title'>
                                                        {{ $post['title'] }} </a>
                                                        @if (filled($post->author_names)) 
                                                        <p class="card-text text-muted fs-6 mt-1">
                                                            <span>{{ __('frontend.ui.tac_gia') }} </span>{{ $post->author_names }}
                                                        </p>
                                                        @endif
                                                        <p class="card-text   text-muted fs-6 mt-1"><span> {{ __('frontend.ui.nguoi_dang') }} </span>{{ $post->author->name  }}</p>
                                                    <p class="card-text text-muted fs-6 mt-1"><span> {{ __('frontend.ui.ngay_dang') }} </span>{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }}</p>
                                                </div>
                                            <br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
