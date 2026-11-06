@php $configData = Helper::appClasses(); @endphp
@extends('cmsbackend::screens.components.shared.layouts.commonMaster')
@section('layoutContent')
@include('cmsbackend::screens.components.editorial.styles')
{{-- Endpoints used by the admin scripts, generated from route names (scripts never write /cms/... themselves). --}}
@php
    $cmsRoute = fn (string $name, mixed $parameters = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $parameters) : null;
    $cmsUrls = [
        'updateHomepage' => $cmsRoute('api.update-homepage'),
        'updateCommentpage' => $cmsRoute('api.update-commentpage'),
        'roleList' => $cmsRoute('api.vai-tro.list'),
        'roleByRole' => ($byRole = $cmsRoute('api.vai-tro.by-role', 0)) ? preg_replace('#/0$#', '', $byRole) : null,
        'roleSave' => $cmsRoute('api.vai-tro.save'),
    ];
@endphp
<script>window.cmsUrls = {!! json_encode($cmsUrls, JSON_UNESCAPED_SLASHES) !!};</script>
@include('cmsbackend::screens.components.shell.reveal')
<div class="cms-editorial-admin" id="cms-editorial-admin">
    @include('cmsbackend::screens.components.shell.header')
    @include('cmsbackend::screens.components.shell.sidebar')
    <button class="cms-menu-backdrop" id="cms-menu-backdrop" aria-label="{{ __('admin.ui.dong') }}" type="button"></button>
    @php
        $cmsRecordPage = \Modules\CmsBackend\Support\AdminTheme::recordPage(request());
        $cmsWorkspacePage = \Modules\CmsBackend\Support\AdminTheme::workspacePage(request());
    @endphp
    <main class="cms-admin-main {{ \Modules\CmsBackend\Support\AdminTheme::listPage(request()) ? 'cms-list-page' : '' }} {{ $cmsRecordPage ? 'cms-record-page' : '' }} {{ $cmsWorkspacePage ? 'cms-workspace-page cms-workspace-'.$cmsWorkspacePage : '' }} {{ request()->routeIs('binh-luan.*') ? 'cms-comments-page' : '' }}">
        @if(\Modules\CmsBackend\Support\AdminTheme::listPage(request()))@include('cmsbackend::screens.components.editorial.list-header')@endif
        @yield('content')
    </main>
</div>
@include('cmsbackend::screens.components.editorial.list-layout')
@include('cmsbackend::screens.components.editorial.record-styles')
@include('cmsbackend::screens.components.editorial.workspace-styles')
@if($cmsRecordPage)@include('cmsbackend::screens.components.editorial.record-layout', ['record' => $cmsRecordPage])@endif
@if($cmsWorkspacePage)@include('cmsbackend::screens.components.editorial.workspace-layout', ['workspace' => $cmsWorkspacePage])@endif
<script>
/*
 * The shared file picker builds its URL internally, so CMS marks only file-manager
 * windows opened from this module. The query flag lets the service provider select
 * the CMS view without changing the shared component used by the other projects.
 */
(() => {
    const nativeOpen = window.open.bind(window);
    const fileManagerUrl = new URL(@json(url('/laravel-filemanager')), window.location.origin);

    window.open = (url, target, features) => {
        if (typeof url !== 'string') return nativeOpen(url, target, features);

        try {
            const popupUrl = new URL(url, window.location.href);
            const isFileManager = popupUrl.origin === fileManagerUrl.origin
                && popupUrl.pathname.replace(/\/$/, '') === fileManagerUrl.pathname.replace(/\/$/, '');

            if (!isFileManager) return nativeOpen(url, target, features);

            popupUrl.searchParams.set('cms', '1');

            const width = Math.min(1280, Math.max(900, (window.screen.availWidth || 1280) - 100));
            const height = Math.min(820, Math.max(620, (window.screen.availHeight || 820) - 100));
            const left = Math.max(0, Math.round(((window.screen.availWidth || width) - width) / 2));
            const top = Math.max(0, Math.round(((window.screen.availHeight || height) - height) / 2));
            const popupFeatures = `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`;

            return nativeOpen(popupUrl.toString(), target || 'FileManager', popupFeatures);
        } catch {
            return nativeOpen(url, target, features);
        }
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('cms-editorial-admin');
    const toggle = document.getElementById('cms-menu-button');
    const sidebar = document.getElementById('cms-admin-menu'), sidebarToggle = document.getElementById('cms-sidebar-toggle');
    const desktop = window.matchMedia('(min-width: 1100px)'), preferenceKey = 'cms.admin.sidebar.collapsed';
    let collapsed = false;
    try { collapsed = localStorage.getItem(preferenceKey) === 'true'; } catch {}
    const syncControls = () => {
        const expanded = desktop.matches ? !collapsed || root.classList.contains('cms-menu-peek') : root.classList.contains('cms-menu-open');
        [toggle, sidebarToggle].forEach(button => button.setAttribute('aria-expanded', String(expanded)));
    };
    const closeMenu = () => { root.classList.remove('cms-menu-open'); syncControls(); };
    const toggleMenu = () => {
        root.classList.remove('cms-menu-peek');
        if (desktop.matches) {
            collapsed = !collapsed; root.classList.toggle('cms-menu-collapsed', collapsed);
            try { localStorage.setItem(preferenceKey, String(collapsed)); } catch {}
        } else root.classList.toggle('cms-menu-open');
        syncControls();
    };
    const peek = () => { if (desktop.matches && collapsed) { root.classList.add('cms-menu-peek'); syncControls(); } };
    const stopPeek = () => { root.classList.remove('cms-menu-peek'); syncControls(); };
    root.classList.toggle('cms-menu-collapsed', desktop.matches && collapsed); syncControls();
    [toggle, sidebarToggle].forEach(button => button.addEventListener('click', toggleMenu));
    sidebar.addEventListener('mouseenter', peek);
    sidebar.addEventListener('mouseleave', () => { if (!sidebar.querySelector(':focus-visible')) stopPeek(); });
    sidebar.addEventListener('focusin', peek);
    sidebar.addEventListener('focusout', event => { if (!sidebar.contains(event.relatedTarget)) stopPeek(); });
    desktop.addEventListener('change', () => {
        root.classList.remove('cms-menu-open', 'cms-menu-peek');
        root.classList.toggle('cms-menu-collapsed', desktop.matches && collapsed); syncControls();
    });
    document.getElementById('cms-menu-backdrop').addEventListener('click', closeMenu);
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && root.classList.contains('cms-menu-open')) { closeMenu(); toggle.focus(); } });
    root.querySelectorAll('.cms-nav-toggle,.menu-sub .menu-toggle').forEach(link => link.addEventListener('click', event => { event.preventDefault(); const open=link.getAttribute('aria-expanded')!=='true';link.setAttribute('aria-expanded',String(open));link.parentElement.classList.toggle('open',open); }));
    document.getElementById('cms-menu-search').addEventListener('input', event => {
        const fold = text => text.toLocaleLowerCase('vi').replace(/đ/g,'d').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        const term = fold(event.target.value.trim());let count=0;
        root.querySelectorAll('.cms-nav-item').forEach(item => { item.hidden = !fold(item.textContent).includes(term); if(!item.hidden)count++; });
        root.querySelectorAll('.cms-nav-heading').forEach(item => { item.hidden=!!term; });
        document.getElementById('cms-menu-empty').hidden= count>0;
    });
});
</script>
@endsection
