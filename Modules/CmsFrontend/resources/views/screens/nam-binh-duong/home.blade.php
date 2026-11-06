@extends('cmsfrontend::screens.layout')
@section('title', __('pages.trang_chu'))
@section('description', __('pages.truong_dai_hoc_cong_nghe_nam_binh_duong_nbdut_kh'))
@section('page-style')
{{-- Effect assets are shared (built in every profile); the page loads only those the enabled blocks declare (Homepage Layout Manager). --}}
@if (in_array('crossfade-slider', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/crossfade-slider/style.css')
@endif
@if (in_array('gallery-orbit', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/gallery-orbit/style.css')
@endif
@endsection

@section('page-script')
@if (in_array('crossfade-slider', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/auto/crossfade-slider.js')
@endif
@if (in_array('gallery-orbit', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/auto/gallery-orbit.js')
@endif
@endsection

@section('content')
    {!! $homeContentHtml !!}
@endsection
