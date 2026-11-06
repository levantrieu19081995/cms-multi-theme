@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', __('survey.thanks.title'))
@section('content')
<article class="sv-card" role="status">
    <h1>{{ __('survey.thanks.title') }}</h1>
    <p>{{ $survey->ks_noi_dung_hoan_thanh ?: __('survey.thanks.default') }}</p>
    <a class="sv-btn sv-btn-ghost" href="{{ url('/') }}">{{ __('survey.thanks.back') }}</a>
</article>
@endsection
