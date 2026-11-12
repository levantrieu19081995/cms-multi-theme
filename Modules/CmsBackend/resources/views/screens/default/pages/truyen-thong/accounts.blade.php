@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.accounts_title'))

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.accounts') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    {{-- One page, one column per platform: the connection card of each. (The figures of a channel are on "Tổng quan - thống kê".) --}}
    <div class="row g-4">
        <div class="col-12 col-xl-6" id="social-column-facebook">
            @include('cmsbackend::screens.pages.truyen-thong.partials.account-facebook')
        </div>

        <div class="col-12 col-xl-6" id="social-column-zalo">
            @include('cmsbackend::screens.pages.truyen-thong.partials.account-zalo')
        </div>
    </div>
@endsection
