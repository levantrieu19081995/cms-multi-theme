@extends('cmsfrontend::screens.layout')

@section('title', __('pages.trang_chu_khuyen_nong_can_tho'))
@section('description', __('pages.cong_thong_tin_trung_tam_khuyen_nong_va_dich_vu_'))

@section('page-style')
    @vite('Modules/CmsFrontend/resources/assets/css/screens/default/home.css')
{{-- Effect assets are shared (built in every profile); the page loads only those the enabled blocks declare (Homepage Layout Manager). --}}
@if (in_array('crossfade-slider', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/crossfade-slider/style.css')
@endif
@if (in_array('gallery-orbit', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/gallery-orbit/style.css')
@endif
@endsection

@section('page-script')
@if (in_array('reveal', $homepageEffectEntries ?? [], true))
    @vite('Modules/CmsFrontend/resources/assets/effects/auto/reveal.js')
@endif
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
