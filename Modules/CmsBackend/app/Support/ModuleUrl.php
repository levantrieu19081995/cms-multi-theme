<?php

namespace Modules\CmsBackend\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ModuleUrl
{
    public static function name(string $name): string
    {
        $original = str_starts_with($name, 'cms.') ? substr($name, strlen('cms.')) : $name;
        $candidates = ['cms.'.$original];
        if (str_starts_with($original, 'cdn.')) {
            $candidates[] = 'cms.dashboard.'.$original;
        }
        foreach (['bai-viet', 'trang', 'banner', 'lien-ket', 'thu-vien-hinh-anh'] as $resource) {
            if (str_starts_with($original, $resource.'.')) {
                $candidates[] = 'cms.dashboard.cdn.'.$original;
            }
        }
        foreach ($candidates as $candidate) {
            if (Route::has($candidate)) {
                return $candidate;
            }
        }

        return $original;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return \route(self::name($name), $parameters, $absolute);
    }

    public static function url(?string $path = null, mixed $parameters = [], ?bool $secure = null): mixed
    {
        if ($path === null) {
            return \url();
        }
        if ($path === '' || str_starts_with($path, '#') || preg_match('~^(?:https?:|javascript:|mailto:|tel:|data:|//)~i', $path)) {
            return $path;
        }

        // Match application paths before url() prepends APP_URL. When APP_URL contains a
        // deployment subdirectory (for example /nam-binh-duong), matching the expanded URL
        // can mistake that first path segment for a route/domain parameter and produce a
        // protocol-relative URL such as //nam-binh-duong/dashboard/....
        $inputPath = parse_url($path, PHP_URL_PATH) ?: '/';
        $routePath = '/'.ltrim($inputPath, '/');

        // Addresses stored before the URL standard: "/cms/dashboard[/cdn]/x" is "/cms/x", and a public page kept under the
        // old "/cms/..." prefix (tin-tuc, x.htm, en/...) is the same page at the root of the site.
        $adminPrefix = '/'.trim((string) config('cmsbackend.route_prefix', 'cms'), '/');
        if ($adminPrefix !== '/' && str_starts_with($routePath, $adminPrefix.'/')) {
            $relative = substr($routePath, strlen($adminPrefix));
            $stripped = preg_replace('#^/dashboard(/cdn)?(?=/|$)#', '', $relative);
            if ($stripped !== $relative) {
                $path = $adminPrefix.($stripped === '' ? '' : $stripped).(parse_url($path, PHP_URL_QUERY) ? '?'.parse_url($path, PHP_URL_QUERY) : '');
                $routePath = $adminPrefix.($stripped === '' ? '' : $stripped);
            } else {
                try {
                    $old = app('router')->getRoutes()->match(Request::create($routePath, 'GET'));
                    if (($old->defaults['compat'] ?? null) === 'public') {
                        $path = $relative.(parse_url($path, PHP_URL_QUERY) ? '?'.parse_url($path, PHP_URL_QUERY) : '');
                        $routePath = $relative;
                    }
                } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
                    // not an old public address
                }
            }
        }
        try {
            $matched = app('router')->getRoutes()->match(Request::create($routePath, 'GET'));
            $name = $matched->getName();
            if ($name) {
                $target = str_starts_with($name, 'cms.') ? $name : self::name($name);
                if (Route::has($target)) {
                    $query = parse_url($path, PHP_URL_QUERY);
                    $fragment = parse_url($path, PHP_URL_FRAGMENT);

                    return \route($target, $matched->parameters(), true)
                        .($query ? '?'.$query : '')
                        .($fragment ? '#'.$fragment : '');
                }
            }
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            // Static files and database links continue through the existing URL handling.
        }

        $url = \url($path, $parameters, $secure);
        $host = parse_url($url, PHP_URL_HOST);
        $localHosts = [strtolower(request()->getHost()), strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '')];
        if ($host && ! in_array(strtolower($host), $localHosts, true)) {
            return $url;
        }
        try {
            $matched = app('router')->getRoutes()->match(Request::create($url, 'GET'));
            $name = $matched->getName();
            if ($name && ! str_starts_with($name, 'cms.')) {
                $target = self::name($name);
                if ($target !== $name && Route::has($target)) {
                    $mapped = \route($target, $matched->parameters());
                    $query = parse_url($url, PHP_URL_QUERY);
                    $fragment = parse_url($url, PHP_URL_FRAGMENT);

                    return $mapped.($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');
                }
            }
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            // Shared endpoints and static files keep their existing URLs.
        }

        // Match legacy database links even when the source project's routes are disabled.
        $prefix = '/'.trim(config('cmsbackend.route_prefix', 'cms'), '/');
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        // Tiền tố rỗng (CMS_ROUTE_PREFIX="") nghĩa là các màn hình nằm ở gốc: không có địa chỉ cũ nào để ghép, và ghép sẽ ra "//đường-dẫn".
        if ($prefix !== '/' && $path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
            $candidatePath = $prefix.'/'.ltrim($path, '/');
            try {
                $matched = app('router')->getRoutes()->match(Request::create($candidatePath, 'GET'));
                if (str_starts_with($matched->getName() ?? '', 'cms.')) {
                    $query = parse_url($url, PHP_URL_QUERY);
                    $fragment = parse_url($url, PHP_URL_FRAGMENT);

                    return \url($candidatePath, [], $secure).($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');
                }
            } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
                // Shared endpoints and assets have no matching project route.
            }
        }

        return $url;
    }
}
