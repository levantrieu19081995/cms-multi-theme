<?php

namespace App\Enums;

enum PostFormat: string
{
    case DEFAULT = 'default';
    case VIDEO = 'video';
    case IMAGE = 'image';
    public function label(): string
    {
        return match ($this) {
            static::DEFAULT => 'Mặc định',
            static::VIDEO => 'Video',
            static::IMAGE => 'Hình ảnh',
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
