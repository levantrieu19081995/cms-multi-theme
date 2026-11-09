<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Http\Requests\SurveyInfoRequest;
use Modules\CmsBackend\Http\Requests\SurveyStructureRequest;
use Modules\CmsBackend\Services\SurveyFormService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsBackend\Support\Riasec;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Quản trị khảo sát. Quyền theo từng route (xem routes/web.php); việc bật/tắt chức năng do Feature Registry quyết định, middleware cms.feature:survey chặn.
 */
class KhaoSatController extends Controller
{
    public function __construct(private SurveyService $surveys, private SurveyFormService $forms) {}

    public function index(Request $request)
    {
        $filters = ['q' => trim((string) $request->query('q')), 'status' => $request->query('status')];
        if ($filters['status'] !== null && ! in_array($filters['status'], [...SurveyStatus::values(), 'trashed'], true)) {
            $filters['status'] = null;
        }

        return view('cmsbackend::screens.pages.khao-sat.index', [
            'surveys' => $this->surveys->paginate($filters),
            'filters' => $filters,
            'statuses' => SurveyStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('cmsbackend::screens.pages.khao-sat.edit', ['survey' => new KhaoSat(['ks_status' => 'draft', 'ks_cho_phep_an_danh' => true, 'ks_so_lan_gui' => 1, 'ks_loai' => 'general']), 'caps' => null, 'structure' => []]);
    }

    public function store(SurveyInfoRequest $request): RedirectResponse
    {
        try {
            $survey = $this->surveys->create($request->validated(), Auth::id());
        } catch (SurveyRuleException $e) {
            return back()->withInput()->withErrors(['ks_ten' => $e->getMessage()]);
        }
        Toastr::success(__('admin.survey.saved'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.edit', $survey->ks_id)->with('tab', 'builder');
    }

    public function edit(KhaoSat $survey)
    {
        return view('cmsbackend::screens.pages.khao-sat.edit', [
            'survey' => $survey,
            'caps' => $this->surveys->capabilities($survey),
            'structure' => $this->surveys->structureForBuilder($survey),
        ]);
    }

    public function update(SurveyInfoRequest $request, KhaoSat $survey): RedirectResponse
    {
        try {
            $this->surveys->update($survey, $request->validated(), Auth::id());
        } catch (SurveyRuleException $e) {
            return back()->withInput()->with('tab', 'info')->withErrors(['ks_ten' => $e->getMessage()]);
        }
        Toastr::success(__('admin.survey.saved'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.edit', $survey->ks_id)->with('tab', 'info');
    }

    public function saveStructure(SurveyStructureRequest $request, KhaoSat $survey): RedirectResponse
    {
        try {
            $this->surveys->saveStructure($survey, $request->groups(), Auth::id());
        } catch (SurveyRuleException $e) {
            // Giữ lại những gì người dùng vừa dựng để không mất công sức.
            return back()->with('tab', 'builder')->with('structure_draft', $request->input('structure'))->withErrors(['structure' => $e->getMessage()]);
        }
        Toastr::success(__('admin.survey.structure_saved'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.edit', $survey->ks_id)->with('tab', 'builder');
    }

    public function changeStatus(Request $request, KhaoSat $survey): RedirectResponse
    {
        $to = SurveyStatus::tryFrom((string) $request->input('status'));
        if ($to === null) {
            abort(422);
        }
        try {
            $this->surveys->transition($survey, $to, Auth::id());
        } catch (SurveyRuleException $e) {
            Toastr::warning($e->getMessage());

            return back();
        }
        Toastr::success(__('admin.survey.status_changed', ['status' => $to->label()]), __('admin.survey.ok'));

        return back();
    }

    public function duplicate(KhaoSat $survey): RedirectResponse
    {
        $copy = $this->surveys->duplicate($survey, Auth::id());
        Toastr::success(__('admin.survey.duplicated'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.edit', $copy->ks_id);
    }

    /** Xem trước = trang công khai thật nhưng không ghi dữ liệu, không phụ thuộc trạng thái/thời gian. */
    public function preview(KhaoSat $survey)
    {
        return view('cmsfrontend::screens.pages.khao-sat.lam-bai', [
            'survey' => $survey,
            'form' => $this->forms->schema($survey),
            'preview' => true,
            'participation' => null,
            'identity' => ['collect' => (bool) $survey->ks_yeu_cau_thong_tin, 'anonymous_allowed' => (bool) $survey->ks_cho_phep_an_danh],
        ]);
    }

    public function destroy(KhaoSat $survey): RedirectResponse
    {
        try {
            $this->surveys->delete($survey);
        } catch (SurveyRuleException $e) {
            Toastr::warning($e->getMessage());

            return back();
        }
        Toastr::success(__('admin.survey.deleted'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat');
    }

    public function restore(KhaoSat $survey): RedirectResponse
    {
        $this->surveys->restore($survey);
        Toastr::success(__('admin.survey.restored'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.edit', $survey->ks_id);
    }

    /** Kiểu câu hỏi cho trình dựng (nhãn đã dịch). */
    public static function questionTypes(bool $career = false): array
    {
        $types = $career ? array_filter(SurveyQuestionType::cases(), fn (SurveyQuestionType $t) => Riasec::supports($t)) : SurveyQuestionType::cases();

        return array_values(array_map(fn (SurveyQuestionType $t) => ['value' => $t->value, 'label' => $t->label(), 'options' => $t->hasOptions()], $types));
    }
}
