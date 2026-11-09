<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\CareerService;
use Modules\CmsBackend\Support\Riasec;
use Modules\CmsFrontend\Models\CareerMajor;

/**
 * Cụm Định hướng nghề nghiệp: tên/mô tả 6 nhóm RIASEC và ngành gợi ý do trường tự khai báo. Cổng chức năng `career` và quyền nghenghiep-*
 * theo từng route (routes/web.php): xem = nghenghiep-view/nghenghiep-update, ghi = nghenghiep-update. Không có dữ liệu mẫu.
 */
class NgheNghiepCatalogController extends Controller
{
    public function __construct(private CareerService $career) {}

    public function index(Request $request)
    {
        $editing = $request->filled('sua') ? CareerMajor::query()->find((int) $request->query('sua')) : null;

        return view('cmsbackend::screens.pages.khao-sat.catalog', [
            'groups' => $this->career->groups(),
            'majors' => $this->career->majors(),
            'editing' => $editing,
        ]);
    }

    public function saveGroups(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (Riasec::LETTERS as $letter) {
            $rules["groups.$letter.name"] = ['nullable', 'string', 'max:'.CareerService::MAX_NAME];
            $rules["groups.$letter.description"] = ['nullable', 'string', 'max:'.CareerService::MAX_DESCRIPTION];
        }
        $data = $request->validate($rules);
        try {
            $this->career->saveGroups($data['groups'] ?? []);
        } catch (SurveyRuleException $e) {
            return back()->withInput()->withErrors(['groups' => $e->getMessage()]);
        }
        Toastr::success(__('career.admin.groups_saved'), __('admin.survey.ok'));

        return redirect()->route('cms.nghe-nghiep.catalog');
    }

    public function storeMajor(Request $request): RedirectResponse
    {
        return $this->persistMajor($request, null);
    }

    public function updateMajor(Request $request, CareerMajor $major): RedirectResponse
    {
        return $this->persistMajor($request, $major);
    }

    public function destroyMajor(CareerMajor $major): RedirectResponse
    {
        $this->career->deleteMajor($major);
        Toastr::success(__('career.admin.major_deleted'), __('admin.survey.ok'));

        return redirect()->route('cms.nghe-nghiep.catalog');
    }

    private function persistMajor(Request $request, ?CareerMajor $major): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [], [
            'name' => __('career.admin.major_name'),
            'code' => __('career.admin.major_code'),
            'description' => __('career.admin.major_description'),
            'url' => __('career.admin.major_url'),
            'order' => __('career.admin.major_order'),
        ]);
        $data['active'] = $request->boolean('active');
        try {
            $this->career->saveMajor($major, $data, Auth::id());
        } catch (SurveyRuleException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }
        Toastr::success(__('career.admin.major_saved'), __('admin.survey.ok'));

        return redirect()->route('cms.nghe-nghiep.catalog');
    }
}
