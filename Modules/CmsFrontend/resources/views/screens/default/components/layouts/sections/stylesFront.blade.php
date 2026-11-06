@vite(Theme::asset(['assets/vendor/scss' . $configData['rtlSupport'] . '/core' . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'assets/vendor/scss' . $configData['rtlSupport'] . '/' . $configData['theme'] . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'assets/css/demo.css', 'assets/vendor/fonts/flag-icons.scss', 'assets/vendor/libs/node-waves/node-waves.scss', 'assets/vendor/libs/toastr/toastr.scss', 'assets/vendor/libs/node-waves/node-waves.scss', 'assets/vendor/libs/sweetalert2/sweetalert2.scss', 'assets/vendor/scss/pages/front-page.scss']))


@vite(Theme::asset(['assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'assets/vendor/libs/typeahead-js/typeahead.scss']))
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')
@vite(['Modules/CmsFrontend/resources/assets/css/header.css', 'Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/css/menu.css', 'Modules/CmsFrontend/resources/assets/css/footer.css', 'Modules/CmsFrontend/resources/assets/css/scroll-to-top.css', 'Modules/CmsFrontend/resources/assets/css/listing.css'])
@vite('Modules/CmsFrontend/resources/assets/css/screens/default/shell.css')
