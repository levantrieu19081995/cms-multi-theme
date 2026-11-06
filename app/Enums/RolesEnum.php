<?php

namespace App\Enums;

enum RolesEnum: String
{
  case ADMINISTRATOR = 'administrator';
  case USER = 'user';
  case MEMBER = 'member';
  case WRITER = 'writer';
  case PUBLISHER = 'publisher';

  public function label(): string
  {
      return match ($this) {
          static::ADMINISTRATOR => 'Quản trị',
          static::USER => 'Người dùng',
          static::MEMBER => 'Thành viên',
          static::WRITER => 'Người viết bài',
          static::PUBLISHER => 'Người duyệt bài'
      };
  }
}
