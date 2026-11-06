<?php

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Features\FeatureRegistry;

/**
 * cms.feature:<mã chức năng> — chặn route khi chức năng đang tắt trong Feature Registry (404, không tiết lộ chức năng tồn tại).
 * Dùng cho cả route quản trị lẫn công khai; không phụ thuộc việc menu có ẩn hay không. Mã không có definition cũng trả 404.
 */
class RequireFeature
{
    public function __construct(private FeatureRegistry $features) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        if (! $this->features->isEnabled($feature)) {
            abort(404);
        }

        return $next($request);
    }
}
