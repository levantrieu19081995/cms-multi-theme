{{-- Tab: Bố cục trang chủ (Homepage Layout Manager). Working layout = form này (chưa lưu); Preview snapshot = bản tạm phía server; Live = bố cục đã áp dụng. --}}
@php
    $hl = $homepageLayout;
    $variantLabel = fn (string $key) => \Illuminate\Support\Facades\Lang::has('homepage.variants.'.$key) ? __('homepage.variants.'.$key) : $key;
    $effectLabel = fn (string $key) => \Illuminate\Support\Facades\Lang::has('homepage.effects.'.$key) ? __('homepage.effects.'.$key) : $key;
    $route = fn (string $name, array $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings'.($name === '' ? '' : '.'.$name), $params);
@endphp
<div class="tab-pane fade" id="dev-pane-layout" role="tabpanel" aria-labelledby="dev-tab-layout" tabindex="0">
    <style>
        .hl-zone { min-height: 3.25rem; margin: 0; padding: .5rem; border: 1px dashed rgba(67, 89, 113, .3); border-radius: .5rem; }
        .hl-zone.hl-over { border-color: #696cff; background: rgba(105, 108, 255, .06); }
        .hl-block { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; padding: .5rem .75rem; margin-bottom: .5rem; background: #fff; border: 1px solid rgba(67, 89, 113, .15); border-radius: .5rem; }
        .hl-block.hl-off { opacity: .6; }
        .hl-block.hl-drag { opacity: .4; }
        .hl-handle { cursor: grab; font-size: 1.25rem; color: #8592a3; }
        .hl-main { flex: 1 1 14rem; min-width: 12rem; }
        .hl-controls { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .hl-controls .form-select { width: auto; min-width: 8rem; }
    </style>

    @if (! $hl['ready'])
        <div class="alert alert-warning" role="alert">{{ __('homepage.ui.not_ready') }}</div>
    @endif
    @if (! empty($hl['registry_errors']))
        <div class="alert alert-danger" role="alert"><strong>{{ __('homepage.ui.registry_errors') }}</strong>
            <ul class="mb-0">@foreach ($hl['registry_errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="alert alert-info py-2" role="status">{{ __('homepage.ui.note') }}</div>
    @if (! empty($hl['layout']) && ! $hl['effect_ready'] && $hl['ready'])
        <div class="alert alert-warning" role="alert">{{ __('homepage.ui.effect_not_ready') }}</div>
    @endif

    @if (empty($hl['layout']))
        <div class="alert alert-secondary">{{ __('homepage.ui.no_theme') }}</div>
    @else
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="text-muted small">{{ __('homepage.ui.theme') }}:</span>
            @foreach ($hl['themes'] as $themeKey => $themeLabel)
                <a class="btn btn-sm {{ $themeKey === $hl['theme'] ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ $route('', ['tab' => 'layout', 'layout_theme' => $themeKey]) }}">{{ $themeLabel }}</a>
            @endforeach
        </div>

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="small text-muted">{{ __('homepage.ui.layout_state') }}</div>
                    <span class="badge {{ $hl['installed'] ? 'bg-label-info' : 'bg-label-warning' }}">{{ $hl['installed'] ? __('homepage.ui.state_live') : __('homepage.ui.state_default') }}</span>
                </div>
                <div class="ms-auto d-flex flex-wrap gap-2">
                    @if ($hl['ready'])
                        @unless ($hl['installed'])
                            <form method="post" action="{{ $route('homepage.install', ['theme' => $hl['theme']]) }}">@csrf<button class="btn btn-outline-primary btn-sm" type="submit">{{ __('homepage.ui.install') }}</button></form>
                        @else
                            <form method="post" action="{{ $route('homepage.reset', ['theme' => $hl['theme']]) }}" data-hl-confirm="{{ __('homepage.ui.confirm_reset') }}">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">{{ __('homepage.ui.reset') }}</button></form>
                        @endunless
                    @endif
                </div>
            </div>
        </div>

        <form method="post" id="hl-form" action="{{ $route('homepage.preview', ['theme' => $hl['theme']]) }}">
            @csrf
            <input type="hidden" name="layout" id="hl-payload" value="">
            @foreach ($hl['zones'] as $zone)
                <div class="card mb-3">
                    <div class="card-header py-2"><strong>{{ __($zone['label']) }}</strong></div>
                    <div class="card-body">
                        <ul class="list-unstyled hl-zone" data-hl-zone="{{ $zone['key'] }}">
                            @foreach ($zone['blocks'] as $block)
                                <li class="hl-block {{ $block['enabled'] ? '' : 'hl-off' }}" draggable="true" data-hl-instance="{{ $block['instance'] }}" data-hl-allowed='@json($block['allowed_zones'])'>
                                    <i class="ri-draggable hl-handle" aria-hidden="true" title="{{ __('homepage.ui.drag') }}"></i>
                                    <i class="{{ $block['icon'] }} fs-4 text-primary" aria-hidden="true"></i>
                                    <div class="hl-main">
                                        <strong>{{ $block['name'] ? __($block['name']) : $block['instance'] }}</strong>
                                        @unless ($block['definition_ok'])<span class="badge bg-label-danger ms-1">{{ __('homepage.ui.missing_definition') }}</span>@endunless
                                        @unless ($block['can_enable'])<span class="badge bg-label-danger ms-1">{{ __('homepage.ui.missing_renderer') }}</span>@endunless
                                        @if ($block['description'])<div class="small text-muted">{{ __($block['description']) }}</div>@endif
                                    </div>
                                    <div class="hl-controls">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" data-hl-enabled id="hl-en-{{ $block['instance'] }}" @checked($block['enabled']) @disabled(! $block['can_enable'])>
                                            <label class="form-check-label small" for="hl-en-{{ $block['instance'] }}">{{ __('homepage.ui.enabled') }}</label>
                                        </div>
                                        @if (count($block['variants']) > 1)
                                            <select class="form-select form-select-sm" data-hl-variant aria-label="{{ __('homepage.ui.variant') }}">
                                                @foreach ($block['variants'] as $variantKey => $variant)
                                                    <option value="{{ $variantKey }}" @selected($variantKey === $block['variant']) @disabled(! $variant['ok'])>{{ $variantLabel($variantKey) }}{{ $variant['ok'] ? '' : ' ✗' }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" data-hl-variant value="{{ $block['variant'] }}">
                                            <span class="badge bg-label-secondary" title="{{ __('homepage.ui.variant') }}">{{ $variantLabel($block['variant']) }}</span>
                                        @endif
                                        <select class="form-select form-select-sm" data-hl-effect aria-label="{{ __('homepage.ui.effect') }}" title="{{ __('homepage.ui.effect') }}" @disabled(! $hl['effect_ready'])>
                                            <option value="" @selected(empty($block['effect']))>{{ __('homepage.ui.effect_default') }}{{ $block['native_effects'] ? ' ('.collect($block['native_effects'])->map(fn ($key) => $effectLabel($key))->implode(', ').')' : '' }}</option>
                                            @foreach ($hl['effect_choices'] as $choice)
                                                <option value="{{ $choice }}" @selected($block['effect'] === $choice)>{{ __('homepage.ui.effect_'.$choice) }}</option>
                                            @endforeach
                                        </select>
                                        @if (count($block['allowed_zones']) > 1)
                                            <select class="form-select form-select-sm" data-hl-zone-select aria-label="{{ __('homepage.ui.zone') }}">
                                                @foreach ($block['allowed_zones'] as $allowed)<option value="{{ $allowed }}" @selected($allowed === $zone['key'])>{{ __($hl['zones'][$allowed]['label'] ?? $allowed) }}</option>@endforeach
                                            </select>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-hl-move="-1" aria-label="{{ __('homepage.ui.move_up') }}"><i class="ri-arrow-up-s-line"></i></button>
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-hl-move="1" aria-label="{{ __('homepage.ui.move_down') }}"><i class="ri-arrow-down-s-line"></i></button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
            <p class="text-muted small mb-0">{{ __('homepage.ui.preview_hint') }}</p>
            {{-- Nút nổi cố định ở góc dưới phải (ngoài vùng cuộn), như tab Quản lý phân quyền. --}}
            <div class="developer-floating-actions" role="region" aria-label="{{ __('homepage.ui.tab') }}">
                <button class="btn btn-outline-primary" type="submit" @disabled(! $hl['ready'])><i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.preview') }}</button>
                <button class="btn btn-success" type="submit" formaction="{{ $route('homepage.save', ['theme' => $hl['theme']]) }}" onclick="return window.confirm(@js(__('homepage.ui.apply_now_confirm')))" @disabled(! $hl['ready'])><i class="ri-check-double-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.apply_now') }}</button>
            </div>
        </form>

        <script>
            (function () {
                var form = document.getElementById('hl-form');
                if (!form) { return; }
                var zones = Array.prototype.slice.call(form.querySelectorAll('[data-hl-zone]'));
                var dragged = null;
                var allowedOf = function (item) { try { return JSON.parse(item.getAttribute('data-hl-allowed') || '[]'); } catch (e) { return []; } };
                var sync = function (item) {
                    var select = item.querySelector('[data-hl-zone-select]');
                    if (select) { select.value = item.parentNode.getAttribute('data-hl-zone'); }
                };
                zones.forEach(function (zone) {
                    zone.addEventListener('dragover', function (event) {
                        if (!dragged || allowedOf(dragged).indexOf(zone.getAttribute('data-hl-zone')) === -1) { return; }
                        event.preventDefault();
                        zone.classList.add('hl-over');
                        var after = Array.prototype.slice.call(zone.querySelectorAll('.hl-block:not(.hl-drag)')).find(function (el) {
                            var box = el.getBoundingClientRect();
                            return event.clientY < box.top + box.height / 2;
                        });
                        zone.insertBefore(dragged, after || null);
                    });
                    zone.addEventListener('dragleave', function () { zone.classList.remove('hl-over'); });
                    zone.addEventListener('drop', function (event) { event.preventDefault(); zone.classList.remove('hl-over'); if (dragged) { sync(dragged); } });
                });
                form.querySelectorAll('.hl-block').forEach(function (item) {
                    item.addEventListener('dragstart', function (event) {
                        dragged = item; item.classList.add('hl-drag');
                        if (event.dataTransfer) { event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', item.getAttribute('data-hl-instance')); }
                    });
                    item.addEventListener('dragend', function () {
                        item.classList.remove('hl-drag'); dragged = null;
                        zones.forEach(function (zone) { zone.classList.remove('hl-over'); });
                    });
                    var toggle = item.querySelector('[data-hl-enabled]');
                    toggle.addEventListener('change', function () { item.classList.toggle('hl-off', !toggle.checked); });
                    var zoneSelect = item.querySelector('[data-hl-zone-select]');
                    if (zoneSelect) {
                        zoneSelect.addEventListener('change', function () {
                            var target = zones.find(function (zone) { return zone.getAttribute('data-hl-zone') === zoneSelect.value; });
                            if (target) { target.appendChild(item); }
                        });
                    }
                    item.querySelectorAll('[data-hl-move]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            var step = parseInt(button.getAttribute('data-hl-move'), 10);
                            var sibling = step < 0 ? item.previousElementSibling : item.nextElementSibling;
                            if (!sibling) { return; }
                            if (step < 0) { item.parentNode.insertBefore(item, sibling); } else { item.parentNode.insertBefore(sibling, item); }
                        });
                    });
                });
                form.addEventListener('submit', function () {
                    var rows = [];
                    zones.forEach(function (zone) {
                        zone.querySelectorAll('.hl-block').forEach(function (item) {
                            var variant = item.querySelector('[data-hl-variant]');
                            rows.push({
                                instance: item.getAttribute('data-hl-instance'),
                                zone: zone.getAttribute('data-hl-zone'),
                                variant: variant ? variant.value : '',
                                effect: (item.querySelector('[data-hl-effect]') || {}).value || '',
                                enabled: item.querySelector('[data-hl-enabled]').checked
                            });
                        });
                    });
                    document.getElementById('hl-payload').value = JSON.stringify(rows);
                });
                document.querySelectorAll('form[data-hl-confirm]').forEach(function (confirmForm) {
                    confirmForm.addEventListener('submit', function (event) {
                        if (!window.confirm(confirmForm.getAttribute('data-hl-confirm'))) { event.preventDefault(); }
                    });
                });
            })();
        </script>
    @endif
</div>
