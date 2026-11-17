@extends('cmsfrontend::screens.components.layouts.blankLayout')
@section('title', $item->vb_trich_yeu ?? __('pages.chi_tiet_van_ban'))
@section('date', \Carbon\Carbon::parse($item->created_at)->format('Y-m-d\TH:i:s'))
@section('description', __('pages.trang_thong_tin_chi_tiet_ve_van_ban'))
@section('keywords', 'van ban, chi tiet van ban, dai hoc cong nghe nam binh duong van ban, tra cuu van ban, van ban SBUT, ' .
    $item->vb_trich_yeu ?? '')

    <!-- Vendor Styles -->
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])

@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
    <style>
        /* Màu nền cho tiêu đề cột */
        .table th.bg-light {
            background-color: #f0f4f8 !important;
            color: #333;
        }

        /* Màu nền khi hover lên hàng */
        .table tbody tr:hover td {
            background-color: #f9fbfd;
        }

        /* Màu sắc icon tệp tin */
        .ri-file-pdf-2-line {
            color: #e74c3c;
        }

        .ri-image-line {
            color: #27ae60;
        }

        .ri-attachment-2 {
            color: #2980b9;
        }

        /* Link tệp tin */
        .table a {
            color: #2c3e50;
            text-decoration: none;
        }

        .table a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        /* Icon xem và tải xuống */
        .table a .ri-eye-line,
        .table a .ri-download-2-line {
            font-size: 1.2em;
            vertical-align: middle;
        }

        .table a .ri-eye-line:hover,
        .table a .ri-download-2-line:hover {
            color: #16a085;
        }

        /* Căn chỉnh khoảng cách trong td */
        .table td {
            vertical-align: middle;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 'assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/js/mega-dropdown.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js']))
    <script>
        document.getElementById('fileSelect').addEventListener('change', function() {
            var url = this.value;
            document.getElementById('fileViewer').src = url;
        });
    </script>
@endsection


@php
    $hasFile = !empty($item->vb_file_url) || (isset($item->fileDinhKem) && count($item->fileDinhKem) > 0);
@endphp

@section('content')
    <div class="container">
        <div class='row my-6'>
            <div class='col-12 col-md-9'>
                <div
                    class="post-35 post type-post status-publish format-gallery has-post-thumbnail hentry category-finance category-health category-technology tag-solution tag-students post_format-post-format-gallery th-blog blog-single">
                    <div class=''>
                        <h4 class=' fw-bold'><i class="ri-pen-nib-line"></i> {{ $item->vb_trich_yeu }}</h4>
                    </div>
                    <div class=''>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="20%" class='fw-bold bg-light'>{{ __('frontend.ui.so_ky_hieu') }}</th>
                                    <td>{{ $item->vb_so_hieu }}</td>
                                </tr>
                                <tr>
                                    <th class=' fw-bold bg-light'>{{ __('frontend.ui.ngay_ban_hanh') }}</th>
                                    <td>{{ \Carbon\Carbon::parse($item->vb_ngay_ban_hanh)->format('d-m-Y') }}</td>
                                </tr>
                                <tr>
                                    <th class='fw-bold bg-light'>{{ __('frontend.ui.loai_van_ban') }}</th>
                                    <td>{{ $item->loaiVanBan->lvb_ten }}</td>
                                </tr>
                                <tr>
                                    <th class='fw-bold bg-light'>{{ __('frontend.ui.co_quan_ban_hanh') }}</th>
                                    <td>{{ $item->vb_co_quan_ban_hanh }}</td>
                                </tr>
                                <tr>
                                    <th class='fw-bold bg-light'>{{ __('frontend.ui.trich_yeu') }}</th>
                                    <td>{{ $item->vb_trich_yeu }}</td>
                                </tr>
                                @if ($hasFile)
                                    <tr>
                                        <th class='fw-bold bg-light'>{{ __('frontend.ui.tai_lieu_dinh_kem') }}</th>
                                        <td>
                                            @if ($item->vb_file_url)
                                                <div class="d-flex align-items-center mb-1">
                                                    @if (str_ends_with($item->vb_file_url, '.pdf'))
                                                        <i class="ri-file-pdf-2-line text-danger me-1"></i>
                                                    @elseif (str_ends_with($item->vb_file_url, '.png') ||
                                                            str_ends_with($item->vb_file_url, '.jpg') ||
                                                            str_ends_with($item->vb_file_url, '.jpeg'))
                                                        <i class="ri-image-line" style="color: green;"></i>
                                                    @else
                                                        <i class="ri-attachment-2"></i>
                                                    @endif
                                                    <div class="d-flex align-items-center mb-1 gap-1">
                                                        <a href="{{ $item->vb_file_url }}" target="_blank"
                                                            class="me-2 ms-1">
                                                            {{-- Use basename to get the file name --}}
                                                            {{ basename($item->vb_file_url) }}</a>

                                                        <a href="{{ $item->vb_file_url }}" target="_blank"
                                                            title="{{ __('frontend.ui.xem_chi_tiet') }}" data-bs-toggle="tooltip"
                                                            class="text-decoration-none">
                                                            <i class="ri-eye-line"></i>
                                                        </a>
                                                        <a href="{{ $item->vb_file_url }}" download title="{{ __('frontend.ui.tai_xuong') }}"
                                                            data-bs-toggle="tooltip" class="text-decoration-none me-2">
                                                            <i class="ri-download-2-line"></i>
                                                        </a>
                                                    </div>

                                                </div>
                                            @endif
                                            @foreach ($item->fileDinhKem as $file)
                                                <div class="d-flex align-items-center mb-1 gap-1">
                                                    @if (str_ends_with($file, '.pdf'))
                                                        <i class="ri-file-pdf-2-line text-danger me-1"></i>
                                                    @elseif (str_ends_with($file, '.png') || str_ends_with($file, '.jpg') || str_ends_with($file, '.jpeg'))
                                                        <i class="ri-image-line" style="color: green;"></i>
                                                    @else
                                                        <i class="ri-attachment-2"></i>
                                                    @endif
                                                    <a href="{{ $file }}" target="_blank"
                                                        class="me-2 ms-1">{{ basename($file) }}</a>
                                                    <a href="{{ $file }}" target="_blank"
                                                        class="text-decoration-none">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ $file }}" download
                                                        class="text-decoration-none me-2">
                                                        <i class="ri-download-2-line"></i>
                                                    </a>

                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @if ($list->count() > 0)
                            <div class="mt-4">
                                <h4 class=' fw-bold'><i class="ri-list-check"></i> {{ __('frontend.ui.cac_van_ban_khac') }}</h4>
                                <ul class="list-group list-group-flush">
                                    @foreach ($list as $relatedItem)
                                        <li class="list-group-item">
                                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.show', ['id' => $relatedItem->vb_id]) }}"
                                                class="text-decoration-none text-dark">
                                                {{ $relatedItem->vb_trich_yeu }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                        @endif
                        {{-- @if ($hasFile)
                            <div class="mt-6">
                                <div class="rounded-top-4">
                                    <h5 class="card-title mb-0  fw-bold">
                                        <i class="ri-file-eye-line me-2"></i>Xem nhanh nội dung tệp đính kèm
                                    </h5>
                                </div>
                                <div class="p-2">
                                    <div class="mb-3">
                                        <p for="fileSelect">Chọn tài liệu để xem:</p>
                                        <select id="fileSelect" class="form-select">
                                            @if ($item->vb_file_url)
                                                <option value="{{ $item->vb_file_url }}">
                                                    {{ basename($item->vb_file_url) }}
                                                </option>
                                            @endif
                                            @foreach ($item->fileDinhKem as $file)
                                                <option value="{{ $file }}">{{ basename($file) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <iframe id="fileViewer" src="{{ $item->vb_file_url ?? ($item->fileDinhKem[0] ?? '') }}"
                                        style="width: 100%; height: 800px; border: none; border-radius: 0.5rem; display: flex; justify-content: center; align-items: center;"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            </div>
                        @endif --}}

                    </div>
                </div>

            </div>
            <div class='col-12 col-md-3'>
                <aside class="sidebar-area">
                    @if ($dsNhomVanBan->count() > 0)
                        <div class="widget widget_categories">
                            <h4 class="widget_title">{{ __('frontend.ui.danh_sach_nhom_van_ban') }}
                            </h4>
                            <ul>
                                @foreach ($dsNhomVanBan as $item)
                                    <li class="cat-item">
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.index', ['slug' => $item->nvb_slug]) }}"
                                            title="{{ __('frontend.ui.xem_chi_tiet') }}" class="text-dark">
                                            {{ $item->nvb_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<section class="sbut-contact-form"><h2>{{ __('frontend.ui.ban_can_ho_tro') }}</h2><p>{{ __('frontend.ui.lien_he_nha_truong_de_duoc_huong_dan_tra_cuu_tho') }}</p><a class="sbut-text-link" href="{{ route('cms.lien-he') }}">{{ __('frontend.ui.ket_noi_cung_sbut') }}</a></section>@endif
                </aside>
            </div>
        </div>

    </div>
@endsection
<script></script>
