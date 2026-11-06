@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', $question['title'])
@section('description', __('pages.chi_tiet_cau_hoi_va_tra_loi'))

@section('page-style')
    @vite([
        'Modules/CmsFrontend/resources/assets/css/banner.css',
        'Modules/CmsFrontend/resources/assets/sass/app.scss'
    ])
@endsection

@section('page-script')
    @vite(['Modules/CmsFrontend/resources/assets/js/app.js'])
@endsection

@section('content')
    <div class="cms-shell-container py-5">
        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.index') }}" class="btn btn-outline-primary mb-4">{{ __('frontend.ui.quay_lai_hoi_dap') }}</a>
        <article class="card p-5">
            <h1 class="h3">{{ $question['title'] }}</h1>
            <p class="text-muted">{{ $question['name'] ?? __('pages.nguoi_gui_an_danh') }}</p>
            <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $question['content'] }}</div>
            @if($question['answer'])
                <hr class="my-4">
                <h2 class="h5">{{ __('frontend.ui.tra_loi') }}</h2>
                <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $question['answer'] }}</div>
            @endif
        </article>
    </div>
@endsection
