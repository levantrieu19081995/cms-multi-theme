@php
    $listTitle = trim($__env->yieldContent('cms-list-title')) ?: \Modules\CmsBackend\Support\AdminTheme::listTitle((array) $cmsMenuData[0]->menu, request()->route()->getName());
    $listTitle = $listTitle ?: __('admin.ui.quan_ly_danh_sach');
@endphp
<nav aria-label="{{ __('admin.ui.duong_dan_trang') }}" class="cms-breadcrumb cms-list-breadcrumb"><i class="ri-home-4-line" aria-hidden="true"></i><a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}">{{ __('admin.ui.quan_tri') }}</a><span>›</span><span>{{ $listTitle }}</span></nav>
<div class="cms-page-heading"><div><h1>{{ $listTitle }}</h1><p class="cms-heading-note">@yield('cms-list-note', __('admin.ui.tra_cuu_thong_tin_va_quan_ly_danh_sach'))</p></div>@yield('cms-list-create')</div>
