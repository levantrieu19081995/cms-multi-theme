    <aside class="cms-admin-sidebar" id="cms-admin-menu" aria-label="{{ __('admin.ui.chuc_nang_quan_tri') }}">
        <div class="cms-sidebar-heading">
            <a class="cms-sidebar-home" aria-label="{{ __('admin.ui.quan_tri') }}" title="{{ __('admin.ui.quan_tri') }}" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}"><i class="ri-home-4-line" aria-hidden="true"></i><span>{{ __('admin.ui.quan_tri') }}</span></a>
            <button class="cms-sidebar-toggle" id="cms-sidebar-toggle" type="button" aria-label="{{ __('admin.ui.thu_gon_hoac_mo_rong_menu') }}" aria-controls="cms-admin-menu" aria-expanded="true"><i class="ri-arrow-left-double-line" aria-hidden="true"></i></button>
        </div>
        <nav><ul class="cms-admin-links">
            @foreach($cmsMenuData[0]->menu as $menu)
                @if(isset($menu->menuHeader))<li class="cms-nav-heading">{{ __($menu->menuHeader) }}</li>
                @else
                    @php $active = \Modules\CmsBackend\Support\AdminTheme::active($menu, request()->route()->getName() ?? ''); @endphp
                    <li class="cms-nav-item {{ $active ? 'active' : '' }} {{ $active && isset($menu->submenu) ? 'open' : '' }}"><a class="cms-nav-link {{ isset($menu->submenu) ? 'cms-nav-toggle' : '' }}" aria-label="{{ __($menu->name ?? '') }}" title="{{ __($menu->name ?? '') }}" href="{{ isset($menu->url) ? \Modules\CmsBackend\Support\ModuleUrl::url($menu->url) : '#' }}" @if(isset($menu->submenu)) aria-expanded="{{ $active ? 'true' : 'false' }}" @endif @if(isset($menu->target)) target="{{ $menu->target }}" @endif><i class="{{ $menu->icon ?? 'ri-circle-line' }}" aria-hidden="true"></i><span>{{ __($menu->name ?? '') }}</span></a>
                    @if(isset($menu->submenu))@include('cmsbackend::screens.components.shared.layouts.sections.menu.submenu', ['menu'=>$menu->submenu])@endif</li>
                @endif
            @endforeach
        </ul><p id="cms-menu-empty" class="cms-menu-empty" hidden>{{ __('admin.ui.khong_tim_thay_chuc_nang_phu_hop') }}</p></nav>
    </aside>
    <script>
        // Every menu click loads a new page, which would reset the sidebar scroll to the top. Keep the scroll position between pages
        // and make sure the active item is visible (runs right after the sidebar is parsed, before the first paint).
        (function () {
            var side = document.getElementById('cms-admin-menu'), KEY = 'cms.admin.sidebar.scroll';
            if (!side) return;
            var save = function () { try { sessionStorage.setItem(KEY, String(side.scrollTop)); } catch (e) {} };
            try { var saved = parseInt(sessionStorage.getItem(KEY), 10); if (!isNaN(saved)) side.scrollTop = saved; } catch (e) {}
            var actives = side.querySelectorAll('.cms-nav-item.active, .cms-nav-item .active'), target = actives.length ? actives[actives.length - 1] : null;
            if (target) {
                var box = side.getBoundingClientRect(), item = target.getBoundingClientRect();
                if (item.top < box.top + 40 || item.bottom > box.bottom - 16) side.scrollTop += (item.top + item.height / 2) - (box.top + box.height / 2);
            }
            side.addEventListener('click', function (event) { if (event.target.closest('a')) save(); });
            window.addEventListener('pagehide', save);
        })();
    </script>
