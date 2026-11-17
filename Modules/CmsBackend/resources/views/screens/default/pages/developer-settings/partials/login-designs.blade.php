{{-- Tab "Đăng nhập": giao diện đăng nhập của từng dự án. Data: $loginDesigns = LoginDesigns::panel() (designs, projects). The controllers of the sign-in page
     are the same for every project; a design is a folder under Modules/CmsFrontend/resources/views/auth/designs and appears here by itself. --}}
@php
    $loginRoute = \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.login.save');
    $loginPreview = fn (string $key) => \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.login.preview', ['design' => $key]);
    $designList = $loginDesigns['designs'];
    $usableDesigns = array_filter($designList, fn (array $design) => $design['built']);
@endphp
<div class="tab-pane fade" id="dev-pane-login" role="tabpanel" aria-labelledby="dev-tab-login" tabindex="0">
    <div class="alert alert-info py-2" role="note">{{ __('admin.developer.login_intro') }}</div>

    <form method="post" action="{{ $loginRoute }}" id="dev-login-form">
        @csrf
        <div class="card mb-4">
            <h5 class="card-header">{{ __('admin.developer.login_projects_title') }}</h5>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-3">
                        <thead>
                            <tr class="text-nowrap">
                                <th>{{ __('admin.developer.login_col_project') }}</th>
                                <th>{{ __('admin.developer.login_col_using') }}</th>
                                <th style="min-width: 18rem">{{ __('admin.developer.login_col_choose') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loginDesigns['projects'] as $project)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $project['label'] }} <code class="ms-1">{{ $project['key'] }}</code>
                                        @if ($project['current'])<span class="badge bg-label-primary ms-1">{{ __('admin.developer.login_current_project') }}</span>@endif
                                    </td>
                                    <td>
                                        {{ $designList[$project['effective']]['label'] ?? $project['effective'] }}
                                        @if ($project['assigned'] === null)<span class="text-muted small">({{ __('admin.developer.login_follows_theme') }})</span>@endif
                                    </td>
                                    <td>
                                        <select name="designs[{{ $project['key'] }}]" class="form-select form-select-sm" aria-label="{{ __('admin.developer.login_col_choose') }}: {{ $project['label'] }}">
                                            <option value="" @selected($project['assigned'] === null)>{{ __('admin.developer.login_option_theme') }}</option>
                                            @foreach ($usableDesigns as $design)
                                                <option value="{{ $design['key'] }}" @selected($project['assigned'] === $design['key'])>{{ $design['label'] }} ({{ $design['key'] }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0">{{ __('admin.developer.login_save_note') }}</p>
            </div>
        </div>
        {{-- Fixed at the bottom right of the screen like the other tabs (the pane is hidden with its tab, so the bar only shows here). --}}
        <div class="developer-floating-actions" role="region" aria-label="{{ __('admin.developer.tab_login') }}">
            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('admin.developer.login_save') }}</button>
        </div>
    </form>

    <div class="card mb-4">
        <h5 class="card-header">{{ __('admin.developer.login_designs_title') }}</h5>
        <div class="card-body">
            <p class="text-muted small">{{ __('admin.developer.login_designs_hint') }}</p>
            <div class="row g-3">
                @forelse ($designList as $design)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <strong>{{ $design['label'] }}</strong>
                                @if ($design['built'])
                                    <span class="badge bg-label-success">{{ __('admin.developer.login_built') }}</span>
                                @else
                                    <span class="badge bg-label-warning" title="{{ __('admin.developer.login_not_built_hint') }}">{{ __('admin.developer.login_not_built') }}</span>
                                @endif
                            </div>
                            <code class="small mb-2">{{ $design['key'] }}</code>
                            @if ($design['description'] !== '')<p class="small text-muted mb-3">{{ $design['description'] }}</p>@endif
                            <div class="mt-auto">
                                @if ($design['built'])
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-login-preview="{{ $loginPreview($design['key']) }}" data-login-preview-title="{{ $design['label'] }}">
                                        <i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('admin.developer.login_preview') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">{{ __('admin.developer.login_none') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modal fade" id="dev-login-preview" tabindex="-1" aria-labelledby="dev-login-preview-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dev-login-preview-title">{{ __('admin.developer.login_preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.developer.login_close') }}"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- sandbox without allow-forms / allow-same-origin: the sign-in form in the frame cannot submit anything. --}}
                    <iframe id="dev-login-preview-frame" title="{{ __('admin.developer.login_preview') }}" sandbox="allow-scripts" style="width: 100%; height: 70vh; border: 0" referrerpolicy="no-referrer"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modalElement = document.getElementById('dev-login-preview');
            var frame = document.getElementById('dev-login-preview-frame');
            var title = document.getElementById('dev-login-preview-title');
            if (!modalElement || !frame) { return; }
            var fallbackTitle = title.textContent;
            Array.prototype.forEach.call(document.querySelectorAll('[data-login-preview]'), function (button) {
                button.addEventListener('click', function () {
                    frame.src = button.getAttribute('data-login-preview');
                    title.textContent = button.getAttribute('data-login-preview-title') || fallbackTitle;
                    if (window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(modalElement).show(); }
                });
            });
            modalElement.addEventListener('hidden.bs.modal', function () { frame.removeAttribute('src'); });
        })();
    </script>
</div>
