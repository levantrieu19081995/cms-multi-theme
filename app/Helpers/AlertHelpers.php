<?php

namespace App\Helpers;

class AlertHelpers
{
    public static function alert($message, $type = 'info')
    {
        if ($message) {
          $data = [];
          if (session()->has('alerts')) {
              // $data = session('alerts');
              $data[$type][] = $message;
          } else {
              $data[$type][] = $message;
          }
          session()->flash('alerts', $data);
          session()->reflash();
        }
    }

    public static function danger($message)
    {
        self::alert($message, 'danger');
    }

    public static function warning($message)
    {
        self::alert($message, 'warning');
    }

    public static function info($message)
    {
        self::alert($message, 'info');
    }

    public static function success($message)
    {
        self::alert($message, 'success');
    }

    public static function primary($message)
    {
        self::alert($message, 'primary');
    }

    public static function secondary($message)
    {
        self::alert($message, 'secondary');
    }
}
