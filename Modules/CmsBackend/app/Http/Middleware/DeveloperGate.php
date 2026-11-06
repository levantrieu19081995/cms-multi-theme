<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\Developer\DeveloperAccess;
use Modules\CmsBackend\Support\ModuleUrl;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard of the Developer Settings routes, independent of the CMS sign-in: no hash configured = 404, otherwise the session
 * must carry the Developer unlock; a locked GET goes to the password form (never to the CMS login), a locked write is 403.
 */
final class DeveloperGate
{
    public function __construct(private DeveloperAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->enabled(), 404);

        if (! $this->access->unlocked($request->session())) {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return redirect(ModuleUrl::route('cms.dashboard.developer-settings.unlock'));
            }
            abort(403);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
