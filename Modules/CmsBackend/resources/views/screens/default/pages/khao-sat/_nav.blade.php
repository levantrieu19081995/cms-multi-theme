{{-- Thanh điều hướng trong một khảo sát: Cấu hình / Phản hồi / Thống kê. Chỉ hiện mục mà người dùng có quyền. $active: edit|responses|statistics --}}
@php
    $user = auth()->user();
    $permMap = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user());
    $perm = fn (string $slug) => (bool) ($permMap[$slug] ?? false);
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ $route('cms.khao-sat') }}" class="text-muted"><i class="ri-arrow-left-line" aria-hidden="true"></i> {{ __('admin.survey.title') }}</a>
        <h3 class="mb-0 mt-1">{{ $survey->ks_ten }} <span class="badge bg-{{ $survey->status()->badge() }} fs-6 align-middle">{{ $survey->status()->label() }}</span></h3>
    </div>
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link {{ $active === 'edit' ? 'active' : '' }}" href="{{ $route('cms.khao-sat.edit', $survey->ks_id) }}">{{ __('admin.survey.edit') }}</a></li>
        @if ($perm('khaosat-response-view') || $perm('khaosat-view'))
            <li class="nav-item"><a class="nav-link {{ $active === 'responses' ? 'active' : '' }}" href="{{ $route('cms.khao-sat.responses', $survey->ks_id) }}">{{ __('admin.survey.responses') }}</a></li>
        @endif
        @if ($perm('khaosat-statistics-view'))
            <li class="nav-item"><a class="nav-link {{ $active === 'statistics' ? 'active' : '' }}" href="{{ $route('cms.khao-sat.statistics', $survey->ks_id) }}">{{ __('admin.survey.statistics') }}</a></li>
        @endif
    </ul>
</div>
