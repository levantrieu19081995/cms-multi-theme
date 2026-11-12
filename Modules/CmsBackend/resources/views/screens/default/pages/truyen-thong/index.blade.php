@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.publications_title'))

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.publications.index') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge', ['hideGuideLink' => true])

    <style>
        /* The two filter selects sit in a .form-floating wrapper (the shared list layout script needs it), but they must look
           like the search box beside them: white, outlined, same height, text vertically centred (no floating label). */
        #social-filter-status, #social-filter-channel {
            height: 40px; min-height: 40px; padding: .4rem 2.25rem .4rem .875rem;
            background-color: #fff; border: 1px solid #d4e0f3; border-radius: .5rem; box-shadow: none; color: #183864;
        }
        #social-filter-status:focus, #social-filter-channel:focus { border-color: #0879ff; box-shadow: 0 0 0 2px #0879ff26; }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-primary text-white">
            <h5 class="mb-0 text-white text-uppercase">{{ __('admin.social.publications_title') }}</h5>
            @if ($can['social-publication-create'])
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.create') }}" class="btn btn-sm btn-light"><i class="ri-add-line me-1" aria-hidden="true"></i>{{ __('admin.social.create_title') }}</a>
            @endif
        </div>
        <div class="card-body mt-4">
            <form method="get" class="mb-4">
                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div class="input-group input-group-merge bg-white">
                        <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="{{ __('admin.social.search_publication') }}" aria-label="{{ __('admin.social.search_publication') }}">
                    </div>
                    <div class="form-floating">
                        <select id="social-filter-status" name="status" class="form-select">
                            <option value="">{{ __('admin.social.all_statuses') }}</option>
                            @foreach (\Modules\CmsBackend\Enums\Social\SocialPublicationStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <label for="social-filter-status">{{ __('admin.social.col_status') }}</label>
                    </div>
                    <div class="form-floating">
                        <select id="social-filter-channel" name="channel" class="form-select">
                            <option value="">{{ __('admin.social.all_channels') }}</option>
                            @foreach (\Modules\CmsBackend\Enums\Social\SocialChannel::cases() as $channel)
                                <option value="{{ $channel->value }}" @selected($filters['channel'] === $channel->value)>{{ $channel->label() }}</option>
                            @endforeach
                        </select>
                        <label for="social-filter-channel">{{ __('admin.social.col_channels') }}</label>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary bg-white"><i class="ri-filter-3-line me-1" aria-hidden="true"></i>{{ __('admin.ui.search') }}</button>
                </div>
            </form>
            <p class="small text-muted">{{ __('admin.social.archived_hidden_note') }}</p>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr class="text-nowrap">
                            <th>{{ __('admin.social.col_post') }}</th>
                            <th>{{ __('admin.social.col_content') }}</th>
                            <th>{{ __('admin.social.col_channels') }}</th>
                            <th>{{ __('admin.social.col_status') }}</th>
                            <th>{{ __('admin.social.col_creator') }}</th>
                            <th>{{ __('admin.social.col_time') }}</th>
                            <th style="width: 1%">{{ __('admin.social.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($publications as $publication)
                            <tr>
                                <td class="small">
                                    <span class="text-muted">#{{ $publication->post_id ?? '—' }}</span>
                                    @if ($publication->post_id && ! $publication->post)
                                        <div class="text-warning">{{ __('admin.social.source_post_deleted') }}</div>
                                    @elseif ($publication->post)
                                        <div>{{ \Illuminate\Support\Str::limit((string) $publication->post->title, 50) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <a class="fw-bold" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.show', $publication->id) }}">{{ $publication->title }}</a>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($publication->message, 90) }}</div>
                                    <span class="badge bg-label-primary">{{ strtoupper($publication->locale) }}</span>
                                </td>
                                <td>@include('cmsbackend::screens.pages.truyen-thong.partials.deliveries', ['publication' => $publication])</td>
                                <td><span class="badge {{ $publication->status->badge() }}">{{ $publication->status->label() }}</span></td>
                                <td>{{ $publication->creator?->name }}</td>
                                <td class="text-nowrap small">{{ $publication->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 text-nowrap">
                                        <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="{{ __('admin.social.action_view') }}" aria-label="{{ __('admin.social.action_view') }}"
                                            href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.show', $publication->id) }}"><i class="ri-eye-line" aria-hidden="true"></i></a>
                                        @if ($can['social-publication-create'])
                                            @if ($publication->status->editable())
                                                <a class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="{{ __('admin.social.action_edit') }}" aria-label="{{ __('admin.social.action_edit') }}"
                                                    href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.edit', $publication->id) }}"><i class="ri-edit-box-line" aria-hidden="true"></i></a>
                                                <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.archive', $publication->id) }}"
                                                    onsubmit="return confirm(@js(__('admin.social.confirm_archive')));">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="{{ __('admin.social.action_archive') }}" aria-label="{{ __('admin.social.action_archive') }}"><i class="ri-archive-line" aria-hidden="true"></i></button>
                                                </form>
                                            @elseif ($publication->status === \Modules\CmsBackend\Enums\Social\SocialPublicationStatus::ARCHIVED)
                                                <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.restore', $publication->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="{{ __('admin.social.action_restore') }}" aria-label="{{ __('admin.social.action_restore') }}"><i class="ri-inbox-unarchive-line" aria-hidden="true"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('admin.social.empty_title') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer mt-4">
                {{ $publications->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
