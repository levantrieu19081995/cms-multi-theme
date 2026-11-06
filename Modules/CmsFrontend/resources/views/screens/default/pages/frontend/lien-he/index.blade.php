@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.lien_he'))
@section('description', __('pages.trang_de_lien_he_va_gui_gop_y_den_cao_dang_nghe_'))
@section('keywords', 'lien he, lien he cao dang soc trang, lien he truong soc trang')
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
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 'assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/js/mega-dropdown.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js']))
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
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
                path: '/assets/img/contact.json'
            })
        });
    </script>
@endsection

@section('content')
    <div class="cms-shell-container">
        <div class='row my-10 '>
            <div class='col-12 col-md-7'>
                {{-- <div class="position-relative h-100">
                    <img src="/assets/img/khuyen-nong-can-tho/anhTruong.jpg" class="img-fluid h-100 object-fit-cover" alt="Banner">
                </div> --}}
                {{-- <dotlottie-player src="/assets/img/contact.lottie" background="transparent" speed="1"
                    class="img-fluid object-fit-cover" loop autoplay></dotlottie-player> --}}
                <div id="lottie-character" class="img-fluid object-fit-cover"></div>
            </div>

            <div class='col-12 col-md-5 card p-4 shadow-sm'>
                <h4 class="fw-bold mb-4 text-center" style='color: #0D6EFD'>{{ __('frontend.ui.thong_tin_lien_he_va_gop_y') }}</h4>

                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he.store') }}">
                    @csrf
@include('cmsfrontend::screens.components.contact-honeypot')
                    {{-- Họ tên --}}
                    <div class="form-group mt-3">
                        <label for="name" class="form-label">{{ __('frontend.ui.ho_ten') }}</label>
                        <input type="text" name="name" id="name" maxlength="255" value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror" required
                            placeholder="{{ __('frontend.ui.nhap_ho_ten_cua_ban') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class='row'>
                        {{-- Email --}}
                        <div class="form-group mt-3 col-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" maxlength="255" value="{{ old('email', request('email')) }}"
                                class="form-control @error('email') is-invalid @enderror" required
                                placeholder="{{ __('pages.nhap_email_cua_ban') }}">
                            @error('email')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Điện thoại --}}
                        <div class="form-group mt-3 col-6">
                            <label for="phone" class="form-label">{{ __('frontend.ui.dien_thoai') }}</label>
                            <input type="text" name="phone" id="phone" maxlength="30" value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror" required
                                placeholder="{{ __('pages.nhap_so_dien_thoai_cua_ban') }}">
                            @error('phone')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    {{-- Địa chỉ --}}
                    <div class="form-group mt-3">
                        <label for="address" class="form-label">{{ __('frontend.ui.dia_chi') }}</label>
                        <input type="text" name="address" id="address" maxlength="255" value="{{ old('address') }}"
                            class="form-control @error('address') is-invalid @enderror" required
                            placeholder="{{ __('pages.nhap_dia_chi_cua_ban') }}">
                        @error('address')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tiêu đề --}}
                    <div class="form-group mt-3">
                        <label for="subject" class="form-label">{{ __('frontend.ui.tieu_de') }}</label>
                        <input type="text" name="subject" id="subject" maxlength="255" value="{{ old('subject') }}"
                            class="form-control @error('subject') is-invalid @enderror" required placeholder="{{ __('pages.nhap_tieu_de') }}">
                        @error('subject')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Nội dung --}}
                    <div class="form-group mt-3">
                        <label for="message" class="form-label">{{ __('frontend.ui.noi_dung') }}</label>
                        <textarea name="message" id="message" minlength="10" maxlength="5000" rows="4" class="form-control @error('message') is-invalid @enderror"
                            required placeholder="{{ __('pages.nhap_noi_dung_loi_nhan') }}">{{ old('message') }}</textarea>
                        @error('message')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end mt-6 ">
                        <button type="submit" class="btn fancy-hover-btn text-none btn-lg">
                            <span>{{ __('frontend.ui.gui_gop_y') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    </div>
@endsection
