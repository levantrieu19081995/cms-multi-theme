<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Models\DoiTac;
use Modules\CmsBackend\Support\AvatarSource;
use Modules\CmsBackend\Services\DoiTacService;
use Modules\CmsBackend\Http\Requests\DoiTacRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DoiTacController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $service;
    private $user;
    protected $permissionsBase;
    public function __construct(DoiTacService $service)
    {
        $this->service = $service;
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $this->user = Auth::user();
                $this->permissionsBase = [
                    'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::DOITAC_UPDATE->label()]),
                    'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::DOITAC_CREATE->label()]),
                    'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::DOITAC_DELETE->label()]),
                ];
            } else {
                $this->permissionsBase = [
                    'canUpdate' => false,
                    'canCreate' => false,
                    'canDelete' => false,
                ];
            }
            return $next($request);
        })->except(['showFront']);


        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::DOITAC_CREATE->label(),
            PermissionsEnum::DOITAC_UPDATE->label(),
            PermissionsEnum::DOITAC_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::DOITAC_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::DOITAC_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::DOITAC_DELETE->label())
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
            // $filterConditions['status'] = (int) $status;
            $filterConditions['show_home_page'] = (int) $status;
        }

        $doitacs = $this->service->paginate(
            $filter,
            // ['title', 'status'],
            ['title', 'show_home_page'],
            $filterConditions, // ✅ chỉ truyền khi cần
            [],
            'id',
            'asc',
            $pageSize,
            []
        );
        // Dùng để kiểm tra xem người này có quyền sửa, xóa không (hiển thị nút hành động dạng 3 chấm)

        return view('cmsbackend::screens.pages.doi-tac.index', [
            'doitacs' => $doitacs,
            'permissions' => $this->permissionsBase,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $doitac = new DoiTac();
        $doitac->show_home_page = 1; // gán mặc định
        return view('cmsbackend::screens.pages.doi-tac.edit', compact('doitac'));
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    public function store(DoiTacRequest  $request)
    {
        $doitac = new DoiTac();
        $doitac->fill($request->only(['title', 'slug', 'excerpt', 'show_home_page']));
        $doitac->content = $request->input('content'); // Thêm dòng này ✅
        $doitac->save();

        // if ($request->filled('avatar')) {
        //     try {
        //         $avatarUrl = trim($request->avatar);
        //         $relativePath = str_replace(\Modules\CmsBackend\Support\ModuleUrl::url('/storage'), '', $avatarUrl);
        //         $absolutePath = storage_path('app/public' . $relativePath);

        //         if (file_exists($absolutePath)) {
        //             $tempPath = storage_path('app/temp/' . basename($absolutePath));
        //             \Illuminate\Support\Facades\File::copy($absolutePath, $tempPath);

        //             $doitac->addMedia($tempPath)->toMediaCollection('avatars');

        //             // Xoá ảnh tạm nếu muốn
        //             \Illuminate\Support\Facades\File::delete($tempPath);
        //         } else {
        //             Log::warning("Không tìm thấy file ảnh đại diện: $absolutePath");
        //         }
        //     } catch (\Exception $e) {
        //         Log::error("Lỗi xử lý ảnh đại diện khi thêm mới đối tác: " . $e->getMessage());
        //     }
        // }

        // Toastr::success('Thêm mới đối tác thành công');
        if ($request->filled('avatar')) {
            if (AvatarSource::attach($doitac, (string) $request->input('avatar')) === 'failed') {
                Toastr::warning(__('admin.message.anh_khong_hop_le_hoac_khong_the_truy_cap'));
            }
        }
        Toastr::success(__('admin.message.them_moi_doi_tac_thanh_cong'));
        return redirect()->route('cms.dashboard.doi-tac.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $doitac = $this->service->find($id);
        return view('cmsbackend::screens.pages.doi-tac.edit', compact('doitac'));
    }


    // public function update(Request $request, $id)
    public function update(DoiTacRequest  $request, $id)
    {
        $data = $request->only(['title', 'slug', 'show_home_page', 'excerpt']);
        $data['show_home_page'] ??= 1;

        $doitac = $this->service->find($id);
        if (!$doitac) {
            Toastr::error(__('admin.message.khong_tim_thay_doi_tac'));
            return redirect()->route('cms.dashboard.doi-tac.index');
        }

        $doitac->fill($data);
        $doitac->content = $request->input('content'); // Thêm dòng này ✅
        $doitac->save();

        // ✅ Xử lý avatar nếu có ảnh mới
        // $newAvatarUrl = trim($request->avatar ?? '');
        // $currentAvatarUrl = $doitac->getFirstMediaUrl('avatars');

        // if ($newAvatarUrl && $newAvatarUrl !== $currentAvatarUrl) {
        //     try {
        //         $relativePath = str_replace(\Modules\CmsBackend\Support\ModuleUrl::url('/storage'), '', $newAvatarUrl);
        //         $absolutePath = storage_path('app/public' . $relativePath);
        //         if (file_exists($absolutePath)) {
        //             $tempPath = storage_path('app/temp/' . basename($absolutePath));
        //             \Illuminate\Support\Facades\File::copy($absolutePath, $tempPath);
        //             $doitac->clearMediaCollection('avatars');
        //             $doitac->addMedia($tempPath)->toMediaCollection('avatars');

        //             \Illuminate\Support\Facades\File::delete($tempPath);
        //         } else {
        //             Log::warning("File avatar không tồn tại: $absolutePath");
        //             Toastr::warning('Ảnh đại diện không hợp lệ hoặc đã bị xoá.');
        //         }
        //     } catch (\Exception $e) {
        //         Log::error("Lỗi avatar khi cập nhật đối tác: " . $e->getMessage());
        //         Toastr::error('Không thể cập nhật ảnh đại diện. Vui lòng thử lại.');
        //     }
        // }

        // Toastr::success('Cập nhật đối tác thành công');

        $newAvatarUrl = trim((string) $request->input('avatar'));
        $currentAvatarUrl = $doitac->getFirstMediaUrl('avatars');

        if ($newAvatarUrl && $newAvatarUrl !== $currentAvatarUrl) {
            if (AvatarSource::attach($doitac, $newAvatarUrl) === 'failed') {
                Toastr::warning(__('admin.message.anh_khong_hop_le_hoac_khong_the_truy_cap'));
            }
        }
        Toastr::success(__('admin.message.cap_nhat_doi_tac_thanh_cong'));
        return redirect()->route('cms.dashboard.doi-tac.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $doitac = $this->service->find($id);

        if (!$doitac) {
            return response()->json([
                'status' => 0,
                'message' => __('admin.message.doi_tac_khong_ton_tai')
            ], 404);
        }

        $doitac->deleted_at = now();
        $doitac->save();
        if (request()->ajax()) {
            return response()->json(['message' => __('admin.message.xoa_thanh_cong')], 200);
        }

        // Nếu không phải AJAX, redirect như cũ
        return redirect()->route('cms.dashboard.doi-tac.index')->with('success', __('admin.message.xoa_doi_tac_thanh_cong'));
    }


    public function showFront($slug)
    {
        $lienket = DoiTac::where('slug', $slug)->firstOrFail();

        return view('cmsfrontend::screens.pages.frontend.doi-tac.chi-tiet-doi-tac', compact('lienket'));
    }
}
