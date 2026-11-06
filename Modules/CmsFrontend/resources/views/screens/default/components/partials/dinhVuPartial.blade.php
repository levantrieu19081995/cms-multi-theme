<style>
    .section-dichvu {
        font-family: var(--cms-font);
    }

    .section-dichvu h4 {
        line-height: 1.2;
    }

    .dich-vu-title h2 {
        color: #0f2239;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    @media (max-width: 820px) {
        .dich-vu-title h2 {
            font-size: 28px;
            line-height: 1.2;
        }
        .line-dich-vu {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .dich-vu-detail h4 {
            font-size: 18px;
        }

        .dich-vu-title {
        text-align: center;
        width: 100%;
    }
    }
</style>
<section class="section-dichvu py-20">
    <div class="container position-relative">
        <div class="d-md-flex d-none position-absolute top-0 left-0 right-0 bottom-0  w-100">
            <div class="{{ $dichvus->count() <= 4 ? 'w-100' : 'flex-grow' }}">
                <img src="/assets/img/khuyen-nong-can-tho/dichvu_line.svg"
                    class="line-dich-vu {{ $dichvus->count() <= 4 ? '' : 'img-fluid' }}"
                    style="{{ $dichvus->count() <= 4 ? 'width:96%; object-fit:cover;' : '' }}" alt="hinh abc">
            </div>
            <div class="d-flex justify-content-around mt-4">
                <div class="swiper-button-prev prev-dichVu mr-4"><i class="ri-arrow-left-line"></i></div>
                <div class="swiper-button-next next-dichVu"><i class="ri-arrow-right-line"></i></div>
            </div>
        </div>
        <div class="row w-100">
            <div class="col-md-4 col-12">
                <div class="d-flex flex-column justify-content-around h-100 align-items-md-start align-items-center">
                    <div class="dich-vu-title">
                        <p class="text-subtitle fs-5"><i class="ri-newspaper-line"></i> {{ __('frontend.ui.dich_vu_cua_chung_toi') }}</p>
                        <h2 class="">{{ __('frontend.ui.dich_vu_dao_tao') }}</h2>
                    </div>
                    <button type="button" class="btn fw-bold fancy-hover-btn text-uppercase p-5">
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.dichvu.index') }}">
                            <span>
                                {{ __('frontend.ui.xem_tat_ca_dich_vu') }} <i class="fa fa-arrow-right" aria-hidden="true"></i>
                            </span>
                        </a>
                    </button>
                </div>
            </div>
            <div class="col-md-8 col-12">
                <div class="swiper swiper-dichvu p-5 py-10 pr-3 mt-10">
                    <div class="swiper-wrapper">
                        @foreach ($dichvus as $dichvu)
                            <div class="swiper-slide section-dich-vu-item p-5">
                                <div class="img-wrapper d-flex justify-content-center align-items-center mb-5">
                                    <i class="ri-{{ $dichvu->icon }}"></i>
                                </div>
                                <div class="card-body text-center dich-vu-detail">
                                    <h4 class="card-title text-dark" style="min-height: 66px">
                                        {{ $dichvu->title }}
                                    </h4>
                                    <div class="d-flex justify-content-center mt-5">
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::url($dichvu->link) }}" target="_blank"
                                            class="btn text-center fancy-hover-btn text-none">
                                            <span>{{ __('frontend.ui.xem_chi_tiet') }}</span>
                                        </a>
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
