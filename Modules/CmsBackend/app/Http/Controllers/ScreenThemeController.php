<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Illuminate\Routing\Controller;

/**
 * The theme is managed in Developer Settings (Theme Management). This old endpoint of General settings is kept so the route
 * name still exists, but it no longer changes anything: it answers 403 to everybody, whatever the request contains.
 */
final class ScreenThemeController extends Controller
{
    public function update(): never
    {
        abort(403, __('admin.message.theme_managed_by_developer'));
    }
}
