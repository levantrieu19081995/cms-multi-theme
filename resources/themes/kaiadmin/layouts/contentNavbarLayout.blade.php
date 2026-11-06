@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/commonMaster' )

@php
/* Display elements */
$contentNavbar = ($contentNavbar ?? true);
$containerNav = ($containerNav ?? 'container-xxl');
$isNavbar = ($isNavbar ?? true);
$isMenu = ($isMenu ?? true);
$isFlex = ($isFlex ?? false);
$isFooter = ($isFooter ?? true);
$customizerHidden = ($customizerHidden ?? '');

/* HTML Classes */
$navbarDetached = 'navbar-detached';
$menuFixed = (isset($configData['menuFixed']) ? $configData['menuFixed'] : '');
if(isset($navbarType)) {
  $configData['navbarType'] = $navbarType;
}
$navbarType = (isset($configData['navbarType']) ? $configData['navbarType'] : '');
$footerFixed = (isset($configData['footerFixed']) ? $configData['footerFixed'] : '');
$menuCollapsed = (isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '');

/* Content classes */
$container = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';

@endphp

@section('layoutContent')
<div class="wrapper">
  <!-- Sidebar -->
  @if ($isMenu)
  @include('layouts/sections/menu/verticalMenu')
  @endif
  <!-- End Sidebar -->

  <div class="main-panel">
    <!-- BEGIN: Navbar-->
    @if ($isNavbar)
    @include('layouts/sections/navbar/navbar')
    @endif
    <!-- END: Navbar-->

    <div class="container">
      <div class="page-inner">
        @include('layouts/sections/alert')
        <!-- Layout Content -->
        @yield('content')
        <!--/ Layout Content -->
      </div>
    </div>

    <!-- Footer -->
    @if ($isFooter)
    @include('layouts/sections/footer/footer')
    @endif
    <!-- / Footer -->
  </div>

  <!-- Custom template | don't include it in your project! -->
  @if ($isMenu)
  @include('layouts/sections/menu/overlay')
  @endif
  <!-- End Custom template -->
</div>
@endsection
