@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
@endphp

<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <x-custom.application-logo />
      <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
          <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
          <i class="gg-menu-left"></i>
        </button>
      </div>
      <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
      </button>
    </div>
    <!-- End Logo Header -->
  </div>
  <div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
      <ul class="nav nav-secondary">
        @foreach($menuData[0]->menu as $menu)
        {{-- adding active and open class if child is active --}}

        {{-- menu headers --}}
        @if (isset($menu->menuHeader))
        <li class="nav-section">
          <span class="sidebar-mini-icon">
            <i class="fa fa-ellipsis-h"></i>
          </span>
          <h4 class="text-section">{{ __($menu->menuHeader) }}</h4>
        </li>
        @else

        {{-- active menu method --}}
        @php
        $activeClass = null;
        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName === $menu->slug) {
          $activeClass = 'active';
        }
        elseif (isset($menu->submenu)) {
          if (gettype($menu->slug) === 'array') {
            foreach($menu->slug as $slug){
              if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = 'active submenu';
              }
            }
          }
          else{
            if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
              $activeClass = 'active submenu';
            }
          }
        }
        @endphp

        {{-- main menu --}}
        <li class="nav-item {{$activeClass}}">
          <a href="{{ isset($menu->submenu) ? ('#' . Illuminate\Support\Str::slug($menu->name)) : (isset($menu->url) ? url($menu->url) : 'javascript:void(0);') }}"
              @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif
              @isset($menu->submenu) data-bs-toggle="collapse" @endisset>
            @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
            @endisset
            <p>{{ isset($menu->name) ? __($menu->name) : '' }}</p>
            {{-- <span class="badge badge-secondary">1</span> --}}
            @isset($menu->badge)
              <span class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</span>
            @endisset
            @isset($menu->submenu)
            <span class="caret"></span>
            @endisset
          </a>
          {{-- submenu --}}
          @isset($menu->submenu)
          @include('layouts.sections.menu.submenu',['menu' => $menu->submenu, 'id' => Illuminate\Support\Str::slug($menu->name), 'show' => $activeClass != null])
          @endisset
        </li>
        @endif
        @endforeach
      </ul>
    </div>
  </div>
</div>
