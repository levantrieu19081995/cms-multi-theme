<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Models\DoiTac;
use Modules\CmsBackend\Services\GuongTieuBieuService;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Models\GuongTieuBieu;
use Illuminate\Support\Facades\Log;
use Modules\CmsBackend\Http\Requests\GuongTieuBieuRequest;

class GuongTieuBieuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $service;
    private $user;
    protected $permissionsBase;
    public function __construct(GuongTieuBieuService $service)
    {
        $this->service = $service;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                // 'canView' => $this->user->hasAnyPermission([PermissionsEnum::GUONG_TIEU_BIEU_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::GUONG_TIEU_BIEU_UPDATE->label()]),
                'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::GUONG_TIEU_BIEU_CREATE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::GUONG_TIEU_BIEU_DELETE->label()]),
            ];
            return $next($request);
        })->except(['showById']);
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::GUONG_TIEU_BIEU_VIEW->label(),
            PermissionsEnum::GUONG_TIEU_BIEU_CREATE->label(),
            PermissionsEnum::GUONG_TIEU_BIEU_UPDATE->label(),
            PermissionsEnum::GUONG_TIEU_BIEU_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::GUONG_TIEU_BIEU_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::GUONG_TIEU_BIEU_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::GUONG_TIEU_BIEU_DELETE->label())
            ->only(['destroy']);
    }
    public function index(Request $request)
    {
        $filter = trim(strip_tags($request->query('filter')));
        $status   = $request->query('status', '-1');
        $pageSize = $request->pageSize ?? 10;
        // dd($statusCustom);
        // $filterConditions = [];
        // if ($status !== '-1' && $status !== null) {
        //     // $filterConditions['status'] = (int) $status;
        //     $filterConditions['show_home_page'] = (int) $status;
        // }

        // $guongTieuBieus = $this->service->paginate(
        //     $filter,
        //     // ['title', 'status'],
        //     ['title', 'show_home_page'],
        //     $filterConditions, // ✅ chỉ truyền khi cần
        //     [],
        //     'id',
        //     'asc',
        //     $pageSize,
        //     []
        // );

        $equals = [];
        if ($status !== '-1') {
            $equals['show_home_page'] = (int) $status;
        }

        $guongTieuBieus = $this->service->paginate(
            $filter,
            ['title', 'role', 'excerpt'],   // bỏ 'show_home_page' khỏi đây
            $equals,
            [],
            'id',
            'desc',
            $pageSize
        )->appends($request->query());

        // Dùng để kiểm tra xem người này có quyền sửa, xóa không (hiển thị nút hành động dạng 3 chấm)

        return view('cmsbackend::screens.pages.guong-tieu-bieu.index', [
            'guongTieuBieus' => $guongTieuBieus,
            'permissions' => $this->permissionsBase,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $guongtieubieu = new GuongTieuBieu();
        $guongtieubieu->show_home_page = 0; // gán mặc định
        return view('cmsbackend::screens.pages.guong-tieu-bieu.edit', compact('guongtieubieu'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GuongTieuBieuRequest $request)
    {
        $data = $request->validated();

        $guongtieubieu = new GuongTieuBieu();
        $guongtieubieu->fill([
            'title'          => $data['title'],
            'rating'         => (float) $data['rating'],   // ép float
            'role'           => $data['role'],
            'excerpt'        => $data['excerpt'] ?? null,
            'content'        => $data['content'],
            'show_home_page' => (int)($data['show_home_page'] ?? 0),
        ])->save();
        $guongtieubieu->save();

        // Avatar (tùy bạn đang dùng medialibrary)
        if (!empty($data['avatar'])) {
            try {
                $guongtieubieu->clearMediaCollection('avatars');
                $guongtieubieu->addMediaFromUrl($data['avatar'])->toMediaCollection('avatars');
            } catch (\Throwable $e) {
                // Nếu không tải được ảnh, không fail form – chỉ cảnh báo
                Toastr::error(__('admin.message.khong_the_tai_anh_dai_dien_tu_duong_dan_da_chon'));
            }
        }

        Toastr::success(__('admin.message.them_moi_tam_guong_tieu_bieu_thanh_cong'));
        return redirect()->route('cms.dashboard.guong-tieu-bieu.index');
    }

    public function edit($id)
    {

        $guongtieubieu = $this->service->find($id);
        return view('cmsbackend::screens.pages.guong-tieu-bieu.edit', compact('guongtieubieu'));
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(GuongTieuBieuRequest $request, $id)
    // {
    //     $data = $request->validated();

    //     $guongtieubieu = $this->service->find($id);
    //     if (!$guongtieubieu) {
    //         Toastr::error('Không tìm thấy tấm gương');
    //         return redirect()->route('cms.dashboard.guong-tieu-bieu.index');
    //     }

    //     $guongtieubieu->fill([
    //         'title'          => $data['title'],
    //         'rating'         => (float) $data['rating'],   // ép float
    //         'role'           => $data['role'],
    //         'excerpt'        => $data['excerpt'] ?? null,
    //         'content'        => $data['content'],
    //         'show_home_page' => (int)($data['show_home_page'] ?? 0),
    //     ])->save();

    //     if (!empty($data['avatar'])) {
    //         try {
    //             $guongtieubieu->clearMediaCollection('avatars');
    //             $guongtieubieu->addMediaFromUrl($data['avatar'])->toMediaCollection('avatars');
    //         } catch (\Throwable $e) {
    //             Toastr::error('Không thể tải ảnh đại diện từ đường dẫn đã chọn.');
    //         }
    //     }

    //     Toastr::success('Cập nhật tấm gương thành công');
    //     return redirect()->route('cms.dashboard.guong-tieu-bieu.index');
    // }

    public function update(GuongTieuBieuRequest $request, $id)
    {
        $data = $request->validated();

        $guongtieubieu = $this->service->find($id);
        if (!$guongtieubieu) {
            Toastr::error(__('admin.message.khong_tim_thay_tam_guong'));
            return redirect()->route('cms.dashboard.guong-tieu-bieu.index');
        }

        $guongtieubieu->fill([
            'title'          => $data['title'],
            'rating'         => (float) $data['rating'],
            'role'           => $data['role'],
            'excerpt'        => $data['excerpt'] ?? null,
            'content'        => $data['content'],
            'show_home_page' => (int)($data['show_home_page'] ?? 0),
        ])->save();

        // ===== FIX Ở ĐÂY =====
        $newAvatar = $data['avatar'] ?? null;
        $currentAvatar = $guongtieubieu->getFirstMediaUrl('avatars') ?: null;

        // Chỉ xử lý media khi người dùng chọn ảnh mới (URL khác ảnh hiện tại)
        if (!empty($newAvatar) && $newAvatar !== $currentAvatar) {
            try {
                $guongtieubieu->clearMediaCollection('avatars');
                $guongtieubieu->addMediaFromUrl($newAvatar)->toMediaCollection('avatars');
            } catch (\Throwable $e) {
                Log::error('Update avatar failed', [
                    'id' => $guongtieubieu->id,
                    'avatar' => $newAvatar,
                    'err' => $e->getMessage()
                ]);

                // an toàn nhất: trả lỗi validation để hiện dưới input avatar
                return back()
                    ->withInput()
                    ->withErrors(['avatar' => __('admin.message.khong_the_tai_anh_dai_dien_tu_duong_dan_da_chon')]);
            }
        }
        // ===== END FIX =====

        Toastr::success(__('admin.message.cap_nhat_tam_guong_thanh_cong'));
        return redirect()->route('cms.dashboard.guong-tieu-bieu.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $guongtieubieu = $this->service->find($id);

        if (!$guongtieubieu) {
            return response()->json([
                'status' => 0,
                'message' => __('admin.message.tam_guong_tieu_bieu_khong_ton_tai')
            ], 404);
        }

        $guongtieubieu->deleted_at = now();
        $guongtieubieu->save();
        if (request()->ajax()) {
            return response()->json(['message' => __('admin.message.xoa_thanh_cong')], 200);
        }

        // Nếu không phải AJAX, redirect như cũ
        return redirect()->route('cms.dashboard.guong-tieu-bieu.index')->with('success', __('admin.message.xoa_doi_tac_tam_guong_tieu_bieu_thanh_cong'));
    }

    public function showById($id)
    {
        // Accepts "<slug>-<id>" (canonical) and a bare "<id>" (old links, redirected).
        abort_unless(preg_match('/(?:^|-)(\d+)$/', (string) $id, $match), 404);

        $guongtieubieu = GuongTieuBieu::findOrFail((int) $match[1]);

        if ((string) $id !== $guongtieubieu->url_key) {
            return redirect()->route('cms.guong-tieu-bieu.detail-by-id', ['id' => $guongtieubieu->url_key], 301);
        }

        return view('cmsfrontend::screens.pages.frontend.guong-tieu-bieu.chi-tiet-guong-tieu-bieu', compact('guongtieubieu'));
    }
}
