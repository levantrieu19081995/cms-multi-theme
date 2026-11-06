<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet">

@vite(Theme::asset([
  'assets/vendor/fonts/remixicon/remixicon.scss',
  'assets/vendor/fonts/flag-icons.scss',
  'assets/vendor/libs/node-waves/node-waves.scss',
  'assets/vendor/libs/toastr/toastr.scss',
  // 'assets/vendor/libs/animate-css/animate.scss',
  // 'assets/vendor/libs/animate-css/animate.scss',
  'assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'assets/vendor/libs/notiflix/notiflix-3.2.7.min.css',
]))
<!-- Core CSS -->
@vite(Theme::asset([
  'assets/vendor/scss'.$configData['rtlSupport'].'/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
  'assets/vendor/scss'.$configData['rtlSupport'].'/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
  'assets/css/demo.css'
]))


<!-- Vendor Styles -->
@vite(Theme::asset([
  'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'assets/vendor/libs/typeahead-js/typeahead.scss'
]))
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')
