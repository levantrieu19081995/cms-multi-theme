<?php

namespace Modules\CmsBackend\Enums;

/**
 * Kiểu câu hỏi. 'radio' là giá trị của nền cũ và được hiểu là Single.
 */
enum SurveyQuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case Text = 'text';
    case Textarea = 'textarea';
    case Scale = 'scale';
    case YesNo = 'yesno';

    public static function fromStored(?string $value): self
    {
        return $value === 'radio' ? self::Single : (self::tryFrom((string) $value) ?? self::Single);
    }

    public function label(): string
    {
        return __('admin.survey.type.'.$this->value);
    }

    /** Có danh sách lựa chọn trong ks_tra_loi. */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Single, self::Multiple], true);
    }

    public function isFreeText(): bool
    {
        return in_array($this, [self::Text, self::Textarea], true);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
