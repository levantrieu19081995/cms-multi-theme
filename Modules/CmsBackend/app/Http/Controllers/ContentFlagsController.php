<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\DataProviders\PostDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Two small switches shown in content lists: "show on home page" and "allow comments".
 * Routes api.update-homepage / api.update-commentpage keep their old names and addresses because the list scripts read them
 * from window.cmsUrls (see screens/default/layout.blade.php).
 */
class ContentFlagsController extends Controller
{
    public function __construct(private readonly PostDataProvider $posts)
    {
    }

    public function toggleShowHomePage(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:posts,id']);
        $post = $this->posts->findByIdSimple((int) $request->input("id"));
        $post->show_home_page = $post->show_home_page == 1 ? 0 : 1;
        $post->save();

        return response()->json(['message' => __('admin.js.cap_nhat_thanh_cong')]);
    }

    public function toggleAllowComment(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:posts,id']);
        $post = $this->posts->findByIdSimple((int) $request->input("id"));
        $post->comment_status = $post->comment_status == 'published' ? 'published_stop' : 'published';
        $post->save();

        return response()->json(['message' => __('admin.message.cap_nhat_thanh_cong')]);
    }
}
