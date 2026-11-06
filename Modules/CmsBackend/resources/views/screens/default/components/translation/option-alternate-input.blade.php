{{--
    Text of a site option in the other languages (option_translations). The default language stays in the option itself.
    Posted as option_translations[{key}][{locale}]; blank = no text in that language (nothing is borrowed from Vietnamese).
    Expects: $key (option key), $label.
--}}
@php
    $optionLocales = app(\App\Services\LocaleManager::class);
    $optionTexts = app(\App\Services\OptionTranslations::class)->forKey($key);
@endphp
@foreach ($optionLocales->alternates() as $optionLocale)
    <div class="mt-2">
        <input type="text" name="option_translations[{{ $key }}][{{ $optionLocale }}]" class="form-control" maxlength="500" autocomplete="off"
            lang="{{ config('locales.html_lang.'.$optionLocale, $optionLocale) }}"
            placeholder="{{ $label }} ({{ $optionLocales->supported()[$optionLocale] }})"
            aria-label="{{ $label }} ({{ $optionLocales->supported()[$optionLocale] }})"
            value="{{ old('option_translations.'.$key.'.'.$optionLocale, $optionTexts[$optionLocale] ?? '') }}">
        <x-input-error :messages="$errors->get('option_translations.'.$key.'.'.$optionLocale)" class="mt-2" />
    </div>
@endforeach
