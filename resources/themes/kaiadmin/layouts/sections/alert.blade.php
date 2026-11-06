@if (session()->has('alerts'))
  @foreach (session('alerts') as $alerts)
    @foreach ($alerts as $alert)
    <div class="alert alert-danger" role="alert">
        {!! $alert !!}
    </div>
    @endforeach
  @endforeach
  @php
    session()->forget('alerts');
  @endphp
@endif
