<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Http\Controllers\Shared\AbstractPostController;
use Modules\CmsBackend\Services\EditorialPolicy;
use Modules\CmsBackend\Services\EditorialWorkflow;
use Modules\CmsBackend\Support\ModuleUrl;

abstract class EditorialPostController extends AbstractPostController
{
    protected const TYPE = 'post';

    protected const RESOURCE = 'cms.dashboard.cdn.bai-viet';

    public function __construct(PostDataProvider $posts, TaxonomyDataProvider $categories, TagDataProvider $tags)
    {
        $this->middleware(function ($request, $next) use ($posts, $categories, $tags) {
            $type = static::TYPE;
            $policy = app(EditorialPolicy::class);
            $user = $request->user();
            abort_unless($user, 403);
            app(EditorialWorkflow::class)->assertDatabase();
            abort_unless(Schema::hasTable('cms_editorial_meta'), 503, __('admin.message.workflow_not_installed'));
            abort_unless($policy->has($user, $type, 'view'), 403);
            parent::__construct($posts, $categories, $tags, PostType::from($type), 'cmsbackend::screens.components.partials.post.table-post', $type === 'event' ? 'Sự kiện' : 'Tin tức', 'cmsbackend::pages.bai-viet', static::RESOURCE.'.index', [], $policy->editor($user, $type), $policy->has($user, $type, 'create'));

            return $next($request);
        });
    }

    private function scopedQuery(Request $request)
    {
        $policy = app(EditorialPolicy::class);
        $user = $request->user();
        $ids = $policy->categoryIds($user);
        $query = Post::with(['taxonomies.term', 'author', 'translations'])->where('post_type', static::TYPE);
        if ($request->boolean('trash')) {
            abort_unless($user->hasRole('Quản trị'), 403);
            $query->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }
        if (! $policy->editor($user, static::TYPE)) {
            $query->where('author_id', $user->id);
        }
        if (! $user->hasRole('Quản trị')) {
            $query->whereDoesntHave('taxonomies', fn ($q) => $q->where('term_taxonomy.taxonomy', 'category')->whereNotIn('term_taxonomy.term_taxonomy_id', $ids));
            $query->where(fn ($q) => $q->whereHas('taxonomies', fn ($c) => $c->where('term_taxonomy.taxonomy', 'category')->whereIn('term_taxonomy.term_taxonomy_id', $ids))->orWhere('author_id', $user->id));
        }

        return $query;
    }

    /** Cửa sổ chọn bài viết (menu, liên kết...) mở ?mode=select&state=…; cùng phạm vi phân quyền với danh sách, chỉ khác giao diện. */
    private function selectList(Request $request)
    {
        $user = $request->user();
        $query = $this->scopedQuery($request);
        if ($request->filled('filter')) {
            $query->where('title', 'like', '%'.$request->string('filter')->trim().'%');
        }
        $statusFilter = collect($request->getBase64Param('postStatuses') ?? [])->filter(fn ($status) => PostStatus::tryFrom((string) $status))->values();
        if ($statusFilter->isNotEmpty()) {
            $query->whereIn('status', $statusFilter->all());
        }
        $categoryFilter = collect($request->getBase64Param('categories') ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $allowedIds = $user->hasRole('Quản trị') ? null : app(EditorialPolicy::class)->categoryIds($user);
        $categories = Taxonomy::with('term')->where('taxonomy', 'category')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('term_taxonomy_id', $allowedIds))
            ->get()->filter(fn ($taxonomy) => $taxonomy->term)->sortBy(fn ($taxonomy) => $taxonomy->term->name)->values();
        foreach ($categories as $category) {
            $category->selected = $categoryFilter->contains((int) $category->term_id);
        }
        if ($categoryFilter->isNotEmpty()) {
            $query->whereHas('taxonomies', fn ($q) => $q->where('term_taxonomy.taxonomy', 'category')->whereIn('term_taxonomy.term_id', $categoryFilter->all()));
        }
        $postStatuses = [];
        foreach (PostStatus::cases() as $status) {
            $postStatuses[] = ['enum' => $status, 'selected' => $statusFilter->contains($status->value)];
        }
        $posts = $query->orderByDesc('updated_at')->paginate(min(50, max(5, $request->integer('pageSize', 10))))->withQueryString();

        return view('cmsbackend::screens.pages.bai-viet.index-select', compact('categories', 'posts', 'postStatuses'));
    }

    public function index(Request $request)
    {
        if (static::TYPE === 'post' && $request->input('mode') === 'select') {
            return $this->selectList($request);
        }
        $query = $this->scopedQuery($request);
        if ($request->filled('filter')) {
            $query->where('title', 'like', '%'.$request->string('filter')->trim().'%');
        }
        if (PostStatus::tryFrom((string) $request->input('status'))) {
            $query->where('status', $request->input('status'));
        }
        $user = $request->user();
        $allowedIds = $user->hasRole('Quản trị') ? null : app(EditorialPolicy::class)->categoryIds($user);
        $categoryOptions = Taxonomy::with('term')->where('taxonomy', 'category')
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('term_taxonomy_id', $allowedIds))
            ->get()->filter(fn ($taxonomy) => $taxonomy->term)->sortBy(fn ($taxonomy) => $taxonomy->term->name)->values();
        $categoryId = (int) $request->input('category');
        if ($categoryId && $categoryOptions->contains('term_taxonomy_id', $categoryId)) {
            $query->whereHas('taxonomies', fn ($q) => $q->where('term_taxonomy.taxonomy', 'category')->where('term_taxonomy.term_taxonomy_id', $categoryId));
        }
        // Tin nổi bật (hiển thị trang chủ) lên đầu danh sách tin tức; sự kiện giữ thứ tự cũ.
        if (static::TYPE === 'post') {
            $query->orderByDesc('show_home_page');
        }
        $posts = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $revisions = \Modules\CmsBackend\Models\EditorialMeta::query()->whereIn('post_id', $posts->pluck('id'))->pluck('revision', 'post_id');

        $purgeAllowed = $posts->filter(fn (Post $post): bool => app(EditorialPolicy::class)->allows($user, $post, 'force_delete'))->mapWithKeys(fn (Post $post): array => [$post->id => true])->all();

        // Công tắc "hiển thị trang chủ" ở danh sách tin tức và sự kiện: chỉ người có quyền cập nhật mới thao tác được.
        $homeToggle = $posts->filter(fn (Post $post): bool => $post->deleted_at === null && app(EditorialPolicy::class)->allows($user, $post, 'show_home'))->mapWithKeys(fn (Post $post): array => [$post->id => true])->all();

        return view('cmsbackend::screens.pages.news.index', $this->common() + compact('posts', 'categoryOptions', 'revisions', 'purgeAllowed', 'homeToggle'));
    }

    public function create()
    {
        abort_unless(app(EditorialPolicy::class)->has(auth()->user(), static::TYPE, 'create'), 403);

        return $this->form(new Post(['post_type' => static::TYPE, 'status' => PostStatus::DRAFT->value, 'author_id' => auth()->id()]));
    }

    public function edit($id)
    {
        $post = Post::with(['taxonomies.term', 'media', 'translations'])->where('post_type', static::TYPE)->whereNull('deleted_at')->findOrFail($id);
        abort_unless(app(EditorialPolicy::class)->view(auth()->user(), $post), 403);

        return $this->form($post);
    }

    public function show($id)
    {
        return $this->edit($id);
    }

    private function form(Post $post)
    {
        $policy = app(EditorialPolicy::class);
        $workflow = app(EditorialWorkflow::class);
        $meta = $workflow->meta($post);
        $categories = Taxonomy::with('term')->whereIn('term_taxonomy_id', $policy->categoryIds(auth()->user()))->get();
        $tags = Taxonomy::with('term')->where('taxonomy', \App\Enums\Taxonomy::POSTTAG->value)->get();
        $history = $post->exists ? DB::table('cms_editorial_history')->leftJoin('users', 'users.id', '=', 'cms_editorial_history.user_id')->where('post_id', $post->id)->select('cms_editorial_history.*', 'users.name as actor')->orderByDesc('cms_editorial_history.id')->get() : collect();
        $allowedActions = [];
        foreach (['save_draft', 'save_changes', 'submit_for_approval', 'approved', 'reject', 'publish', 'stop_publishing', 'delete', 'force_delete', 'show_home'] as $action) {
            $allowedActions[$action] = $post->exists ? $policy->allows(auth()->user(), $post, $action) : in_array($action, ['save_draft', 'submit_for_approval'], true);
        }
        $editable = ! $post->exists || $allowedActions['save_changes'];
        $view = static::TYPE === 'post' ? 'cmsbackend::screens.pages.news.edit' : 'cmsbackend::screens.pages.events.edit';

        return view($view, $this->common() + compact('post', 'meta', 'categories', 'tags', 'history', 'editable', 'allowedActions'));
    }

    private function common(): array
    {
        return [
            'type' => static::TYPE,
            'resource' => static::RESOURCE,
            'heading' => __(static::TYPE === 'event' ? 'admin.ui.su_kien' : 'admin.ui.tin_tuc'),
            'canCreate' => app(EditorialPolicy::class)->has(auth()->user(), static::TYPE, 'create'),
            'policy' => app(EditorialPolicy::class),
        ];
    }

    public function store(Request $request)
    {
        app(EditorialWorkflow::class)->execute($request->user(), static::TYPE, $request);

        return redirect(ModuleUrl::route(static::RESOURCE.'.index'))->with('success', __('admin.message.da_luu'));
    }

    public function update(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        app(EditorialWorkflow::class)->execute($request->user(), static::TYPE, $request, (string) $id);

        return redirect(ModuleUrl::route(static::RESOURCE.'.edit', $id))->with('success', __('admin.message.da_xu_ly_theo_quy_trinh'));
    }

    /**
     * Bật/tắt "hiển thị ở trang chủ" mà không đổi trạng thái đăng (không cần ngưng xuất bản). Chỉ bật được khi đã phê duyệt hoặc đang đăng;
     * tắt thì luôn được. Không đụng revision nên không làm mất phê duyệt hay làm lệch form đang mở.
     */
    public function showHome(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $post = Post::with('taxonomies')->where('post_type', static::TYPE)->whereNull('deleted_at')->findOrFail($id);
        abort_unless(app(EditorialPolicy::class)->allows($request->user(), $post, 'show_home'), 403);
        $show = $request->boolean('value');
        if ($show && ! in_array($post->status, [PostStatus::APPROVED->value, PostStatus::PUBLISHED->value], true)) {
            return response()->json(['message' => __('admin.message.su_kien_phai_duyet_moi_hien_trang_chu'), 'checked' => (bool) $post->show_home_page], 422);
        }
        if ((bool) $post->show_home_page !== $show) {
            $post->show_home_page = $show;
            $post->save();
            \Illuminate\Support\Facades\Log::info('Editorial: '.static::TYPE.' #'.$post->id.' show_home_page='.(int) $show.' by user #'.$request->user()->id.'.');
        }

        return response()->json(['message' => __('admin.js.cap_nhat_thanh_cong'), 'checked' => $show]);
    }

    public function reject(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $request->merge(['action' => 'reject']);

        return $this->update($request, $id);
    }

    public function destroy($id)
    {
        $request = request();
        $request->merge(['action' => 'delete']);
        app(EditorialWorkflow::class)->execute($request->user(), static::TYPE, $request, (string) $id);

        return redirect(ModuleUrl::route(static::RESOURCE.'.index'))->with('success', __('admin.message.da_xoa_mem'));
    }

    public function purge(\Modules\CmsBackend\Http\Requests\PermanentlyDeletePostRequest $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $result = app(\Modules\CmsBackend\Services\PostPurger::class)->execute($request->user(), static::TYPE, $id, (int) $request->validated('revision'));
        $message = __('admin.message.purged_post', ['count' => $result['removed']]);
        if ($result['shared'] > 0) {
            $message .= ' '.__('admin.message.purged_kept_files', ['count' => $result['shared']]);
        }
        $response = redirect(ModuleUrl::route(static::RESOURCE.'.index', $request->boolean('trash') ? ['trash' => 1] : []))->with('success', $message);
        if ($result['failed'] > 0) {
            $response->with('warning', __('admin.message.purged_failed_files', ['count' => $result['failed']]));
        }

        return $response;
    }

    public function export(Request $request)
    {
        // Keep the existing spreadsheet export implementation, with the same scoped author/editor filtering.
        return parent::export($request);
    }

    protected function buildExportQueryV2(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return parent::buildExportQueryV2($request)->whereIn('posts.id', $this->scopedQuery($request)->select('posts.id'));
    }
}
