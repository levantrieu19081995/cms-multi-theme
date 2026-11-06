<?php

namespace App\Enums;

enum PublishType: string
{
    case PUBLISH_NOW = 'publish_now';
    case SCHEDULE_PUBLICATION = 'schedule_publication';
    public function label(): string
    {
        return match ($this) {
            static::PUBLISH_NOW => 'Xuất bản sau khi được duyệt',
            static::SCHEDULE_PUBLICATION => 'Lên lịch xuất bản'
        };
    }
    // Hàm trả về danh sách object cho select option
    public static function toObject(): array
    {
        return array_map(function ($case) {
            return (object)[
                'value' => $case->value, // Giá trị của enum
                'label' => $case->label(), // Nhãn tương ứng với enum
            ];
        }, self::cases());
    }
}