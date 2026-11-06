<?php

namespace App\Enums;

enum FileTypeEnum: string
{
    case PDF = 'application/pdf';
    case DOC = 'application/msword';
    case XLSX = 'application/xlsx';
    public function label(): string
    {
        return match ($this) {
            static::PDF => 'PDF',
            static::DOC => 'Word',
            static::XLSX => 'Excel - xlsx',

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