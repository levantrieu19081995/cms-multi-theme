<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class MenuServiceProvider extends ServiceProvider
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

            $view->with('menuData', [$vertical, $horizontal]);
        });
    }

    public function getTwinMenu()
    {
        $cacheKey = 'twin_menu';
        if (env('APP_DEBUG') == false) {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
        }

        $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
        $verticalMenuData = json_decode($verticalMenuJson);
        $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
        $horizontalMenuData = json_decode($horizontalMenuJson);

        $modules = Module::allEnabled();
        foreach ($modules as $module) {
            // Auto load vertical menu
            $moduleVerticalMenuFile = $module->getPath() . '/resources/menu/verticalMenu.json';
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
                            (!isset($mainMenuItem->key) || !isset($moduleMenuItem->key)) &&
                            isset($mainMenuItem->name, $moduleMenuItem->name) &&
                            $mainMenuItem->name === $moduleMenuItem->name
                        ) {
                            $verticalMenuData->menu[$index] = $moduleMenuItem;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $verticalMenuData->menu[] = $moduleMenuItem;
                    }
                }

                $verticalMenuData->menu = array_values(array_filter(
                    $verticalMenuData->menu,
                    function ($item) {
                        return !isset($item->visible) || $item->visible;
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
                                    } else if (!in_array($newMenu->slug, $mainMenu->slug)) {
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
            $moduleHorizontalMenuFile = $module->getPath() . '/resources/menu/verticalMenu.json';
            if (file_exists($moduleHorizontalMenuFile)) {
                $moduleHorizontalMenuJson = file_get_contents($moduleHorizontalMenuFile);
                $moduleHorizontalMenuData = json_decode($moduleHorizontalMenuJson);
                $horizontalMenuData->menu = array_merge($horizontalMenuData->menu, $moduleHorizontalMenuData->menu);
            }
        }

        // $verticalMenuData->menu = collect($verticalMenuData->menu)->sortBy('order')->toArray();
        $verticalMenuData->menu = collect($verticalMenuData->menu)
            ->filter(fn($item) => !isset($item->disabled) || !$item->disabled)
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
        if (!$roler) return false;
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

                $keepChildren = !empty($item['submenu']);
                $keepSelf     = $this->itemAllowedByRoler($roler, $userPermSlugs);

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
            if (!class_exists($enum) || !method_exists($enum, 'cases')) continue;

            foreach ($enum::cases() as $case) {
                $label = method_exists($case, 'label') ? $case->label() : $case->value;
                $map[$label] = $case->value; // 'Xem trang' => 'page-view'
            }
        }
        return $map;
    }

    /**
     * Lấy danh sách slug quyền mà user đang có, CHỈ dựa vào name (label) trong DB.
     * => phù hợp với hiện trạng bạn chưa seed cột slug.
     */
    private function userPermissionSlugs($user): array
    {
        if (!$user) return [];

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
        $allSlugs    = array_values($labelToSlug);
        return array_values(array_filter($allSlugs, fn($s) => $user->can($s)));
    }
}
