@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.su_kien'))
@section('description', __('pages.trang_thong_tin_ve_cac_su_kien_cua_cao_dang_nghe'))
@section('keywords', 'su kien, su kien cao dang soc trang, su kien truong soc trang')

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/su-kien/su-kien.css', 'Modules/CmsFrontend/resources/assets/css/guong-tieu-bieu/guong-tieu-bieu.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 'assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/js/mega-dropdown.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js']))
    @vite(['Modules/CmsFrontend/resources/assets/js/app.js'])
@endsection



@section('content')
    <section class="section-suKien">
        <div class="container my-4">
            <div class="row">
                <div class="col-lg-8 col-sm-12 col-12">
                    <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien') }}" method="GET" class="p-5 rounded-3 border mb-5">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <input type="search" id="filter" name="filter" class="form-control"
                                    placeholder="{{ __('frontend.ui.tim_kiem_su_kien') }}" value="{{ request('filter') }}">
                            </div>
                            <div class="col-md-3">
                                <select id="filter_duration" name="filter_duration" class="form-select"
                                    onchange="this.form.submit();">
                                    <option value="">{{ __('frontend.ui.tat_ca_cac_ngay') }}</option>
                                    <option value="today" @selected(request('filter_duration') == 'today')>{{ __('frontend.ui.hom_nay') }}</option>
                                    <option value="tomorrow" @selected(request('filter_duration') == 'tomorrow')>{{ __('frontend.ui.ngay_mai') }}</option>
                                    <option value="this_week" @selected(request('filter_duration') == 'this_week')>{{ __('frontend.ui.tuan_nay') }}</option>
                                    <option value="next_week" @selected(request('filter_duration') == 'next_week')>{{ __('frontend.ui.tuan_toi') }}</option>
                                    <option value="this_month" @selected(request('filter_duration') == 'this_month')>{{ __('frontend.ui.thang_nay') }}</option>
                                    <option value="next_month" @selected(request('filter_duration') == 'next_month')>{{ __('frontend.ui.thang_toi') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-search-line"></i> {{ __('frontend.ui.tim_kiem') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    @foreach ($posts as $post)
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien.chi-tiet', $post->slug) }}" class="text-decoration-none text-dark">
                            <div class="card cardLarge mb-8">
                                <div class="card-header py-2">
                                    <h2 class="mb-0 text-uppercase">{{ $post->title }}</h2>
                                </div>
                                <div class="card-body border-top-0 pt-5">
                                    <div class="row">
                                        <div class="col-lg-7 col-md-6 col-sm-12">
                                            <div class="w-100 rounded-3 border overflow-hidden" style="height: 250px">
                                                <img src={{ $post->getFirstMediaUrl('avatars') ? $post->getFirstMediaUrl('avatars') : '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}
                                                    alt="blog s 1 1" class="img-fluid w-100 h-100 object-fit-cover">
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-6 col-sm-12">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <td style="width: 30%">
                                                            <span class="fw-bold fs-5">{{ __('frontend.ui.bat_dau') }}</span>
                                                        </td>
                                                        <td> <span class="fs-5">
                                                                {{ \Carbon\Carbon::parse(($post->event_starts_at ?? $post->published_at))->format('H:i d/m/Y') }}
                                                            </span></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 30%">
                                                            <span class="fw-bold fs-5">{{ __('frontend.ui.ket_thuc') }}</span>
                                                        </td>
                                                        <td> <span class="fs-5">
                                                                {{ \Carbon\Carbon::parse(($post->event_ends_at ?? $post->publication_date_expires))->format('H:i d/m/Y') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width: 30%">
                                                            <span class="fw-bold fs-5">{{ __('frontend.ui.dia_diem') }}</span>
                                                        </td>
                                                        <td> <span class="fs-5">{{ $post->excerpt }}</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="col-lg-4 col-sm-12 col-12">
                    @include('cmsfrontend::screens.pages.frontend.su-kien.partials.danh-sach', [
                        'eventsToday' => $eventsToday,
                    ]) </div>
            </div>
        </div>



    </section>
@endsection
