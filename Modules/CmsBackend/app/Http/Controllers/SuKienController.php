<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Enums\PostStatus;
use App\Enums\PostType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Diglactic\Breadcrumbs\Breadcrumbs;

class SuKienController extends EditorialPostController
{
    protected const TYPE = 'event';
    protected const RESOURCE = 'cms.dashboard.su-kien';

    public function indexFrontPage(Request $request)
    {
        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $filter = trim(strip_tags($request->input('filter', '')));
        $filterDuration = $request->input('filter_duration', '');

        $query = \App\Models\Post::query()
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::EVENT->value);

        if ($filter) {
            $query->where('title', 'like', '%' . $filter . '%');
        }

        // Lọc theo thời gian
        $now = Carbon::now();
        switch ($filterDuration) {
            case 'today':
                $query->whereDate('published_at', '<=', $now->toDateString())
                    ->whereDate('publication_date_expires', '>=', $now->toDateString());
                break;
            case 'tomorrow':
                $tomorrow = $now->copy()->addDay()->toDateString();
                $query->whereDate('published_at', '<=', $tomorrow)
                    ->whereDate('publication_date_expires', '>=', $tomorrow);
                break;
            case 'this_week':
                $start = $now->startOfWeek()->toDateString();
                $end = $now->endOfWeek()->toDateString();
                $query->whereDate('published_at', '<=', $end)
                    ->whereDate('publication_date_expires', '>=', $start);
                break;
            case 'next_week':
                $start = $now->copy()->addWeek()->startOfWeek()->toDateString();
                $end = $now->copy()->addWeek()->endOfWeek()->toDateString();
                $query->whereDate('published_at', '<=', $end)
                    ->whereDate('publication_date_expires', '>=', $start);
                break;
            case 'this_month':
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                $query->whereDate('published_at', '<=', $end)
                    ->whereDate('publication_date_expires', '>=', $start);
                break;
            case 'next_month':
                $start = $now->copy()->addMonth()->startOfMonth()->toDateString();
                $end = $now->copy()->addMonth()->endOfMonth()->toDateString();
                $query->whereDate('published_at', '<=', $end)
                    ->whereDate('publication_date_expires', '>=', $start);
                break;
        }

        $posts = $query->orderBy('published_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        return view('cmsfrontend::screens.pages.frontend.su-kien.index', [
            'posts' => $posts,
            'breadcrumbs' => Breadcrumbs::render('cms.dashboard.su-kien'),
        ]);
    }
}
