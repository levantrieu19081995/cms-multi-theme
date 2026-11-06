<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Modules\CmsFrontend\Http\Requests\LienHeRequest;
use Modules\CmsFrontend\Services\LienHeService;

class LienHeController extends Controller
{
    public function __construct(protected LienHeService $service) {}

    public function index()
    {
        return view('cmsfrontend::screens.pages.frontend.lien-he.index', [
            'breadcrumbs' => Breadcrumbs::render('cms.lien-he'),
        ]);
    }

    public function store(LienHeRequest $request)
    {
        // Honeypot: người dùng thật không thấy và không nhập `website`. Có giá trị thì từ chối, không ghi DB, không báo lý do.
        if ($request->filled('website')) {
            return back()->with('error', __('messages.try_again'));
        }

        try {
            $this->service->store($request->validated());
        } catch (\Throwable $e) {
            report($e);
            Toastr::warning(__('messages.error_occurred'), __('messages.send_failed'));

            return back()->withInput()->with('error', __('messages.try_again'));
        }

        Toastr::success(__('messages.feedback_sent'), __('messages.success'));

        return back()->with('success', __('messages.sent_ok'));
    }
}
