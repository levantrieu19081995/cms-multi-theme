@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.hoi_dap'))
@section('description', __('pages.trang_trao_doi_hoi_dap_giua_nha_truong_va_hoc_si_2'))
@section('keywords', 'hoi dap, hoi dap NBDUT, hoi dap truong nam binh duong')
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
@endsection


@section('content')
    <div class="cms-shell-container">
        <div class="d-flex align-items-center justify-content-between mt-10">
            <div class="d-flex align-items-center">
                <span style="font-size: 3rem; margin-right: 0.5rem;">👋</span>
                <div>
                    <h1 class="h3 fw-bold mb-1">
                        {{ __('frontend.ui.chao_mung_ban') }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ __('frontend.ui.tim_kiem_cau_tra_loi_cho_ban_va_dat_cau_hoi_cho_') }}
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.gui') }}">
                    <button class="btn fancy-hover-btn mt-4 text-none btn-lg">
                        <span>
                            {{ __('frontend.ui.dat_cau_hoi') }}
                        </span>
                    </button>
                </a>
            </div>
        </div>

        <ul class="list-group mt-10 mb-5">
            @foreach ($questions as $q)
                <li class="list-group-item card mb-3">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-grow-1">
                            <!-- Title -->
                            {{-- <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.show', ['id' => $q['id']]) }}"> --}}
                            <h5 class="mb-1 text-primary">{{ $q['title'] }}</h5>
                            {{-- </a> --}}

                            <!-- Content -->
                            <p class="mb-1 text-muted">{{ $q['content'] }}</p>

                            <!-- Info row -->
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-secondary">
                                    <strong>{{ $q['name'] ?? __('pages.nguoi_gui_an_danh') }}</strong> •
                                    {{ \Carbon\Carbon::parse($q['createdAt'])->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                    </div>
                    @if ($q['answer'])
                        <hr class="my-4">
                        <span class='mb-1 fw-bold'><i class="fa-solid fa-reply text-success"></i> {{ __('frontend.ui.tra_loi') }}</span>
                        <div>
                            <p class="mb-1 text-muted">
                                {{-- <strong>{{ $q['answered_by'] ?? 'Người trả lời ẩn danh' }}</strong> • --}}
                                {{-- {{ \Carbon\Carbon::parse($q['answered_at'])->format('d/m/Y H:i') }} --}}
                                {{ $q['answer'] }}
                            </p>
                        </div>
                        <!-- Info row -->
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-secondary">
                                {{-- <strong> --}}
                                {{-- {{ $q['answered_by'] ?? 'Người trả lời ẩn danh' }}</strong> • --}}
                                {{ \Carbon\Carbon::parse($q['answered_at'])->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    @endif


                </li>
            @endforeach
        </ul>
        <div class="form-layout-footer">
            @if ($questions->isEmpty())
                <div class="alert alert-info text-center">
                    {{ __('frontend.ui.hien_tai_khong_co_cau_hoi_nao_hay_dat_cau_hoi_cu') }}
                </div>
            @else
                {{ $questions->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            @endif
        </div>

    </div>
@endsection
