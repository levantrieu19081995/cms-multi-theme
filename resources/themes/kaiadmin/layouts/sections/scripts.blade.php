<!-- BEGIN: plugin JS-->

<!--   Core JS Files   -->
<script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>

<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<!-- jQuery Scrollbar -->
<script src="{{ asset('assets/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
<!-- Moment JS -->
<script src="{{ asset('assets/plugin/moment/moment.min.js') }}"></script>
<!-- Chart JS -->
<script src="{{ asset('assets/plugin/chart.js/chart.min.js') }}"></script>
<!-- jQuery Sparkline -->
<script src="{{ asset('assets/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>
<!-- Chart Circle -->
<script src="{{ asset('assets/plugin/chart-circle/circles.min.js') }}"></script>
<!-- Datatables -->
<script src="{{ asset('assets/plugin/datatables/datatables.min.js') }}"></script>
<!-- Bootstrap Notify -->
<script src="{{ asset('assets/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
<!-- jQuery Vector Maps -->
<script src="{{ asset('assets/plugin/jsvectormap/jsvectormap.min.js') }}"></script>
<script src="{{ asset('assets/plugin/jsvectormap/world.js') }}"></script>
<!-- Sweet Alert -->
<script src="{{ asset('assets/plugin/sweetalert/sweetalert.min.js') }}"></script>
<!-- Kaiadmin JS -->
<script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>
<!-- Kaiadmin DEMO methods, don't include it in your project! -->
<script src="{{ asset('assets/js/setting-demo2.js') }}"></script>

@yield('plugin-script')
<!-- END: Page plugin JS-->
<!-- BEGIN: Theme JS-->
{{-- @vite(Theme::asset(['assets/js/main.js'])) --}}

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
