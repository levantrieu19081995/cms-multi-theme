@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.quan_ly_hoi_dap'))

@section('vendor-style')
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/confirm-delete.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.hoidap.index', '') }}
    <!-- Users List Table -->
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_hoi_dap') }}</h5>
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
            <div class="table-responsive">
                <table id="lienket-table" class="table table-bordered table-striped" data-table-id="lienket-table"
                    data-action="lienket-delete">
                    <thead>
                        <tr class="text-center text-nowrap">
                            <th style="width: 1%" class="text-center">STT</th>
                            <th>{{ __('admin.ui.thong_tin_cau_hoi') }}</th>
                            <th style="width: 1%;">{{ __('admin.ui.nguoi_gui_2') }}</th>
                            <th style="width: 80px;">{{ __('admin.ui.trang_thai') }}</th>
                            <th style="width: 10%;">{{ __('admin.ui.hien_thi_trang_chu_2') }}</th>
                            <th style="width: 80px;">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $question)
                            <tr>
                                <td class="text-center">
                                    {{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->index + 1 }}
                                </td>
                                <td>
                                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.edit', $question) }}" title="{{ __('admin.ui.tra_loi') }}"
                                        class='fw-bold'>
                                        {{ $question->title }}</a>
                                    <p class='mb-0 mt-1'>{{ $question->content }}</p>
                                    <small>{{ __('admin.ui.thoi_gian_gui') }} {{ $question->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class='text-nowrap'>
                                    {{ $question->name ?? __('admin.ui.nguoi_gui_an_danh') }}
                                    <div class='row  text-start mt-1'>
                                        <div class="col-12">
                                            @if ($question->phone)
                                                <span class="text-muted"><i class="ri-phone-line "></i>
                                                    {{ $question->phone }}</span>
                                            @endif
                                        </div>
                                        <div class="col-12">
                                            @if ($question->email)
                                                <span class="text-muted"> <i class="ri-mail-line"></i>
                                                    {{ $question->email }}</span>
                                            @endif

                                        </div>

                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($question->answer)
                                        <span class="badge bg-label-success">{{ __('admin.ui.da_tra_loi') }}</span>
                                    @else
                                        <span class="badge bg-label-warning">{{ __('admin.ui.chua_tra_loi') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.approve', $question->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <label class="switch switch-success">
                                            <input type="checkbox" name="is_approved" data-post-id="{{ $question->id }}"
                                                class="switch-input" onchange="this.form.submit()"
                                                {{ $question->is_approved == true ? 'checked' : '' }} />
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
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.edit', $question) }}" class="dropdown-item"
                                            title="{{ __('admin.ui.tra_loi') }}">
                                            <i class="ri-edit-box-line me-2"></i>
                                        </a>
                                        <a href="javascript:void(0)" class="delete-record text-danger"
                                            data-action="lienket-delete" data-id="{{ $question->id }}"
                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.hoi-dap.destroy', $question->id) }}"
                                            title="{{ __('admin.ui.xoa') }}">
                                            <i class="ri-delete-bin-7-line me-2"></i>
                                        </a>
                                        {{-- </div> --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if ($questions->isEmpty())
                            <tr>
                                <td colspan="100">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $questions->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
