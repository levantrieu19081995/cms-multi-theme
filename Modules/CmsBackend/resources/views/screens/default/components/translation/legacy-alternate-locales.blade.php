{{--
    Other-language fields for the older post screens (pages, banners, image libraries...). Their Vietnamese fields keep the
    original top-level names; every additional language is posted as translations[{locale}][field] and saved by
    PostTranslationWriter in the same transaction as the post. Same rules as the bilingual editorial form:
    shared image/categories/status, no auto-translation, no auto-publishing.
    Expects: $post.
--}}
@php
    $localeManager = app(\App\Services\LocaleManager::class);
    $alternates = $localeManager->alternates();
@endphp
@foreach ($alternates as $code)
    @php
        $label = $localeManager->supported()[$code];
        $value = fn (string $field) => old("translations.$code.$field", $post->exists ? $post->translation($code)?->getAttribute($field) : null);
    @endphp
    <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 card-title">{{ $label }}</h5>
            <span class="badge bg-label-secondary">{{ $post->exists && $post->hasTranslation($code) ? __('admin.ui.co_noi_dung') : __('admin.ui.chua_nhap') }}</span>
        </div>
        <div class="card-body" lang="{{ config('locales.html_lang.'.$code, $code) }}">
            <p class="text-muted small">{{ __('admin.ui.lang_note_legacy', ['label' => $label]) }}</p>
            <div class="form-floating form-floating-outline mb-5">
                <input type="text" class="form-control" id="title-{{ $code }}" name="translations[{{ $code }}][title]" maxlength="255" value="{{ $value('title') }}" placeholder="Title">
                <label for="title-{{ $code }}">Title</label>
                <x-input-error :messages="$errors->get('translations.'.$code.'.title')" class="mt-2" />
            </div>
            <div class="form-floating form-floating-outline mb-5">
                <input type="text" class="form-control" id="slug-{{ $code }}" name="translations[{{ $code }}][slug]" maxlength="255" value="{{ $value('slug') }}" placeholder="slug">
                <label for="slug-{{ $code }}">Slug</label>
                <x-input-error :messages="$errors->get('translations.'.$code.'.slug')" class="mt-2" />
            </div>
            <div class="form-floating form-floating-outline mb-5">
                <textarea class="form-control h-px-100" id="excerpt-{{ $code }}" name="translations[{{ $code }}][excerpt]" maxlength="1000" placeholder="Excerpt">{{ $value('excerpt') }}</textarea>
                <label for="excerpt-{{ $code }}">Excerpt</label>
            </div>
            <label class="form-label" for="ckeditor5-{{ $code }}">Content</label>
            <textarea id="ckeditor5-{{ $code }}" data-cms-editor name="translations[{{ $code }}][content]" rows="7">{{ \Modules\CmsBackend\Support\PublicContent::forEditor($value('content')) }}</textarea>
            <x-input-error :messages="$errors->get('translations.'.$code.'.content')" class="mt-2" />
            <div class="row mt-5">
                <div class="col-md-6"><div class="form-floating form-floating-outline"><input type="text" class="form-control" id="seo-title-{{ $code }}" name="translations[{{ $code }}][seo_title]" maxlength="255" value="{{ $value('seo_title') }}" placeholder="SEO title"><label for="seo-title-{{ $code }}">SEO title</label></div></div>
                <div class="col-md-6"><div class="form-floating form-floating-outline"><input type="text" class="form-control" id="seo-description-{{ $code }}" name="translations[{{ $code }}][seo_description]" maxlength="500" value="{{ $value('seo_description') }}" placeholder="SEO description"><label for="seo-description-{{ $code }}">SEO description</label></div></div>
            </div>
        </div>
    </div>
@endforeach
