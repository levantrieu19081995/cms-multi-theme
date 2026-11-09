@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', \Modules\CmsBackend\Support\SurveyCluster::isCareer() ? __('career.public.list_title') : __('survey.list.title'))
@section('head')
<style>.sv-card h2 a { color: inherit; text-decoration: none; } .sv-card h2 a:hover { text-decoration: underline; } .sv-card p { margin: 0 0 12px; }</style>
@endsection
@section('content')
<h1>{{ \Modules\CmsBackend\Support\SurveyCluster::isCareer() ? __('career.public.list_title') : __('survey.list.title') }}</h1>
<p class="sv-muted">{{ \Modules\CmsBackend\Support\SurveyCluster::isCareer() ? __('career.public.list_subtitle') : __('survey.list.subtitle') }}</p>
@forelse ($surveys as $survey)
    <article class="sv-card">
        <h2><a href="{{ \Modules\CmsBackend\Support\SurveyCluster::route('cms.frontend.khaosat.show', $survey->ks_ma) }}">{{ $survey->ks_ten }}</a></h2>
        @if (filled($survey->ks_mo_ta_ngan))<p>{{ $survey->ks_mo_ta_ngan }}</p>@endif
        @if ($survey->ks_ket_thuc)<p class="sv-muted">{{ __('survey.list.closes', ['time' => $survey->ks_ket_thuc->format('H:i d/m/Y')]) }}</p>@endif
        <a class="sv-btn" href="{{ \Modules\CmsBackend\Support\SurveyCluster::route('cms.frontend.khaosat.show', $survey->ks_ma) }}">{{ __('survey.list.join') }}</a>
    </article>
@empty
    <div class="sv-card" role="status"><p class="sv-muted">{{ \Modules\CmsBackend\Support\SurveyCluster::isCareer() ? __('career.public.list_empty') : __('survey.list.empty') }}</p></div>
@endforelse
@endsection
