@php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
@endphp
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal  menu bg-menu-theme flex-grow-0">
  <div class="{{$containerNav}} d-flex h-100">
    <ul class="menu-inner">
      @foreach ($cmsMenuData[1]->menu as $menu)

      {{-- active menu method --}}
      @php
        $activeClass = null;
        $currentRouteName =  Route::currentRouteName();

        if ($currentRouteName === $menu->slug) {
            $activeClass = 'active';
        }
        elseif (isset($menu->submenu)) {
          if (gettype($menu->slug) === 'array') {
            foreach($menu->slug as $slug){
              if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = 'active';
              }
            }
          }
          else{
            if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
              $activeClass = 'active';
            }
          }

        }

        $menuHref = isset($menu->slug) && is_string($menu->slug) && Route::has($menu->slug)
          ? \Modules\CmsBackend\Support\ModuleUrl::route($menu->slug)
          : (isset($menu->url)
            ? \Modules\CmsBackend\Support\ModuleUrl::url($menu->url)
            : 'javascript:void(0);');
      @endphp

      {{-- main menu --}}
      <li class="menu-item {{$activeClass}}">
        <a href="{{ $menuHref }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
          @isset($menu->icon)
            <i class="{{ $menu->icon }}"></i>
          @endisset
          <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
        </a>

        {{-- submenu --}}
        @isset($menu->submenu)
          @include('cmsbackend::screens.components.shared.layouts.sections.menu.submenu',['menu' => $menu->submenu])
        @endisset
      </li>
      @endforeach
    </ul>
  </div>
</aside>
<!--/ Horizontal Menu -->
