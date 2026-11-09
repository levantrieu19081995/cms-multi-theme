@extends('cmsfrontend::screens.pages.khao-sat.layout')
@section('title', __('survey.thanks.title'))
@if (! empty($result))
    @section('head')
    <style>
        .sv-career-code { display: flex; gap: 10px; margin: 10px 0 4px; }
        .sv-career-code span { width: 56px; height: 56px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; color: #fff; background: var(--sv-primary); border-radius: var(--sv-radius); }
        .sv-career-code span:nth-child(2) { opacity: .82; } .sv-career-code span:nth-child(3) { opacity: .66; }
        .sv-career-row { display: grid; grid-template-columns: minmax(120px, 34%) 1fr 52px; gap: 10px; align-items: center; margin-bottom: 10px; }
        .sv-career-row .sv-bar { height: 12px; }
        .sv-career-row.is-top .sv-bar > span { background: var(--sv-primary); } .sv-career-row:not(.is-top) .sv-bar > span { background: var(--sv-muted); opacity: .55; }
        .sv-career-row .sv-pct { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        .sv-career-top { margin: 0 0 14px; } .sv-career-top h3 { margin: 0 0 2px; font-size: 1.05rem; }
        .sv-career-majors { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
        .sv-career-majors li { border: 1px solid var(--sv-border); border-radius: var(--sv-radius); padding: 12px 14px; }
        .sv-career-majors .sv-tag { display: inline-block; font-size: .8rem; font-weight: 700; padding: 1px 8px; border-radius: 99px; background: color-mix(in srgb, var(--sv-primary) 12%, #fff); color: var(--sv-primary); margin-left: 6px; }
        @media (max-width: 520px) { .sv-career-row { grid-template-columns: 1fr 52px; } .sv-career-row .sv-label { grid-column: 1 / -1; } }
    </style>
    @endsection
@endif
@section('content')
<article class="sv-card" role="status">
    <h1>{{ __('survey.thanks.title') }}</h1>
    <p>{{ $survey->ks_noi_dung_hoan_thanh ?: __('survey.thanks.default') }}</p>
    @if (empty($result))
        <a class="sv-btn sv-btn-ghost" href="{{ url('/') }}">{{ __('survey.thanks.back') }}</a>
    @endif
</article>
@if (! empty($result))
    @include('cmsfrontend::screens.pages.khao-sat._career-result', ['result' => $result])
    <p><a class="sv-btn sv-btn-ghost" href="{{ url('/') }}">{{ __('survey.thanks.back') }}</a></p>
@endif
@endsection
