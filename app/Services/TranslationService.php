<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Translatable;
use App\Models\MenuItem;
use App\Models\OptionTranslation;
use App\Models\Post;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes administrator-entered translations. Controllers hand over `translations[locale][field]` input and never touch
 * the translation tables themselves. The service does not translate anything and never publishes anything: status,
 * approval and visibility stay on the original record.
 *
 * Per locale: a payload with every field blank deletes that locale's row ("not entered"); otherwise the row is created or
 * updated. Slugs are generated from the title when empty and kept unique per locale. Locales that are not supported are ignored.
 */
final class TranslationService
{
    public function __construct(private LocaleManager $locales) {}

    /** @param array<string, array<string, mixed>> $translations locale => [field => value] */
    public function savePostTranslations(Post $post, array $translations): void
    {
        $this->saveFor($post, $translations);
    }

    /** @param array<string, array<string, mixed>> $translations */
    public function saveTermTranslations(Term $term, array $translations): void
    {
        $this->saveFor($term, $translations);
    }

    /** @param array<string, array<string, mixed>> $translations */
    public function saveMenuItemTranslations(MenuItem $item, array $translations): void
    {
        $this->saveFor($item, $translations);
    }

    /**
     * Text options (homepage texts, unit information...). The language-independent value stays in `options`.
     *
     * @param  array<string, string|null>  $values locale => text
     */
    public function saveOptionTranslations(string $optionKey, array $values): void
    {
        DB::transaction(function () use ($optionKey, $values) {
            DB::afterCommit(fn () => $this->locales->bumpCacheVersion());
            foreach ($this->locales->supported() as $locale => $label) {
                if (! array_key_exists($locale, $values)) {
                    continue;
                }
                $value = trim((string) $values[$locale]);
                $query = OptionTranslation::where('option_key', $optionKey)->where('locale', $locale);
                if ($value === '') {
                    $query->delete();

                    continue;
                }
                OptionTranslation::updateOrCreate(['option_key' => $optionKey, 'locale' => $locale], ['value' => $value]);
            }
        });
    }

    /** @param array<string, array<string, mixed>> $translations */
    public function saveFor(Translatable&Model $owner, array $translations): void
    {
        DB::transaction(function () use ($owner, $translations) {
            DB::afterCommit(fn () => $this->locales->bumpCacheVersion());
            foreach ($this->locales->supported() as $locale => $label) {
                if (! isset($translations[$locale]) || ! is_array($translations[$locale])) {
                    continue;
                }
                $values = $this->clean($owner, $translations[$locale]);
                $existing = $owner->translations()->where('locale', $locale)->first();

                if ($this->isBlank($values)) {
                    $existing?->delete();

                    continue;
                }
                if (array_key_exists('slug', $values) && $values['slug'] === null) {
                    $values['slug'] = $this->slugFrom($values[$owner->translationRequiredField()] ?? null);
                }
                if (array_key_exists('slug', $values) && $values['slug'] !== null) {
                    $values['slug'] = $this->uniqueSlug($owner, $locale, $values['slug'], $existing);
                }

                $existing ? $existing->update($values) : $owner->translations()->create($values + ['locale' => $locale]);
            }
            $owner->unsetRelation('translations');
        });
    }

    /** @return array<string, string|null> only the owner's translatable fields, trimmed, blank as null */
    private function clean(Translatable $owner, array $input): array
    {
        $values = [];
        foreach ($owner->translatableFields() as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }
            $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            $values[$field] = $value === '' ? null : $value;
        }

        return $values;
    }

    private function isBlank(array $values): bool
    {
        return array_filter($values, fn ($value) => $value !== null && $value !== '') === [];
    }

    private function slugFrom(?string $text): ?string
    {
        $slug = Str::slug((string) $text);

        return $slug === '' ? null : mb_substr($slug, 0, 240);
    }

    /** unique(locale, slug) across the translation table: "slug", "slug-2", "slug-3"... excluding this owner's own row. */
    private function uniqueSlug(Translatable&Model $owner, string $locale, string $slug, ?Model $existing): string
    {
        $table = $owner->translationModel();
        $candidate = $slug;
        $n = 2;
        while ($table::query()->where('locale', $locale)->where('slug', $candidate)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->getKey()))->exists()) {
            $candidate = mb_substr($slug, 0, 240).'-'.$n++;
        }

        return $candidate;
    }
}
