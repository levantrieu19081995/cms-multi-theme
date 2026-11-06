<style>
    .section-lienket {
        background: linear-gradient(120deg, #f8f9fa 60%, #e0e7ff 100%);
        padding: 5rem 0;
        font-family: var(--cms-font) !important;
    }

    .lienket-card {
        display: block;
        text-align: center;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 2px 16px rgba(0, 102, 255, 0.07), 0 1.5px 8px rgba(94, 231, 223, 0.08);
        transition: transform 0.25s cubic-bezier(.4, 2, .6, 1), box-shadow 0.25s, border 0.3s;
        padding: 18px 10px;
        height: 100%;
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
        background-clip: padding-box;
    }

    .lienket-card:hover {
        box-shadow: 0 8px 32px rgba(0, 102, 255, 0.13), 0 2px 8px rgba(94, 231, 223, 0.12);
    }

    .lienket-card-inner {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100px;
        min-height: 70px;
        padding: 0;
    }

    .lienket-card img {
        max-height: 80px;
        max-width: 90%;
        width: auto;
        height: auto;
        object-fit: contain;
        object-position: center;
        filter: grayscale(0.1) brightness(0.98) drop-shadow(0 2px 8px #5ee7df22);
        transition: filter 0.3s, transform 0.3s;
        margin: 0 auto;
        display: block;
    }

    .lienket-card:hover img {
        filter: grayscale(0) brightness(1.07) drop-shadow(0 4px 16px #5ee7df55);
        transform: scale(1.07) rotate(-2deg);
    }

    .section-lienket-item {
        margin-bottom: 18px;
    }

    .doi-tac-title h2 {
        line-height: 1.2;
        font-size: 44px;
    }

    @media (max-width: 992px) {
        .lienket-card-inner {
            height: 70px;
        }

        .lienket-card img {
            max-height: 50px;
        }
    }

    @media (max-width: 820px) {
        .lienket-card-inner {
            height: 48px;
        }

        .lienket-card img {
            max-height: 32px;
        }

        .doi-tac-title {
            text-align: center !important;
            width: 100%;
            line-height: 1.2rem;
        }

        .doi-tac-title h2 {
            line-height: 1.2;
            font-size: 28px;
        }
    }

</style>
<section class="section-lienket" style="padding: 5rem 0; background-color: #f8f9fa;">
    <div class="container">
        <div class="row align-items-center gx-10">
            <div class="col-lg-6 col-12 doi-tac-title">
                <div class="pe-10">
                    {{-- <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}"> --}}
                    <p class="text-subtitle fs-5"><i class="ri-newspaper-line"></i> {{ __('frontend.ui.doi_tac') }}</p>
                    {{-- </a> --}}
                    <h2 class="fw-bold text-uppercase">{{ __('frontend.ui.cac_doi_tac_dang_tin_cay_cua_chung_toi') }}</h2>
                </div>

            </div>
            <div class="col-lg-6 col-12">
                <div class="swiper swiper-lienket">
                    <div class="swiper-wrapper">
                        @foreach ($links as $link)
                            <div class="swiper-slide section-lienket-item">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.doi-tac.show', ['slug' => $link->slug]) }}" class="lienket-card">
                                    <div class="lienket-card-inner">
                                        <img src="{{ $link->getFirstMediaUrl('avatars') ?? 'assets/img/khuyen-nong-can-tho/lien-ket/default.png' }}"
                                            style="max-height: 110px;width: 100%; height: 110px; object-fit: contain; object-position: center;"
                                            alt="Card {{ $link->title }}">
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
