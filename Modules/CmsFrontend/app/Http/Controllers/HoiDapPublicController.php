<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CmsFrontend\Services\HoiDapService;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Modules\CmsFrontend\Http\Requests\HoiDapRequest;
use function Termwind\parse;

class HoiDapPublicController extends Controller
{
    protected $service;

    public function __construct(HoiDapService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $questions = $this->service->paginate(
            true,
            ['is_approved'],
            [],
            [],
            'id',
            'desc',
            3,
            []
        );
        return view('cmsfrontend::screens.pages.frontend.hoi-dap.index', compact('questions'));
        // return response()->json($questions);
    }
    public function gui()
    {
        return view('cmsfrontend::screens.pages.frontend.hoi-dap.gui');
    }

    public function store(HoiDapRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id() ?? 1;

        $result = $this->service->store($data);

        if ($result) {
            Toastr::success(__('messages.question_sent'), __('messages.success'));
            return back()->with('success', __('messages.question_sent'));
        } else {
            Toastr::error(__('messages.error_occurred'), __('messages.send_failed'));
            return back()->with('error', __('messages.question_failed'));
        }
    }



    public function show($id)
    {
        $questions = $this->service->getApproved();
        $question = collect($questions)->firstWhere('id', $id);

        if (!$question) {
            abort(404); // Hoặc redirect nếu không tìm thấy
        }

        return view('cmsfrontend::screens.pages.frontend.hoi-dap.show', [
            'question' => $question,
        ]);
    }
}
