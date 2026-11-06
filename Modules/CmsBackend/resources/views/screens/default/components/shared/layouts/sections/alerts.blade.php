@if (session()->has('alerts'))
  @foreach (session('alerts') as $type => $alerts)
    @foreach ($alerts as $alert)
    <x-theme::alert :type="$type" :message="$alert" />
    @endforeach
  @endforeach
  @php
    session()->forget('alerts');
  @endphp
@endif
