<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Facades\Toastr;
use Illuminate\Http\Request;
use Modules\CmsBackend\Http\Requests\LoaiVanBan\CreateLoaiVanBanRequest;
use Modules\CmsBackend\Http\Requests\LoaiVanBan\UpdateLoaiVanBanRequest;
use Modules\CmsBackend\Models\LoaiVanBan;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\LoaiVanBanService;
class CDNLoaiVanBanController extends Controller
{

    protected LoaiVanBanService $loaiVanBanService;

    private $user;
    protected $permissionsBase;

    public function __construct(LoaiVanBanService $loaiVanBanService)
    {
        $this->loaiVanBanService = $loaiVanBanService;
         $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                'canView' => $this->user->hasAnyPermission([PermissionsEnum::LOAIVANBAN_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::LOAIVANBAN_UPDATE->label()]),
                'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::LOAIVANBAN_CREATE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::LOAIVANBAN_DELETE->label()]),
            ];
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::LOAIVANBAN_VIEW->label(),
            PermissionsEnum::LOAIVANBAN_CREATE->label(),
            PermissionsEnum::LOAIVANBAN_UPDATE->label(),
            PermissionsEnum::LOAIVANBAN_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::LOAIVANBAN_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::LOAIVANBAN_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::LOAIVANBAN_DELETE->label())
            ->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = trim($request->filter ?? '');
        $list = $this->loaiVanBanService->getDsLoaiVanBan($filter);
        return view('cmsbackend::screens.pages.loai-van-ban.loai-van-ban-list', compact(['list', 'filter']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $item = new LoaiVanBan;
        return view('cmsbackend::screens.pages.loai-van-ban.loai-van-ban-edit', compact(['item']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateLoaiVanBanRequest $request)
    {
        $data = $request->all();
        $item = new LoaiVanBan;
        $item->fill($data);
        $item->user_id = Auth::user()->id;
        $item->save();

        Toastr::success(__('admin.message.them_moi_thanh_cong'));

        return redirect()->route('cms.dashboard.cdn.loai-van-ban.index');
    }


    public function edit(LoaiVanBan $loaiVanBan)
    {
        $item = $loaiVanBan;
        return view('cmsbackend::screens.pages.loai-van-ban.loai-van-ban-edit', compact(['item']));
    }

    // /**
    //  * Update the specified resource in storage.
    //  */
    public function update(UpdateLoaiVanBanRequest $request, LoaiVanBan $loaiVanBan)
    {
        $loaiVanBan->fill($request->all());
        $loaiVanBan->save();

        Toastr::success(__('admin.message.cap_nhat_thanh_cong'));

        return redirect()->route('cms.dashboard.cdn.loai-van-ban.index');
    }

    // /**
    //  * Remove the specified resource from storage.
    //  */


public function destroy(string $id)
{
    $this->loaiVanBanService->destroy($id);
    return response()->json(['status' => 1, 'message' => __('admin.message.xoa_thanh_cong')]);
}

}
