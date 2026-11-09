<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

class ProjectMenu extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $twinMenu = $this->getTwinMenu();

        // Share all menuData to all the views
        $this->app->make('view')->share('menuData', $twinMenu);

        // View::composer('*', function ($view) {
        //     $user = auth()->user();
        //     [$vertical, $horizontal] = $this->getTwinMenu(); // $vertical là stdClass

        //     if ($user) {
        //         $userPermSlugs = $this->userPermissionSlugs($user);
        //         // dò kiểu phần tử menu hiện tại (object/array)
        //         $asObject = is_object(($vertical->menu[0] ?? null));
        //         $vertical->menu = $this->filterMenuByRoler($vertical->menu, $userPermSlugs, null, $asObject);
        //     }

        //     $view->with('menuData', [$vertical, $horizontal]);
        // });

        View::composer('*', function ($view) {
            $user = auth()->user();
            [$vertical, $horizontal] = $this->getTwinMenu();

            if ($user) {
                $userPermSlugs = $this->userPermissionSlugs($user); // <-- dùng hàm mới
                $asObject = is_object(($vertical->menu[0] ?? null));
                $vertical->menu = $this->filterMenuByRoler($vertical->menu, $userPermSlugs, null, $asObject);
            }

            $vertical->menu = $this->localizeMenu($vertical->menu);
            $horizontal->menu = $this->localizeMenu($horizontal->menu);
            $view->with('menuData', [$vertical, $horizontal]);
        });
    }

    /**
     * Menu names live in the JSON files in Vietnamese and carry a translation key (`i18n`, e.g. admin.menu.posts). The
     * name shown is resolved per request in the administration language (session/cookie), never stored in the menu cache.
     * Items without a key, or whose key has no text, keep the name written in the file.
     *
     * @param  array<int, object>  $items
     * @return array<int, object>
     */
    private function localizeMenu(array $items): array
    {
        $items = unserialize(serialize($items));
        foreach ($items as $item) {
            $key = $item->i18n ?? null;
            if (is_string($key) && \Illuminate\Support\Facades\Lang::has($key)) {
                if (isset($item->menuHeader)) {
                    $item->menuHeader = __($key);
                } else {
                    $item->name = __($key);
                }
            }
            if (! empty($item->submenu) && is_array($item->submenu)) {
                $item->submenu = $this->localizeMenu($item->submenu);
            }
        }

        return $items;
    }

    public function forUser($user): array
    {
        $cacheKey = '_cms_screen_menu_'.($user?->getAuthIdentifier() ?? 'guest');
        if (request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }
        [$vertical, $horizontal] = $this->getTwinMenu();
        if ($user) {
            $slugs = $this->userPermissionSlugs($user);
            $vertical->menu = $this->filterMenuByRoler($vertical->menu, $slugs);
        }
        $vertical->menu = $this->localizeMenu($vertical->menu);
        $horizontal->menu = $this->localizeMenu($horizontal->menu);
        $this->scopeMenuUrls($vertical->menu);
        $this->scopeMenuUrls($horizontal->menu);
        request()->attributes->set($cacheKey, [$vertical, $horizontal]);

        return [$vertical, $horizontal];
    }

    private function scopeMenuUrls(array $items): void
    {
        foreach ($items as $item) {
            // The slug of an item is a route name: the link is generated from it, so it follows the URL structure
            // (/cms/<function>) without any path written in the menu file. Items without a usable route keep their url.
            $routeName = isset($item->slug) && is_string($item->slug) ? ModuleUrl::name($item->slug) : null;
            if ($routeName !== null && \Illuminate\Support\Facades\Route::has($routeName)) {
                try {
                    $item->url = route($routeName);
                } catch (\Illuminate\Routing\Exceptions\UrlGenerationException) {
                    // needs parameters: fall back to the stored url below
                }
            }
            if (isset($item->url)) {
                $item->url = ModuleUrl::url($item->url);
            }
            if (isset($item->submenu)) {
                $this->scopeMenuUrls($item->submenu);
            }
        }
    }

    public function getTwinMenu()
    {
        $cacheKey = 'cms_twin_menu';
        if (! config('app.debug')) {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
        }

        $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
        $verticalMenuData = json_decode($verticalMenuJson);
        $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
        $horizontalMenuData = json_decode($horizontalMenuJson);

        $modules = array_filter([Module::find('CmsBackend')]);
        foreach ($modules as $module) {
            // Auto load vertical menu
            $moduleVerticalMenuFile = $module->getPath().'/resources/menu/project-verticalMenu.json';
            if (file_exists($moduleVerticalMenuFile)) {
                $moduleVerticalMenuJson = file_get_contents($moduleVerticalMenuFile);
                $moduleVerticalMenuData = json_decode($moduleVerticalMenuJson);
                // $verticalMenuData->menu = array_merge($verticalMenuData->menu, $moduleVerticalMenuData->menu);

                foreach ($moduleVerticalMenuData->menu as $moduleMenuItem) {
                    $found = false;
                    foreach ($verticalMenuData->menu as $index => $mainMenuItem) {
                        // Prefer to match by 'key' if both have it
                        if (
                            isset($mainMenuItem->key, $moduleMenuItem->key) &&
                            $mainMenuItem->key === $moduleMenuItem->key
                        ) {
                            $verticalMenuData->menu[$index] = $moduleMenuItem;
                            $found = true;
                            break;
                        }

                        // Fallback to 'name' if 'key' is missing
                        elseif (
                            (! isset($mainMenuItem->key) || ! isset($moduleMenuItem->key)) &&
                            isset($mainMenuItem->name, $moduleMenuItem->name) &&
                            $mainMenuItem->name === $moduleMenuItem->name
                        ) {
                            $verticalMenuData->menu[$index] = $moduleMenuItem;
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        $verticalMenuData->menu[] = $moduleMenuItem;
                    }
                }

                $verticalMenuData->menu = array_values(array_filter(
                    $verticalMenuData->menu,
                    function ($item) {
                        return ! isset($item->visible) || $item->visible;
                    }
                ));

                foreach ($verticalMenuData->menu as $index => $newMenu) {
                    if (isset($newMenu->submenu) && isset($newMenu->merge) && $newMenu->merge) {
                        foreach ($verticalMenuData->menu as $mainMenu) {
                            if (isset($mainMenu->order) && $mainMenu->order == $newMenu->order) {
                                $mainMenu->submenu = array_merge($mainMenu->submenu, $newMenu->submenu);
                                if (is_array($mainMenu->slug)) {
                                    if (is_array($newMenu->slug)) {
                                        $mainMenu->slug = array_merge($mainMenu->slug, $newMenu->slug);
                                    } elseif (! in_array($newMenu->slug, $mainMenu->slug)) {
                                        $mainMenu->slug[] = $newMenu->slug;
                                    }
                                } else {
                                    if (is_array($newMenu->slug)) {
                                        $newMenu->slug[] = $mainMenu->slug;
                                        $mainMenu->slug = $newMenu->slug;
                                    } else {
                                        $mainMenu->slug = [$mainMenu->slug, $newMenu->slug];
                                    }
                                }
                                break;
                            }
                        }
                        unset($verticalMenuData->menu[$index]);
                    }
                }
            }

            // Auto load horizontal menu
            $moduleHorizontalMenuFile = $module->getPath().'/resources/menu/project-verticalMenu.json';
            if (file_exists($moduleHorizontalMenuFile)) {
                $moduleHorizontalMenuJson = file_get_contents($moduleHorizontalMenuFile);
                $moduleHorizontalMenuData = json_decode($moduleHorizontalMenuJson);
                $horizontalMenuData->menu = array_merge($horizontalMenuData->menu, $moduleHorizontalMenuData->menu);
            }
        }

        // $verticalMenuData->menu = collect($verticalMenuData->menu)->sortBy('order')->toArray();
        $verticalMenuData->menu = collect($verticalMenuData->menu)
            ->filter(fn ($item) => ! isset($item->disabled) || ! $item->disabled)
            ->sortBy('order')
            ->values()
            ->toArray();

        $twinMenu = [$verticalMenuData, $horizontalMenuData];

        Cache::forever($cacheKey, $twinMenu);

        return $twinMenu;
    }

    // private function userPermissionSlugs($user): array
    // {
    //     if (!$user) return [];

    //     // Spatie: lấy tất cả quyền từ role + gán trực tiếp
    //     if (method_exists($user, 'getAllPermissions')) {
    //         $slugs = $user->getAllPermissions()->pluck('slug')->filter()->values()->toArray();
    //         if (!empty($slugs)) return $slugs;

    //         // fallback nếu cột slug chưa có đủ dữ liệu
    //         return $user->getAllPermissions()->pluck('name')->filter()->values()->toArray();
    //     }

    //     // Fallback tuyệt đối: duyệt enum + check can()
    //     $all = [];
    //     $enums = [\App\Enums\PermissionsEnum::class];
    //     if (class_exists(\Modules\CmsBackend\Enums\PermissionsEnum::class)) {
    //         $enums[] = \Modules\CmsBackend\Enums\PermissionsEnum::class;
    //     }
    //     foreach ($enums as $enum) {
    //         foreach ($enum::cases() as $case) $all[] = $case->value;
    //     }
    //     return array_values(array_filter($all, fn($slug) => $user->can($slug)));
    // }

    /** ANY-of: roler có thể là string hoặc array các permission slugs */
    private function itemAllowedByRoler($roler, array $userPermSlugs): bool
    {
        if (! $roler) {
            return false;
        }
        $need = is_array($roler) ? $roler : [$roler];
        $need = array_values(array_filter(array_map('trim', $need)));

        return count(array_intersect($need, $userPermSlugs)) > 0;
    }

    /**
     * Lọc menu theo 'roler' nhưng GIỮ NGUYÊN KIỂU PHẦN TỬ như đầu vào (object hay array).
     * $asObject: true -> trả về object; false -> array; null -> tự dò theo phần tử đầu.
     */
    private function filterMenuByRoler(array $items, array $userPermSlugs, $inheritedRoler = null, ?bool $asObject = null): array
    {
        // tự dò kiểu nếu chưa truyền
        if ($asObject === null) {
            $first = $items[0] ?? null;
            $asObject = is_object($first);
        }

        $out = [];

        foreach ($items as $raw) {
            // làm việc nội bộ bằng array
            $item = is_object($raw) ? get_object_vars($raw) : (array) $raw;

            // header giữ nguyên
            if (isset($item['menuHeader'])) {
                $out[] = $asObject ? (object) $item : $item;

                continue;
            }

            // roler: ưu tiên của item, không có thì dùng của cha
            $roler = array_key_exists('roler', $item) ? $item['roler'] : $inheritedRoler;

            // có submenu -> lọc con trước
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $item['submenu'] = $this->filterMenuByRoler($item['submenu'], $userPermSlugs, $roler, $asObject);

                $keepChildren = ! empty($item['submenu']);
                $keepSelf = $this->itemAllowedByRoler($roler, $userPermSlugs);

                if ($keepChildren || $keepSelf) {
                    $out[] = $asObject ? (object) $item : $item;
                }

                continue;
            }

            // item thường
            if ($this->itemAllowedByRoler($roler, $userPermSlugs)) {
                $out[] = $asObject ? (object) $item : $item;
            }
        }

        return array_values($out);
    }

    /**
     * Tạo map: label (name trong DB) -> slug (value trong Enum).
     * Không đụng gì tới DB ngoài lấy name; KHÔNG cần cột slug.
     */
    private function labelToSlugMap(): array
    {
        $map = [];

        $sources = [
            \App\Enums\PermissionsEnum::class,
        ];
        if (class_exists(\Modules\CmsBackend\Enums\PermissionsEnum::class)) {
            $sources[] = \Modules\CmsBackend\Enums\PermissionsEnum::class;
        }

        foreach ($sources as $enum) {
            if (! class_exists($enum) || ! method_exists($enum, 'cases')) {
                continue;
            }

            foreach ($enum::cases() as $case) {
                $label = method_exists($case, 'label') ? $case->label() : $case->value;
                $map[$label] = $case->value; // 'Xem trang' => 'page-view'
            }
        }
        foreach (EditorialPermissions::definitions() as $permission) {
            $map[$permission['name']] = $permission['slug'];
        }

        return $map;
    }

    /**
     * Lấy danh sách slug quyền mà user đang có, CHỈ dựa vào name (label) trong DB.
     * => phù hợp với hiện trạng bạn chưa seed cột slug.
     */
    private function userPermissionSlugs($user): array
    {
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'getAllPermissions')) {
            $labelToSlug = $this->labelToSlugMap();

            // Lấy các name (label) quyền của user từ Spatie
            $names = $user->getAllPermissions()
                ->pluck('name')          // ví dụ: 'Xem trang', 'Quản lý trang', ...
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Map name -> slug theo Enum
            $slugs = [];
            foreach ($names as $name) {
                if (isset($labelToSlug[$name])) {
                    $slugs[] = $labelToSlug[$name]; // 'page-view', 'page-manager', ...
                }
            }

            return array_values(array_unique($slugs));
        }

        // Trường hợp hiếm không dùng Spatie:permissions
        // (có thể bỏ nếu bạn không dùng nhánh này)
        $labelToSlug = $this->labelToSlugMap();
        $allSlugs = array_values($labelToSlug);

        return array_values(array_filter($allSlugs, fn ($s) => $user->can($s)));
    }
}
