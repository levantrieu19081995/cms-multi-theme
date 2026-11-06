{{--
    Canonical and alternate-language links of the page, shared by every public theme (plan: Phase 13).
    hreflang lists only languages in which this very page really exists (LocalizedUrlResolver::counterpart), so an untranslated
    article never advertises an English URL. x-default is the default-language version.
--}}
@php
    $seoLocales = app(\App\Services\LocaleManager::class);
    $seoResolver = app(\App\Services\LocalizedUrlResolver::class);
    $seoAlternates = [];
    foreach ($seoLocales->supported() as $seoCode => $seoLabel) {
        $seoUrl = $seoCode === $seoLocales->current() ? url()->current() : $seoResolver->counterpart($seoCode);
        if ($seoUrl) {
            $seoAlternates[config('locales.html_lang.'.$seoCode, $seoCode)] = $seoUrl;
        }
    }
    $seoDefaultUrl = $seoAlternates[config('locales.html_lang.'.$seoLocales->default(), $seoLocales->default())] ?? null;
@endphp
<link rel="canonical" href="{{ url()->current() }}">
@if (count($seoAlternates) > 1)
    @foreach ($seoAlternates as $seoHreflang => $seoHref)
        <link rel="alternate" hreflang="{{ $seoHreflang }}" href="{{ $seoHref }}">
    @endforeach
    @if ($seoDefaultUrl)
        <link rel="alternate" hreflang="x-default" href="{{ $seoDefaultUrl }}">
    @endif
@endif
@foreach ($seoAlternates as $seoHreflang => $seoHref)
    @if ($seoHreflang !== $seoLocales->current())
        <meta property="og:locale:alternate" content="{{ config('locales.og_locale.'.$seoHreflang, $seoHreflang) }}">
    @endif
@endforeach
