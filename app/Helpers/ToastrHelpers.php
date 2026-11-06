<?php

namespace App\Helpers;

class ToastrHelpers
{
    public static function toast($message, $type = 'info')
    {
        if ($message) {
          $data = [];
          if (session()->has('toastrs')) {
              $data[$type][] = $message;
          } else {
              $data[$type][] = $message;
          }
          session()->flash('toastrs', $data);
          session()->reflash();
        }
    }

    public static function warning($message)
    {
        self::toast($message, 'warning');
    }

    public static function info($message)
    {
        self::toast($message, 'info');
    }

    public static function success($message)
    {
        self::toast($message, 'success');
    }

    public static function primary($message)
    {
        self::toast($message, 'primary');
    }

    public static function secondary($message)
    {
        self::toast($message, 'secondary');
    }
}
