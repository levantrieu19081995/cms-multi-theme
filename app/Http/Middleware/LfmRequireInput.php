<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * File manager (unisharp/laravel-filemanager): các địa chỉ thao tác trên tập tin cần tham số bắt buộc (tên ảnh, danh sách mục, kích thước...).
 * Thiếu tham số thì gói tự ném lỗi PHP (TypeError, "Undefined array key", FileNotFoundException) và trả 500; ở đây trả 400 với thông báo rõ ràng
 * trước khi vào controller của gói. Không đổi hành vi khi đủ tham số.
 */
final class LfmRequireInput
{
    /** @var array<string, list<string>> hậu tố tên route của gói (unisharp.lfm.*) => tham số bắt buộc */
    private const REQUIRED = [
        'getCrop' => ['img'],
        'getCropImage' => ['img', 'dataX', 'dataY', 'dataWidth', 'dataHeight'],
        'getNewCropImage' => ['img', 'dataX', 'dataY', 'dataWidth', 'dataHeight'],
        'getResize' => ['img'],
        'performResize' => ['img', 'dataWidth', 'dataHeight'],
        'performResizeNew' => ['img', 'dataWidth', 'dataHeight'],
        'getDelete' => ['items'],
        'move' => ['items'],
        'doMove' => ['items', 'goToFolder'],
        'getDownload' => ['file'],
        'getRename' => ['file', 'new_name'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $name = (string) $request->route()?->getName();
        $key = str_starts_with($name, 'unisharp.lfm.') ? substr($name, strlen('unisharp.lfm.')) : null;
        $missing = [];
        if ($key !== null && isset(self::REQUIRED[$key])) {
            foreach (self::REQUIRED[$key] as $field) {
                $value = $request->input($field);
                if ($value === null || $value === '' || $value === []) {
                    $missing[] = $field;
                }
            }
        }
        // Tải lên cần tệp trong trường `upload` (mảng hoặc một tệp).
        if ($key === 'upload' && ! $request->hasFile('upload')) {
            $missing[] = 'upload';
        }
        if ($missing === []) {
            return $next($request);
        }
        $message = 'Missing required input: '.implode(', ', $missing);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message, 'missing' => $missing], 400);
        }

        return response($message, 400);
    }
}
