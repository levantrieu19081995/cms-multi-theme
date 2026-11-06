<?php

namespace App\Enums;

enum CommentStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    public function label(): string
    {
        return match ($this) {
            static::OPEN => 'Mở',
            static::CLOSED => 'Đóng',
            static::PENDING => 'Chờ duyệt',
            static::APPROVED => 'Đã duyệt',
            static::REJECTED => 'Không duyệt',
        
        };
    }
}