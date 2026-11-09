<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\DataProviders\MenuDataProvider;
use App\DataProviders\MenuItemDataProvider;
use App\DataProviders\OptionDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Facades\Toastr;
use App\Http\Requests\Menu\CreateMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;
use stdClass;
use App\Enums\Taxonomy as EnumsTaxonomy;
use Modules\CmsBackend\DataProviders\NhomVanBanDataProvider;
use App\Enums\PermissionsEnum;


class MenuController extends Controller
{
    protected MenuDataProvider $menuDataProvider;

    protected MenuItemDataProvider $menuItemDataProvider;

    protected OptionDataProvider $optionDataProvider;

    protected TaxonomyDataProvider $taxonomyDataProvider;

    protected NhomVanBanDataProvider $nhomVanBanDataProvider;

    public function __construct(
        MenuDataProvider $menuDataProvider,
        MenuItemDataProvider $menuItemDataProvider,
        OptionDataProvider $optionDataProvider,
        TaxonomyDataProvider $taxonomyDataProvider,
        NhomVanBanDataProvider $nhomVanBanDataProvider
    ) {
        $this->menuDataProvider = $menuDataProvider;
        $this->menuItemDataProvider = $menuItemDataProvider;
        $this->optionDataProvider = $optionDataProvider;
        $this->taxonomyDataProvider = $taxonomyDataProvider;
        $this->nhomVanBanDataProvider = $nhomVanBanDataProvider;

        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('check.permission:' . PermissionsEnum::MENU_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    private function getModuleThemeMenu()
    {
        $modules = Module::allEnabled();
        $moduleThemes = collect();
        foreach ($modules as $module) {
            $moduleThemeConfig = $module->getPath() . '/module-ext.json';
            if (file_exists($moduleThemeConfig)) {
                $moduleThemeConfigJson = file_get_contents($moduleThemeConfig);
                $moduleThemeConfigData = json_decode($moduleThemeConfigJson);
                // Check validate config file
                if (
                    isset($moduleThemeConfigData->type)
                    && $moduleThemeConfigData->type == 'frontpage'
                    && isset($moduleThemeConfigData->menu)
                    && $moduleThemeConfigData->menu
                ) {
                    $module->ext = $moduleThemeConfigData;
                    $moduleThemes->push($module);
                }
            }
        }
        return $moduleThemes;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pageSize = $request->pageSize ?? 20;
        $sortBy = $request->sortBy ?? 'title';
        $order = $request->order ?? 'asc';
        $filter = trim(strip_tags($request->query('filter')));

        $menus = $this->menuDataProvider->paginate($filter, $pageSize);
        $menus->appends($request->all());

        // Extend - Xử lý hiển thị vị trí menu
        $moduleThemes = $this->getModuleThemeMenu();
        $menuKeys = [];
        foreach ($moduleThemes as $module) {
            foreach ($module->ext->menu as $menuKey => $menuName) {
                $menuKeys[] = $menuKey;
            }
        }
        $options = $this->optionDataProvider->getOptionByKey($menuKeys);
        foreach ($menus as $menu) {
            foreach ($options as $option) {
                if ($option->value == $menu->id) {
                    if (!isset($menu->position)) {
                        $menu->position = collect();
                    }
                    foreach ($moduleThemes as $module) {
                        $breakLoop = false;
                        foreach ($module->ext->menu as $menuKey => $menuName) {
                            if ($menuKey == $option->key) {
                                $menu->position->push($menuName);
                                $breakLoop = true;
                                break;
                            }
                        }
                        if ($breakLoop) {
                            break;
                        }
                    };
                }
            }
        }

        return view('pages.menu.menu-list', compact(['menus', 'filter']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $menu = new Menu;
        $categories = $this->taxonomyDataProvider->getCategoryAll(EnumsTaxonomy::CATEGORY->value);
        $listNhomVanBan = $this->nhomVanBanDataProvider->getDsNhomVanBan('');
        $moduleThemes = $this->getModuleThemeMenu();
        $positions = [];

        $features = app(\Modules\CmsBackend\Features\FeatureRegistry::class)->menuChoices();

        return view('pages.menu.menu-config', compact(['menu', 'moduleThemes', 'positions', 'categories', 'listNhomVanBan', 'features']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMenuRequest $request)
    {
        DB::transaction(function () use ($request) {
            $menu = new Menu;
            $menu->fill($request->all());
            $menu->save();

            // Cấu trúc menu
            if ($request->structure) {
                $listMenuItemData = json_decode($request->structure, true);
                $this->menuItemDataProvider->saveAll($menu->id, $listMenuItemData);
            }

            // Vị trí menu
            if (isset($request->position) && $request->position) {
                foreach ($request->position as $position => $enable) {
                    if ($enable) {
                        $this->optionDataProvider->save($position, $menu->id);
                    }
                }
            }

            Toastr::success(__('Thêm mới thành công'));
        });

        return redirect()->route('menus.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        return $this->menuItemDataProvider->getMenuItemByMenuId($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->structure = $this->menuItemDataProvider->getMenuItemByMenuId($id, false, true);
        $categories = $this->taxonomyDataProvider->getCategoryAll(EnumsTaxonomy::CATEGORY->value);
        $categories->load('parent');
        $listNhomVanBan = $this->nhomVanBanDataProvider->getDsNhomVanBan('');
        $moduleThemes = $this->getModuleThemeMenu();

        $menuKeys = [];
        foreach ($moduleThemes as $module) {
            foreach ($module->ext->menu as $menuKey => $menuName) {
                $menuKeys[] = $menuKey;
            }
        }

        $options = $this->optionDataProvider->getOptionByKey($menuKeys);
        $positions = [];
        foreach ($options as $position) {
            $positions[$position->key] = $position->value == $menu->id;
        }

        $features = app(\Modules\CmsBackend\Features\FeatureRegistry::class)->menuChoices();

        return view('pages.menu.menu-config', compact(['menu', 'moduleThemes', 'positions', 'categories', 'listNhomVanBan', 'features']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, int $id)
    {

        $moduleThemes = $this->getModuleThemeMenu();
        $menuKeys = [];
        foreach ($moduleThemes as $module) {
            foreach ($module->ext->menu as $menuKey => $menuName) {
                $menuKeys[] = $menuKey;
            }
        }

        $options = $this->optionDataProvider->getOptionByKey($menuKeys);

        DB::transaction(function () use ($request, $id, $options) {
            $menu = Menu::findOrFail($id);
            $menu->fill($request->all());
            $menu->save();

            // Cấu trúc menu
            if ($request->structure) {
                $listMenuItemData = json_decode($request->structure, true);
                $this->menuItemDataProvider->saveAll($menu->id, $listMenuItemData);
            }

            // Vị trí menu
            $positions = [];
            if (isset($request->position) && $request->position) {
                foreach ($request->position as $position => $enable) {
                    if ($enable) {
                        $positions[] = $position;
                        $this->optionDataProvider->save($position, $menu->id);
                    }
                }
            }

            // Bỏ vị trí menu
            foreach ($options as $option) {
                if ($option->value == $menu->id && !in_array($option->key, $positions)) {
                    $option->value = 0;
                    $option->save();
                }
            }

            Toastr::success(__('Cập nhật thành công'));
        });

        return redirect()->route('menus.index');
        // return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Xóa thành công'
        ]);
    }
}
