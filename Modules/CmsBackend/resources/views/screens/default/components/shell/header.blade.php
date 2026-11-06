    <header class="cms-admin-topbar">
        <a class="cms-admin-brand" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}" aria-label="{{ __('admin.ui.ve_trang_chu_quan_tri') }}">@php($siteLogo = \Modules\CmsBackend\Support\SiteLogo::url())@if($siteLogo)<img src="{{ $siteLogo }}" alt="Logo trang web">@else<img src="{{ asset('assets/img/khuyen-nong-can-tho/admin/logo.png') }}" alt="{{ __('admin.ui.khuyen_nong_can_tho') }}" width="2172" height="724">@endif</a>
        <button class="cms-menu-button" type="button" id="cms-menu-button" aria-label="{{ __('admin.ui.mo_hoac_thu_gon_menu') }}" aria-controls="cms-admin-menu" aria-expanded="true"><i class="ri-menu-line" aria-hidden="true"></i></button>
        <div class="cms-admin-search"><i class="ri-search-line" aria-hidden="true"></i><input type="search" id="cms-menu-search" aria-label="{{ __('admin.ui.tim_kiem_chuc_nang_trong_menu') }}" placeholder="{{ __('admin.ui.tim_kiem_chuc_nang') }}" autocomplete="off"></div>
        {{-- Administration language (session + cookie, App\Http\Controllers\LanguageController). Independent of the public-site language. --}}
        {{-- Ẩn khi chức năng "Đa ngôn ngữ" tắt trong Developer Settings: quản trị chỉ còn tiếng Việt. --}}
        @if (\Illuminate\Support\Facades\Route::has('lang.switch') && app(\App\Services\LocaleManager::class)->multilingualEnabled())
            <nav class="cms-admin-lang" aria-label="{{ __('admin.ui.ngon_ngu') }}">
                @foreach (config('app.available_locales', []) as $adminLocale => $adminLocaleLabel)
                    <a href="{{ route('lang.switch', $adminLocale) }}" lang="{{ $adminLocale }}" title="{{ $adminLocaleLabel }}" @if (app()->getLocale() === $adminLocale) aria-current="true" @endif>{{ strtoupper($adminLocale) }}</a>
                @endforeach
            </nav>
            <style>.cms-admin-topbar .cms-admin-lang{display:flex;align-items:center;gap:4px;margin-left:auto;margin-right:20px;flex-shrink:0}.cms-admin-topbar .cms-admin-lang~.cms-admin-account{margin-left:0}.cms-admin-lang a{min-width:34px;padding:5px 9px;border-radius:6px;font-size:.8rem;font-weight:600;line-height:1.2;text-align:center;text-decoration:none;color:inherit;border:1px solid transparent}.cms-admin-lang a:hover{background:rgba(0,0,0,.05)}.cms-admin-lang a[aria-current]{border-color:currentColor}</style>
        @endif
        <div class="dropdown cms-admin-account">
            <button class="cms-account-button" data-bs-toggle="dropdown" type="button" aria-expanded="false"><span class="cms-account-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><span>{{ auth()->user()->name }}</span><i class="ri-arrow-down-s-line" aria-hidden="true"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if(\Illuminate\Support\Facades\Route::has('profile.edit'))<li><a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('admin.ui.tai_khoan_cua_toi') }}</a></li>@endif
                <li><form method="POST" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.logout') }}" data-cms-logout data-auth-transition="{{ data_get(config('cmsbackend.screens.themes'), app(\Modules\CmsBackend\Services\ScreenTheme::class)->key('public').'.login.transition') }}" data-login-url="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.login') }}">@csrf<button class="dropdown-item" type="submit">{{ __('admin.ui.dang_xuat') }}</button></form></li>
            </ul>
        </div>
    </header>
    @include('cmsbackend::screens.components.developer-shortcut', ['selector' => '.cms-admin-brand'])
