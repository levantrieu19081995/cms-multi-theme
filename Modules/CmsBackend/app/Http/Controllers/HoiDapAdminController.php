<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\HoiDapService;

class HoiDapAdminController extends Controller
{
    protected $hoiDapService;
    private $user;
    protected $permissionsBase;

    public function __construct(HoiDapService $hoiDapService)
    {
        $this->hoiDapService = $hoiDapService;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                'canView' => $this->user->hasAnyPermission([PermissionsEnum::HOIDAP_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::HOIDAP_UPDATE->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::HOIDAP_APPROVE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::HOIDAP_DELETE->label()]),
            ];
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::HOIDAP_VIEW->label(),
            PermissionsEnum::HOIDAP_UPDATE->label(),
            PermissionsEnum::HOIDAP_APPROVE->label(),
            PermissionsEnum::HOIDAP_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::HOIDAP_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::HOIDAP_APPROVE->label())
            ->only(['approve']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::HOIDAP_DELETE->label())
            ->only(['destroy']);
    }

    public function index(Request $request)
    {
        $pageSize = $request->pageSize ?? 5;
        $mode = $request->mode;
        $page = $request->mode;
        $filter = trim(strip_tags($request->query('filter')));
        $questions = $this->hoiDapService->paginate($filter, ['title', 'name'], [], []);
        // $questions = $this->service ->getAll();
        return view('cmsbackend::screens.pages.hoi-dap.index', compact('questions'));
    }

    public function edit($id)
    {
        $question = $this->hoiDapService->get($id);
        return view('cmsbackend::screens.pages.hoi-dap.edit', compact('question'));
    }

    public function update(Request $request, $id)
    {
        $result = $this->hoiDapService->answer($id, $request->input('answer'), auth()->id());
        if ($result) {
            Toastr::success(__('admin.message.da_tra_loi_cau_hoi'), __('admin.message.thanh_cong'));
        } else {
            Toastr::error(__('admin.message.da_co_loi_xay_ra'), __('admin.message.khong_thanh_cong'));
        }
        return redirect()->route('cms.dashboard.hoi-dap.index');
    }

    public function approve($id)
    {
        $result = $this->hoiDapService->approve($id);
        if ($result) {
            Toastr::success(__('admin.message.da_cap_nhat'), __('admin.message.thanh_cong'));
        } else {
            Toastr::error(__('admin.message.da_co_loi_xay_ra'), __('admin.message.khong_thanh_cong'));
        }
        return redirect()->route('cms.dashboard.hoi-dap.index');
    }


    public function destroy($id)
    {
        $result = $this->hoiDapService->destroy($id);
        if ($result) {
            Toastr::success(__('admin.message.da_xoa_cau_hoi'), __('admin.message.thanh_cong'));
        } else {
            Toastr::error(__('admin.message.da_co_loi_xay_ra'), __('admin.message.khong_thanh_cong'));
        }
        return ResponseHelper::responseJson(200, 'success', __('admin.message.xoa_thanh_cong'));
    }
}
