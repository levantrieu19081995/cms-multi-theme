<?php

namespace App\Enums;

enum UserStatusEnum: int
{
  case ACTIVE = 1;
  case INACTIVE = 0;

  public function label(): string
    {
        return match ($this) {
            static::ACTIVE => 'Hoạt động',
            static::INACTIVE => 'Không hoạt động'
        };
    }
}
