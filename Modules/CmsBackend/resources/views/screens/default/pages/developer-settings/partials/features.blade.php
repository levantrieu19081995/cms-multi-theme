        {{-- Tab 3: Feature Registry (chức năng hệ thống: định nghĩa ở code, bật/tắt ở DB) --}}
        <div class="tab-pane fade" id="dev-pane-features" role="tabpanel" aria-labelledby="dev-tab-features" tabindex="0">
            <div class="alert alert-info py-2" role="status">{{ __('admin.developer.feature_note') }}</div>
            @if (! empty($featureErrors))
                <div class="alert alert-danger" role="alert">
                    <strong>{{ __('admin.developer.feature_errors') }}</strong>
                    <ul class="mb-0">@foreach ($featureErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="dev-features-table">
                        <thead>
                            <tr>
                                <th>{{ __('admin.developer.feature_title') }}</th>
                                <th>{{ __('admin.developer.feature_admin_url') }} / {{ __('admin.developer.feature_public_urls') }}</th>
                                <th>{{ __('admin.developer.feature_permissions') }}</th>
                                <th>{{ __('admin.developer.feature_routes') }}</th>
                                <th class="text-center">{{ __('admin.developer.feature_menus') }}</th>
                                <th class="text-center">{{ __('admin.developer.feature_on_badge') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($features as $feature)
                            <tr data-feature="{{ $feature['key'] }}">
                                <td style="min-width:240px">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="{{ $feature['icon'] }} fs-4 text-primary" aria-hidden="true"></i>
                                        <div>
                                            <strong>{{ $feature['name'] }}</strong> <code class="ms-1">{{ $feature['key'] }}</code>
                                            <div class="small text-muted">{{ $feature['description'] }}</div>
                                            <span class="badge {{ $feature['enabled'] ? 'bg-label-success' : 'bg-label-secondary' }} mt-1" data-feature-state>{{ $feature['enabled'] ? __('admin.developer.feature_on_badge') : __('admin.developer.feature_off_badge') }}</span>
                                            @unless ($feature['overridden'])<span class="badge bg-label-info mt-1">{{ __('admin.developer.feature_default') }}</span>@endunless
                                        </div>
                                    </div>
                                </td>
                                <td class="small" style="min-width:230px">
                                    @if ($feature['admin_url'])<div><i class="ri-settings-3-line me-1" aria-hidden="true"></i><a href="{{ $feature['admin_url'] }}"><code>{{ parse_url($feature['admin_url'], PHP_URL_PATH) }}</code></a></div>@elseif ($feature['admin_route'])<div class="text-danger"><code>{{ $feature['admin_route'] }}</code></div>@endif
                                    @foreach ($feature['targets'] as $target)
                                        <div><i class="ri-global-line me-1" aria-hidden="true"></i>{{ $target['name'] }}:
                                            @if ($target['url'])<a href="{{ $target['url'] }}" target="_blank" rel="noopener"><code>{{ parse_url($target['url'], PHP_URL_PATH) }}</code></a>@else<span class="text-danger"><code>{{ $target['route'] }}</code></span>@endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="small">
                                    @if ($feature['permissions_declared'] > 0)
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings', ['tab' => 'permissions', 'feature' => $feature['key']]) }}" title="{{ __('admin.developer.feature_permissions_link') }}"><i class="ri-shield-keyhole-line me-1" aria-hidden="true"></i>{{ __('admin.developer.feature_permissions_declared', ['count' => $feature['permissions_declared']]) }}</a>
                                    @else
                                        {{ __('admin.developer.feature_permissions_declared', ['count' => $feature['permissions_declared']]) }}
                                    @endif
                                    @if ($feature['permissions_missing_code'])<div class="text-danger">{{ __('admin.developer.feature_permissions_missing_code', ['list' => implode(', ', $feature['permissions_missing_code'])]) }}</div>@endif
                                    @if ($feature['permissions_missing_db'])<div class="text-warning">{{ __('admin.developer.feature_permissions_missing_db', ['list' => implode(', ', $feature['permissions_missing_db'])]) }}</div>@endif
                                    @if (! $feature['permissions_missing_code'] && ! $feature['permissions_missing_db'])<div class="text-success">{{ __('admin.developer.feature_permissions_ok') }}</div>@endif
                                </td>
                                <td>@if ($feature['routes_ok'])<span class="badge bg-label-success">{{ __('admin.developer.feature_routes_ok') }}</span>@else<span class="badge bg-label-danger">{{ __('admin.developer.feature_routes_missing') }}</span>@endif</td>
                                <td class="text-center">{{ $feature['menu_count'] }}</td>
                                <td class="text-center">
                                    <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.features.save', $feature['key']) }}" class="dev-feature-form" data-name="{{ $feature['name'] }}">
                                        @csrf
                                        <input type="hidden" name="enabled" value="{{ $feature['enabled'] ? 0 : 1 }}">
                                        <div class="form-check form-switch d-inline-block m-0">
                                            <input class="form-check-input dev-feature-switch" type="checkbox" role="switch" @checked($feature['enabled']) aria-label="{{ __('admin.developer.feature_switch', ['name' => $feature['name']]) }}">
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('admin.developer.feature_none') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
