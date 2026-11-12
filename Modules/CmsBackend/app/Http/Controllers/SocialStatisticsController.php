<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * The statistics now live in the page "Tổng quan - thống kê" (SocialOverviewController). The old address is kept so that a saved link
 * or a bookmark still works: it goes to that page, which decides what the user may see.
 */
class SocialStatisticsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(ModuleUrl::route('cms.dashboard.truyen-thong.index'));
    }
}
