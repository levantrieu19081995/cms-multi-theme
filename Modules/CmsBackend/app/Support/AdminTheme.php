<?php

namespace Modules\CmsBackend\Support;

use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;

final class AdminTheme
{
    public static function request(Request $request): bool
    {
        return (bool) $request->user() && ($request->routeIs('cms.dashboard*', 'cms.setting.*', 'cms.khao-sat', 'cms.khao-sat.show') || self::sharedRequest($request));
    }

    public static function sharedRequest(Request $request): bool
    {
        $enabled = array_map(fn ($module) => $module->getName(), array_values(Module::allEnabled()));
        sort($enabled);
        if ($enabled !== ['CmsBackend', 'CmsFrontend'] || ! $request->user()) {
            return false;
        }

        // Only the selected project's shared administration routes; never frontend, guest or another module.
        return $request->routeIs('dashboard', 'profile.*', 'users.*', 'roles.*', 'menus.*', 'setting.*',
            'chuyen-muc.*', 'the.*', 'binh-luan.*', 'hinh-anh.*', 'video.*', 'file.*',
            'quang-cao.*', 'banner.*', 'lien-ket.*', 'activities.*', 'bai-viet.*', 'trang.*');
    }

    public static function active(object $item, string $route): bool
    {
        foreach ((array) ($item->slug ?? []) as $slug) {
            if ($route === $slug || str_starts_with($route, preg_replace('/\.index$/', '.', $slug))) {
                return true;
            }
        }
        foreach ($item->submenu ?? [] as $child) {
            if (self::active($child, $route)) {
                return true;
            }
        }

        return false;
    }

    public static function listPage(Request $request): bool
    {
        return str_ends_with($request->route()?->getName() ?? '', '.index')
            || ($request->routeIs('cms.khao-sat', 'cms.dashboard.khao-sat') && $request->route()?->getActionMethod() === 'index');
    }

    public static function recordPage(Request $request): ?array
    {
        if (! self::request($request) || ! in_array($request->route()?->getActionMethod(), ['create', 'edit', 'show'], true)) {
            return null;
        }
        $resources = [
            'cms.dashboard.cdn.bai-viet' => 'admin.resource.news', 'cms.dashboard.cdn.trang' => 'admin.resource.page',
            'cms.dashboard.su-kien' => 'admin.resource.event', 'cms.dashboard.guong-tieu-bieu' => 'admin.resource.role_model',
            'chuyen-muc' => 'admin.resource.category', 'cms.dashboard.doi-tac' => 'admin.resource.partner',
            'cms.dashboard.dich-vu' => 'admin.resource.service', 'cms.dashboard.cdn.van-ban' => 'admin.resource.document',
            'cms.dashboard.cdn.loai-van-ban' => 'admin.resource.document_type', 'cms.dashboard.cdn.nhom-van-ban' => 'admin.resource.document_group',
            'cms.dashboard.cdn.banner' => 'admin.resource.banner', 'cms.dashboard.cdn.marquee' => 'admin.resource.marquee',
            'cms.dashboard.cdn.lien-ket' => 'admin.resource.website_link',
        ];
        $mode = $request->route()->getActionMethod();
        $resource = preg_replace('/\.(create|edit|show)$/', '', $request->route()->getName() ?? '');
        if ($mode === 'show' && $request->routeIs('cms.dashboard.khao-sat', 'cms.khao-sat.show')) {
            return ['title' => __('admin.record.show').' '.__('admin.resource.survey'), 'mode' => $mode, 'index' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.khao-sat')];
        }
        if (! isset($resources[$resource])) {
            return null;
        }
        $name = __($resources[$resource]);
        $action = $mode === 'create' ? __('admin.record.create') : ($mode === 'show' ? __('admin.record.show') : __('admin.record.edit'));

        return ['title' => $action.' '.$name, 'label' => __('admin.record.manage').' '.$name, 'mode' => $mode, 'index' => \Modules\CmsBackend\Support\ModuleUrl::route($resource.'.index')];
    }

    public static function workspacePage(Request $request): ?string
    {
        if (! self::request($request)) {
            return null;
        }

        return match (true) {
            $request->routeIs('roles.create', 'roles.edit') => 'role',
            $request->routeIs('users.create', 'users.edit') => 'user',
            $request->routeIs('setting.general') => 'settings-general',
            $request->routeIs('cms.setting.trang-chu') => 'settings-home',
            default => null,
        };
    }

    public static function listTitle(array $items, string $route): string
    {
        if (in_array($route, ['cms.khao-sat', 'cms.dashboard.khao-sat'], true)) {
            return __('admin.menu.quan_ly_khao_sat_dhnn');
        }
        foreach ($items as $item) {
            if (isset($item->submenu)) {
                $title = self::listTitle((array) $item->submenu, $route);
                if ($title !== '') {
                    return $title;
                }
            } elseif (in_array($route, (array) ($item->slug ?? []), true)) {
                return __($item->name ?? __('admin.ui.quan_ly_danh_sach'));
            }
        }

        return '';
    }
}
