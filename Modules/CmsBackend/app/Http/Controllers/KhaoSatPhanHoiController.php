<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CmsBackend\Services\CareerService;
use Modules\CmsBackend\Services\SurveyReportService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;

/**
 * Phản hồi của khảo sát: danh sách, chi tiết, xóa (mềm) và xuất Excel. Quyền theo từng route (routes/web.php).
 */
class KhaoSatPhanHoiController extends Controller
{
    public function __construct(private SurveyReportService $reports) {}

    public function index(Request $request, KhaoSat $survey)
    {
        $filters = $this->reports->normalizeFilters($request->query());

        return view('cmsbackend::screens.pages.khao-sat.responses', [
            'survey' => $survey,
            'filters' => $filters,
            'participations' => $this->reports->list($survey, $filters),
        ]);
    }

    public function show(KhaoSatUser $participation)
    {
        return view('cmsbackend::screens.pages.khao-sat.show', [
            'participation' => $participation,
            'survey' => $participation->khaoSat,
            'answers' => $this->reports->detail($participation),
            'career' => $participation->khaoSat?->ks_loai === SurveyService::CAREER ? app(CareerService::class)->resultFor($participation) : null,
        ]);
    }

    public function destroy(KhaoSatUser $participation): RedirectResponse
    {
        $surveyId = $participation->ks_id;
        $this->reports->deleteParticipation($participation);
        Toastr::success(__('admin.survey_report.deleted'), __('admin.survey.ok'));

        return redirect()->route('cms.khao-sat.responses', $surveyId);
    }

    /** Xuất Excel theo đúng bộ lọc đang áp dụng. Chi tiết mặc định không gồm thông tin liên hệ. */
    public function export(Request $request, KhaoSat $survey)
    {
        $type = $request->query('type') === 'detail' ? 'detail' : 'summary';
        $filters = $this->reports->normalizeFilters($request->query());
        $withContact = $type === 'detail' && $request->boolean('contact');
        $name = sprintf('khao-sat-%s-%s-%s.xlsx', $survey->ks_ma ?: $survey->ks_id, $type === 'detail' ? 'chi-tiet' : 'tong-hop', now()->format('Ymd-His'));

        Log::info('survey.export', ['survey_id' => $survey->ks_id, 'type' => $type, 'contact' => $withContact, 'filters' => $filters, 'user_id' => Auth::id()]);

        return Excel::download(
            $type === 'detail' ? $this->reports->detailExport($survey, $filters, $withContact) : $this->reports->summaryExport($survey, $filters),
            $name,
        );
    }
}
