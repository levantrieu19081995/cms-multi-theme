<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\LocaleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Makes every query of a translatable model (Post, Term, MenuItem) language-aware on a public page in another language,
 * without touching the controllers or the theme views:
 *
 *  1. the translation of that language is eager-loaded together with the model;
 *  2. models without a filled translation are left out (Post, MenuItem), so Vietnamese content never stands in for it;
 *  3. after the query, title/slug/excerpt/content (name/slug for a term, title/url for a menu item) are replaced in memory
 *     by the translated values, so views keep reading $post->title, $post->slug... and build URLs with the right slug.
 *
 * It is inactive for Vietnamese pages, the administration, console and queue contexts, where the original columns remain the
 * source (they mirror the Vietnamese translation during the transition). When `locales.read_default_from_translations` is on, the
 * default-language public pages read the translation too (LocaleManager::overlayLocale()), but gently: nothing is hidden, and a
 * record without a Vietnamese translation row keeps its original columns.
 *
 * Models carry the marker `_public_overlay`; saving such a model is refused, so a translated copy can never overwrite the originals.
 */
final class PublicLocaleScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $locales = app(LocaleManager::class);
        $locale = $locales->overlayLocale();
        if ($locale === null) {
            return;
        }
        $isDefault = $locales->isDefault($locale);

        $builder->with(['translations' => fn ($translations) => $translations->where('locale', $locale)]);

        if (! $isDefault && $model->publicRequiresTranslation()) {
            $required = $model->translationRequiredField();
            $builder->whereHas('translations', fn ($translations) => $translations
                ->where('locale', $locale)->whereNotNull($required)->where($required, '!=', ''));
        }

        $builder->afterQuery(function ($result) use ($locale, $isDefault) {
            $models = $result instanceof Model ? [$result] : (is_iterable($result) ? $result : []);
            foreach ($models as $item) {
                if (! ($item instanceof Model) || ! method_exists($item, 'applyPublicTranslation') || ($item->getAttributes()['_public_overlay'] ?? null)) {
                    continue;
                }
                $translation = $item->relationLoaded('translations') ? $item->getRelation('translations')->firstWhere('locale', $locale) : null;
                if ($isDefault && $translation === null) {
                    continue; // default language, no translation row yet: the original columns are still right
                }
                $item->applyPublicTranslation($translation);
                $item->setAttribute('_public_overlay', $locale);
            }

            return $result;
        });
    }
}
