@extends('cmsbackend::screens.pages.developer-settings.layout')

@section('title', __('admin.developer.title'))

@section('content')
    <style>
        #dev-pane-permissions {
            padding-bottom: 5rem;
        }

        .developer-permission-actions {
            position: fixed;
            right: max(1rem, env(safe-area-inset-right));
            bottom: max(1rem, env(safe-area-inset-bottom));
            z-index: 1080;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .5rem;
            max-width: calc(100vw - 2rem);
            padding: .625rem;
            border: 1px solid rgba(67, 89, 113, .12);
            border-radius: .5rem;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 .25rem 1rem rgba(67, 89, 113, .18);
            backdrop-filter: blur(8px);
        }

        @media (max-width: 575.98px) {
            .developer-permission-actions {
                right: max(.75rem, env(safe-area-inset-right));
                bottom: max(.75rem, env(safe-area-inset-bottom));
                max-width: calc(100vw - 1.5rem);
            }
        }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.developer.title') }}</h4>
            <div class="text-muted small">
                {{ __('admin.developer.current_project') }}: <strong>{{ $theme['project'] }}</strong>
                <span class="ms-1">({{ $theme['project_fixed'] ? __('admin.developer.project_fixed') : __('admin.developer.project_follows_theme') }})</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-warning"><i class="ri-code-s-slash-line me-1" aria-hidden="true"></i>{{ __('admin.developer.badge') }}</span>
            <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.lock') }}">@csrf<button type="submit" class="btn btn-outline-warning btn-sm"><i class="ri-lock-line me-1" aria-hidden="true"></i>{{ __('admin.developer.lock') }}</button></form>
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="ri-arrow-left-line me-1" aria-hidden="true"></i>{{ __('admin.developer.back') }}</a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto text-nowrap" id="dev-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dev-tab-permissions" data-bs-toggle="tab" data-bs-target="#dev-pane-permissions" type="button" role="tab" aria-controls="dev-pane-permissions" aria-selected="true">
                <i class="ri-shield-keyhole-line me-1" aria-hidden="true"></i>{{ __('admin.developer.tab_permissions') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="dev-tab-themes" data-bs-toggle="tab" data-bs-target="#dev-pane-themes" type="button" role="tab" aria-controls="dev-pane-themes" aria-selected="false">
                <i class="ri-palette-line me-1" aria-hidden="true"></i>{{ __('admin.developer.tab_themes') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="dev-tab-features" data-bs-toggle="tab" data-bs-target="#dev-pane-features" type="button" role="tab" aria-controls="dev-pane-features" aria-selected="false">
                <i class="ri-apps-2-line me-1" aria-hidden="true"></i>{{ __('admin.developer.tab_features') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="dev-tab-layout" data-bs-toggle="tab" data-bs-target="#dev-pane-layout" type="button" role="tab" aria-controls="dev-pane-layout" aria-selected="false">
                <i class="ri-layout-masonry-line me-1" aria-hidden="true"></i>{{ __('homepage.ui.tab') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="dev-tab-accounts" data-bs-toggle="tab" data-bs-target="#dev-pane-accounts" type="button" role="tab" aria-controls="dev-pane-accounts" aria-selected="false">
                <i class="ri-team-line me-1" aria-hidden="true"></i>{{ __('developer_accounts.tab') }}
            </button>
        </li>
    </ul>

    <div class="tab-content p-0 bg-transparent shadow-none">
        {{-- Tab 1: Permission Management (Phase 2: permission availability of this deployment) --}}
        <div class="tab-pane fade show active" id="dev-pane-permissions" role="tabpanel" aria-labelledby="dev-tab-permissions" tabindex="0">
            @if ($manifest['state'] === 'invalid')
                <div class="alert alert-danger" role="alert">
                    <strong>{{ __('admin.developer.manifest_invalid') }}</strong>
                    {{ __('admin.developer.manifest_invalid_reason.'.$manifest['reason']) }}
                    {{ __('admin.developer.manifest_invalid_mode') }}
                </div>
            @elseif ($manifest['state'] === 'absent')
                <div class="alert alert-info" role="status">{{ __('admin.developer.manifest_absent') }}</div>
            @endif
            @unless ($manifest['has_key'])
                <div class="alert alert-warning" role="alert">{{ __('admin.developer.manifest_no_key') }}</div>
            @endunless

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('admin.developer.total_permissions') }}</div><div class="fs-3 fw-semibold" id="dev-total">{{ $permissions['total'] }}</div></div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('admin.developer.available') }}</div><div class="fs-3 fw-semibold text-success" id="dev-available">{{ $permissions['available'] }}</div></div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('admin.developer.disabled') }}</div><div class="fs-3 fw-semibold text-warning" id="dev-disabled">{{ $permissions['disabled'] }}</div></div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('admin.developer.groups') }}</div><div class="fs-3 fw-semibold">{{ count($permissions['groups']) }}</div></div></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted">{{ __('admin.developer.current_project') }}:</span> <strong>{{ $theme['project'] }}</strong> <code>{{ $manifest['project'] }}</code></div>
                        <div class="col-md-4"><span class="text-muted">{{ __('admin.developer.manifest_updated') }}:</span> <strong>{{ $manifest['updated_at'] ? \Illuminate\Support\Carbon::parse($manifest['updated_at'])->format('d/m/Y H:i') : '—' }}</strong></div>
                        <div class="col-md-4"><span class="text-muted">{{ __('admin.developer.manifest_state') }}:</span> <strong>{{ __('admin.developer.state_'.$manifest['state']) }}</strong></div>
                    </div>
                    <div class="text-muted mt-2">{{ __('admin.developer.rules_note') }}</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label small" for="dev-search">{{ __('admin.developer.search') }}</label>
                            <input id="dev-search" type="search" class="form-control" placeholder="{{ __('admin.developer.search_placeholder') }}" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small" for="dev-group">{{ __('admin.developer.filter_group') }}</label>
                            <select id="dev-group" class="form-select">
                                <option value="">{{ __('admin.developer.all_groups') }}</option>
                                @foreach ($permissions['groups'] as $i => $group)
                                    <option value="g{{ $i }}">{{ $group['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" id="dev-count" aria-live="polite"></div>
                </div>
            </div>

            <form method="post" id="dev-perm-form" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.permissions.save') }}">
                @csrf
                @foreach ($permissions['groups'] as $i => $group)
                    @php $parentOn = $group['permissions'][0]['listed']; @endphp
                    <div class="card mb-3 dev-group" data-group="g{{ $i }}">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between gap-2">
                            <h6 class="mb-0">{{ $group['name'] }}@if ($group['core']) <span class="badge bg-label-danger ms-1">{{ __('admin.developer.core') }}</span>@endif</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-label-secondary">{{ count($group['permissions']) }}</span>
                                @unless ($group['core'])
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-group-toggle="{{ $group['code'] }}">{{ __('admin.developer.toggle_group') }}</button>
                                @endunless
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5rem">{{ __('admin.developer.col_enabled') }}</th>
                                        <th>{{ __('admin.developer.col_code') }}</th>
                                        <th>{{ __('admin.developer.col_name') }}</th>
                                        <th>{{ __('admin.developer.col_description') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['permissions'] as $row)
                                        @php $locked = ! $row['is_parent'] && ! $parentOn; @endphp
                                        <tr class="dev-row" data-search="{{ \Illuminate\Support\Str::lower($row['code'].' '.$row['name']) }}">
                                            <td>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input dev-sw" type="checkbox" role="switch" name="allowed[]" value="{{ $row['code'] }}"
                                                        data-parent="{{ $row['parent'] }}" data-code="{{ $row['code'] }}" data-initial="{{ $row['listed'] && ! $locked ? 1 : 0 }}"
                                                        @checked($row['listed'] && ! $locked) @disabled($row['core'] || $locked)
                                                        aria-label="{{ $row['name'] }}">
                                                </div>
                                            </td>
                                            <td><code>{{ $row['code'] }}</code>
                                                @if ($row['is_parent']) <span class="badge bg-label-primary ms-1">{{ __('admin.developer.parent') }}</span>@endif
                                                @if ($row['core']) <span class="badge bg-label-danger ms-1">{{ __('admin.developer.core') }}</span>@endif
                                                @if (! empty($row['feature_blocked'])) <span class="badge bg-label-info ms-1" title="{{ __('admin.developer.feature_blocked_hint') }}">{{ __('admin.developer.feature_blocked') }}</span>@endif
                                                <span class="badge bg-label-warning ms-1 dev-locked {{ $locked ? '' : 'd-none' }}">{{ __('admin.developer.locked_by_parent') }}</span>
                                            </td>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-muted small">{{ $row['description'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                <div class="alert alert-secondary d-none" id="dev-empty" role="status">{{ __('admin.developer.no_result') }}</div>

                <div class="developer-permission-actions" role="region" aria-label="{{ __('admin.developer.tab_permissions') }}">
                    <button type="submit" class="btn btn-primary" @disabled(! $manifest['has_key'])><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('admin.developer.save') }}</button>
                    <button type="submit" class="btn btn-outline-secondary" form="dev-restore-form" @disabled(! $manifest['has_backup']) data-confirm="{{ __('admin.developer.confirm_restore') }}"><i class="ri-history-line me-1" aria-hidden="true"></i>{{ __('admin.developer.restore') }}</button>
                </div>
            </form>
            <form method="post" id="dev-restore-form" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.permissions.restore') }}">@csrf</form>
        </div>

        {{-- Tab 2: Theme Management (Phase 3) --}}
        <div class="tab-pane fade" id="dev-pane-themes" role="tabpanel" aria-labelledby="dev-tab-themes" tabindex="0">
            <div class="card mb-4">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 col-lg-3">{{ __('admin.developer.build_mode') }}</dt>
                        <dd class="col-sm-8 col-lg-9">
                            @if (! $theme['known']) {{ __('admin.developer.mode_unknown') }}
                            @elseif ($theme['mode'] === 'single') {{ __('admin.developer.mode_single', ['theme' => $theme['locked_theme']]) }}
                            @else {{ __('admin.developer.mode_all') }}
                            @endif
                            <span class="text-muted">({{ __('admin.developer.source') }}: {{ $theme['source'] }})</span>
                        </dd>
                        <dt class="col-sm-4 col-lg-3">{{ __('admin.developer.current_project') }}</dt>
                        <dd class="col-sm-8 col-lg-9">{{ $theme['project'] }} <code>{{ $theme['project_key'] }}</code> <span class="text-muted">({{ $theme['project_fixed'] ? __('admin.developer.project_fixed') : __('admin.developer.project_follows_theme') }})</span></dd>
                        <dt class="col-sm-4 col-lg-3">{{ __('admin.developer.active_theme') }}</dt>
                        <dd class="col-sm-8 col-lg-9"><code>{{ $theme['active'] }}</code></dd>
                        <dt class="col-sm-4 col-lg-3">{{ __('admin.developer.default_theme') }}</dt>
                        <dd class="col-sm-8 col-lg-9"><code>{{ $theme['default'] }}</code></dd>
                        @if ($theme['generated_at'])
                            <dt class="col-sm-4 col-lg-3">{{ __('admin.developer.build_version') }}</dt>
                            <dd class="col-sm-8 col-lg-9">{{ \Illuminate\Support\Carbon::parse($theme['generated_at'])->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($theme['read_only_reason'])
                <div class="alert {{ $theme['read_only_reason'] === 'unknown' ? 'alert-warning' : 'alert-secondary' }} py-2" role="status">
                    <i class="ri-lock-line me-1" aria-hidden="true"></i>{{ __('admin.developer.theme_ro_'.$theme['read_only_reason']) }}
                </div>
            @else
                <div class="alert alert-info py-2" role="status">{{ __('admin.developer.theme_switch_warning') }}</div>
            @endif

            <form method="post" id="dev-theme-form" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.theme.save') }}">
                @csrf
                <div class="row g-4">
                    @foreach ($theme['themes'] as $item)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100 {{ $item['active'] ? 'border border-primary' : '' }}">
                                @if ($item['preview'])
                                    <div class="bg-light text-center p-3" style="height: 140px">
                                        <img src="{{ $item['preview'] }}" alt="" style="max-height: 100%; max-width: 100%; object-fit: contain" loading="lazy">
                                    </div>
                                @else
                                    <div class="bg-light text-center text-muted d-flex align-items-center justify-content-center small" style="height: 140px">{{ __('admin.developer.no_preview') }}</div>
                                @endif
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div>
                                            <h6 class="mb-0">{{ $item['label'] }}</h6>
                                            <code class="small">{{ $item['key'] }}</code>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            @if ($item['active']) <span class="badge bg-primary">{{ __('admin.developer.badge_active') }}</span>@endif
                                            @if ($item['default']) <span class="badge bg-label-secondary">{{ __('admin.developer.badge_default') }}</span>@endif
                                            @if ($item['in_build']) <span class="badge bg-label-success">{{ __('admin.developer.badge_in_build') }}</span>@endif
                                            @if ($item['dependency']) <span class="badge bg-label-info">{{ __('admin.developer.badge_dependency') }}</span>@endif
                                            @if ($theme['mode'] === 'single' && $theme['known'] && $item['selectable']) <span class="badge bg-label-warning">{{ __('admin.developer.badge_locked') }}</span>@endif
                                        </div>
                                    </div>
                                    <ul class="list-unstyled small mb-3">
                                        @if ($item['brand'] !== '') <li>{{ $item['brand'] }}</li>@endif
                                        @if ($item['tagline'] !== '') <li class="text-muted">{{ $item['tagline'] }}</li>@endif
                                        @if ($item['project']) <li class="text-muted">{{ __('admin.developer.project') }}: {{ $item['project'] }}</li>@endif
                                        @if ($item['version'] !== null) <li class="text-muted">{{ __('admin.developer.cache_version') }}: {{ $item['version'] }}</li>@endif
                                    </ul>
                                    @if ($theme['can_switch'] && $item['selectable'])
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="dev-theme-{{ $item['key'] }}" value="{{ $item['key'] }}" @checked($item['active']) data-active="{{ $item['active'] ? 1 : 0 }}">
                                            <label class="form-check-label" for="dev-theme-{{ $item['key'] }}">{{ __('admin.developer.choose_theme') }}</label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($theme['can_switch'])
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" id="dev-theme-submit" disabled><i class="ri-palette-line me-1" aria-hidden="true"></i>{{ __('admin.developer.switch_theme') }}</button>
                    </div>
                @endif
            </form>
        </div>

        @include('cmsbackend::screens.pages.developer-settings.partials.features')
        @include('cmsbackend::screens.pages.developer-settings.partials.homepage-layout')
        <div class="tab-pane fade" id="dev-pane-accounts" role="tabpanel" aria-labelledby="dev-tab-accounts" tabindex="0">
            @include('cmsbackend::screens.pages.developer-settings.partials.accounts')
        </div>
    </div>

    <script>
        (function () {
            var search = document.getElementById('dev-search');
            var group = document.getElementById('dev-group');
            var count = document.getElementById('dev-count');
            var empty = document.getElementById('dev-empty');
            var form = document.getElementById('dev-perm-form');
            var shownText = @js(__('admin.developer.shown'));
            var confirmText = @js(__('admin.developer.confirm_save'));
            var switches = Array.prototype.slice.call(document.querySelectorAll('.dev-sw'));

            function applyFilter() {
                var q = search.value.trim().toLowerCase();
                var g = group.value;
                var shown = 0;
                document.querySelectorAll('.dev-group').forEach(function (card) {
                    var inGroup = g === '' || card.getAttribute('data-group') === g;
                    var any = 0;
                    card.querySelectorAll('.dev-row').forEach(function (row) {
                        var ok = inGroup && (q === '' || row.getAttribute('data-search').indexOf(q) !== -1);
                        row.classList.toggle('d-none', !ok);
                        if (ok) { any++; }
                    });
                    card.classList.toggle('d-none', any === 0);
                    shown += any;
                });
                count.textContent = shownText.replace(':count', shown);
                empty.classList.toggle('d-none', shown !== 0);
            }

            // A child is locked (off and not sent) while its parent is off. The server enforces this again.
            function syncChildren(parentSwitch) {
                var on = parentSwitch.checked;
                switches.forEach(function (sw) {
                    if (sw.getAttribute('data-parent') !== parentSwitch.getAttribute('data-code')) { return; }
                    if (!on) { sw.checked = false; }
                    sw.disabled = !on;
                    var badge = sw.closest('tr').querySelector('.dev-locked');
                    if (badge) { badge.classList.toggle('d-none', on); }
                });
            }

            function totals() {
                var total = switches.length, available = 0;
                switches.forEach(function (sw) { if (sw.checked) { available++; } });
                document.getElementById('dev-available').textContent = available;
                document.getElementById('dev-disabled').textContent = total - available;
            }

            switches.forEach(function (sw) {
                sw.addEventListener('change', function () {
                    if (!sw.getAttribute('data-parent')) { syncChildren(sw); }
                    totals();
                });
            });

            document.querySelectorAll('[data-group-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var code = button.getAttribute('data-group-toggle');
                    var parent = switches.filter(function (sw) { return sw.getAttribute('data-code') === code; })[0];
                    if (!parent || parent.disabled) { return; }
                    parent.checked = !parent.checked;
                    syncChildren(parent);
                    // Turning a group on also turns its permissions on (the Developer can still untick single ones).
                    if (parent.checked) {
                        switches.forEach(function (sw) { if (sw.getAttribute('data-parent') === code) { sw.checked = true; } });
                    }
                    totals();
                });
            });

            form.addEventListener('submit', function (event) {
                var on = 0, off = 0;
                switches.forEach(function (sw) {
                    var was = sw.getAttribute('data-initial') === '1';
                    if (sw.checked && !was) { on++; }
                    if (!sw.checked && was) { off++; }
                });
                if (!window.confirm(confirmText.replace(':on', on).replace(':off', off))) { event.preventDefault(); }
            });
            document.querySelectorAll('[data-confirm]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    if (!window.confirm(button.getAttribute('data-confirm'))) { event.preventDefault(); }
                });
            });

            var themeForm = document.getElementById('dev-theme-form');
            var themeSubmit = document.getElementById('dev-theme-submit');
            if (themeForm && themeSubmit) {
                var confirmTheme = @js(__('admin.developer.theme_confirm'));
                themeForm.addEventListener('change', function () {
                    var chosen = themeForm.querySelector('input[name=theme]:checked');
                    themeSubmit.disabled = !chosen || chosen.getAttribute('data-active') === '1';
                });
                themeForm.addEventListener('submit', function (event) {
                    var chosen = themeForm.querySelector('input[name=theme]:checked');
                    if (!chosen || chosen.getAttribute('data-active') === '1' || !window.confirm(confirmTheme.replace(':theme', chosen.value))) { event.preventDefault(); }
                });
            }
            var tabByQuery = { theme: 'dev-tab-themes', features: 'dev-tab-features', layout: 'dev-tab-layout', accounts: 'dev-tab-accounts' };
            var requestedTab = tabByQuery[new URLSearchParams(window.location.search).get('tab')];
            if (requestedTab) {
                // Open the Theme / Feature tab after a save (the tab button's own Bootstrap handler does the work once the page loaded).
                window.addEventListener('load', function () {
                    var tabButton = document.getElementById(requestedTab);
                    if (tabButton) { tabButton.click(); }
                });
            }

            var confirmFeatureOff = @js(__('admin.developer.feature_confirm_off'));
            var confirmFeatureOn = @js(__('admin.developer.feature_confirm_on'));
            document.querySelectorAll('.dev-feature-switch').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    var featureForm = toggle.closest('form');
                    if (window.confirm(toggle.checked ? confirmFeatureOn : confirmFeatureOff)) {
                        featureForm.querySelector('input[name=enabled]').value = toggle.checked ? 1 : 0;
                        featureForm.submit();
                    } else {
                        toggle.checked = !toggle.checked;
                    }
                });
            });

            search.addEventListener('input', applyFilter);
            group.addEventListener('change', applyFilter);
            applyFilter();
            totals();
        })();
    </script>
@endsection
