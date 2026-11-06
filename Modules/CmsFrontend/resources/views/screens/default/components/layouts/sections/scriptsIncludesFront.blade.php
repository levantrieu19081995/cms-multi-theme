@php
use Illuminate\Support\Facades\Vite;
@endphp


@vite(Theme::asset(['assets/vendor/js/helpers.js']))

@if ($configData['hasCustomizer'])

@vite(Theme::asset(['assets/vendor/js/template-customizer.js']))
@endif


@vite([
'Modules/CmsFrontend/resources/assets/js/front-config.js',
])

@if ($configData['hasCustomizer'])
<script type="module">
  window.templateCustomizer = new TemplateCustomizer({
    cssPath: '',
    themesPath: '',
    defaultStyle: "{{$configData['styleOpt']}}",
    displayCustomizer: "{{$configData['displayCustomizer']}}",
    pathResolver: function(path) {
      var resolvedPaths = {

        @foreach(['core'] as $name)
        '{{ $name }}.scss': '{{ Vite::asset('
        resources / assets / vendor / scss '.$configData["rtlSupport"].' / '.$name.'.scss ') }}',
        '{{ $name }}-dark.scss': '{{ Vite::asset('
        resources / assets / vendor / scss '.$configData["rtlSupport"].' / '.$name.' - dark.scss ') }}',
        @endforeach


        @foreach(['default', 'bordered', 'semi-dark'] as $name)
        'theme-{{ $name }}.scss': '{{ Vite::asset('
        resources / assets / vendor / scss '.$configData["rtlSupport"].' / theme - '.$name.'.scss ') }}',
        'theme-{{ $name }}-dark.scss': '{{ Vite::asset('
        resources / assets / vendor / scss '.$configData["rtlSupport"].' / theme - '.$name.' - dark.scss ') }}',
        @endforeach
      }
      return resolvedPaths[path] || path;
    },
    'controls': <?php echo json_encode(['rtl', 'style']); ?>,

  });
</script>
@endif