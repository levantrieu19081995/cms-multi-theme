
<!-- BEGIN: Vendor JS-->
@vite(Theme::asset([
  'assets/vendor/js/dropdown-hover.js',
  'assets/vendor/js/mega-dropdown.js',
  'assets/vendor/libs/popper/popper.js',
  'assets/vendor/js/bootstrap.js',
  'assets/vendor/libs/node-waves/node-waves.js',
  'assets/vendor/libs/toastr/toastr.js',
]))
<script type="module">
  toastr.options = {
    positionClass: 'toast-bottom-right'
  }
</script>
@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(Theme::asset(['assets/js/front-main.js']))
<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
@include('cmsbackend::shared.layouts/sections/toastrs')