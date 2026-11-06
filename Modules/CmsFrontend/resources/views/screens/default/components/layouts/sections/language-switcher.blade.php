@php
    $localeManager = app(\App\Services\LocaleManager::class);
    $urlResolver = app(\App\Services\LocalizedUrlResolver::class);
    $currentLocale = $localeManager->current();
@endphp
@if (count($localeManager->supported()) > 1)
{{-- Language switcher shared by every public theme: each link goes to the same content in that language (LocalizedUrlResolver),
     or to a "content is being updated" notice when it has no version there. Themes only restyle .cms-lang-switcher. --}}
<nav class="cms-lang-switcher" aria-label="{{ __('pages.ngon_ngu') }}">
    @foreach ($localeManager->supported() as $code => $label)
        <a href="{{ $urlResolver->urlFor($code) }}" hreflang="{{ config('locales.html_lang.'.$code, $code) }}" lang="{{ config('locales.html_lang.'.$code, $code) }}"
           @if ($code === $currentLocale) aria-current="true" @endif title="{{ $label }}">{{ strtoupper($code) }}</a>
    @endforeach
</nav>
<style>
    .cms-lang-switcher{display:inline-flex;align-items:center;gap:2px;margin-inline:8px;font-size:.8rem;font-weight:600;line-height:1}
    .cms-lang-switcher a{padding:5px 8px;border-radius:5px;color:inherit;text-decoration:none;opacity:.72}
    .cms-lang-switcher a:hover{opacity:1}
    .cms-lang-switcher a[aria-current]{opacity:1;box-shadow:inset 0 -2px 0 currentColor}
</style>
@endif
