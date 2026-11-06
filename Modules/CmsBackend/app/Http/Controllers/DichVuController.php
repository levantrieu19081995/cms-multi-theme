<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\CmsBackend\DataProviders\DichVuDataProvider;
use Modules\CmsBackend\Http\Requests\DichVuRequest;
use Modules\CmsBackend\Models\DichVu;
use Modules\CmsBackend\Services\DichVuService;
use App\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;

class DichVuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $service;
    protected $user;
    protected DichVuDataProvider $dataProvider;
    public function __construct(DichVuService $service)
    {
        $this->service = $service;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::DICHVU_VIEW->label(),
            PermissionsEnum::DICHVU_CREATE->label(),
            PermissionsEnum::DICHVU_UPDATE->label(),
            PermissionsEnum::DICHVU_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::DICHVU_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::DICHVU_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::DICHVU_DELETE->label())
            ->only(['destroy']);
    }

    public function index(Request $request)
    {

        $filter = trim(strip_tags($request->query('filter')));
        $status = $request->query('status'); // Mặc định là -1 (tất cả trạng thái)
        $pageSize = $request->pageSize ?? 10;
        // dd($statusCustom);
        $filterConditions = [];
        if ($status !== '-1' && $status !== null) {
            $filterConditions['status'] = (int) $status;
        }

        $dichvus = $this->service->paginate(
            $filter,
            ['title', 'status'],
            $filterConditions, // ✅ chỉ truyền khi cần
            [],
            'id',
            'asc',
            $pageSize,
            []
        );

        // Dùng để kiểm tra xem người này có quyền sửa, xóa không (hiển thị nút hành động dạng 3 chấm)
        $hasActions = $this->user->hasAnyPermission([
            PermissionsEnum::DICHVU_UPDATE->label(),
            PermissionsEnum::DICHVU_DELETE->label()
        ]);
        return view('cmsbackend::screens.pages.dich-vu.index', compact('dichvus', 'hasActions'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dichvu = new DichVu();
        $dichvu->status = 1; // gán mặc định

        $json = file_get_contents(base_path('Modules/CmsBackend/resources/assets/js/remix_icons_normalized.json'));
        $iconGroups = json_decode($json, true);

        return view('cmsbackend::screens.pages.dich-vu.edit', compact('dichvu', 'iconGroups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DichVuRequest $request)
    {
        $dichvu = new DichVu();
        $dichvu->title = $request->input('title');
        $dichvu->link = $request->input('link');
        $dichvu->icon = $request->input('icon');
        $dichvu->status = $request->input('status', 1); // mặc định là 1
        $dichvu->save();
        Toastr::success(__('admin.message.them_moi_dich_vu_thanh_cong'));

        return redirect()->route('cms.dashboard.dich-vu.index');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cmsbackend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $json = file_get_contents(base_path('Modules/CmsBackend/resources/assets/js/remix_icons_normalized.json'));
        $iconGroups = json_decode($json, true);
        $dichvu = $this->service->find($id);
        return view('cmsbackend::screens.pages.dich-vu.edit', compact('dichvu', 'iconGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DichVuRequest $request, $id): RedirectResponse
    {
        $data = $request->only(['title', 'link', 'icon', 'status']);

        // Gán mặc định nếu thiếu status
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }

        // Gọi Service xử lý cập nhật
        $updated = $this->service->update($id, $data);

        if (!$updated) {
            Toastr::error(__('admin.message.khong_the_cap_nhat_dich_vu'));
            return redirect()->route('cms.dashboard.dich-vu.index');
        }

        Toastr::success(__('admin.message.cap_nhat_dich_vu_thanh_cong'));
        return redirect()->route('cms.dashboard.dich-vu.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $dichvu = $this->service->find($id);

        if (!$dichvu) {
            return response()->json([
                'status' => 0,
                'message' => __('admin.message.dich_vu_khong_ton_tai')
            ], 404);
        }
        // $this->checkPermission($dichvu);
        // Kiểm tra quyền
        // if (!$this->permissionsBase['canDelete']) {
        //     return response()->json([
        //         'status' => 0,
        //         'message' => 'Bạn không có quyền xóa bài viết này.'
        //     ], 403);
        // }
        $dichvu->deleted_at = now();
        $dichvu->save();
        if (request()->ajax()) {
            return response()->json(['message' => __('admin.message.xoa_thanh_cong')], 200);
        }

        // Nếu không phải AJAX, redirect như cũ
        return redirect()->route('cms.dashboard.dich-vu.index')->with('success', __('admin.message.xoa_dich_vu_thanh_cong'));
    }
}
