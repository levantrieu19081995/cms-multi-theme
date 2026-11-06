@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.detail_title'))

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $editable = $publication->status->editable();
    $archived = $publication->status === \Modules\CmsBackend\Enums\Social\SocialPublicationStatus::ARCHIVED;
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.publications.show') }}

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-primary text-white">
            <h5 class="mb-0 text-white text-uppercase">{{ __('admin.social.detail_title') }}</h5>
            <span class="badge {{ $publication->status->badge() }} fs-6">{{ $publication->status->label() }}</span>
        </div>
        <div class="card-body mt-4">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <h4 class="mb-2">{{ $publication->title }}</h4>
                    <p class="text-break" style="white-space: pre-line">{{ $publication->message }}</p>
                    <p class="mb-0"><a href="{{ $publication->public_url }}" target="_blank" rel="noopener noreferrer" class="text-break">{{ $publication->public_url }}</a></p>
                </div>
                <div class="col-12 col-lg-5">
                    @if ($publication->image_url)
                        <img src="{{ $publication->image_url }}" alt="" class="img-fluid rounded border" loading="lazy">
                    @else
                        <div class="text-muted small">{{ __('admin.social.preview_no_image') }}</div>
                    @endif
                </div>
            </div>

            <hr class="my-4">

            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('admin.social.field_source_post') }}</dt>
                <dd class="col-sm-9">
                    @if ($publication->post)
                        {{ $publication->post->title }} <span class="text-muted">(#{{ $publication->post_id }})</span>
                    @elseif ($publication->post_id)
                        <span class="text-warning">{{ __('admin.social.source_post_deleted') }}</span> <span class="text-muted">(#{{ $publication->post_id }})</span>
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-sm-3">{{ __('admin.social.field_locale') }}</dt>
                <dd class="col-sm-9">{{ strtoupper($publication->locale) }}</dd>
                <dt class="col-sm-3">{{ __('admin.social.field_project') }}</dt>
                <dd class="col-sm-9">{{ \Modules\CmsBackend\Support\ProjectContext::label($publication->project_key) }}</dd>
                <dt class="col-sm-3">{{ __('admin.social.col_creator') }}</dt>
                <dd class="col-sm-9">{{ $publication->creator?->name ?? '—' }} <span class="text-muted">· {{ $publication->created_at?->format('d/m/Y H:i') }}</span></dd>
                <dt class="col-sm-3">{{ __('admin.social.field_updated_by') }}</dt>
                <dd class="col-sm-9">{{ $publication->updater?->name ?? '—' }} <span class="text-muted">· {{ $publication->updated_at?->format('d/m/Y H:i') }}</span></dd>
                @if ($publication->archived_at)
                    <dt class="col-sm-3">{{ __('admin.social.field_archived_at') }}</dt>
                    <dd class="col-sm-9">{{ $publication->archived_at->format('d/m/Y H:i') }}</dd>
                @endif
            </dl>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.index') }}" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1" aria-hidden="true"></i>{{ __('admin.social.back_to_list') }}</a>
            @if ($can['social-publication-create'])
                <div class="d-flex flex-wrap gap-2">
                    @if ($editable)
                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.edit', $publication->id) }}" class="btn btn-primary"><i class="ri-edit-box-line me-1" aria-hidden="true"></i>{{ __('admin.social.action_edit') }}</a>
                        <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.archive', $publication->id) }}" onsubmit="return confirm(@js(__('admin.social.confirm_archive')));">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary"><i class="ri-archive-line me-1" aria-hidden="true"></i>{{ __('admin.social.action_archive') }}</button>
                        </form>
                    @elseif ($archived)
                        <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'publications.restore', $publication->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary"><i class="ri-inbox-unarchive-line me-1" aria-hidden="true"></i>{{ __('admin.social.action_restore') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <h5 class="card-header text-uppercase">{{ __('admin.social.deliveries_title') }}</h5>
        <div class="card-body">
            <div class="alert alert-info" role="note">{{ __('admin.social.phase1_no_send_note') }}</div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr class="text-nowrap">
                            <th>{{ __('admin.social.col_channel') }}</th>
                            <th>{{ __('admin.social.col_account') }}</th>
                            <th>{{ __('admin.social.col_status') }}</th>
                            <th>{{ __('admin.social.col_attempts') }}</th>
                            <th>{{ __('admin.social.col_result') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($publication->channels as $delivery)
                            <tr>
                                <td class="text-nowrap"><i class="{{ $delivery->channel->icon() }} me-1" aria-hidden="true"></i>{{ $delivery->channel->label() }}</td>
                                <td>{{ $delivery->account?->name ?? __('admin.social.not_connected') }}</td>
                                <td><span class="badge {{ $delivery->status->badge() }}">{{ $delivery->status->label() }}</span></td>
                                <td>{{ $delivery->attempts }}</td>
                                <td class="small">
                                    @if ($delivery->remote_url)
                                        <a href="{{ $delivery->remote_url }}" target="_blank" rel="noopener noreferrer">{{ $delivery->remote_url }}</a>
                                    @endif
                                    @if ($delivery->error_message)
                                        <div class="text-danger">{{ $delivery->error_message }}</div>
                                    @endif
                                    @if ($delivery->published_at)
                                        <div class="text-muted">{{ $delivery->published_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('admin.social.no_channel_selected') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
