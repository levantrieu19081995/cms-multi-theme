<?php

namespace App\Http\Controllers;

use App\DataProviders\TaxonomyDataProvider;
use App\Facades\Toastr;
use App\Http\Requests\Category\CategoryCreateRequest;
use App\Models\Taxonomy;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Enums\PermissionsEnum;

abstract class AbstractTaxonomyController extends Controller
{
    protected TaxonomyDataProvider $taxonomyDataProvider;
    private $type;
    private string $formName;
    private string $viewName;
    private string $routeName;

    public function __construct(TaxonomyDataProvider $taxonomyDataProvider, EnumsTaxonomy  $type, string $formName, string $viewName, string $routeName)
    {
        $this->taxonomyDataProvider = $taxonomyDataProvider;
        $this->type = $type;
        $this->formName = $formName;
        $this->viewName = $viewName;
        $this->routeName = $routeName;

        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('check.permission:' . PermissionsEnum::TERM_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pageSize = $request->pageSize ?? 20;
        $mode = $request->mode;
        $filter = trim(strip_tags($request->query('filter')));
        //Lấy danh sách chuyên mục theo cha con
        $categories = $this->taxonomyDataProvider->paginate($filter, $pageSize, $this->type->value);
        $categories->appends($request->all());
        if ($mode == 'select') {
            $this->viewName = $this->viewName . '-select';
        }
        return view($this->viewName, compact(['categories']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->taxonomyDataProvider->getCategoryAll($this->type->value);
        $titleName = __('Thêm mới ') . Str::lower($this->formName);
        return view('pages.category.category-create', compact(['categories', 'titleName']));
    }

    /**s
     * Store a newly created resource in storage.
     */
    public function store(CategoryCreateRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Tạo term
            $term = Term::create([
                'name' => $request->input('name'),
                'slug' => $this->createUniqueSlug($request->input('name'))
            ]);
            // Bản dịch (Phase 5): vi sao chép từ terms, ngôn ngữ khác chỉ nằm trong term_translations.
            $this->saveTermTranslations($term, $request);
            // Tạo taxonomy
            $taxonomy = Taxonomy::create([
                'term_id' => $term->term_id,
                'taxonomy' => $this->type->value,
                'parent' => $request->input('parent_id') == null ? 0 : $request->input('parent_id'),
                'description' => $request->input('description'),
                'loai_chuyen_muc' => $request->input('catarogy_id') // 👈 THÊM DÒNG NÀY
            ]);
            Toastr::success(__('Thêm mới thành công'));
        });

        return redirect()->route($this->routeName);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cate = Taxonomy::with('term', 'parent')->where('taxonomy', $this->type->value)->findOrFail($id);
        // Lấy toàn bộ chuyên mục con của chuyên mục hiện tại
        $allChildrenIds =  $this->taxonomyDataProvider->getAllChildrenIds($cate);
        $categories = $this->taxonomyDataProvider->getCategoryExclusions($cate->term_id, $allChildrenIds, $this->type->value);
        $titleName = __('Cập nhật ') . Str::lower($this->formName);;
        return view('pages.category.category-edit', compact('cate', 'categories', 'titleName'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, string $id)
    {
        DB::transaction(function () use ($request, $id) {
            $cate = Taxonomy::with('term', 'parent')->where('taxonomy', $this->type->value)->findOrFail($id);
            // Cập nhật term
            $cate->term->update([
                'name' => $request->input('name'),
                'slug' => $this->createUniqueSlug($request->input('name'), $cate->term_id)
            ]);

            $this->saveTermTranslations($cate->term, $request);

            // Cập nhật taxonomy
            $cate->update([
                'description' => $request->input('description'),
                'parent' => $request->input('parent_id') == null ? 0 : $request->input('parent_id'),
                'loai_chuyen_muc' => $request->input('catarogy_id') // 👈 Thêm loại chuyên mục
            ]);
            Toastr::success(__('Cập nhật thành công'));
        });
        return redirect()->route('chuyen-muc.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     $category = Taxonomy::with('term', 'parent')->where('taxonomy', $this->type->value)->findOrFail($id);
    //     // Kiểm tra xem chuyên mục có bài viết nào liên kết không
    //     $postsUsingCategory = DB::table('term_relationships')
    //         ->join('posts', 'term_relationships.object_id', '=', 'posts.id') // Liên kết với bảng bài viết
    //         ->where('term_relationships.term_taxonomy_id', $category->term_taxonomy_id)
    //         ->whereNull('posts.deleted_at') // Điều kiện chỉ lấy bài viết chưa bị xóa mềm
    //         ->exists();
    //     $menuUsingCategory = DB::table('menu_items')
    //         ->where('menu_items.ref_id', $category->term_id)
    //         ->where('menu_items.type', 'category') // Điều kiện chỉ lấy bài viết chưa bị xóa mềm
    //         ->exists();

    //     dd($postsUsingCategory);
    //     dd($menuUsingCategory);
    //     // Nếu chuyên mục có bài viết đang sử dụng
    //     if ($postsUsingCategory || $menuUsingCategory) {
    //         return response()->json([
    //             'status' => 0,
    //             'message' => 'Không thể xóa chuyên mục này vì đang được sử dụng!'
    //         ]);
    //     }

    //     $term = Term::where('term_id', $category->term_id)->first();
    //     $term->delete();
    //     $category->delete();

    //     return response()->json([
    //         'status' => 1,
    //         'message' => 'Xóa thành công'
    //     ]);
    // }

    public function destroy(string $id)
    {
        try {
            $category = Taxonomy::with('term', 'parent')
                ->where('taxonomy', $this->type->value)
                ->findOrFail($id);

            $postsUsingCategory = DB::table('term_relationships')
                ->join('posts', 'term_relationships.object_id', '=', 'posts.id')
                ->where('term_relationships.term_taxonomy_id', $category->term_taxonomy_id)
                ->where('term_relationships.object_type', \App\Models\Post::class)
                ->whereNull('posts.deleted_at')
                ->exists();
            // dd($postsUsingCategory);

            $menuUsingCategory = DB::table('menu_items')
                ->where('menu_items.ref_id', $category->term_id)
                ->where('menu_items.type', 'category')
                ->exists();

            if ($postsUsingCategory || $menuUsingCategory) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Không thể xóa chuyên mục này vì đang được sử dụng!'
                ]);
            }

            // xoá liên kết rác nếu có
            DB::table('term_relationships')->where('term_taxonomy_id', $category->term_taxonomy_id)->delete();

            Term::where('term_id', $category->term_id)->delete();
            $category->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Xóa thành công'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Default language (vi) is mirrored from the term row just written (terms.name / terms.slug stay in use until Phase 15);
     * other languages come from translations[locale][name|slug] and are stored only in term_translations.
     */
    protected function saveTermTranslations(Term $term, $request): void
    {
        $locales = app(\App\Services\LocaleManager::class);
        $input = (array) $request->input('translations', []);
        $payload = [$locales->default() => ['name' => $term->name, 'slug' => $term->slug]];
        foreach ($locales->alternates() as $locale) {
            if (isset($input[$locale]) && is_array($input[$locale])) {
                $payload[$locale] = array_intersect_key($input[$locale], array_flip(['name', 'slug']));
            }
        }
        app(\App\Services\TranslationService::class)->saveTermTranslations($term, $payload);
    }

    protected function createUniqueSlug($name, $id = null)
    {
        // Tạo slug ban đầu từ tiêu đề
        $slug = Str::slug($name, '-');
        // Khởi tạo slug ban đầu để sử dụng sau này
        $originalSlug = $slug;

        // Kiểm tra xem slug đã tồn tại trong cơ sở dữ liệu hay chưa
        $i = 1;
        while (Term::where('slug', $slug)->when($id, function ($query, $id) {
            // Bỏ qua bài viết hiện tại nếu đang cập nhật
            return $query->where('term_id', '!=', $id);
        })
            ->exists()
        ) {
            // Nếu slug đã tồn tại, thêm số phía sau slug
            $slug = $originalSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
