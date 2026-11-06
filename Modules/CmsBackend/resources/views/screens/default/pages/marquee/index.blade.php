@php
    $configData = Helper::appClasses();
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_marquee'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/confirm-delete.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.cdn.marquee.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_marquee') }}</h5>
        <div class="card-body mt-5">
            <div>
                <form action="" method="get" class="mb-5">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="{{ __('admin.ui.search_placeholder') }}" aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">

                        </div>

                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap" id="table-trang-container">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    {{-- @can(PermissionsEnum::THEM_MON_HOC->label()) --}}
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.create') }}"
                        type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                    </a>
                    {{-- @endcan --}}
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table id="loaivanban-table" class="table table-bordered" data-table-id="loaivanban-table"
                    data-action="loaivanban-delete">
                    <thead>
                        <tr>
                            <th style="width: 1%" class="text-center">STT</th>
                            <th class="text-center">{{ __('admin.ui.ten_marquee') }}</th>
                            <th class="text-center">{{ __('admin.ui.noi_dung') }}</th>
                            <th style="width: 200px;" class="text-center">{{ __('admin.ui.nguoi_them') }}</th>
                            <th style="width: 2%;word-break: break-all">{{ __('admin.ui.hien_thi_trang_chu_2') }}</th>
                            <th style="width: 80px;" class="text-center">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($list->isEmpty())
                            <tr>
                                <td colspan="100" class='text-center'>{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @else
                            @foreach ($list as $item)
                                <tr>
                                    <td class="text-center">
                                        {{ ($list->currentPage() - 1) * $list->perPage() + $loop->index + 1 }}</td>
                                    <td class="text-center">{{ $item->name }}</td>
                                    <td>{{ $item->content }}</td>
                                    <td class="text-center">{{ $item->user->name }}</td>
                                    <td class="text-center">
                                        <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.approve', $item->id) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <label class="switch switch-success">
                                                <input type="checkbox" name="show_home_page"
                                                    data-post-id="{{ $item->id }}" class="switch-input"
                                                    onchange="this.form.submit()"
                                                    {{ $item->show_home_page == true ? 'checked' : '' }} />
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on">
                                                        <i class="ri-check-line"></i>
                                                    </span>
                                                    <span class="switch-off">
                                                        <i class="ri-close-line"></i>
                                                    </span>
                                                </span>
                                            </label>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-50 justify-content-center">
                                            {{-- <button
                                                class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button> --}}
                                            {{-- <div class="dropdown-menu dropdown-menu-end m-0" style=""> --}}

                                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.edit', $item) }}" title="{{ __('admin.record.edit') }}"
                                                class="dropdown-item">
                                                <i class="ri-edit-box-line me-2"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                                data-action="loaivanban-delete" data-id="{{ $item->lvb_id }}"
                                                data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.destroy', $item) }}"
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
