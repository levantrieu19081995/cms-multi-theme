@props(['messages'])

@if ($messages)
  @foreach ((array) $messages as $message)
    <div class="invalid-feedback" style="display: block">{{ $message }}</div>
  @endforeach
@endif
