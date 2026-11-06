@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.hoi_dap'))
@section('description', __('pages.trang_trao_doi_hoi_dap_giua_nha_truong_va_hoc_si'))
@section('keywords', 'hoi dap, hoi dap cao dang soc trang, hoi dap truong soc trang')
<!-- Vendor Styles -->
@section('vendor-style')
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 
    'Modules/CmsFrontend/resources/assets/sass/app.scss', 
    'Modules/CmsFrontend/resources/assets/css/su-kien/su-kien.css', 
    'Modules/CmsFrontend/resources/assets/css/guong-tieu-bieu/guong-tieu-bieu.css', 
    'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
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
@endsection


@section('content')

    <div class="container my-5"> 
                <div class="d-flex align-items-center justify-content-between mt-10">
            <div class="d-flex align-items-center mb-5">
                <span style="font-size: 3rem; margin-right: 0.5rem;">🌐</span>
                <div>
                    <h1 class="h3 fw-bold mb-1">
                        {{ __('frontend.ui.danh_sach_dich_vu') }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ __('frontend.ui.danh_sach_dich_vu_lien_ket_voi_cac_thong_tin_ngo') }}
                    </p>
                </div>
            </div> 
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%; text-align: center; vertical-align: middle;">#</th>
                        <th style="width: auto; font-weight: bold; font-size: 16px; text-align: center; vertical-align: middle;">{{ __('frontend.ui.ten_dich_vu') }}</th>
                        <th style="width: 30%; font-weight: bold; font-size: 16px; text-align: center; vertical-align: middle;">{{ __('frontend.ui.duong_dan') }}</th>
                        <th style="width: 15%; font-weight: bold; font-size: 16px; text-align: center; vertical-align: middle;">Icon</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dichvus as $index => $dichvu)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $dichvu->title }}</strong>
                            </td>
                            <td>
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::url($dichvu->link) }}" target="_blank" rel="noopener" style="color: blue">
                                    {{ $dichvu->link }}
                                </a>
                            </td>
                            <td class="text-center">
                                @if (!empty($dichvu->icon))
                                    <i class="ri-{{ $dichvu->icon }} ri-xl"></i>
                                    <div class="small text-muted">{{ $dichvu->icon }}</div>
                                @else
                                    <span class="text-muted">{{ __('frontend.ui.khong_co') }}</span>
                                @endif
                            </td>
                            
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ __('frontend.ui.hien_tai_chua_co_dich_vu_nao_duoc_cap_nhat') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
