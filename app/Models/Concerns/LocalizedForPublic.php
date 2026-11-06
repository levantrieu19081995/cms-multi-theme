<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\PublicLocaleScope;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Opt-in for models that use HasTranslations: public pages in another language read the translation (see PublicLocaleScope).
 *
 * The model describes how: publicOverlayMap() = attribute => translation column, publicRequiresTranslation() = hide records
 * that have no filled translation. applyPublicTranslation() can be overridden for special cases (menu item address, SEO texts).
 */
trait LocalizedForPublic
{
    public static function bootLocalizedForPublic(): void
    {
        static::addGlobalScope(new PublicLocaleScope());

        // A copy that holds translated text in the original attributes must never be written back.
        static::saving(function (Model $model) {
            if ($model->getAttributes()['_public_overlay'] ?? null) {
                throw new LogicException('A model carrying translated public content cannot be saved.');
            }
        });
    }

    /** @return array<string, string> model attribute => translation column */
    abstract public function publicOverlayMap(): array;

    /** Hide records without a filled translation on pages in another language. */
    public function publicRequiresTranslation(): bool
    {
        return true;
    }

    /** Replace the original texts by the translated ones, in memory only. A missing translation blanks the texts (never Vietnamese). */
    public function applyPublicTranslation(?Model $translation): void
    {
        foreach ($this->publicOverlayMap() as $attribute => $column) {
            $value = $translation?->getAttribute($column);
            $this->setAttribute($attribute, $value === null ? '' : $value);
        }
    }

    /** The value stored in the original column (Vietnamese), for code that matches records by it. */
    public function originalColumn(string $attribute): mixed
    {
        return $this->getRawOriginal($attribute);
    }
}
