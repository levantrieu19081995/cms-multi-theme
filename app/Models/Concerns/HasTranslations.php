<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\LocaleManager;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared translation behaviour for Post, Term and MenuItem.
 *
 * The model using it supplies translationModel(), translationForeignKey(), translatableFields() and
 * translationRequiredField(). A translation is "present" for a locale when its required field (title / name) is filled.
 *
 * Fallback rule: the public site never substitutes one language for another. currentTranslation() returns only the
 * row of the active locale (null when missing, so the page can show "Nội dung đang cập nhật").
 * translationOrDefault() may fall back to the default locale and is meant for administration screens, where an
 * empty English tab should still show the Vietnamese text as reference.
 */
trait HasTranslations
{
    /** @return class-string<Model> */
    abstract public function translationModel(): string;

    /** Foreign key on the translation table pointing at this model. */
    abstract public function translationForeignKey(): string;

    /** @return string[] */
    abstract public function translatableFields(): array;

    abstract public function translationRequiredField(): string;

    /** @return HasMany<Model> */
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel(), $this->translationForeignKey(), $this->getKeyName());
    }

    /** Translation for one locale (reuses the loaded relation, otherwise one query). */
    public function translation(string $locale): ?Model
    {
        if ($this->relationLoaded('translations')) {
            return $this->getRelation('translations')->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    public function currentTranslation(): ?Model
    {
        return $this->translation(app(LocaleManager::class)->current());
    }

    /** Administration only: active locale, else the default locale. */
    public function translationOrDefault(?string $locale = null): ?Model
    {
        $locales = app(LocaleManager::class);

        return $this->translation($locale ?? $locales->current()) ?? $this->translation($locales->default());
    }

    public function hasTranslation(string $locale): bool
    {
        $translation = $this->translation($locale);

        return $translation !== null && trim((string) $translation->getAttribute($this->translationRequiredField())) !== '';
    }

    /** One translated field for a locale (null when that locale has no translation). */
    public function translated(string $field, ?string $locale = null): ?string
    {
        $translation = $this->translation($locale ?? app(LocaleManager::class)->current());

        return $translation?->getAttribute($field);
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations locale => [field => value]
     */
    public function saveTranslations(array $translations): void
    {
        app(TranslationService::class)->saveFor($this, $translations);
    }

    /** Eager-load only the active locale's translation. */
    public function scopeWithCurrentTranslation(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app(LocaleManager::class)->current();

        return $query->with(['translations' => fn ($translations) => $translations->where('locale', $locale)]);
    }

    /** Only records that have a filled translation (title / name) in the locale. */
    public function scopeWhereTranslationPublished(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app(LocaleManager::class)->current();
        $required = $this->translationRequiredField();

        return $query->whereHas('translations', fn ($translations) => $translations
            ->where('locale', $locale)->whereNotNull($required)->where($required, '!=', ''));
    }

    /** Records whose translation in the locale has this slug. */
    public function scopeWhereTranslationSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale ??= app(LocaleManager::class)->current();

        return $query->whereHas('translations', fn ($translations) => $translations->where('locale', $locale)->where('slug', $slug));
    }
}
