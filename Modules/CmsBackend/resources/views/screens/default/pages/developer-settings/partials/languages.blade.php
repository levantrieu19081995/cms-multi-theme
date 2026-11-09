        {{-- Tab: Ngôn ngữ (công tắc "multilingual" của Feature Registry, tách khỏi danh sách chức năng nghiệp vụ) --}}
        <div class="tab-pane fade" id="dev-pane-languages" role="tabpanel" aria-labelledby="dev-tab-languages" tabindex="0">
            <div class="alert alert-info py-2" role="status">{{ __('admin.developer.language_note') }}</div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="{{ $language['icon'] }} fs-3 text-primary" aria-hidden="true"></i>
                            <div>
                                <strong>{{ $language['name'] }}</strong> <code class="ms-1">multilingual</code>
                                <div class="small text-muted">{{ $language['description'] }}</div>
                                <span class="badge {{ $language['enabled'] ? 'bg-label-success' : 'bg-label-secondary' }} mt-1" data-feature-state>{{ $language['enabled'] ? __('admin.developer.feature_on_badge') : __('admin.developer.feature_off_badge') }}</span>
                                @unless ($language['overridden'])<span class="badge bg-label-info mt-1">{{ __('admin.developer.feature_default') }}</span>@endunless
                            </div>
                        </div>
                        <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.features.save', 'multilingual') }}" class="dev-feature-form">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $language['enabled'] ? 0 : 1 }}">
                            <div class="form-check form-switch d-inline-block m-0">
                                <input class="form-check-input dev-feature-switch" type="checkbox" role="switch" @checked($language['enabled']) aria-label="{{ __('admin.developer.feature_switch', ['name' => $language['name']]) }}">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card h-100"><div class="card-body">
                        <h6>{{ __('admin.developer.language_locales') }}</h6>
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>{{ __('admin.developer.language_code') }}</th><th>{{ __('admin.developer.language_label') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($language['locales'] as $locale)
                                <tr><td><code>{{ $locale['code'] }}</code></td><td>{{ $locale['label'] }}</td>
                                    <td>@if ($locale['default'])<span class="badge bg-label-primary">{{ __('admin.developer.language_default') }}</span>@elseif (! $language['enabled'])<span class="badge bg-label-secondary">{{ __('admin.developer.language_inactive') }}</span>@else<span class="badge bg-label-success">{{ __('admin.developer.language_active') }}</span>@endif</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                        <p class="small text-muted mt-2 mb-0">{{ __('admin.developer.language_config_hint') }}</p>
                    </div></div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100"><div class="card-body">
                        <h6>{{ __('admin.developer.language_default_only') }}</h6>
                        <p class="small text-muted">{{ __('admin.developer.language_default_only_hint') }}</p>
                        @forelse ($language['default_only'] as $prefix)
                            <div><code>{{ $prefix }}*</code></div>
                        @empty
                            <p class="text-muted mb-0">—</p>
                        @endforelse
                    </div></div>
                </div>
            </div>
        </div>
