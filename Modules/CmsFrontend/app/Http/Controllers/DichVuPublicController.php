<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Diglactic\Breadcrumbs\Breadcrumbs;
use function Termwind\parse;
use Modules\CmsBackend\Services\DichVuService;

class DichVuPublicController extends Controller
{
    protected $dichvu;

    public function __construct(DichVuService $dichvu)
    {
        $this->dichvu = $dichvu;
    }



    public function index()
    {
        $dichvus = $this->dichvu->getAllActive();

        return view('cmsfrontend::screens.pages.frontend.dich-vu.index', compact('dichvus'));
    }
}
