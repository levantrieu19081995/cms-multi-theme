@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.title'))

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.index') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.social.title') }}</h5>
        <div class="card-body mt-4">
            <div class="alert alert-warning mb-0" role="alert">
                <h6 class="alert-heading mb-1">{{ __('admin.social.not_installed_title') }}</h6>
                <p class="mb-0">{{ __('admin.social.not_installed_text') }}</p>
            </div>
        </div>
    </div>
@endsection
