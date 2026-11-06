<!-- BEGIN: Vendor JS-->

@vite(Theme::asset([
  'assets/vendor/libs/jquery/jquery.js',
  'assets/vendor/libs/popper/popper.js',
  'assets/vendor/js/bootstrap.js',
  'assets/vendor/libs/node-waves/node-waves.js',
  'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'assets/vendor/libs/hammer/hammer.js',
  'assets/vendor/libs/typeahead-js/typeahead.js',
  'assets/vendor/libs/toastr/toastr.js',
  'assets/vendor/libs/sweetalert2/sweetalert2.js',
  'assets/vendor/libs/notiflix/notiflix-3.2.7.min.js',
  'assets/vendor/js/menu.js'
]))
<script type="module">
  toastr.options = {
    positionClass: 'toast-bottom-right'
  }
</script>

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(Theme::asset(['assets/js/main.js']))

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

@include('layouts/sections/toastrs')
