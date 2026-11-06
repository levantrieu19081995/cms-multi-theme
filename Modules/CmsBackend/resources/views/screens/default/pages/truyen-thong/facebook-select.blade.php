@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.fb_select_title'))

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $reasons = ['no_token' => 'admin.social.fb_reason_no_token', 'no_role' => 'admin.social.fb_reason_no_role'];
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.accounts') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    {{-- The page access tokens stay on the server: this form only sends the chosen page id. --}}
    <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'facebook.confirm-page') }}">
        @csrf
        <div class="card">
            <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.social.fb_select_title') }}</h5>
            <div class="card-body mt-4">
                <p class="text-muted">{{ __('admin.social.fb_select_note') }}</p>

                @if (! empty($expected['page_id']) || ! empty($expected['page_url']) || ! empty($expected['business_id']))
                    <div class="alert alert-info small" role="note">
                        <strong>{{ __('admin.social.fb_expected_title') }}</strong>
                        @if (! empty($expected['page_id'])) <div>{{ __('admin.social.fb_field_page_id') }}: {{ $expected['page_id'] }}</div> @endif
                        @if (! empty($expected['page_url'])) <div class="text-break">{{ __('admin.social.fb_field_page_url') }}: {{ $expected['page_url'] }}</div> @endif
                        @if (! empty($expected['business_id'])) <div>{{ __('admin.social.fb_field_business_id') }}: {{ $expected['business_id'] }} ({{ __('admin.social.fb_business_not_verified') }})</div> @endif
                    </div>
                @endif

                @if ($current)
                    <div class="alert alert-warning" role="alert">
                        <p class="mb-2">{{ __('admin.social.fb_current_page', ['name' => $current->name, 'id' => $current->external_account_id]) }}</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="replace" value="1" id="fb-replace">
                            <label class="form-check-label" for="fb-replace">{{ __('admin.social.fb_replace') }}</label>
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 1%"></th>
                                <th>{{ __('admin.social.fb_page') }}</th>
                                <th>{{ __('admin.social.fb_page_id') }}</th>
                                <th>{{ __('admin.social.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pages as $page)
                                @php $selectable = $page['eligible'] && $page['allowed']; @endphp
                                <tr class="{{ $selectable ? '' : 'text-muted' }}">
                                    <td>
                                        <input type="radio" class="form-check-input" name="page_id" value="{{ $page['id'] }}" id="fb-page-{{ $page['id'] }}"
                                            @disabled(! $selectable) @required(true) aria-label="{{ $page['name'] }}">
                                    </td>
                                    <td>
                                        <label class="d-flex align-items-center gap-3 mb-0" for="fb-page-{{ $page['id'] }}">
                                            @if ($page['picture_url'])
                                                <img src="{{ $page['picture_url'] }}" alt="" width="48" height="48" class="rounded border" style="object-fit: cover" referrerpolicy="no-referrer" loading="lazy">
                                            @endif
                                            <span>
                                                <span class="fw-bold d-block">{{ $page['name'] }}</span>
                                                <a href="{{ $page['link'] }}" target="_blank" rel="noopener noreferrer" class="small text-break">{{ $page['link'] }}</a>
                                            </span>
                                        </label>
                                    </td>
                                    <td class="text-break">{{ $page['id'] }}</td>
                                    <td>
                                        @if ($page['eligible'] && $page['allowed'])
                                            <span class="badge bg-label-success">{{ __('admin.social.fb_permission_ok') }}</span>
                                        @elseif (! $page['allowed'])
                                            <span class="badge bg-label-secondary">{{ __('admin.social.fb_not_expected') }}</span>
                                        @else
                                            <span class="badge bg-label-danger">{{ __('admin.social.fb_permission_missing') }}</span>
                                            <div class="small">{{ __($reasons[$page['reason']] ?? 'admin.social.fb_reason_no_role') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'accounts') }}" class="btn btn-outline-secondary">{{ __('admin.social.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.social.fb_confirm') }}</button>
            </div>
        </div>
    </form>
@endsection
