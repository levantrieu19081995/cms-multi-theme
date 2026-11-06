@if (session()->has('toastrs'))
  <script type="module">
    $(document).ready(() => {
      @foreach (session('toastrs') as $type => $toastrs)
        @foreach ($toastrs as $toastr)
        toastr.{{ $type }}('{{ $toastr }}')
        @endforeach
      @endforeach
    })
  </script>
  @php
    session()->forget('toastrs');
  @endphp
@endif
