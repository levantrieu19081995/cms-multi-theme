<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Support\SurveyCluster;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Giữ mỗi khảo sát đúng cụm của nó: khảo sát loại career chỉ mở được ở cụm Định hướng nghề nghiệp, các loại khác chỉ ở cụm Khảo sát.
 * Mở nhầm cụm bằng GET (liên kết/dấu trang cũ) thì chuyển sang cùng màn của cụm đúng; thao tác ghi nhầm cụm trả 404.
 * Chạy sau SubstituteBindings nên route đã có mô hình {survey} / {participation}.
 */
final class SurveyClusterGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $survey = $request->route('survey');
        $participation = $request->route('participation');
        if ($participation instanceof KhaoSatUser) {
            $survey = $participation->khaoSat;
        }
        if (! $survey instanceof KhaoSat) {
            return $next($request);   // danh sách, tạo mới, màn nhóm & ngành: không gắn với một khảo sát cụ thể
        }

        $current = SurveyCluster::current($request);
        $owner = SurveyCluster::of($survey);
        if ($owner === $current) {
            return $next($request);
        }
        if ($request->isMethod('GET')) {
            $name = SurveyCluster::name((string) $request->route()->getName(), $owner);
            if (Route::has($name)) {
                return redirect()->route($name, $request->route()->parameters() + $request->query());
            }
        }

        abort(404);
    }
}
