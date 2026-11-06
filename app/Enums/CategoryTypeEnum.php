<?php

namespace App\Enums;

enum CategoryTypeEnum: string
{
    case CONG_BO = 'cong_bo';
    case NOI_BO = 'noi_bo';

    public function label(): string
    {
        return match ($this) {
            self::CONG_BO => 'Tin công bố',
            self::NOI_BO => 'Tin nội bộ',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => $case, self::cases());
    }
}
