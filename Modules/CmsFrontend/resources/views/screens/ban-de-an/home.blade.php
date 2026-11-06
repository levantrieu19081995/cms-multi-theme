@extends('cmsfrontend::screens.layout')
@section('title', __('pages.trang_chu'))
@section('description', __('pages.cong_thong_tin_ban_de_an_cac_de_an_du_an_hoat_do'))
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
