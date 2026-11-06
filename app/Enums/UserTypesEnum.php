<?php

namespace App\Enums;

enum UserTypesEnum: int
{
  case USER = 1;
  case MEMBER = 2;

  public function label(): string
    {
        return match ($this) {
            static::USER => 'Người dùng',
            static::MEMBER => 'Thành viên'
        };
    }
}
