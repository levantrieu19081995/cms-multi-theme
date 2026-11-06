@php
    use Illuminate\Support\Str;
    use App\Enums\PermissionsEnum
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_banner'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 'resources/assets/js/forms-selects.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.cdn.banner.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.danh_sach_banner') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form id="searchForm" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.banner.index') }}" data-search-form>
                    @csrf

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
                                    <option value="{{ Str::toBase64($status['enum']->value) }}"
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

            <div class="table-responsive text-nowrap"table-trang-container>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    @can(PermissionsEnum::BANNER_CREATE->label())
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.banner.create') }}"
                        type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>{{ __('admin.ui.add_new') }}
                    </a>
                    @endcan
                </div>

                @include('cmsbackend::screens.components.partials.post.table-post', [
                    'mode' => 'normal',
                    'type' => 'banner',
                    'route' => 'cms.dashboard.cdn.banner',
                    'tableId' => 'banner',
                ])
            </div>


        </div>
    </div>

@endsection
