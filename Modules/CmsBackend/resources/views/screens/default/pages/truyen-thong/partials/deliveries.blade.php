{{-- One line per channel of a publication: "Facebook — Chưa kết nối". $publication->channels must be loaded. --}}
<ul class="list-unstyled mb-0 small">
    @forelse ($publication->channels as $delivery)
        <li class="d-flex align-items-center gap-2 mb-1">
            <i class="{{ $delivery->channel->icon() }}" aria-hidden="true"></i>
            <span class="fw-medium">{{ $delivery->channel->label() }}</span>
            <span class="text-muted" aria-hidden="true">—</span>
            <span class="badge {{ $delivery->status->badge() }}">{{ $delivery->status->label() }}</span>
        </li>
    @empty
        <li class="text-muted">{{ __('admin.social.no_channel_selected') }}</li>
    @endforelse
</ul>
