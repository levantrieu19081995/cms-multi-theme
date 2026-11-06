<!-- Existing feature pages retain their vendor dependencies. -->
@vite(Theme::asset(['assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js']))
@yield('vendor-script')
@stack('pricing-script')
@yield('page-script')
@vite('Modules/CmsFrontend/resources/assets/js/screens/default/shell.js')
@include('cmsbackend::shared.layouts/sections/toastrs')

