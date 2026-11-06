{{-- Step indicator of the create flow. $current = 1..4 --}}
@php
    $steps = [1 => 'admin.social.step_post', 2 => 'admin.social.step_content', 3 => 'admin.social.step_channels', 4 => 'admin.social.step_preview'];
@endphp
<ol class="cms-social-steps list-unstyled d-flex flex-wrap gap-3 mb-4" aria-label="{{ __('admin.social.steps_label') }}">
    @foreach ($steps as $number => $label)
        <li class="d-flex align-items-center gap-2 {{ $number === $current ? 'fw-bold text-primary' : ($number < $current ? 'text-success' : 'text-muted') }}" @if ($number === $current) aria-current="step" @endif>
            <span class="badge rounded-pill {{ $number === $current ? 'bg-primary' : ($number < $current ? 'bg-success' : 'bg-label-secondary') }}">{{ $number }}</span>
            <span>{{ __($label) }}</span>
        </li>
    @endforeach
</ol>
