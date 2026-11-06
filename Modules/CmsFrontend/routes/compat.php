<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Old addresses (temporary redirects, GET only)
|--------------------------------------------------------------------------
| Loaded under the administration prefix (/cms). They only send an old address to its canonical one; nothing here shows
| data. Writes (POST/PUT/PATCH/DELETE) are not redirected: forms and scripts post straight to the new addresses. Redirects
| are temporary (302) until the new addresses have been confirmed stable; then switch to 301.
|
|   /cms/dashboard[/cdn]/x      ->  /cms/x                  (administration)
|   /cms/tin-tuc, /cms/x.htm,   ->  /tin-tuc, /x.htm, ...  (public site, when the old address cannot be an administration
|   /cms/en/..., sitemap, ...                                address; /cms/su-kien, /cms/lien-he, ... now belong to the admin)
*/

$to = static function (Request $request, string $path, bool $admin = false) {
    $prefix = '/'.trim((string) config('cmsbackend.route_prefix', 'cms'), '/');
    $target = ($admin ? $prefix : '').'/'.ltrim($path, '/');
    $query = $request->getQueryString();

    return redirect()->to(rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/').($target === '/' ? '/' : rtrim($target, '/')).($query ? '?'.$query : ''), 302);
};

// Administration: drop "dashboard" and the technical "cdn" level. The path is read from the request itself (route
// parameters can carry application-wide defaults).
Route::get('dashboard/{path?}', function (Request $request) use ($to) {
    $prefix = trim((string) config('cmsbackend.route_prefix', 'cms'), '/');
    $relative = substr(trim($request->path(), '/'), strlen($prefix));
    $relative = preg_replace('#^/?dashboard(/cdn)?(?=/|$)#', '', $relative);

    return $to($request, ltrim((string) $relative, '/'), true);
})->where('path', '.*')->defaults('compat', 'admin');

// Public site: only addresses that cannot be administration addresses. The target is the same path without the /cms prefix
// (taken from the request, not from route parameters, so route defaults can never change it).
$toPublic = static function (Request $request) use ($to) {
    $prefix = trim((string) config('cmsbackend.route_prefix', 'cms'), '/');
    $path = trim($request->path(), '/');

    return $to($request, $prefix !== '' && str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path);
};

Route::get('tin-tuc/{category?}', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
Route::get('{slug}.htm', fn (Request $request) => $toPublic($request))->where('slug', '[^/]+')->defaults('compat', 'public');
Route::get('en/{path?}', fn (Request $request) => $toPublic($request))->where('path', '.*')->defaults('compat', 'public');
Route::get('sitemap.xml', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
Route::get('thu-vien-video/{slug?}', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
Route::get('yeu-cau-dang-nhap', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
Route::get('social-image/{post}/preview.jpg', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
Route::get('soc-trang', fn (Request $request) => $toPublic($request))->defaults('compat', 'public');
