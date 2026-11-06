@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.quan_ly_lien_he'))

@section('vendor-style')
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.lienhe.index', '') }}
    @php($indexUrl = \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.index'))
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_lien_he') }}</h5>
        <div class="card-body mt-5">
            <ul class="nav nav-pills mb-4">
                <li class="nav-item"><a class="nav-link {{ $trashed ? '' : 'active' }}" href="{{ $indexUrl }}">{{ __('admin.contact.filter_active') }}</a></li>
                <li class="nav-item"><a class="nav-link {{ $trashed ? 'active' : '' }}" href="{{ $indexUrl }}?status=trash">{{ __('admin.contact.filter_trash') }}</a></li>
            </ul>
            <form action="{{ $indexUrl }}" method="get" class="mb-5">
                @if ($trashed)
                    <input type="hidden" name="status" value="trash">
                @endif
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <div class="input-group input-group-merge bg-white">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="search" id="filter" name="filter" class="form-control" maxlength="100"
                            placeholder="{{ __('admin.ui.search_placeholder') }}" aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                    </div>
                    <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                        <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table id="lienhe-table" class="table table-bordered table-striped">
                    <thead>
                        <tr class="text-center text-nowrap">
                            <th style="width: 1%" class="text-center">STT</th>
                            <th>{{ __('admin.ui.tieu_de') }}</th>
                            <th style="width: 1%">{{ __('admin.ui.nguoi_gui_2') }}</th>
                            <th style="width: 120px;">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dsgopy as $gopy)
                            <tr>
                                <td class="text-center">{{ ($dsgopy->currentPage() - 1) * $dsgopy->perPage() + $loop->index + 1 }}</td>
                                <td>
                                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.show', $gopy->id) }}" class="fw-bold">{{ $gopy->subject }}</a>
                                    <p class="mb-0 mt-1">{{ \Illuminate\Support\Str::limit($gopy->message, 200) }}</p>
                                    <small>{{ __('admin.ui.thoi_gian_gui') }} {{ $gopy->created_at?->format('d/m/Y H:i') }}</small>
                                    @if ($trashed && $gopy->deleted_at)
                                        <small class="d-block text-danger">{{ __('admin.contact.deleted_at') }} {{ $gopy->deleted_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $gopy->name ?? __('admin.ui.nguoi_gui_an_danh') }}
                                    @if ($gopy->phone)
                                        <div class="text-muted"><i class="ri-phone-line"></i> {{ $gopy->phone }}</div>
                                    @endif
                                    @if ($gopy->email)
                                        <div class="text-muted"><i class="ri-mail-line"></i> {{ $gopy->email }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.show', $gopy->id) }}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="{{ __('admin.record.show') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        @if ($canDelete)
                                            @if ($trashed)
                                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.restore', $gopy->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-text-success rounded-pill" title="{{ __('admin.contact.restore') }}"><i class="ri-arrow-go-back-line"></i></button>
                                                </form>
                                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.purge', $gopy->id) }}" data-confirm="{{ __('admin.contact.confirm_purge') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-text-danger rounded-pill" title="{{ __('admin.contact.purge') }}"><i class="ri-delete-bin-7-line"></i></button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.lien-he.destroy', $gopy->id) }}" data-confirm="{{ __('admin.contact.confirm_delete') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-text-danger rounded-pill" title="{{ __('admin.contact.delete') }}"><i class="ri-delete-bin-7-line"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if ($dsgopy->isEmpty())
                            <tr><td colspan="4">{{ __('admin.contact.empty') }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $dsgopy->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endsection
