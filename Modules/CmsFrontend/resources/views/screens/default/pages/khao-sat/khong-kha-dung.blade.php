@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', __('survey.state.'.$state.'.title'))
@section('content')
<article class="sv-card" role="status">
    <h1>{{ __('survey.state.'.$state.'.title') }}</h1>
    @if ($survey)<p><strong>{{ $survey->ks_ten }}</strong></p>@endif
    <p class="sv-muted">{{ __('survey.state.'.$state.'.text', ['time' => $survey?->ks_bat_dau?->format('H:i d/m/Y') ?? '']) }}</p>
    <a class="sv-btn sv-btn-ghost" href="{{ url('/') }}">{{ __('survey.state.back') }}</a>
</article>
@endsection
