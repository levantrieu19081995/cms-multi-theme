{{-- Zalo Official Account: connection card of the Zalo OA column. Needs $zalo (ZaloConnectionService::card()). --}}
@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $account = $zalo['account'];
@endphp
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h5 class="mb-0"><i class="ri-chat-smile-3-line me-2" aria-hidden="true"></i>{{ __('admin.social.account_title_zalo') }}</h5>
            @if ($account)
                <span class="badge {{ $account->status->badge() }}">{{ $account->status->label() }}</span>
            @else
                <span class="badge bg-label-secondary">{{ __('admin.social.not_connected') }}</span>
            @endif
        </div>

        @unless ($zalo['configured'])
            <div class="alert alert-warning" role="alert">{{ __('admin.social.zalo_not_configured') }}@if ($zalo['config_problem']) — {{ $zalo['config_problem'] }}@endif</div>
        @endunless

        @if ($account)
            <div class="d-flex gap-3 mb-3">
                @if ($zalo['avatar'])
                    <img src="{{ $zalo['avatar'] }}" alt="" width="64" height="64" class="rounded border" style="object-fit: cover" referrerpolicy="no-referrer" loading="lazy">
                @endif
                <div>
                    <div class="fw-bold">{{ $account->name }}
                        @if ($zalo['is_verified'])
                            <i class="ri-verified-badge-fill text-primary" aria-hidden="true" title="{{ __('admin.social.zalo_verified') }}"></i><span class="visually-hidden">{{ __('admin.social.zalo_verified') }}</span>
                        @endif
                    </div>
                    @if ($zalo['alias'])
                        <a href="https://zalo.me/{{ $zalo['alias'] }}" target="_blank" rel="noopener noreferrer" class="small text-break">zalo.me/{{ $zalo['alias'] }}</a>
                    @endif
                </div>
            </div>

            <dl class="row small mb-3">
                <dt class="col-5">{{ __('admin.social.zalo_oa_id') }}</dt>
                <dd class="col-7 text-break">{{ $account->external_account_id }}</dd>
                <dt class="col-5">{{ __('admin.social.zalo_followers') }}</dt>
                <dd class="col-7">{{ $zalo['followers'] !== null ? number_format((int) $zalo['followers']) : '—' }}</dd>
                <dt class="col-5">{{ __('admin.social.zalo_package') }}</dt>
                <dd class="col-7">{{ $zalo['package'] ?? '—' }}</dd>
                <dt class="col-5">{{ __('admin.social.current_project') }}</dt>
                <dd class="col-7">{{ \Modules\CmsBackend\Support\ProjectContext::label($account->project_key) }}</dd>
                <dt class="col-5">{{ __('admin.social.fb_connected_by') }}</dt>
                <dd class="col-7">{{ $zalo['connected_by'] ?? '—' }}</dd>
                <dt class="col-5">{{ __('admin.social.field_last_checked') }}</dt>
                <dd class="col-7">{{ $account->last_checked_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt class="col-5">{{ __('admin.social.zalo_connection_expires') }}</dt>
                <dd class="col-7">{{ $account->token_expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </dl>

            @unless ($account->isConnected())
                <div class="alert alert-warning small" role="alert">{{ __('admin.social.zalo_needs_reconnect') }}</div>
            @endunless

            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'zalo.check') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary" @disabled(! $zalo['configured'])><i class="ri-refresh-line me-1" aria-hidden="true"></i>{{ __('admin.social.zalo_check') }}</button>
                </form>
                @unless ($account->isConnected())
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'zalo.connect') }}" class="btn btn-primary {{ $zalo['configured'] ? '' : 'disabled' }}" @unless ($zalo['configured']) aria-disabled="true" @endunless>{{ __('admin.social.zalo_reconnect') }}</a>
                @endunless
                <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'zalo.disconnect') }}" onsubmit="return confirm(@js(__('admin.social.zalo_confirm_disconnect')));">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="ri-link-unlink-m me-1" aria-hidden="true"></i>{{ __('admin.social.zalo_disconnect') }}</button>
                </form>
            </div>
            <div class="form-text mt-2">{{ __('admin.social.zalo_disconnect_note') }}</div>
        @else
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'zalo.connect') }}" class="btn btn-primary {{ $zalo['configured'] ? '' : 'disabled' }}" @unless ($zalo['configured']) aria-disabled="true" @endunless><i class="ri-chat-smile-3-line me-1" aria-hidden="true"></i>{{ __('admin.social.connect_zalo') }}</a>

            <h6 class="small text-uppercase text-muted mt-4">{{ __('admin.social.zalo_shell_needs_title') }}</h6>
            <ul class="small mb-0">
                <li>{{ __('admin.social.zalo_shell_need_oa') }}</li>
                <li>{{ __('admin.social.zalo_shell_need_app') }}</li>
                <li>{{ __('admin.social.zalo_shell_need_callback') }}</li>
                <li>{{ __('admin.social.zalo_shell_need_permission') }}</li>
            </ul>
        @endif
    </div>
</div>
