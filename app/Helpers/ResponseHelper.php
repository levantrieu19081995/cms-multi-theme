<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function responseJson($code, $status, $message, $data = [], $error = null)
    {
        return response()->json([
            'code'    => $code,
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
            'error'   => $error,
        ], $code);
    }
}
