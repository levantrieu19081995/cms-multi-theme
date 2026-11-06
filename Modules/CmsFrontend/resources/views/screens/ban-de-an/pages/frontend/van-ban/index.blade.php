@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', $nvb->nvb_name ?? __('pages.van_ban'))
@section('description', __('pages.trang_danh_sach_van_ban'))
@section('keywords',
    'van ban, danh sach van ban, dai hoc cong nghe nam binh duong van ban, tra cuu van ban,
    van ban SBUT, ' .
    ($nvb?->nvb_name ?? ''))
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
    <div class="container my-4">
        <div class="row my-6">
            <div class='col-12 col-lg-9'>
                <div class="grid-item">
                    {{-- <div class='card-header bg-white'>
                        <h4 class='card-title fw-bold'><i class="ri-list-check"></i> Danh sách văn bản</h4>
                    </div> --}}
                    <div class="content-title">
                        <h4>{{ __('frontend.ui.danh_sach_van_ban') }}</h4>
                    </div>
                    <div class='mt-2 w-100'>
                        <form action="" method="get" class='px-2'>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-6">
                                <div class="input-group input-group-merge bg-white">
                                    <input type="search" id="filter" name="filter" class="form-control"
                                        placeholder="{{ __('frontend.ui.nhap_tu_khoa_tim_kiem') }}" aria-label="{{ __('frontend.ui.tim_kiem_2') }}"
                                        value="{{ request()->filter }}">
                                    <a href="#advanced-filter"
                                        class="btn btn-outline-secondary waves-effect bg-white text-none" type="button"
                                        data-bs-toggle="collapse" id="toggleFilter">{{ __('frontend.ui.nang_cao') }}</a>
                                </div>


                                <button type="submit" class="btn" style="background-color: var(--brand-primary-hover); color: var(--text-on-dark);"><span
                                        class='text-none' style="min-width: 100px;">
                                        <i class="ri-search-line"></i> {{ __('frontend.ui.tim_kiem') }}</span>
                                </button>

                            </div>
                            <div class="collapse row @if (request()->lvb_id || request()->vb_ngay_ban_hanh_tu_ngay || request()->vb_ngay_ban_hanh_den_ngay) show @endif" id='advanced-filter'>
                                <div class="col-md-6 mb-6">
                                    <div class="form-floating form-floating-outline">
                                        <select id="roles" name="lvb_id" class="select2 form-select"
                                            data-allow-clear="true">
                                            <option value="">{{ __('frontend.ui.chon_loai_van_ban') }}</option>
                                            @foreach ($listLoaiVanBan as $loaiVanBan)
                                                <option value="{{ Illuminate\Support\Str::toBase64($loaiVanBan->lvb_id) }}"
                                                    @if ($loaiVanBan->lvb_id == $lvbId) selected @endif>
                                                    {{ $loaiVanBan->lvb_ten }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="roles">{{ __('frontend.ui.loai_van_ban') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-floating form-floating-outline">
                                        <input type="date" name="vb_ngay_ban_hanh_tu_ngay" class="form-control"
                                            value="{{ $tuNgayString }}">
                                        <label for="roles">{{ __('frontend.ui.ban_hanh_tu_ngay') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-floating form-floating-outline">
                                        <input type="date" name="vb_ngay_ban_hanh_den_ngay" class="form-control"
                                            value="{{ $denNgayString }}">
                                        <label for="roles">{{ __('frontend.ui.den_ngay') }}</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                        {{-- bang du lieu  --}}
                        <div class="table-responsive text-nowrap">
                            <table id="main-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 1%" class='text-center'>STT</th>
                                        <th style="width: 150px;" class='text-center'>{{ __('frontend.ui.so_hieu') }}</th>
                                        <th class='text-center'>{{ __('frontend.ui.trich_yeu') }}</th>
                                        <th style="width: 150px;" class='text-center'>{{ __('frontend.ui.loai_van_ban') }}</th>
                                        <th style="width: 150px;" class='text-center'>{{ __('frontend.ui.ngay_ban_hanh') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($list->isEmpty())
                                        <tr>
                                            <td colspan="100">{{ __('frontend.ui.khong_tim_thay_du_lieu') }}</td>
                                        </tr>
                                    @else
                                        @foreach ($list as $item)
                                            <tr>
                                                <td class='text-center'>
                                                    {{ ($list->currentPage() - 1) * $list->perPage() + $loop->index + 1 }}
                                                </td>
                                                <td>
                                                    <p class=''>{{ $item->vb_so_hieu }}</p>
                                                </td>
                                                <td> <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.show', ['id' => $item->vb_id]) }}"
                                                        class="text-decoration-none fw-bold text-dark">
                                                        {{ $item->vb_trich_yeu }}
                                                    </a>
                                                    <br />
                                                    <div class='mt-2'>
                                                        @if (!empty($item->vb_file_url))
                                                            <a href="{{ $item->vb_file_url }}" title="{{ __('frontend.ui.xem_tap_tin') }}"
                                                                target="_blank">
                                                                <span class="badge text-bg-light text-primary">
                                                                    {{-- {{ basename($item->vb_file_url) }} --}}
                                                                    @if (str_ends_with($item->vb_file_url, '.pdf'))
                                                                        <i class="ri-file-pdf-2-line text-danger me-1"></i>
                                                                    @elseif (str_ends_with($item->vb_file_url, '.png') ||
                                                                            str_ends_with($item->vb_file_url, '.jpg') ||
                                                                            str_ends_with($item->vb_file_url, '.jpeg'))
                                                                        <i class="ri-image-line" style="color: green;"></i>
                                                                    @else
                                                                        <i class="ri-attachment-2"></i>
                                                                    @endif
                                                                    Xem tập tin
                                                                </span>
                                                            </a>
                                                        @endif
                                                        @foreach ($item->fileDinhKem as $file)
                                                            <a href="{{ $file }}" title="{{ __('frontend.ui.xem_tap_tin') }}"
                                                                target="_blank">
                                                                <span class="badge text-bg-light text-primary">
                                                                    {{-- {{ basename($file) }} --}}
                                                                    @if (str_ends_with($file, '.pdf'))
                                                                        <i class="ri-file-pdf-2-line text-danger me-1"></i>
                                                                    @elseif (str_ends_with($file, '.png') || str_ends_with($file, '.jpg') || str_ends_with($file, '.jpeg'))
                                                                        <i class="ri-image-line" style="color: green;"></i>
                                                                    @else
                                                                        <i class="ri-attachment-2"></i>
                                                                    @endif
                                                                    Xem tập tin
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class='text-center'>{{ $item->loaiVanBan->lvb_ten }}</td>
                                                <td class='text-center'>{{ $item->vb_ngay_ban_hanh->format('d/m/Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                            {{ $list->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class='col-12 col-lg-3 mt-6 mt-lg-0'>
                <div class="categories grid-item">
                    <h4 class="categories-title"><i class="ri-information-line me-2"></i> {{ __('frontend.ui.thong_tin') }}</h4>
                    <ul class="px-5">
                        <li class="description categories-list-item">
                            <p class="card-text mb-2 text-justify" style="text-align: justify;">
                                <i class="ri-file-list-line me-1" style="color: var(--brand-secondary);"></i>
                                {{ __('frontend.ui.danh_sach_van_ban_cua_truong_dai_hoc_cong_nghe_n') }}
                            </p>
                        </li>
                        <li class="description categories-list-item">
                            <p class="card-text mb-2 text-justify" style="text-align: justify;">
                                <i class="ri-search-line me-1" style="color: var(--brand-secondary);"></i>
                                {{ __('frontend.ui.ban_co_the_tim_kiem_van_ban_theo_trich_yeu_loai_') }}
                            </p>
                        </li>
                        <li class="description categories-list-item">
                            <p class="card-text text-justify" style="text-align: justify;">
                                <i class="ri-customer-service-2-line me-1" style="color: var(--brand-secondary);"></i>
                                {{ __('frontend.ui.neu_ban_can_ho_tro_vui_long_lien_he_voi_chung_to') }}
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
