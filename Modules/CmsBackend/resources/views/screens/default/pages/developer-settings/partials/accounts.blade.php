{{-- Tab "Tài khoản": danh sách tài khoản, đổi mật khẩu, khóa/mở, gán vai trò, cấu hình quyền của vai trò. Mọi form POST về DeveloperAccountsController. --}}
@php
    $route = fn (string $name) => \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.accounts.'.$name);
@endphp
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h5 class="mb-0">{{ __('developer_accounts.title') }} <span class="badge bg-label-secondary ms-1">{{ count($accounts['users']) }}</span></h5>
            <input type="search" class="form-control form-control-sm" style="max-width: 320px" id="dev-acc-search" placeholder="{{ __('developer_accounts.search') }}" autocomplete="off">
        </div>
        <p class="text-muted small">{{ __('developer_accounts.hint') }}</p>
        @unless ($accounts['edit_published_ready'])
            <div class="alert alert-warning py-2" role="status">{{ __('developer_accounts.edit_published_not_ready') }}</div>
        @endunless
        @if ($accounts['truncated'])
            <div class="alert alert-warning py-2" role="status">{{ __('developer_accounts.truncated') }}</div>
        @endif
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="dev-acc-table">
                <thead>
                    <tr>
                        <th>{{ __('developer_accounts.col_name') }}</th>
                        <th>{{ __('developer_accounts.col_username') }}</th>
                        <th>{{ __('developer_accounts.col_email') }}</th>
                        <th>{{ __('developer_accounts.col_roles') }}</th>
                        <th>{{ __('developer_accounts.col_status') }}</th>
                        <th title="{{ __('developer_accounts.edit_published_hint') }}">{{ __('developer_accounts.col_edit_published') }}</th>
                        <th>{{ __('developer_accounts.col_created') }}</th>
                        <th class="text-end">{{ __('developer_accounts.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts['users'] as $account)
                        <tr data-search="{{ \Illuminate\Support\Str::lower($account['name'].' '.$account['username'].' '.$account['email']) }}">
                            <td class="fw-semibold">{{ $account['name'] }}</td>
                            <td>{{ $account['username'] }}</td>
                            <td class="text-break">{{ $account['email'] }}</td>
                            <td>
                                @forelse ($account['roles'] as $role)
                                    <span class="badge bg-label-primary">{{ $role['label'] }}</span>
                                @empty
                                    <span class="text-muted small">{{ __('developer_accounts.no_role') }}</span>
                                @endforelse
                            </td>
                            <td><span class="badge {{ $account['active'] ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $account['active'] ? __('developer_accounts.active') : __('developer_accounts.inactive') }}</span></td>
                            <td>
                                <form method="post" action="{{ $route('edit-published') }}">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $account['id'] }}">
                                    <input type="hidden" name="enabled" value="{{ $account['edit_published'] ? 0 : 1 }}">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" onchange="this.form.submit()" @checked($account['edit_published']) @disabled(! $accounts['edit_published_ready']) aria-label="{{ __('developer_accounts.edit_published_switch', ['name' => $account['name']]) }}">
                                    </div>
                                </form>
                            </td>
                            <td class="text-nowrap">{{ $account['created_at'] }}</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-dev-acc-password data-user="{{ $account['id'] }}" data-name="{{ $account['name'] }}"><i class="ri-key-2-line me-1" aria-hidden="true"></i>{{ __('developer_accounts.btn_password') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-dev-acc-roles data-user="{{ $account['id'] }}" data-name="{{ $account['name'] }}" data-roles='@json($account['role_ids'])'><i class="ri-user-settings-line me-1" aria-hidden="true"></i>{{ __('developer_accounts.btn_roles') }}</button>
                                <form class="d-inline" method="post" action="{{ $route('status') }}" onsubmit="return window.confirm(@js($account['active'] ? __('developer_accounts.confirm_lock') : __('developer_accounts.confirm_unlock')))">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $account['id'] }}">
                                    <input type="hidden" name="active" value="{{ $account['active'] ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-sm {{ $account['active'] ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $account['active'] ? __('developer_accounts.btn_lock') : __('developer_accounts.btn_unlock') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="dev-acc-empty" hidden><td colspan="8" class="text-center text-muted py-3">{{ __('developer_accounts.empty') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-1">{{ __('developer_accounts.roles_section') }}</h5>
        <p class="text-muted small">{{ __('developer_accounts.roles_section_hint') }}</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('developer_accounts.col_role') }}</th>
                        <th>{{ __('developer_accounts.col_users') }}</th>
                        <th>{{ __('developer_accounts.col_permissions') }}</th>
                        <th class="text-end">{{ __('developer_accounts.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts['roles'] as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role['label'] }}</td>
                            <td>{{ $role['users'] }}</td>
                            <td>{{ $role['permission_count'] }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-dev-acc-perms data-role="{{ $role['id'] }}" data-name="{{ $role['label'] }}" data-permissions='@json($role['permission_ids'])'><i class="ri-shield-keyhole-line me-1" aria-hidden="true"></i>{{ __('developer_accounts.btn_permissions') }}</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Đổi mật khẩu --}}
<div class="modal fade" id="dev-acc-modal-password" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post" action="{{ $route('password') }}" autocomplete="off">
        @csrf
        <input type="hidden" name="user_id" value="">
        <div class="modal-header"><h5 class="modal-title">{{ __('developer_accounts.password_title') }}: <span data-dev-acc-target></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('developer_accounts.btn_cancel') }}"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label" for="dev-acc-pw">{{ __('developer_accounts.password_new') }}</label><input class="form-control" type="password" id="dev-acc-pw" name="password" minlength="8" maxlength="128" required autocomplete="new-password"></div>
            <div><label class="form-label" for="dev-acc-pw2">{{ __('developer_accounts.password_confirm') }}</label><input class="form-control" type="password" id="dev-acc-pw2" name="password_confirmation" minlength="8" maxlength="128" required autocomplete="new-password"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('developer_accounts.btn_cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('developer_accounts.btn_save') }}</button></div>
    </form></div>
</div>

{{-- Vai trò của tài khoản --}}
<div class="modal fade" id="dev-acc-modal-roles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post" action="{{ $route('roles') }}">
        @csrf
        <input type="hidden" name="user_id" value="">
        <div class="modal-header"><h5 class="modal-title">{{ __('developer_accounts.roles_title') }}: <span data-dev-acc-target></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('developer_accounts.btn_cancel') }}"></button></div>
        <div class="modal-body">
            <p class="text-muted small">{{ __('developer_accounts.roles_hint') }}</p>
            @foreach ($accounts['roles'] as $role)
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role['id'] }}" id="dev-acc-role-{{ $role['id'] }}"><label class="form-check-label" for="dev-acc-role-{{ $role['id'] }}">{{ $role['label'] }}</label></div>
            @endforeach
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('developer_accounts.btn_cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('developer_accounts.btn_save') }}</button></div>
    </form></div>
</div>

{{-- Quyền của vai trò --}}
<div class="modal fade" id="dev-acc-modal-perms" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ $route('role-permissions') }}">
        @csrf
        <input type="hidden" name="role_id" value="">
        <div class="modal-header"><h5 class="modal-title">{{ __('developer_accounts.permissions_title') }}: <span data-dev-acc-target></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('developer_accounts.btn_cancel') }}"></button></div>
        <div class="modal-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <input type="search" class="form-control form-control-sm" style="max-width: 260px" id="dev-acc-perm-search" placeholder="{{ __('developer_accounts.permissions_search') }}" autocomplete="off">
                <button type="button" class="btn btn-sm btn-outline-primary" id="dev-acc-perm-all">{{ __('developer_accounts.permissions_all') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="dev-acc-perm-none">{{ __('developer_accounts.permissions_none') }}</button>
            </div>
            @foreach ($accounts['groups'] as $group)
                <fieldset class="border rounded p-2 mb-2" data-dev-acc-group>
                    <legend class="float-none w-auto px-1 fs-6 mb-1">
                        <label class="form-check-label fw-semibold"><input class="form-check-input me-1" type="checkbox" name="permissions[]" value="{{ $group['id'] }}" data-perm data-label="{{ \Illuminate\Support\Str::lower($group['name']) }}">{{ $group['name'] }}</label>
                    </legend>
                    <div class="row g-1">
                        @foreach ($group['children'] as $child)
                            <div class="col-md-6"><label class="form-check-label small"><input class="form-check-input me-1" type="checkbox" name="permissions[]" value="{{ $child['id'] }}" data-perm data-label="{{ \Illuminate\Support\Str::lower($child['name']) }}">{{ $child['name'] }}</label></div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('developer_accounts.btn_cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('developer_accounts.btn_save') }}</button></div>
    </form></div>
</div>

<script>
    (function () {
        var modal = function (id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); };
        var open = function (id, field, value, name) {
            var el = document.getElementById(id);
            el.querySelector('input[name=' + field + ']').value = value;
            el.querySelector('[data-dev-acc-target]').textContent = name;
            return el;
        };
        document.querySelectorAll('[data-dev-acc-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                var el = open('dev-acc-modal-password', 'user_id', button.dataset.user, button.dataset.name);
                el.querySelectorAll('input[type=password]').forEach(function (input) { input.value = ''; });
                modal('dev-acc-modal-password').show();
            });
        });
        document.querySelectorAll('[data-dev-acc-roles]').forEach(function (button) {
            button.addEventListener('click', function () {
                var el = open('dev-acc-modal-roles', 'user_id', button.dataset.user, button.dataset.name);
                var ids = JSON.parse(button.dataset.roles || '[]').map(String);
                el.querySelectorAll('input[name="roles[]"]').forEach(function (box) { box.checked = ids.indexOf(box.value) !== -1; });
                modal('dev-acc-modal-roles').show();
            });
        });
        var permBoxes = function () { return document.querySelectorAll('#dev-acc-modal-perms [data-perm]'); };
        document.querySelectorAll('[data-dev-acc-perms]').forEach(function (button) {
            button.addEventListener('click', function () {
                open('dev-acc-modal-perms', 'role_id', button.dataset.role, button.dataset.name);
                var ids = JSON.parse(button.dataset.permissions || '[]').map(String);
                permBoxes().forEach(function (box) { box.checked = ids.indexOf(box.value) !== -1; });
                document.getElementById('dev-acc-perm-search').value = '';
                document.querySelectorAll('#dev-acc-modal-perms [data-dev-acc-group]').forEach(function (group) { group.hidden = false; });
                modal('dev-acc-modal-perms').show();
            });
        });
        document.getElementById('dev-acc-perm-all').addEventListener('click', function () { permBoxes().forEach(function (box) { box.checked = true; }); });
        document.getElementById('dev-acc-perm-none').addEventListener('click', function () { permBoxes().forEach(function (box) { box.checked = false; }); });
        document.getElementById('dev-acc-perm-search').addEventListener('input', function (event) {
            var q = event.target.value.trim().toLowerCase();
            document.querySelectorAll('#dev-acc-modal-perms [data-dev-acc-group]').forEach(function (group) {
                var hit = q === '' || Array.prototype.some.call(group.querySelectorAll('[data-perm]'), function (box) { return box.dataset.label.indexOf(q) !== -1; });
                group.hidden = !hit;
            });
        });
        document.getElementById('dev-acc-search').addEventListener('input', function (event) {
            var q = event.target.value.trim().toLowerCase();
            var shown = 0;
            document.querySelectorAll('#dev-acc-table tbody tr[data-search]').forEach(function (row) {
                var hit = q === '' || row.dataset.search.indexOf(q) !== -1;
                row.hidden = !hit;
                shown += hit ? 1 : 0;
            });
            document.getElementById('dev-acc-empty').hidden = shown !== 0;
        });
    })();
</script>
