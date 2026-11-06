@php
    $configData = Helper::appClasses();
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_van_ban'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
    <style>
  /* Trích yếu: cho phép xuống dòng, bẻ từ dài */
  .table-vb td.trich-yeu, .table-vb th.trich-yeu {
    white-space: normal;       /* Cho phép xuống dòng */
    word-wrap: break-word;     /* Hỗ trợ trình duyệt cũ */
    overflow-wrap: break-word; /* Chuẩn mới */
  }
  /* Cột đường dẫn: rút gọn hiển thị nhưng vẫn là link đầy đủ */
  .table-vb td.url a {
    display: inline-block;
    max-width: 200px;   /* khớp colgroup 220px - trừ padding */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
  }
  .label {
    display: inline-block;
    width: 100px;  
    font-weight: bold;
}
</style>
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    {{-- @vite(['Modules/CmsBackend/resources/assets/js/confirm-delete.js']) --}}
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 'resources/assets/js/forms-selects.js', 'Modules/CmsBackend/resources/assets/js/bai-viet.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.cdn.van-ban.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_van_ban') }}</h5>
        <div class="card-body mt-5">
            <form action="" method="get">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    <div class="input-group input-group-merge bg-white">
                        <span class="input-group-text" id="basic-addon-search31">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="search" id="filter" name="filter" class="form-control" placeholder="{{ __('admin.ui.search_placeholder') }}"
                            aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                    </div>
                    <div class="form-floating form-floating-outline bg-white" style="min-width: 260px">
                        <select id="roles" name="lvb_id" class="select2 form-select" data-allow-clear="true">
                            <option value="">{{ __('admin.ui.chon_loai_van_ban') }}</option>
                            @foreach ($listLoaiVanBan as $loaiVanBan)
                                <option value="{{ Illuminate\Support\Str::toBase64($loaiVanBan->lvb_id) }}"
                                    @if ($loaiVanBan->lvb_id == $lvbId) selected @endif>{{ $loaiVanBan->lvb_ten }}
                                </option>
                            @endforeach
                        </select>
                        <label for="roles">{{ __('admin.ui.loai_van_ban') }}</label>
                    </div>
                    <div class="form-floating form-floating-outline bg-white">
                        <input type="date" id="vb_tu_ngay" name="vb_ngay_ban_hanh_tu_ngay" class="form-control"
                            value="{{ $tuNgayString }}">
                        <label for="vb_tu_ngay">{{ __('admin.ui.ban_hanh_tu_ngay') }}</label>
                    </div>
                    <div class="form-floating form-floating-outline bg-white">
                        <input type="date" id="vb_den_ngay" name="vb_ngay_ban_hanh_den_ngay" class="form-control"
                            value="{{ $denNgayString }}">
                        <label for="vb_den_ngay">{{ __('admin.ui.den_ngay_2') }}</label>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                        <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                    </button>
                </div>

            </form>

            <div class="table-responsive text-nowrap" id="table-trang-container">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    {{-- @can(PermissionsEnum::THEM_MON_HOC->label()) --}}
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.create') }}"
                        type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                    </a>
                    {{-- @endcan --}}
                </div>
            </div>
            {{-- {{ dd($list) }} --}}
            {{-- bang du lieu  --}}
            <div class="table-responsive text-nowrap">
                <table id="vanban-table" class="table table-bordered table-vb" data-table-id="vanban-table"
                    data-action="vanban-delete">
                      <colgroup>
                        <col style="width:60px">     {{-- STT --}}
                        <col style="width:140px">    {{-- Số hiệu --}}
                        <col>
                        <col style="width:220px">    {{-- Đường dẫn --}}
                        <col style="width:80px">     {{-- Tập tin --}}
                        <col style="width:90px">     {{-- Hành động --}}
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="width: 1%" class="text-center">STT</th>
                            <th style="width: 150px;" class="text-center">{{ __('admin.ui.so_hieu') }}</th>
                            <th class="text-center  trich-yeu">{{ __('admin.ui.trich_yeu') }}</th>
                             <th style="width: 200px;" class="text-center">{{ __('admin.ui.duong_dan') }}</th>
                            <th style="width: 50px;" class="text-center">{{ __('admin.ui.tap_tin') }}</th>
                            <th style="width: 80px;" class="text-center">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($list->isEmpty())
                            <tr class='text-center'>
                                <td colspan="100">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @else
                            @foreach ($list as $item)
                                <tr>
                                    <td class="text-center">
                                        {{ ($list->currentPage() - 1) * $list->perPage() + $loop->index + 1 }}</td>
                                   <td>
                                        <div ><span class=" ">{{ __('admin.ui.so_hieu_2') }}</span><span style="color: #084b85; font-weight: bold"> {{ $item->vb_so_hieu }}</span></div>
                                        <div><span class=" ">{{ __('admin.ui.loai_van_ban_2') }}</span> {{ $item->loaiVanBan->lvb_ten }}</div>
                                        <div><span class=" ">{{ __('admin.ui.ngay_ban_hanh_2') }}</span> {{ $item->vb_ngay_ban_hanh }}</div>
                                        <div><span class=" ">{{ __('admin.ui.nguoi_them_2') }}</span> {{ $item->user->name}}</div>
                                    </td>

                                    <td class="trich-yeu"> <span style="color: #084b85;"> {{ $item->vb_trich_yeu }}</span></td>
                                    
                                    <td class="text-center url"> 
                                        @if(!empty($item->vb_file_url))
                                        <a href="{{ $item->vb_file_url }}" target="_blank">
                                            {{ $item->vb_file_url }}
                                        </a>
                                        @else
                                        <em>—</em>
                                        @endif
                                    </td>
                                    <td class="text-center"> 
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-attachment-2"></i>
                                            </button>
                                            <ul class="dropdown-menu px-2">
                                                @php
                                                    $hasFile =
                                                        !empty($item->vb_file_url) ||
                                                        (isset($item->fileDinhKem) && count($item->fileDinhKem) > 0);
                                                @endphp

                                                @if ($hasFile)
                                                    {{-- @if (!empty($item->vb_file_url))
                                                        <li>
                                                            <a href="{{ $item->vb_file_url }}" title="Xem tập tin"
                                                                target="_blank">
                                                                <i class="ri-eye-fill"></i>
                                                            </a>
                                                            <a href="{{ $item->vb_file_url }}" title="Tải tập tin"
                                                                target="_blank" download>
                                                                <i class="ri-file-download-fill"></i>
                                                            </a>
                                                            <span>{{ basename($item->vb_file_url) }}</span>
                                                        </li>
                                                    @endif --}}

                                                    @foreach ($item->fileDinhKem ?? [] as $file)
                                                        <li>
                                                            <a href="{{ $file }}" title="{{ __('admin.ui.xem_tap_tin') }}"
                                                                target="_blank">
                                                                <i class="ri-eye-fill"></i>
                                                            </a>
                                                            <a href="{{ $file }}" title="{{ __('admin.ui.tai_tap_tin') }}"
                                                                target="_blank" download>
                                                                <i class="ri-file-download-fill"></i>
                                                            </a>
                                                            <span>{{ basename($file) }}</span>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <li><em>{{ __('admin.ui.khong_co_file_dinh_kem') }}</em></li>
                                                @endif

                                            </ul>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            {{-- <button
                                                class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button> --}}
                                            {{-- <div class="dropdown-menu dropdown-menu-end m-0" style=""> --}}
                                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.edit', $item) }}"
                                                class="dropdown-item" title="{{ __('admin.record.edit') }}">
                                                <i class="ri-edit-box-line me-2"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                                data-action="vanban-delete" data-id="{{ $item->lvb_id }}"
                                                data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.van-ban.destroy', $item) }}"
                                                data-reload-table="main-table" title="{{ __('admin.ui.xoa') }}">
                                                <i class="ri-delete-bin-7-line me-2"></i>
                                            </a>
                                            {{-- </div> --}}
                                        </div>
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
@endsection
