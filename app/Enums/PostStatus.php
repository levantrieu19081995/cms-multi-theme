<?php

namespace App\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case STOP_PUBLISHING = 'stop_publishing';
    case APPROVED = 'approved';
    case PENDING_APPROVAL = 'pending_approval';
    case NOT_APPROVED = 'not_approved';

    public function label(): string
    {
        return match ($this) {
            static::DRAFT => __('admin.status.draft'),
            static::PUBLISHED => __('admin.status.published'),
            static::STOP_PUBLISHING => __('admin.status.stop_publishing'),
            static::PENDING_APPROVAL => __('admin.status.pending_approval'),
            static::APPROVED => __('admin.status.approved'),
            static::NOT_APPROVED => __('admin.status.not_approved'),
        };
    }

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
