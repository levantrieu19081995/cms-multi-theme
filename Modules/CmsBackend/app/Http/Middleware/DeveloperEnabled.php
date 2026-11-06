<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\Developer\DeveloperAccess;
use Symfony\Component\HttpFoundation\Response;

/**
 * First gate of Developer Settings: without DEVELOPER_ACCESS_TOKEN_HASH every route answers 404, to everyone, and reveals
 * nothing. Developer Settings does not use the CMS authentication middleware at all.
 */
final class DeveloperEnabled
{
    public function __construct(private DeveloperAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->enabled(), 404);

        // The debug bar records request payloads (it would show the submitted Developer key on the next page): off here.
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return $next($request);
    }
}
