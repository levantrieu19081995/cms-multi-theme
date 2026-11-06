<?php

namespace App\Enums;

enum PostType: string
{
    case POST = 'post';
    case PAGE = 'page';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case BANNER = 'banner';
    case EVENT = 'event';
    public function label(): string
    {
        return match ($this) {
            static::POST => 'Bài viết',
            static::PAGE => 'Trang',
            static::IMAGE => 'Thư viện ảnh',
            static::VIDEO => 'Video',
            static::BANNER => 'Banner',
            static::EVENT => 'event',

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