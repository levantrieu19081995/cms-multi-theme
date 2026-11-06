<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialPermissions;

/**
 * Shared by the screens of "Truyền thông đa kênh". While the tables do not exist yet (migration not run) every screen
 * shows an explanatory notice instead of a SQL error.
 */
abstract class SocialBaseController extends Controller
{
    public function __construct(protected SocialPublicationDataProvider $data) {}

    /** Register last in a controller constructor, so the permission middleware answers first (403) and this one second. */
    protected function ensureInstalled(): void
    {
        $this->middleware(function ($request, $next) {
            if (! $this->data->installed()) {
                return response()->view('cmsbackend::screens.pages.truyen-thong.not-installed');
            }

            return $next($request);
        });
    }

    /** The project's Toastr helper has no error(): an error toast is toast($message, 'error'). */
    protected function flashError(string $message): void
    {
        \App\Facades\Toastr::toast($message, 'error');
    }

    /** Permission middleware string for one or more permissions (names in the database are the enum labels). */
    protected function requires(PermissionsEnum ...$permissions): string
    {
        return 'cms.check.permission:'.implode(',', array_map(fn (PermissionsEnum $permission) => $permission->label(), $permissions));
    }

    /** @return array<string, bool> permissions of the signed-in user, for views */
    protected function abilities(): array
    {
        return SocialPermissions::map(request()->user());
    }
}
