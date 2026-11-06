@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.lien_ket_website'))

@section('vendor-style')
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/confirm-delete.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.cdn.lien-ket.index', '') }}
    <!-- Users List Table -->
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.lien_ket_website') }}</h5>
        <div class="card-body mt-5">
            <form action="" method="get">
                <div class="d-grid gap-2 d-md-flex justify-content-md-en mb-5">
                    <div class="input-group input-group-merge bg-white">
                        <span class="input-group-text" id="basic-addon-search31">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="search" id="filter" name="filter" class="form-control" placeholder="{{ __('admin.ui.search_placeholder') }}"
                            aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">

                    </div>

                    <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                        <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                    </button>
                </div>
            </form>

            <div class="table-responsive text-nowrap">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.create') }}" type="button"
                        class="btn btn-primary waves-effect text-none"><span
                            class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}</a>
                </div>
                <table id="lienket-table" class="table table-bordered" data-table-id="lienket-table"
                    data-action="lienket-delete">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 5%" class="text-center">STT</th>
                            <th style="width: 15%">{{ __('admin.ui.anh_dai_dien') }}</th>
                            <th>{{ __('admin.ui.ten_website_lien_ket') }}</th>
                            <th>{{ __('admin.ui.duong_dan') }}</th>
                            <th style="width: 80px;">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($links as $link)
                            <tr>
                                <td class="text-center">
                                    {{ ($links->currentPage() - 1) * $links->perPage() + $loop->index + 1 }}</td>
                                <td class="text-center">
                                    <img style="width: 50px" src="{{ $link->getFirstMediaUrl('anhQuangCaos') }}"
                                        alt="{{ __('admin.ui.xem_truoc_quang_cao') }}">
                                </td>
                                <td>{{ $link->title }}

                                </td>
                                <td>
                                    @if ($link->redirect_url)
                                        <a href="{{ $link->redirect_url }}" target="_blank">{{ $link->redirect_url }}</a>
                                    @else
                                        <span class="fst-italic">{{ __('admin.ui.chua_co_duong_dan') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-50">
                                        {{-- <button
                                            class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-line ri-20px"></i>
                                        </button> --}}
                                        {{-- <div class="dropdown-menu dropdown-menu-end m-0" style=""> --}}
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.edit', $link) }}" class="dropdown-item"
                                            data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}">
                                            <i class="ri-edit-box-line me-2"></i>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                            data-bs-toggle="tooltip" data-action="lienket-delete"
                                            data-id="{{ $link->id }}"
                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.destroy', $link->id) }}"
                                            title="{{ __('admin.ui.xoa') }}">
                                            <i class="ri-delete-bin-7-line me-2"></i>
                                        </a>
                                        {{-- </div> --}}
                                </td>
                            </tr>
                        @endforeach
                        @if ($links->isEmpty())
                            <tr class='text-center'>
                                <td colspan="50">{{ __('admin.ui.not_found') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $links->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
