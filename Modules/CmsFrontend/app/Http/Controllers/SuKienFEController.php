<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Enums\CommentStatus;
use Carbon\Carbon;
use App\Enums\PostType;
use App\Enums\PostStatus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Diglactic\Breadcrumbs\Breadcrumbs;

class SuKienFEController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $filter = trim(strip_tags($request->input('filter', '')));
        $filterDuration = $request->input('filter_duration', '');

        $query = \Modules\CmsFrontend\Services\PublicEvents::query();

        if ($filter) {
            $searchLocale = app(\App\Services\LocaleManager::class)->publicLocale();
            if ($searchLocale !== null) {
                $query->whereHas('translations', fn ($translation) => $translation->where('locale', $searchLocale)->where('title', 'like', '%' . $filter . '%'));
            } else {
                $query->where('title', 'like', '%' . $filter . '%');
            }
        }

        // Lọc theo thời gian
        $now = Carbon::now();
        switch ($filterDuration) {
            case 'today':
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $now->toDateString(), $now->toDateString());
                break;
            case 'tomorrow':
                $tomorrow = $now->copy()->addDay()->toDateString();
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $tomorrow, $tomorrow);
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek()->toDateString();
                $end = $now->copy()->endOfWeek()->toDateString();
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $start, $end);
                break;
            case 'next_week':
                $start = $now->copy()->addWeek()->startOfWeek()->toDateString();
                $end = $now->copy()->addWeek()->endOfWeek()->toDateString();
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $start, $end);
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $start, $end);
                break;
            case 'next_month':
                $start = $now->copy()->addMonth()->startOfMonth()->toDateString();
                $end = $now->copy()->addMonth()->endOfMonth()->toDateString();
                \Modules\CmsFrontend\Services\PublicEvents::during($query, $start, $end);
                break;
        }

        $posts = $query->orderBy('event_starts_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        $today = Carbon::today()->toDateString();
        $eventsToday = \Modules\CmsFrontend\Services\PublicEvents::today();

        return view('cmsfrontend::screens.pages.frontend.su-kien.index', [
            'posts' => $posts,
            'eventsToday' => $eventsToday,
            // ... các biến khác
        ]);
    }
    public function detail($slug)
    {
        $post = \Modules\CmsFrontend\Services\PublicEvents::query()
            ->where('posts.slug', $slug)
            ->where('posts.post_type', PostType::EVENT->value)
            ->where('posts.status', PostStatus::PUBLISHED->value)
            ->firstOrFail();

        $today = Carbon::today()->toDateString();
        $eventsToday = \Modules\CmsFrontend\Services\PublicEvents::today();

        $comments = Comment::with(['user', 'replies'])
            ->where('post_id', '=', $post->id)
            ->where('status', '=', CommentStatus::APPROVED->value)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cmsfrontend::screens.pages.frontend.su-kien.chi-tiet', [
            'post' => $post,
            'eventsToday' => $eventsToday,
            'comments' => $comments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cmsfrontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cmsfrontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cmsfrontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
