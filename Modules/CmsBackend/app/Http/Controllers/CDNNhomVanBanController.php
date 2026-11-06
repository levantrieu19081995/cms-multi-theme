<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Facades\Toastr;
use Illuminate\Http\Request;
use Modules\CmsBackend\Models\NhomVanBan;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Http\Requests\NhomVanBan\CreateNhomVanBanRequest;
use Modules\CmsBackend\Http\Requests\NhomVanBan\UpdateNhomVanBanRequest;
use Modules\CmsBackend\Services\NhomVanBanService;

class CDNNhomVanBanController extends Controller
{

    protected NhomVanBanService $nhomVanBanService;
    private $user;
    protected $permissionsBase;

    public function __construct(NhomVanBanService $nhomVanBanService)
    {
        $this->nhomVanBanService = $nhomVanBanService;
         $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                'canView' => $this->user->hasAnyPermission([PermissionsEnum::NHOMVANBAN_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::NHOMVANBAN_UPDATE->label()]),
                'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::NHOMVANBAN_CREATE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::NHOMVANBAN_DELETE->label()]),
            ];
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::NHOMVANBAN_VIEW->label(),
            PermissionsEnum::NHOMVANBAN_CREATE->label(),
            PermissionsEnum::NHOMVANBAN_UPDATE->label(),
            PermissionsEnum::NHOMVANBAN_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::NHOMVANBAN_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::NHOMVANBAN_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::NHOMVANBAN_DELETE->label())
            ->only(['destroy']);

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = trim($request->filter ?? '');
        $list = $this->nhomVanBanService->getDsNhomVanBan($filter);
        return view('cmsbackend::screens.pages.nhom-van-ban.nhom-van-ban-list', compact(['list', 'filter']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $item = new NhomVanBan;
        return view('cmsbackend::screens.pages.nhom-van-ban.nhom-van-ban-edit', compact(['item']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateNhomVanBanRequest $request)
    {
        $this->nhomVanBanService->create($request->validated());

        Toastr::success(__('admin.message.them_moi_thanh_cong'));

        return redirect()->route('cms.dashboard.cdn.nhom-van-ban.index');
    }


    public function edit(NhomVanBan $nhomVanBan)
    {
        $item = $nhomVanBan;
        return view('cmsbackend::screens.pages.nhom-van-ban.nhom-van-ban-edit', compact(['item']));
    }

    // /**
    //  * Update the specified resource in storage.
    //  */
    public function update(UpdateNhomVanBanRequest $request, NhomVanBan $nhomVanBan)
    {
       $this->nhomVanBanService->updateNVB($request->validated(), $nhomVanBan);

        Toastr::success(__('admin.message.cap_nhat_thanh_cong'));

        return redirect()->route('cms.dashboard.cdn.nhom-van-ban.index');
    }

    // /**
    //  * Remove the specified resource from storage.
    //  */


public function destroy(string $id)
{
    $this->nhomVanBanService->destroy($id);
    return response()->json(['status' => 1, 'message' => __('admin.message.xoa_thanh_cong')]);
}
}