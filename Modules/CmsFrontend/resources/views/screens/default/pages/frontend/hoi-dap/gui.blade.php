@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.dat_cau_hoi'))
@section('description', __('pages.trang_dat_cau_hoi_giua_hoc_sinh_sinh_vien_cao_da'))
@section('keywords', 'hoi dap, hoi dap cao dang soc trang, hoi dap truong soc trang, dat cau hoi')
<!-- Vendor Styles -->
@section('vendor-style')
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/su-kien/su-kien.css', 'Modules/CmsFrontend/resources/assets/css/guong-tieu-bieu/guong-tieu-bieu.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 
    'assets/vendor/libs/jquery/jquery.js', 
    'assets/vendor/js/dropdown-hover.js', 
    'assets/vendor/js/mega-dropdown.js', 
    'assets/vendor/libs/popper/popper.js', 
    'assets/vendor/js/bootstrap.js', 
    'assets/vendor/libs/node-waves/node-waves.js', 
    'assets/vendor/libs/toastr/toastr.js', 
    'assets/vendor/libs/sweetalert2/sweetalert2.js']))
    <script type="module" src='/assets/js/lottie.min.js'></script>
    <script>
        function docReady(fn) {
            // see if DOM is already available
            if (document.readyState === "complete" || document.readyState === "interactive") {
                // call on next available tick
                setTimeout(fn, 1);
            } else {
                document.addEventListener("DOMContentLoaded", fn);
            }
        }
        docReady(function() {
            lottie.loadAnimation({
                container: document.getElementById('lottie-character'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: '/assets/img/animation-question.json'
            })
        });
    </script>
@endsection


@section('content')
    <div class="cms-shell-container">
        <div class="row justify-content-between mb-10 mt-10">
            <div class="col-12 col-md-4"> 
                <div id="lottie-character" class="img-fluid object-fit-cover"></div> 
            </div>
            <div class="col-12 col-md-7  card p-4  shadow-sm">
                <h4 class="fw-bold mb-4 text-center" style='color: #0D6EFD'>{{ __('frontend.ui.dat_cau_hoi_cho_chung_toi') }}</h4>

                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('frontend.ui.ho_ten') }}</label>
                        <input type="text" name="name" class="form-control"   value="{{ old('name') }}"
                            placeholder="{{ __('frontend.ui.nhap_ho_ten_cua_ban') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class='row'>
                        <div class="mb-3 col-12 col-md-6">
                            <label for="phone" class="form-label">{{ __('frontend.ui.so_dien_thoai') }}</label>
                            {{-- <input type="number" name="phone" class="form-control" value="{{ old('phone') }}"
                                placeholder='Nhập số điện thoại của bạn'> --}}
                                <input
                                type="tel"
                                name="phone"
                                class="form-control"
                                value="{{ old('phone') }}"
                                placeholder="{{ __('frontend.ui.nhap_so_dien_thoai_cua_ban') }}"
                                inputmode="numeric"
                                autocomplete="tel"
                                pattern="^(0|\+84)\d{9,10}$"
                                title="{{ __('frontend.ui.so_dien_thoai_phai_bat_dau_bang_0_hoac_84_va_co_') }}"
                                />

                            @error('phone')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3 col-12 col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                placeholder="{{ __('pages.nhap_email_cua_ban') }}">
                            @error('email')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">{{ __('frontend.ui.tieu_de_cau_hoi') }}</label>
                        <input type="text" name="title" class="form-control"   value="{{ old('title') }}"
                            placeholder="{{ __('pages.nhap_tieu_de_cau_hoi') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">{{ __('frontend.ui.noi_dung_cau_hoi') }}</label>
                        <textarea name="content" class="form-control"   rows="4" placeholder="{{ __('pages.nhap_noi_dung_cau_hoi') }}">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="font-size: 1rem; margin-right: 0.5rem;">*</span>
                        <div>
                            <p class="text-muted mb-0 fst-italic">
                                {{ __('frontend.ui.chu_y_se_mat_mot_thoi_gian_de_chung_toi_tra_loi_') }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end ">
                        <button type="submit" class="btn fancy-hover-btn text-none btn-lg">
                            <span>{{ __('frontend.ui.gui_cau_hoi') }}</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>
@endsection
