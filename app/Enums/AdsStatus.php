<?php

namespace App\Enums;

enum AdsStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    public function label(): string
    {
        return match ($this) {
            static::ACTIVE => 'Đang hoạt động',
            static::INACTIVE => 'Chưa hoạt động',
            static::EXPIRED => 'Hết hạn',
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
