@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.quan_ly_su_kien'))

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
    {{ Breadcrumbs::render('cms.dashboard.su-kien.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_su_kien') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.index') }}"
                    data-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.index') }}">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="{{ __('admin.ui.search_placeholder') }}" aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                        </div>
                        <div class="form-floating form-floating-outline bg-white" style="min-width: 260px">
                            <select id="postStatus" name="postStatuses[]" class="select2 form-select"
                                placeholder="{{ __('admin.ui.select_status') }}" data-allow-clear="true" multiple>
                                @foreach ($postStatuses as $status)
                                    <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                                        @if ($status['selected']) selected @endif>
                                        {{ $status['enum']->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="postStatus">{{ __('admin.ui.trang_thai') }}</label>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>{{ __('admin.ui.search') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap" id="table-su-kien-container">
                @if ($permissions['canPublishNow'] === true)
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5  "> 
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.create') }}" type="button"
                            class="btn btn-primary waves-effect text-none">
                            <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                        </a> 
                    </div>
                @endif
                @include('cmsbackend::screens.pages.su-kien.partials.table-su-kien', [
                    'mode' => 'normal',
                    'type' => 'event',
                    'route' => 'cms.dashboard.su-kien',
                ])
            </div>
        </div>
    </div>

@endsection
