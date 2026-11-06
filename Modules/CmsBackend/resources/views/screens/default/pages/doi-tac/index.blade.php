@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_doi_tac'))

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
    {{ Breadcrumbs::render('cms.dashboard.doi-tac.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_doi_tac') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card ">
                <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.index') }}"
                    data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.index') }}">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="{{ __('admin.ui.search_placeholder') }}" aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                        </div>
                        @php $status = request()->query('status', '-1'); @endphp
                        <div class="form-floating form-floating-outline bg-white" style="min-width: 260px">
                            <select id="status" name="status" class="select2 form-select">
                                <option value="-1" @selected($status === '-1')>{{ __('admin.ui.tat_ca') }}</option>
                                <option value="1" @selected($status === '1')>{{ __('admin.ui.hien_thi_trang_chu_2') }}</option>
                                <option value="0" @selected($status === '0')>{{ __('admin.ui.da_an') }}</option>
                            </select>
                            <label for="status">{{ __('admin.ui.trang_thai') }}</label>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap" id="table-doi-tac-container">
                @if ($permissions['canCreate'] === true)
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.create') }}" type="button"
                            class="btn btn-primary waves-effect text-none">
                            <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                        </a>
                    </div>
                @endif
                <table class="table table-bordered table-striped table-bai-viet" id="doi-tac" data-table-id="doi-tac"
                    data-action="doi-tac-delete">
                    <thead>
                        <tr>
                            <th style="width: 5px">{{ __('column-name.index') }}</th>
                            <th>{{ __('admin.ui.ten_doi_tac') }}</th>
                            <th style="width: 30%">{{ __('admin.ui.duong_dan') }}</th>
                            <th style="width: 10%">{{ __('admin.ui.trang_thai') }}</th>
                            @if ($permissions['canCreate'] === true || $permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                                <th style="width: 5%;">{{ __('admin.ui.hanh_dong') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if (!isset($doitacs) || $doitacs->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @else
                            @foreach ($doitacs as $index => $doitac)
                                <tr>
                                    <th scope="row" class="text-center">
                                        {{ ($doitacs->currentPage() - 1) * $doitacs->perPage() + $loop->index + 1 }}
                                    </th>
                                    <td class="td-thong-tin-bai-viet">
                                        <p class="h6">
                                            <a
                                                href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.edit', $doitac) }}">{{ $doitac->title }}</a>
                                        </p>


                                    </td>
                                    <td>{{ $doitac->slug }}</td>


                                    <td class="text-center"> <label class="switch switch-success">
                                            <input type="checkbox" name="show_home_page" data-post-id="{{ $doitac->id }}"
                                                class="switch-input"
                                                {{ $doitac->show_home_page == '1' ? 'checked' : '' }} />
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
                                    @if ($permissions['canCreate'] === true || $permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                                        <td class="text-center">
                                            <div class="gap-50">
                                                {{-- <button
                                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-line ri-20px"></i>
                                                </button> --}}
                                                <div class="d-flex align-items-center gap-50 justify-content-center">
                                                    @if ($permissions['canCreate'] === true)
                                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.edit', $doitac) }}"
                                                            class="dropdown-item delete-record text-dark"
                                                            data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}">
                                                            <i class="ri-edit-box-line ri-20px"></i>
                                                        </a>
                                                    @endif
                                                    @if ($permissions['canUpdate'] === true)
                                                        <a href="javascript:void(0)"
                                                            class="dropdown-item delete-record text-danger"
                                                            data-action="{{ 'doi-tac-delete' }}"
                                                            data-id="{{ $doitac->id }}" data-bs-toggle="tooltip"
                                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.destroy', $doitac) }}"
                                                            title="{{ __('admin.ui.xoa') }}">
                                                            <i class="ri-delete-bin-7-line me-2"></i>
                                                        </a>
                                                    @endif
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
                    {{ $doitacs->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
                </div>

            </div>
        </div>
    </div>

@endsection
