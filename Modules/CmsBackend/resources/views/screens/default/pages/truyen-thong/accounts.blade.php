@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.accounts_title'))

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $account = $facebook['account'];
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.accounts') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    <div class="row g-4">
        {{-- Facebook Page --}}
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <h5 class="mb-0"><i class="ri-facebook-circle-fill me-2" aria-hidden="true"></i>{{ __('admin.social.account_title_facebook') }}</h5>
                        @if ($account)
                            <span class="badge {{ $account->status->badge() }}">{{ $account->status->label() }}</span>
                        @else
                            <span class="badge bg-label-secondary">{{ __('admin.social.not_connected') }}</span>
                        @endif
                    </div>

                    @unless ($facebook['configured'])
                        <div class="alert alert-warning" role="alert">{{ __('admin.social.fb_not_configured') }}@if ($facebook['config_problem']) — {{ $facebook['config_problem'] }}@endif</div>
                    @endunless

                    @if ($account)
                        <div class="d-flex gap-3 mb-3">
                            @if ($facebook['picture_url'])
                                <img src="{{ $facebook['picture_url'] }}" alt="" width="64" height="64" class="rounded border" style="object-fit: cover" referrerpolicy="no-referrer" loading="lazy">
                            @endif
                            <div>
                                <div class="fw-bold">{{ $account->name }}</div>
                                @if ($facebook['page_url'])
                                    <a href="{{ $facebook['page_url'] }}" target="_blank" rel="noopener noreferrer" class="small text-break">{{ $facebook['page_url'] }}</a>
                                @endif
                            </div>
                        </div>

                        <dl class="row small mb-3">
                            <dt class="col-5">{{ __('admin.social.fb_page_id') }}</dt>
                            <dd class="col-7 text-break">{{ $account->external_account_id }}</dd>
                            <dt class="col-5">{{ __('admin.social.current_project') }}</dt>
                            <dd class="col-7">{{ \Modules\CmsBackend\Support\ProjectContext::label($account->project_key) }}</dd>
                            <dt class="col-5">{{ __('admin.social.fb_connected_by') }}</dt>
                            <dd class="col-7">{{ $facebook['connected_by'] ?? '—' }}</dd>
                            <dt class="col-5">{{ __('admin.social.field_last_checked') }}</dt>
                            <dd class="col-7">{{ $account->last_checked_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                            <dt class="col-5">{{ __('admin.social.fb_token_expires') }}</dt>
                            <dd class="col-7">{{ $account->token_expires_at ? $account->token_expires_at->format('d/m/Y H:i') : __('admin.social.fb_token_no_expiry') }}</dd>
                        </dl>

                        @unless ($account->isConnected())
                            <div class="alert alert-warning small" role="alert">{{ __('admin.social.fb_needs_reconnect') }}</div>
                        @endunless

                        <div class="d-flex flex-wrap gap-2">
                            <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'facebook.check') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary" @disabled(! $facebook['configured'])><i class="ri-refresh-line me-1" aria-hidden="true"></i>{{ __('admin.social.fb_check') }}</button>
                            </form>
                            @unless ($account->isConnected())
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'facebook.connect') }}" class="btn btn-primary {{ $facebook['configured'] ? '' : 'disabled' }}" @unless ($facebook['configured']) aria-disabled="true" @endunless>{{ __('admin.social.fb_reconnect') }}</a>
                            @endunless
                            <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'facebook.disconnect') }}" onsubmit="return confirm(@js(__('admin.social.fb_confirm_disconnect')));">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger"><i class="ri-link-unlink-m me-1" aria-hidden="true"></i>{{ __('admin.social.fb_disconnect') }}</button>
                            </form>
                        </div>
                        <div class="form-text mt-2">{{ __('admin.social.fb_disconnect_note') }}</div>
                    @else
                        <form method="get" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'facebook.connect') }}">
                            <details class="mb-3">
                                <summary class="small text-muted">{{ __('admin.social.fb_expected_title') }}</summary>
                                <div class="mt-2">
                                    <p class="small text-muted">{{ __('admin.social.fb_expected_note') }}</p>
                                    <div class="mb-2">
                                        <label class="form-label small" for="fb-page-id">{{ __('admin.social.fb_field_page_id') }}</label>
                                        <input id="fb-page-id" type="text" inputmode="numeric" name="expected_page_id" class="form-control form-control-sm" maxlength="25" pattern="\d{5,25}" autocomplete="off">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small" for="fb-page-url">{{ __('admin.social.fb_field_page_url') }}</label>
                                        <input id="fb-page-url" type="url" name="expected_page_url" class="form-control form-control-sm" maxlength="500" placeholder="https://www.facebook.com/..." autocomplete="off">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small" for="fb-business-id">{{ __('admin.social.fb_field_business_id') }}</label>
                                        <input id="fb-business-id" type="text" inputmode="numeric" name="business_id" class="form-control form-control-sm" maxlength="25" pattern="\d{5,25}" autocomplete="off" aria-describedby="fb-business-note">
                                        <div id="fb-business-note" class="form-text">{{ __('admin.social.fb_business_note') }}</div>
                                    </div>
                                </div>
                            </details>
                            <button type="submit" class="btn btn-primary" @disabled(! $facebook['configured'])><i class="ri-facebook-circle-fill me-1" aria-hidden="true"></i>{{ __('admin.social.connect_facebook') }}</button>
                        </form>
                        <div class="form-text mt-2">{{ __('admin.social.fb_scopes_note') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Zalo OA: not available yet --}}
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <h5 class="mb-0"><i class="ri-chat-smile-3-line me-2" aria-hidden="true"></i>{{ __('admin.social.account_title_zalo') }}</h5>
                        <span class="badge bg-label-secondary">{{ __('admin.social.not_connected') }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">{{ __('admin.social.connect_zalo_later') }}</button>
                    <div class="form-text mt-2">{{ __('admin.social.coming_later') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
