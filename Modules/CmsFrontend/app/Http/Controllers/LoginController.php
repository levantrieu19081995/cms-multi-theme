<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('cms.dashboard');
        }

        return view('cmsfrontend::screens.pages.auth.login');
    }

    public function store(LoginRequest $request, AuthenticatedSessionController $sessions): RedirectResponse|JsonResponse
    {
        $response = $sessions->store($request);

        // AuthenticatedSessionController keeps the legacy redirect response for failed
        // credentials. The themed login form submits with fetch, so convert that
        // redirect (and its flashed errors) to the JSON validation contract expected
        // by auth/login.js. Only an authenticated request may receive a redirect JSON.
        if ($request->expectsJson() && ! Auth::check()) {
            $viewErrors = $request->session()->get('errors');
            $errors = $viewErrors instanceof ViewErrorBag
                ? $viewErrors->getBag('default')->toArray()
                : ['login' => [trans('auth.failed')]];

            $request->session()->forget('errors');

            return response()->json([
                'message' => collect($errors)->flatten()->first() ?: trans('auth.failed'),
                'errors' => $errors,
            ], 422);
        }

        if ($response->getTargetUrl() === url('/')) {
            $response = redirect()->route('cms.dashboard');
        }

        // The login screen posts with fetch so its opening animation only runs after the server accepted the login.
        // Failures still raise ValidationException (422 JSON); the destination stays whatever the redirect above decided.
        if ($request->expectsJson()) {
            return response()->json(['redirect' => $response->getTargetUrl()]);
        }

        return $response;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cms.login');
    }
}
