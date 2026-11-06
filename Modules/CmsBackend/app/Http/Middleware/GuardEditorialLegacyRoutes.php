<?php

namespace Modules\CmsBackend\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Support\ModuleUrl;

/** Prevent old shared article endpoints from bypassing CMS moderation. Installed only for the selected CMS project. */
class GuardEditorialLegacyRoutes
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()?->getName() ?? '';
        if (str_starts_with($name, 'bai-viet.')) {
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) abort(403, __('admin.message.dung_man_hinh_tin_tuc_khuyen_nong_de_xu_ly_theo_'));
            $suffix = substr($name, strlen('bai-viet.'));
            if (in_array($suffix, ['index', 'create', 'edit', 'show'], true)) {
                $id = $request->route('bai_viet');
                // Giữ query (vd. ?mode=select&state=… của cửa sổ chọn bài viết, bộ lọc, phân trang).
                $query = $request->getQueryString();

                return redirect(ModuleUrl::route('cms.dashboard.cdn.bai-viet.'.$suffix, $id ? [$id] : []).($query ? '?'.$query : ''));
            }
        }
        $id = $request->input('post_id') ?? $request->input('postId') ?? $request->input('id');
        if ($id && Post::whereKey($id)->whereIn('post_type', ['post', 'event'])->exists()) abort(403, __('admin.message.thay_doi_tin_su_kien_tu_man_hinh_quy_trinh_khuye'));
        return $next($request);
    }
}
