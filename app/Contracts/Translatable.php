<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A record whose administrator-entered text lives in a translation table instead of on the record itself.
 * Implemented through App\Models\Concerns\HasTranslations.
 */
interface Translatable
{
    /** @return HasMany<Model> */
    public function translations(): HasMany;

    public function translation(string $locale): ?Model;

    public function currentTranslation(): ?Model;

    /** @return class-string<Model> */
    public function translationModel(): string;

    /** @return string[] columns of the translation row that hold translated content */
    public function translatableFields(): array;

    /** Column that must be filled for a translation to count as present (title / name). */
    public function translationRequiredField(): string;
}
