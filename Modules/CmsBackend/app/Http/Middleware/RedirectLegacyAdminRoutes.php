<?php

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Old shared-framework screens (trang, banner, lien-ket) were replaced by the CmsBackend module screens at /cms/<function>.
 * Their old GET pages send the visitor to the canonical address (temporary redirect); other methods are untouched.
 */
class RedirectLegacyAdminRoutes
{
    private const MAP = ['trang' => 'trang', 'banner' => 'banner', 'lien-ket' => 'lien-ket'];

    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }
        $name = (string) ($request->route()?->getName() ?? '');
        [$resource, $suffix] = array_pad(explode('.', $name, 2), 2, '');
        if (isset(self::MAP[$resource]) && in_array($suffix, ['index', 'create', 'edit', 'show'], true)) {
            $parameter = $request->route()->parameters();

            // Giữ query (vd. ?mode=select&state=… của cửa sổ chọn trang, bộ lọc, phân trang) khi chuyển sang địa chỉ chuẩn.
            $query = $request->getQueryString();

            return redirect()->to(ModuleUrl::route('cms.dashboard.cdn.'.self::MAP[$resource].'.'.$suffix, array_values($parameter)).($query ? '?'.$query : ''), 302);
        }

        return $next($request);
    }
}
