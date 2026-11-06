{{-- Which project this screen works on. Everything below (posts, history, accounts, figures) belongs to it. --}}
<div class="alert alert-secondary d-flex flex-wrap align-items-center gap-2 py-2 mb-4" role="status">
    <i class="ri-building-line" aria-hidden="true"></i>
    <span>{{ __('admin.social.current_project') }}:</span>
    <strong>{{ \Modules\CmsBackend\Support\ProjectContext::label() }}</strong>
    <span class="text-muted small">
        {{ \Modules\CmsBackend\Support\ProjectContext::fixed() ? __('admin.social.project_fixed') : __('admin.social.project_follows_theme') }}
    </span>
</div>
