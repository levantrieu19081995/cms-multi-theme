<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Models\Marquee;
use Modules\CmsBackend\Http\Requests\MarqueeRequest;
use Modules\CmsBackend\Services\MarqueeService;

class CDNMarqueeController extends Controller
{

    protected MarqueeService $marqueeService;

    private $user;
    protected $permissionsBase;

    public function __construct(MarqueeService $marqueeService)
    {
        $this->marqueeService = $marqueeService;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->permissionsBase = [
                'canView' => $this->user->hasAnyPermission([PermissionsEnum::MARQUEE_VIEW->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::MARQUEE_UPDATE->label()]),
                'canCreate' => $this->user->hasAnyPermission([PermissionsEnum::MARQUEE_CREATE->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::MARQUEE_APPROVE->label()]),
                'canDelete' => $this->user->hasAnyPermission([PermissionsEnum::MARQUEE_DELETE->label()]),
            ];
            return $next($request);
        });
        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::MARQUEE_VIEW->label(),
            PermissionsEnum::MARQUEE_CREATE->label(),
            PermissionsEnum::MARQUEE_UPDATE->label(),
            PermissionsEnum::MARQUEE_APPROVE->label(),
            PermissionsEnum::MARQUEE_DELETE->label(),
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::MARQUEE_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::MARQUEE_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::MARQUEE_APPROVE->label())
            ->only(['approve']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::MARQUEE_DELETE->label())
            ->only(['destroy']);

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = trim($request->filter ?? '');
        $list = $this->marqueeService->getDsMarquee($filter);
        return view('cmsbackend::screens.pages.marquee.index', compact(['list', 'filter']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $item = new Marquee;
        return view('cmsbackend::screens.pages.marquee.edit', compact(['item']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MarqueeRequest $request)
    {
        $data = $request->all();
        // Tạo mới
        $marquee = $this->marqueeService->create($data);

        // Xử lý avatar nếu có
        if (!empty($data['avatar'])) {
            $marquee->addMediaFromUrl($data['avatar'])->toMediaCollection('avatars');
        }

        Toastr::success(__('admin.message.them_moi_thanh_cong'));
        return redirect()->route('cms.dashboard.cdn.marquee.index');
    }


    public function edit(Marquee $marquee)
    {
        $item = $marquee;
        return view('cmsbackend::screens.pages.marquee.edit', compact(['item']));
    }

    // /**
    //  * Update the specified resource in storage.
    //  */
  public function update(MarqueeRequest $request, Marquee $marquee)
    {
        $data = $request->all();

        $this->marqueeService->updateMarquee($data, $marquee);

        if ($request->has('avatar')) {
            if (($request->avatar !== $marquee->getFirstMediaUrl('avatars')) && $request->avatar != null && $request->avatar != '') {
                $marquee->clearMediaCollection('avatars');
                $marquee->addMediaFromUrl($request->avatar)->toMediaCollection('avatars');
            }
        }


        Toastr::success(__('admin.message.cap_nhat_thanh_cong'));

        return redirect()->route('cms.dashboard.cdn.marquee.index');
    }


    // /**
    //  * Remove the specified resource from storage.
    //  */
    public function destroy(string $id)
    {
        $this->marqueeService->destroy($id);
        return response()->json(['status' => 1, 'message' => __('admin.message.xoa_thanh_cong')]);
    }

     public function approve($id)
    {
        $result = $this->marqueeService->approveMarquee($id);
        if ($result) {
                 Toastr::success(__('admin.message.da_cap_nhat'), __('admin.message.thanh_cong'));
            } else {
                Toastr::error(__('admin.message.da_co_loi_xay_ra'),__('admin.message.khong_thanh_cong'));
            }
        return redirect()->route('cms.dashboard.cdn.marquee.index');
    }
}