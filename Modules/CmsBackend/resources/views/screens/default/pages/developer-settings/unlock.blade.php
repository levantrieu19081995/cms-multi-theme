@extends('cmsbackend::screens.pages.developer-settings.layout')

@section('title', __('admin.developer.title'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1"><i class="ri-lock-password-line me-2" aria-hidden="true"></i>{{ __('admin.developer.unlock_title') }}</h4>
                    <p class="text-muted small">{{ __('admin.developer.unlock_note') }}</p>

                    @unless ($configured)
                        <div class="alert alert-warning" role="alert">{{ __('admin.developer.unlock_not_configured') }}</div>
                    @endunless

                    <form method="post" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings.unlock.store') }}" autocomplete="off">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="dev-token">{{ __('admin.developer.unlock_token') }}</label>
                            <input id="dev-token" type="password" name="token" class="form-control @error('token') is-invalid @enderror" maxlength="512" autocomplete="off" required autofocus @disabled(! $configured)>
                            @error('token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" @disabled(! $configured)>{{ __('admin.developer.unlock') }}</button>
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}" class="btn btn-outline-secondary">{{ __('admin.developer.back') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
