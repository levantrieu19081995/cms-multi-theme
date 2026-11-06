{{--
    Bilingual content fields of a Post (title, slug, excerpt, content, SEO) in one card with a tab per language.
    Everything else on the form (image, categories, author, dates, status, workflow) is shared by all languages.

    Input names: translations[{locale}][title|slug|excerpt|content|seo_title|seo_description].
    The default language (vi) keeps the element ids the existing scripts rely on (#title, #slug, #excerpt, #ckeditor5);
    its title/slug/excerpt/content are still written to the original posts columns by the workflow (transition until Phase 15).
    Expects: $post, $editable, $isEvent, $editorContent.
--}}
@php
    $localeManager = app(\App\Services\LocaleManager::class);
    $defaultLocale = $localeManager->default();
    $tabs = $localeManager->supported();
    $initialErrors = collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'translations.'))->map(fn ($key) => explode('.', $key)[1] ?? null)->filter()->unique()->values();
    $activeLocale = $initialErrors->first() ?? $defaultLocale;
    $value = function (string $locale, string $field) use ($post, $defaultLocale) {
        // The default language is still held by the original columns; it only owns SEO in post_translations.
        $stored = ($locale === $defaultLocale && $field !== 'seo_title' && $field !== 'seo_description')
            ? $post->getAttribute($field)
            : $post->translation($locale)?->getAttribute($field);

        return old("translations.$locale.$field", $stored);
    };
    $filled = fn (string $locale) => $post->exists && $post->hasTranslation($locale);
@endphp
<section class="cms-field-card cms-lang-card">
    @if (count($tabs) > 1)
    <ul class="nav nav-tabs cms-lang-tabs" role="tablist" aria-label="{{ __('admin.ui.ngon_ngu_noi_dung') }}">
        @foreach ($tabs as $code => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if($code === $activeLocale) active @endif" id="cms-lang-tab-{{ $code }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#cms-lang-pane-{{ $code }}" aria-controls="cms-lang-pane-{{ $code }}" aria-selected="{{ $code === $activeLocale ? 'true' : 'false' }}">
                    {{ $label }}
                    @if ($code !== $defaultLocale)<span class="cms-lang-state @if($filled($code)) is-filled @endif">{{ $filled($code) ? __('admin.ui.co_noi_dung') : __('admin.ui.chua_nhap') }}</span>@endif
                </button>
            </li>
        @endforeach
    </ul>
    @endif
    <div class="tab-content cms-lang-panes">
        @foreach ($tabs as $code => $label)
            @php
                $isDefault = $code === $defaultLocale;
                $suffix = $isDefault ? '' : '-'.$code;
            @endphp
            <div class="tab-pane fade @if($code === $activeLocale) show active @endif" id="cms-lang-pane-{{ $code }}" role="tabpanel" aria-labelledby="cms-lang-tab-{{ $code }}" tabindex="0" lang="{{ config('locales.html_lang.'.$code, $code) }}">
                @unless ($isDefault)
                    <p class="cms-help cms-lang-note">{{ __('admin.ui.lang_note_shared', ['label' => $label]) }}</p>
                @endunless

                <label for="title{{ $suffix }}">{{ __('admin.ui.tieu_de') }} @if($isDefault)<span class="cms-required">*</span>@endif</label>
                <input id="title{{ $suffix }}" class="form-control" name="translations[{{ $code }}][title]" maxlength="255" @if($isDefault) required @endif placeholder="{{ $isDefault ? __('admin.ui.nhap_tieu_de_bai_viet') : 'Enter the title...' }}" data-cms-counter="cms-title-count{{ $suffix }}" value="{{ $value($code, 'title') }}">
                <span class="cms-counter" id="cms-title-count{{ $suffix }}">{{ mb_strlen((string) $value($code, 'title')) }}/255</span>
                @error("translations.$code.title")<p class="text-danger small mt-1">{{ $message }}</p>@enderror

                <label for="slug{{ $suffix }}" class="mt-3">{{ __('admin.ui.duong_dan') }}</label>
                <div class="cms-slug-wrap"><input id="slug{{ $suffix }}" class="form-control" name="translations[{{ $code }}][slug]" maxlength="255" @if($isDefault) data-new-post="{{ $post->exists ? 'false' : 'true' }}" @endif value="{{ $value($code, 'slug') }}" placeholder="{{ __('admin.ui.tu_tao_tu_tieu_de_bo_trong_de_he_thong_tu_tao') }}"><i class="ri-link" aria-hidden="true"></i></div>
                <p class="cms-help">{{ __('admin.ui.slug_help') }} @unless($isDefault){{ __('admin.ui.slug_help_independent') }}@endunless</p>
                @error("translations.$code.slug")<p class="text-danger small mt-1">{{ $message }}</p>@enderror

                <label for="excerpt{{ $suffix }}" class="mt-3">{{ $isEvent ? ($isDefault ? __('admin.ui.dia_diem') : 'Location') : ($isDefault ? __('admin.ui.trich_dan') : 'Excerpt') }} @if($isDefault)<span class="cms-required">*</span>@endif</label>
                <textarea id="excerpt{{ $suffix }}" class="form-control" name="translations[{{ $code }}][excerpt]" rows="2" maxlength="1000" data-cms-counter="cms-excerpt-count{{ $suffix }}" placeholder="{{ $isDefault ? ($isEvent ? __('admin.ui.nhap_dia_diem_to_chuc_su_kien') : __('admin.ui.nhap_trich_dan_ngan_gon_cho_bai_viet')) : 'Short summary...' }}">{{ $value($code, 'excerpt') }}</textarea>
                <span class="cms-counter" id="cms-excerpt-count{{ $suffix }}">{{ mb_strlen((string) $value($code, 'excerpt')) }}/1000</span>

                <label for="{{ $isDefault ? 'ckeditor5' : 'ckeditor5-'.$code }}" class="mt-3">{{ $isDefault ? __('admin.ui.noi_dung') : 'Content' }} @if($isDefault)<span class="cms-required">*</span>@endif</label>
                @if ($editable)
                    <textarea id="{{ $isDefault ? 'ckeditor5' : 'ckeditor5-'.$code }}" class="form-control" @unless($isDefault) data-cms-editor @endunless name="translations[{{ $code }}][content]" rows="7" placeholder="{{ $isDefault ? __('admin.ui.nhap_noi_dung_bai_viet') : 'Enter the content...' }}">{{ $isDefault ? $editorContent : \Modules\CmsBackend\Support\PublicContent::forEditor($value($code, 'content')) }}</textarea>
                @else
                    @include('cmsbackend::screens.components.editorial.content-preview', ['previewHeight' => $isEvent ? 320 : 240, 'previewSource' => $isDefault ? null : ($post->translation($code)?->content ?? '')])
                @endif

                <details class="cms-lang-seo mt-3">
                    <summary>SEO</summary>
                    <label for="seo-title{{ $suffix }}" class="mt-2">SEO title</label>
                    <input id="seo-title{{ $suffix }}" class="form-control" name="translations[{{ $code }}][seo_title]" maxlength="255" value="{{ $value($code, 'seo_title') }}">
                    <label for="seo-description{{ $suffix }}" class="mt-2">SEO description</label>
                    <textarea id="seo-description{{ $suffix }}" class="form-control" name="translations[{{ $code }}][seo_description]" rows="2" maxlength="500">{{ $value($code, 'seo_description') }}</textarea>
                </details>
            </div>
        @endforeach
    </div>
</section>
<style>
    .cms-lang-tabs{margin-bottom:14px;border-bottom:1px solid var(--bs-border-color,#dee2e6)}
    .cms-lang-tabs .nav-link{display:inline-flex;align-items:center;gap:8px}
    .cms-lang-state{font-size:.72rem;font-weight:500;padding:1px 8px;border-radius:999px;background:#eef1f4;color:#5b6b78}
    .cms-lang-state.is-filled{background:#e3f1e8;color:#1f6b3a}
    .cms-lang-note{margin-bottom:12px}
    .cms-lang-seo summary{cursor:pointer;font-weight:500}
</style>
