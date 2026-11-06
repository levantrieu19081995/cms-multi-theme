<?php

namespace App\DataProviders;

use App\Models\Menu;
use App\Models\Option;
use Modules\CmsBackend\Models\Marquee;

class MenuDataProvider
{
    protected MenuItemDataProvider $menuItemDataProvider;

    public function __construct(MenuItemDataProvider $menuItemDataProvider)
    {
        $this->menuItemDataProvider = $menuItemDataProvider;
    }

    public function paginate($filter, $pageSize = 20)
    {
        return Menu::where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('name', 'like', "%$filter%");
                }
            })
            ->paginate($pageSize);
    }

    public function getMenu(string $key, bool $loadRef = true)
    {
        $option = Option::where('key', $key)->first();
        if ($option && intval($option->value) > 0) {
            $menu = $this->menuItemDataProvider->getMenuItemByMenuId($option->value, $loadRef);
            return $menu;
        }
        return null;
    }

    public function getMarquee(){
        $marquee = Marquee::with(['media'])
        ->where('show_home_page', 1)
        ->orderBy('updated_at', 'desc') // Sắp xếp theo updated_at giảm dần
        ->paginate();
        return $marquee;
    }
}
