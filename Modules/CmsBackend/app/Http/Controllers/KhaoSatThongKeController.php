<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\SurveyReportService;
use Modules\CmsFrontend\Models\KhaoSat;

/** Thống kê khảo sát. Quyền: khaosat-statistics-view (routes/web.php). */
class KhaoSatThongKeController extends Controller
{
    public function __construct(private SurveyReportService $reports) {}

    public function index(Request $request, KhaoSat $survey)
    {
        $filters = $this->reports->normalizeFilters($request->query());

        return view('cmsbackend::screens.pages.khao-sat.statistics', [
            'survey' => $survey,
            'filters' => $filters,
            'stats' => $this->reports->statistics($survey, $filters),
        ]);
    }
}
