@php
    use Modules\CmsBackend\Enums\PermissionsEnum;
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_dich_vu'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 'resources/assets/js/forms-selects.js', 'Modules/CmsBackend/resources/assets/js/bai-viet.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.dich-vu.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_dich_vu') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card mb-5">
                <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.index') }}"
                    data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.index') }}"
                    class="cms-filter-panel" method="get">
                    <label for="filter">{{ __('admin.ui.tim_kiem') }}
                        <input type="search" id="filter" name="filter" class="form-control"
                            placeholder="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                    </label>
                    <label for="status">{{ __('admin.ui.trang_thai') }}
                        <select id="status" name="status" class="select2 form-select">
                            <option value="-1" @selected((string) request('status', '-1') === '-1')>{{ __('admin.ui.tat_ca') }}</option>
                            <option value="1" @selected((string) request('status') === '1')>{{ __('admin.ui.hien_thi') }}</option>
                            <option value="0" @selected((string) request('status') === '0')>{{ __('admin.ui.da_an') }}</option>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-outline-primary">
                        <span class="tf-icons ri-search-line ri-16px" aria-hidden="true"></span>{{ __('admin.ui.search') }}
                    </button>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    @can(PermissionsEnum::DICHVU_CREATE->label())
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.create') }}"
                            type="button"class="btn btn-primary waves-effect text-none">
                            <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                        </a>
                    @endcan
                </div>

                <table class="table table-bordered table-striped table-bai-viet" id="dich-vu" data-table-id="dich-vu"
                    data-action="dich-vu-delete">
                    <thead>
                        <tr>
                            <th style="width: 5px">{{ __('column-name.index') }}</th>
                            <th>{{ __('admin.ui.ten_dich_vu') }}</th>
                            <th style="width: 30%">{{ __('admin.ui.duong_dan') }}</th>
                            <th style="width: 15%">Icon</th>
                            <th style="width: 10%">{{ __('admin.ui.trang_thai') }}</th>
                            @if ($hasActions)
                                <th style="width: 5%;">{{ __('admin.ui.hanh_dong') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if (!isset($dichvus) || $dichvus->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @else
                            @foreach ($dichvus as $index => $dichvu)
                                <tr>
                                    <th scope="row" class="text-center">
                                        {{ ($dichvus->currentPage() - 1) * $dichvus->perPage() + $loop->index + 1 }}</th>
                                    <td class="td-thong-tin-bai-viet">
                                        <p class="h6">
                                            <strong>
                                                <a
                                                    href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.edit', $dichvu) }}">{{ $dichvu->title }}</a>
                                            </strong>
                                        </p>


                                    </td>
                                    <td>{{ $dichvu->link }}</td>
                                    <td class="text-center">
                                        <div class="p-3 h-100">
                                            <i class="ri-{{ $dichvu->icon }} fs-3" data-icon="{{ $dichvu->icon }}"></i>
                                            <div class="small">{{ $dichvu->icon }}</div>
                                        </div>
                                    </td>

                                    <td class="text-center"> <label class="switch switch-success">
                                            <input type="checkbox" name="show_home_page" data-post-id="{{ $dichvu->id }}"
                                                class="switch-input" {{ $dichvu->status == '1' ? 'checked' : '' }} />
                                            <span class="switch-toggle-slider">
                                                <span class="switch-on">
                                                    <i class="ri-check-line"></i>
                                                </span>
                                                <span class="switch-off">
                                                    <i class="ri-close-line"></i>
                                                </span>
                                            </span>
                                        </label>
                                    </td>
                                    @if ($hasActions)
                                        <td class="text-center">
                                            <div class="gap-50">
                                                {{-- <button
                                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-line ri-20px"></i>
                                                </button> --}}
                                                <div class="d-flex align-items-center gap-50 justify-content-center">
                                                    @can(PermissionsEnum::DICHVU_UPDATE->label())
                                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.edit', $dichvu) }}"
                                                            class="dropdown-item delete-record text-dark"
                                                            data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}">
                                                            <i class="ri-edit-box-line ri-20px"></i>
                                                        </a>
                                                    @endcan
                                                    @can(PermissionsEnum::DICHVU_DELETE->label())
                                                        <a href="javascript:void(0)"
                                                            class="dropdown-item delete-record text-danger"
                                                            data-bs-toggle="tooltip" title="{{ __('admin.ui.xoa') }}"
                                                            data-action="{{ 'dich-vu-delete' }}" data-id="{{ $dichvu->id }}"
                                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.destroy', $dichvu) }}">
                                                            <i class="ri-delete-bin-7-line me-2"></i>
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>

                <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
                    {{ $dichvus->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
                </div>

            </div>
        </div>
    </div>

@endsection
