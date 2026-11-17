<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\CmsBackend\Services\LoginDesigns;
use Modules\CmsBackend\Support\ModuleUrl;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Tab "Đăng nhập" of Developer Settings: which login design each project uses, and a preview of any design. Only the Developer
 * session reaches it (middleware cms.developer); the rules and the audit are in LoginDesigns. The sign-in controllers are untouched.
 */
final class DeveloperLoginController extends Controller
{
    public function __construct(private LoginDesigns $designs) {}

    /** Save the choice of every project listed in the form; an empty value gives the project back to its theme's own design. */
    public function save(Request $request): RedirectResponse
    {
        $submitted = $request->input('designs', []);
        $submitted = is_array($submitted) ? $submitted : [];
        $changed = 0;

        try {
            foreach (array_keys(ProjectContext::projects()) as $project) {
                if (! array_key_exists($project, $submitted) || ! is_string($submitted[$project])) {
                    continue;
                }
                $wanted = trim($submitted[$project]) === '' ? null : trim($submitted[$project]);
                // A project whose stored choice is unusable counts as "no choice": an empty value then changes nothing.
                if ($wanted === $this->designs->assigned($project)) {
                    continue;
                }
                $this->designs->assign($project, $wanted, $request->ip());
                $changed++;
            }
        } catch (InvalidArgumentException $e) {
            Toastr::toast(__('admin.developer.'.$e->getMessage()), 'error');

            return redirect(ModuleUrl::route('cms.dashboard.developer-settings', ['tab' => 'login']));
        }

        if ($changed > 0) {
            Toastr::success(__('admin.developer.login_saved'), __('admin.message.thanh_cong'));
        } else {
            Toastr::toast(__('admin.developer.login_unchanged'), 'info');
        }

        return redirect(ModuleUrl::route('cms.dashboard.developer-settings', ['tab' => 'login']));
    }

    /** One design shown as the sign-in page, for the iframe of the tab. Nothing is stored; the form inside the frame is inert (sandbox). */
    public function preview(string $design)
    {
        try {
            $this->designs->preview($design);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response()
            ->view('cmsfrontend::screens.pages.auth.login')
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex');
    }
}
