{{-- Which project this screen works on. Everything below (posts, history, accounts, figures) belongs to it. At the right: the guides. --}}
<div class="alert alert-secondary d-flex flex-wrap align-items-center gap-2 py-2 mb-4" role="status">
    <i class="ri-building-line" aria-hidden="true"></i>
    <span>{{ __('admin.social.current_project') }}:</span>
    <strong>{{ \Modules\CmsBackend\Support\ProjectContext::label() }}</strong>
    <span class="text-muted small">
        {{ \Modules\CmsBackend\Support\ProjectContext::fixed() ? __('admin.social.project_fixed') : __('admin.social.project_follows_theme') }}
    </span>
    @if (! ($hideGuideLink ?? false) && \Modules\CmsBackend\Services\Social\SocialPermissions::can(auth()->user(), \Modules\CmsBackend\Enums\PermissionsEnum::SOCIAL_PUBLICATION_VIEW))
        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.guides.index') }}" class="btn btn-sm btn-outline-secondary bg-white ms-auto"
            title="{{ __('admin.social.guides_link') }}">
            <i class="ri-book-open-line me-1" aria-hidden="true"></i>{{ __('admin.social.guides_link') }}
        </a>
    @endif
</div>
