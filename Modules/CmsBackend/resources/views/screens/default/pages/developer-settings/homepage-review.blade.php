@extends('cmsbackend::screens.pages.developer-settings.layout')

@section('title', __('homepage.ui.review_title'))

@section('content')
    @php
        $route = fn (string $name, array $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings'.($name === '' ? '' : '.'.$name), $params);
        $blocked = ! empty($review['issues']) || ! empty($review['blocking']) || ! $review['renderable'];
    @endphp
    <style>
        .hl-review-bar { position: sticky; top: 0; z-index: 1030; padding: .75rem 1rem; background: #fff3cd; border: 1px solid #ffe69c; border-radius: .5rem; }
        .hl-review-stage { margin-top: 1rem; background: #e9ecef; border-radius: .5rem; padding: 1rem; overflow: auto; text-align: center; }
        .hl-review-stage iframe { width: 100%; max-width: 100%; height: 78vh; border: 0; background: #fff; box-shadow: 0 .25rem 1rem rgba(0, 0, 0, .18); transition: width .2s ease; }
    </style>

    <div class="hl-review-bar">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="badge bg-warning text-dark fs-6"><i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.preview_mode') }}</span>
            <span><strong>{{ $review['theme_label'] }}</strong> <code>{{ $review['theme'] }}</code></span>
            <span class="small text-muted">{{ __('homepage.ui.snapshot_at') }}: {{ $review['created_at']->format('d/m/Y H:i:s') }}</span>
            <span class="badge bg-label-secondary">{{ $review['differs'] ? __('homepage.ui.not_applied') : __('homepage.ui.same_as_live') }}</span>
            <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="{{ __('homepage.ui.device') }}">
                @foreach ($review['devices'] as $device => $width)
                    <button type="button" class="btn btn-outline-secondary {{ $loop->first ? 'active' : '' }}" data-hl-width="{{ $width }}"><i class="{{ ['desktop' => 'ri-computer-line', 'tablet' => 'ri-tablet-line', 'mobile' => 'ri-smartphone-line'][$device] ?? 'ri-window-line' }} me-1"></i>{{ __('homepage.ui.'.$device) }}</button>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="hl-replay"><i class="ri-restart-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.replay') }}</button>
            <a class="btn btn-sm btn-outline-secondary" href="{{ $route('', ['tab' => 'layout', 'layout_theme' => $review['theme']]) }}"><i class="ri-arrow-left-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.back_to_config') }}</a>
            <form method="post" action="{{ $route('homepage.apply', ['token' => $review['token']]) }}" class="m-0" id="hl-apply-form" data-hl-confirm="{{ __('homepage.ui.confirm_apply') }}">@csrf
                <button type="submit" class="btn btn-sm btn-success" @disabled($blocked)><i class="ri-check-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.apply') }}</button>
            </form>
        </div>
        @if ($blocked)
            <div class="alert alert-danger mt-2 mb-0 py-2" role="alert">
                <strong>{{ __('homepage.ui.cannot_apply') }}</strong>
                <ul class="mb-0">
                    @unless ($review['renderable'])<li>{{ __('homepage.errors.theme_not_in_build') }}</li>@endunless
                    @foreach (array_merge($review['issues'], $review['blocking']) as $issue)
                        <li><code>{{ $issue['instance'] }}</code>: {{ __('homepage.issues.'.$issue['code']) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="hl-review-stage">
        @if ($review['renderable'])
            <iframe id="hl-frame" title="{{ __('homepage.ui.preview_mode') }}" src="{{ $route('homepage.frame', ['token' => $review['token']]) }}" referrerpolicy="no-referrer"></iframe>
        @else
            <div class="alert alert-warning m-0">{{ __('homepage.errors.theme_not_in_build') }}</div>
        @endif
    </div>

    <script>
        (function () {
            var frame = document.getElementById('hl-frame');
            document.querySelectorAll('[data-hl-width]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('[data-hl-width]').forEach(function (other) { other.classList.remove('active'); });
                    button.classList.add('active');
                    if (frame) { frame.style.width = button.getAttribute('data-hl-width'); }
                });
            });
            // Replay: tải lại trang chủ trong khung để các hiệu ứng khởi chạy lại từ đầu.
            document.getElementById('hl-replay').addEventListener('click', function () {
                if (frame) { frame.contentWindow.location.reload(); }
            });
            var applyForm = document.getElementById('hl-apply-form');
            applyForm.addEventListener('submit', function (event) {
                if (!window.confirm(applyForm.getAttribute('data-hl-confirm'))) { event.preventDefault(); }
            });
        })();
    </script>
@endsection
