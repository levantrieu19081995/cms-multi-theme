@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', __('pages.video_khuyen_nong'))
@section('description', __('pages.video_ky_thuat_hoat_dong_khuyen_nong_cua_trung_t'))
@section('keywords', __('pages.video_khuyen_nong_video_ky_thuat_nong_nghiep_khu'))

@section('content')
    {{-- Video list: cards open the video page (player there), see VideoController::danhSach. --}}
    <main class="cms-page-container cms-video-page" id="main-content">
        <nav class="cms-video-crumb" aria-label="Breadcrumb">
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}">{{ __('frontend.ui.trang_chu') }}</a><span aria-hidden="true">›</span>
            <span aria-current="page">{{ __('frontend.ui.video_khuyen_nong') }}</span>
        </nav>
        <h1 class="cms-video-heading">{{ __('frontend.ui.video_khuyen_nong') }}</h1>
        @if ($videos->count())
            @include('cmsfrontend::screens.pages.frontend.video.cards', ['cards' => $videos->items()])
            <div class="cms-video-pages">
                {{ $videos->withQueryString()->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
            </div>
        @else
            <p class="cms-video-empty">{{ __('frontend.ui.chua_co_video_duoc_cong_bo') }}</p>
        @endif
    </main>
@endsection
