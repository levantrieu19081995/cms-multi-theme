<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Modules\CmsBackend\Http\Requests\VanBan\CreateVanBanRequest;
use Modules\CmsBackend\Http\Requests\VanBan\UpdateVanBanRequest;
use Modules\CmsBackend\Models\VanBan;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\VanBanService;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;

class CDNVanBanController extends Controller
{

    protected $vanBanService;

    private $user;
    protected $permissionsBase;

    public function __construct(VanBanService $vanBanService)
    {
        $this->vanBanService = $vanBanService;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                'canView' => $this->user->hasAnyPermission([PermissionsEnum::VANBAN_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::VANBAN_UPDATE->label()]),
                'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::VANBAN_CREATE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::VANBAN_DELETE->label()]),
            ];
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::VANBAN_VIEW->label(),
            PermissionsEnum::VANBAN_CREATE->label(),
            PermissionsEnum::VANBAN_UPDATE->label(),
            PermissionsEnum::VANBAN_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::VANBAN_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::VANBAN_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::VANBAN_DELETE->label())
            ->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $this->vanBanService->getList($request);
        return view('cmsbackend::screens.pages.van-ban.van-ban-list', $data);
    }

    public function create()
    {
        $data = $this->vanBanService->prepareCreate();
        return view('cmsbackend::screens.pages.van-ban.van-ban-edit', $data);
    }

    // public function store(CreateVanBanRequest $request)

    // {
    //     dd('them mới văn bản');
    //     //dd($request->all());
    //     $this->vanBanService->store($request);
    //     Toastr::success(__('Thêm mới thành công'));
    //     return redirect()->route('cms.dashboard.cdn.van-ban.index');
    // }

    public function store(CreateVanBanRequest $request)
    {
        $url = trim((string) $request->input('vb_file_url', ''));

        // fileDinhKem từ component (string / array / json)
        $fd = $request->input('fileDinhKem');

        $hasFileValue = false;
        if (is_string($fd)) {
            $s = trim($fd);
            if ($s !== '' && $s !== '[]' && $s !== '{}' && $s !== 'null' && $s !== 'undefined') {
                $hasFileValue = true;
            }
        } elseif (is_array($fd)) {
            $fd = array_values(array_filter($fd, fn($x) => trim((string)$x) !== ''));
            if (count($fd) > 0) {
                $hasFileValue = true;
            }
        }

        // nếu có upload file thật (phòng hờ)
        $hasUpload = $request->hasFile('fileDinhKem');
        if ($url === '' && !$hasFileValue && !$hasUpload) {
            return back()
                ->withErrors([
                    'vb_file_url' => __('admin.message.ban_phai_nhap_duong_dan_tap_tin_hoac_dinh_kem_fi'),
                ])
                ->withInput();
        }

        // qua được đây là hợp lệ
        $this->vanBanService->store($request);

        Toastr::success(__('admin.message.them_moi_thanh_cong'));
        return redirect()->route('cms.dashboard.cdn.van-ban.index');
    }


    public function edit(VanBan $vanBan)
    {

        // dd("suar");
        // dd($vanBan->toArray());
        $data = $this->vanBanService->prepareEdit($vanBan);
        return view('cmsbackend::screens.pages.van-ban.van-ban-edit', $data);
    }

    // public function update(UpdateVanBanRequest $request, VanBan $vanBan)
    // {

    //     $this->vanBanService->updateVB($request, $vanBan);
    //     Toastr::success(__('Cập nhật thành công'));
    //     return redirect()->route('cms.dashboard.cdn.van-ban.index');
    // }

    public function update(UpdateVanBanRequest $request, VanBan $vanBan)
    {
        $url = trim((string) $request->input('vb_file_url', ''));

        // fileDinhKem từ component (string / array / json)
        $fd = $request->input('fileDinhKem');

        $hasFileValue = false;
        if (is_string($fd)) {
            $s = trim($fd);
            if ($s !== '' && $s !== '[]' && $s !== '{}' && $s !== 'null' && $s !== 'undefined') {
                $hasFileValue = true;
            }
        } elseif (is_array($fd)) {
            $fd = array_values(array_filter($fd, fn($x) => trim((string)$x) !== ''));
            if (count($fd) > 0) {
                $hasFileValue = true;
            }
        }

        // nếu có upload file thật (phòng hờ)
        $hasUpload = $request->hasFile('fileDinhKem');
        $hasExistingFile = !$request->has('fileDinhKem') && $vanBan->getMedia('fileDinhKem')->isNotEmpty();

        if ($url === '' && !$hasFileValue && !$hasUpload && !$hasExistingFile) {
            return back()
                ->withErrors([
                    'vb_file_url' => __('admin.message.ban_phai_nhap_duong_dan_tap_tin_hoac_dinh_kem_fi'),
                ])
                ->withInput();
        }

        // qua được đây là hợp lệ
        $this->vanBanService->updateVB($request, $vanBan);

        Toastr::success(__('admin.message.cap_nhat_thanh_cong'));
        return redirect()->route('cms.dashboard.cdn.van-ban.index');
    }


    public function destroy(string $id)
    {
        $this->vanBanService->destroy($id);
        return response()->json(['status' => 1, 'message' => __('admin.message.xoa_thanh_cong')]);
    }
}
