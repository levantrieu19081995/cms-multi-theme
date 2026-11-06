<?php

namespace Modules\CmsBackend\Enums;

/**
 * Trạng thái khảo sát: Nháp → Đang mở ⇄ Tạm dừng → Kết thúc. Không có đường quay về Nháp.
 */
enum SurveyStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paused = 'paused';
    case Ended = 'ended';

    public function label(): string
    {
        return __('admin.survey.status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Open => 'success',
            self::Paused => 'warning',
            self::Ended => 'dark',
        };
    }

    /** @return self[] */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Open],
            self::Open => [self::Paused, self::Ended],
            self::Paused => [self::Open, self::Ended],
            self::Ended => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    /** Nháp: sửa toàn bộ. Đang mở/Tạm dừng: chỉ thông tin chung. Kết thúc: chỉ xem. */
    public function allowsStructureEdit(): bool
    {
        return $this === self::Draft;
    }

    public function allowsInfoEdit(): bool
    {
        return $this !== self::Ended;
    }

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Draft;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
