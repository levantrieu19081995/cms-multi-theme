<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Post;
use App\Services\LocaleManager;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\CmsBackend\Support\PublicContent;

/**
 * One place that turns the bilingual post form into rows of post_translations, for every controller that edits a Post
 * (editorial workflow for news/events, the legacy post controllers for pages, banners, image libraries...).
 *
 * Transition rules (kept until Phase 15, because the public site still reads the old columns):
 *  - the default locale (vi) is written to post_translations AND stays in posts.title/slug/excerpt/content;
 *  - every other locale (en) is written to post_translations only, never to `posts`.
 *
 * The caller owns the transaction and decides *when* content may be written (permissions, workflow action);
 * this class only reads `translations[locale][field]` from the request.
 */
final class PostTranslationWriter
{
    /** Post columns that mirror the default locale. */
    private const LEGACY_FIELDS = ['title', 'slug', 'excerpt', 'content'];

    public function __construct(private LocaleManager $locales, private TranslationService $translations) {}

    /**
     * Validation for the translation inputs. Vietnamese title/slug/excerpt/content keep their original rules (they are
     * validated as the legacy top-level fields); only SEO of the default locale and the whole of the other locales are added here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'translations' => ['nullable', 'array'],
        ];
        $default = $this->locales->default();
        $rules["translations.$default.seo_title"] = ['nullable', 'string', 'max:255'];
        $rules["translations.$default.seo_description"] = ['nullable', 'string', 'max:500'];
        foreach ($this->locales->alternates() as $locale) {
            $others = array_map(fn ($field) => "translations.$locale.$field", ['slug', 'excerpt', 'content', 'seo_title', 'seo_description']);
            // A translation needs a title as soon as any other field of that language is filled in.
            $rules["translations.$locale.title"] = ['nullable', 'string', 'max:255', 'required_with:'.implode(',', $others)];
            $rules["translations.$locale.slug"] = ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'];
            $rules["translations.$locale.excerpt"] = ['nullable', 'string', 'max:1000'];
            $rules["translations.$locale.content"] = ['nullable', 'string'];
            $rules["translations.$locale.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$locale.seo_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /** For controllers without their own validator (legacy post controllers). */
    public function validate(Request $request): void
    {
        Validator::make($request->all(), $this->rules(), [
            'required_with' => __('admin.message.nhap_tieu_de_cua_ngon_ngu_nay_khi_da_nhap_cac_tr'),
            'regex' => __('admin.message.duong_dan_chi_chua_chu_khong_dau_so_va_dau_gach_'),
        ])->validate();
    }

    /**
     * Forms post the default language as translations[vi][title|slug|excerpt|content]. The existing workflow and its
     * validation know the top-level names, so copy them over (top-level input, when present, wins).
     */
    public function useDefaultLocaleInput(Request $request): void
    {
        $default = (array) $request->input('translations.'.$this->locales->default(), []);
        $merge = [];
        foreach (self::LEGACY_FIELDS as $field) {
            if (array_key_exists($field, $default) && ! $request->has($field)) {
                $merge[$field] = $default[$field];
            }
        }
        $merge !== [] && $request->merge($merge);
    }

    /**
     * Persist all locales of a saved post. Call it after the post row has been saved, inside the same transaction.
     * No-op for a post that does not exist yet.
     */
    public function save(Post $post, Request $request): void
    {
        if (! $post->exists) {
            return;
        }
        $input = (array) $request->input('translations', []);
        $default = $this->locales->default();

        // Default locale: copy of what the original columns hold now, plus its own SEO fields when the form sent them.
        $payload = [$default => array_intersect_key($post->only(self::LEGACY_FIELDS), array_flip(self::LEGACY_FIELDS))];
        foreach (['seo_title', 'seo_description'] as $field) {
            if (array_key_exists($field, (array) ($input[$default] ?? []))) {
                $payload[$default][$field] = $input[$default][$field];
            }
        }

        foreach ($this->locales->alternates() as $locale) {
            if (! isset($input[$locale]) || ! is_array($input[$locale])) {
                continue; // form without that tab: leave the stored translation alone
            }
            $values = $input[$locale];
            if (array_key_exists('content', $values)) {
                $values['content'] = PublicContent::forStorage($values['content']);
            }
            $payload[$locale] = $values;
        }

        $this->translations->savePostTranslations($post, $payload);
    }
}
