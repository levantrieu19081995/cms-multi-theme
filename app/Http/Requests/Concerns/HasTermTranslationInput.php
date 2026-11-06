<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Services\LocaleManager;

/**
 * Category/tag forms post the names per language as translations[{locale}][name|slug]. The default language keeps feeding
 * the original top-level `name` rule (and terms.name); every other language gets its own optional rules here.
 */
trait HasTermTranslationInput
{
    protected function prepareForValidation(): void
    {
        $default = app(LocaleManager::class)->default();
        $name = $this->input("translations.$default.name");
        if ($name !== null && ! $this->has('name')) {
            $this->merge(['name' => $name]);
        }
    }

    /** @return array<string, mixed> */
    protected function termTranslationRules(): array
    {
        $rules = ['translations' => ['nullable', 'array']];
        foreach (app(LocaleManager::class)->alternates() as $locale) {
            $rules["translations.$locale.name"] = ['nullable', 'string', 'max:255', "required_with:translations.$locale.slug"];
            $rules["translations.$locale.slug"] = ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    protected function termTranslationMessages(): array
    {
        return [
            'translations.*.name.required_with' => 'Nhập tên của ngôn ngữ này khi đã nhập đường dẫn.',
            'translations.*.slug.regex' => 'Đường dẫn chỉ chứa chữ không dấu, số và dấu gạch ngang.',
        ];
    }
}
