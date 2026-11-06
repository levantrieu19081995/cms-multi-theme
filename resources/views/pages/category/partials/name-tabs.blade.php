{{--
    Category name per language. Tiếng Việt posts translations[vi][name] (the request copies it to `name`, so terms.name and the
    existing rules keep working); every other language is optional and is stored only in term_translations.
    Parent, type and description are shared by all languages. Expects: $term (App\Models\Term|null).
--}}
@php
    $localeManager = app(\App\Services\LocaleManager::class);
    $defaultLocale = $localeManager->default();
    $tabs = $localeManager->supported();
    $term = $term ?? null;
    $errorLocales = collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'translations.'))->map(fn ($key) => explode('.', $key)[1] ?? null)->filter();
    $activeLocale = $errorLocales->first() ?? $defaultLocale;
    $value = function (string $locale, string $field) use ($term, $defaultLocale) {
        $stored = $locale === $defaultLocale && $field === 'name'
            ? $term?->name
            : ($term?->exists ? $term->translation($locale)?->getAttribute($field) : null);

        return old("translations.$locale.$field", $locale === $defaultLocale && $field === 'name' ? old('name', $stored) : $stored);
    };
@endphp
<div class="mt-6 mb-6">
    <ul class="nav nav-tabs mb-4" role="tablist" aria-label="Ngôn ngữ">
        @foreach ($tabs as $code => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if($code === $activeLocale) active @endif" type="button" role="tab" id="category-lang-tab-{{ $code }}" data-bs-toggle="tab" data-bs-target="#category-lang-pane-{{ $code }}" aria-controls="category-lang-pane-{{ $code }}" aria-selected="{{ $code === $activeLocale ? 'true' : 'false' }}">{{ $label }}</button>
            </li>
        @endforeach
    </ul>
    <div class="tab-content p-0">
        @foreach ($tabs as $code => $label)
            @php $isDefault = $code === $defaultLocale; @endphp
            <div class="tab-pane fade @if($code === $activeLocale) show active @endif" id="category-lang-pane-{{ $code }}" role="tabpanel" aria-labelledby="category-lang-tab-{{ $code }}" lang="{{ config('locales.html_lang.'.$code, $code) }}">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="{{ $isDefault ? 'name' : 'name-'.$code }}" name="translations[{{ $code }}][name]"
                        placeholder="{{ $isDefault ? __('Tên chuyên mục') : 'Category name' }}" autocomplete="off" value="{{ $value($code, 'name') }}" @if($isDefault) required @endif>
                    <label for="{{ $isDefault ? 'name' : 'name-'.$code }}">{{ $isDefault ? __('Tên chuyên mục') : 'Category name' }}</label>
                    <x-input-error :messages="$errors->get($isDefault ? 'name' : 'translations.'.$code.'.name')" class="mt-2" />
                </div>
                @unless ($isDefault)
                    <div class="form-floating form-floating-outline mb-2">
                        <input type="text" class="form-control" id="slug-{{ $code }}" name="translations[{{ $code }}][slug]" maxlength="255" autocomplete="off" placeholder="slug" value="{{ $value($code, 'slug') }}">
                        <label for="slug-{{ $code }}">Slug ({{ $label }})</label>
                        <x-input-error :messages="$errors->get('translations.'.$code.'.slug')" class="mt-2" />
                    </div>
                    <p class="text-muted small mb-0">Để trống toàn bộ nếu chưa có bản {{ $label }}. Đường dẫn bỏ trống sẽ tự tạo từ tên; hệ thống không tự dịch.</p>
                @endunless
            </div>
        @endforeach
    </div>
</div>
