@php
use Illuminate\Support\Facades\Route;
@endphp

@if (isset($menu))
<div class="collapse @if (isset($show) && $show) show @endif" id="{{ $id }}">
  <ul class="nav nav-collapse @isset($subnav) subnav @endisset">
    @foreach ($menu as $submenu)
    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName =  Route::currentRouteName();

      if ($currentRouteName === $submenu->slug) {
          $activeClass = 'active';
      }
      elseif (isset($submenu->submenu)) {
        if (gettype($submenu->slug) === 'array') {
          foreach($submenu->slug as $slug){
            if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = $active;
            }
          }
        }
        else{
          if (str_contains($currentRouteName,$submenu->slug) and strpos($currentRouteName,$submenu->slug) === 0) {
            $activeClass = $active;
          }
        }
      }
    @endphp

    <li class="{{$activeClass}}">
      <a href="{{ isset($submenu->submenu) ? ('#' . Illuminate\Support\Str::slug($submenu->name)) : (isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)') }}"
          @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif
          @isset($submenu->submenu) data-bs-toggle="collapse" @endisset>
        <span class="sub-item">{{ isset($submenu->name) ? __($submenu->name) : '' }}</span>
        {{-- <span class="badge badge-secondary">1</span> --}}
        @isset($submenu->badge)
          <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
        @endisset
      </a>
      {{-- submenu --}}
      @if (isset($submenu->submenu))
        @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu, 'id' => Illuminate\Support\Str::slug($submenu->name), 'subnav' => true])
      @endif
    </li>
    @endforeach
  </ul>
</div>
@endif
