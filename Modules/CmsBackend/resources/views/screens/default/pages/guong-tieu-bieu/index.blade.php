@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_guong_tieu_bieu'))

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
    <style>
        .cms-clamp-5 { display: -webkit-box; -webkit-line-clamp: 5; line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    </style>
@endsection


@section('content')
    {{ Breadcrumbs::render('cms.dashboard.guong-tieu-bieu.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_guong_tieu_bieu') }}</h5>

        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.index') }}"
                    data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.index') }}">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-6">
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
            <div class="table-responsive text-nowrap" id="table-guong-tieu-bieu-container">
                @if ($permissions['canCreate'] === true)
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.create') }}" type="button"
                            class="btn btn-primary waves-effect text-none">
                            <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                        </a>
                    </div>
                @endif
                <table class="table table-bordered table-striped table-bai-viet" id="guong-tieu-bieu"
                    data-table-id="guong-tieu-bieu" data-action="guong-tieu-bieu-delete">
                    <thead>
                        <tr>
                            <th style="width: 5px">{{ __('column-name.index') }}</th>
                            <th>{{ __('admin.ui.ten_tam_guong_tieu_bieu') }}</th>
                            <th>{{ __('admin.ui.gioi_thieu') }}</th>
                            <th>{{ __('admin.ui.thong_tin_tam_guong_tieu_bieu') }}</th>

                            @if ($permissions['canCreate'] === true || $permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                                <th style="width: 5%;">{{ __('admin.ui.hanh_dong') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if (!isset($guongTieuBieus) || $guongTieuBieus->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @else
                            @foreach ($guongTieuBieus as $index => $guongTieuBieu)
                                <tr>

                                    <th scope="row" class="text-center">
                                        {{ ($guongTieuBieus->currentPage() - 1) * $guongTieuBieus->perPage() + $loop->index + 1 }}
                                    </th>
                                    <td class="td-thong-tin-bai-viet">
                                        <p class="h6">
                                        <div><strong>

                                                <a
                                                    href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.edit', $guongTieuBieu) }}">{{ $guongTieuBieu->title }}</a>
                                            </strong>
                                        </div>
                                        <div>
                                            <small class="text-muted">{{ __('admin.ui.vai_tro') }} {{ $guongTieuBieu->role }}</small>
                                        </div>
                                        <div>
                                            <small class="text-muted">{{ __('admin.ui.rating_stars', ['value' => number_format($guongTieuBieu->rating, 0)]) }}</small>
                                        </div>


                                        </p>
                                    </td>
                                    <td class="td-thong-tin-bai-viet">
                                        <p class="h6">
                                            {{ strip_tags($guongTieuBieu->excerpt) }}
                                        </p>
                                    </td>
                                    <td class="td-thong-tin-bai-viet">
                                        <p class="h6 mb-0 cms-clamp-5">
                                            {{ strip_tags($guongTieuBieu->content) }}
                                        </p>
                                    </td>

                                    @if ($permissions['canCreate'] === true || $permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                                        <td class="text-center">
                                            <div class="gap-50">
                                                <div class="d-flex align-items-center gap-50 justify-content-center">
                                                    @if ($permissions['canCreate'] === true)
                                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.edit', $guongTieuBieu) }}"
                                                            class="dropdown-item delete-record text-dark"
                                                            data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}">
                                                            <i class="ri-edit-box-line ri-20px"></i>
                                                        </a>
                                                    @endif
                                                    @if ($permissions['canUpdate'] === true)
                                                        <a href="javascript:void(0)"
                                                            class="dropdown-item delete-record text-danger"
                                                            data-action="{{ 'guong-tieu-bieu-delete' }}"
                                                            data-id="{{ $guongTieuBieu->id }}"
                                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.destroy', $guongTieuBieu) }}"
                                                            title="{{ __('admin.record.edit') }}">
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
                    {{ $guongTieuBieus->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
                </div>

            </div>
        </div>
    </div>

@endsection
