<?php

namespace App\DataProviders;

use App\Models\MenuItem;
use App\Services\LocaleManager;
use App\Services\TranslationService;
use Illuminate\Validation\ValidationException;
use App\Models\Post;
use App\Models\Term;
use Modules\CmsBackend\Features\FeatureRegistry;
use Modules\CmsBackend\Models\NhomVanBan;
use Illuminate\Support\Facades\Schema;

class MenuItemDataProvider
{

    /**
     * recursive
     *
     * @param integer $menuId
     * @return void
     */
    public function getMenuItemByMenuId(int $menuId, bool $loadRef = false, bool $withTranslations = false)
    {
        $menuItems = MenuItem::whereMenuId($menuId)->when($withTranslations, fn ($query) => $query->with('translations'))->orderBy('ordinal')->get();
        // Load
        // 'url','category','tag','post','page','image','video'
        if ($loadRef) {
            $postMenuItems = $menuItems->whereIn('type', ['post', 'page', 'image', 'video']);
            if (!$postMenuItems->isEmpty()) {
                $posts = Post::whereIn('id', $postMenuItems->pluck('ref_id')->values())
                    ->select('id', 'slug')
                    ->get();
                foreach ($postMenuItems as $menuItem) {
                    $menuItem->ref = $posts->where('id', $menuItem->ref_id)->first();
                }
            }
            $categoryMenuItems = $menuItems->whereIn('type', ['category', 'tag']);
            if (!$categoryMenuItems->isEmpty()) {
                $categories = Term::whereIn('term_id', $categoryMenuItems->pluck('ref_id')->values())
                    ->select('term_id', 'slug')
                    ->get();
                foreach ($categoryMenuItems as $menuItem) {
                    $menuItem->ref = $categories->where('term_id', $menuItem->ref_id)->first();
                    // On a page in another language a category without a name in that language has a blank slug: no link at all
                    // (the item is dropped by the menu), never a link built from the Vietnamese text.
                    if (app(LocaleManager::class)->publicLocale() !== null && blank($menuItem->ref?->slug)) {
                        $menuItem->ref = null;
                    }
                }
            }
            $docsMenuItems = $menuItems->whereIn('type', ['docs']);
            if (!$docsMenuItems->isEmpty()) {
                $docs = NhomVanBan::whereIn('nvb_id', $docsMenuItems->pluck('ref_id')->values())
                    ->select('nvb_id', 'nvb_slug')
                    ->get();
                foreach ($docsMenuItems as $menuItem) {
                    $menuItem->ref = $docs->where('nvb_id', $menuItem->ref_id)->first();
                }
            }
        }

        // Format
        $formatTree = function ($menuItems, $parent, $formatTree_) use ($withTranslations) {
            $results = collect();
            $pid = $parent ? $parent->id : 0;
            foreach ($menuItems as $menuItem) {
                if ($menuItem->parent_id == $pid) {
                    $returnModel = $this->convertToDto($menuItem, $withTranslations);
                    $results->push($returnModel);
                    if ($menuItems->where('parent_id', $menuItem->id)->count() > 0) {
                        $returnModel->childs = $formatTree_($menuItems, $menuItem, $formatTree_);
                    }
                }
            }
            return $results;
        };

        return $formatTree($menuItems, null, $formatTree);
    }

    private function convertToDto(MenuItem $menuItem, bool $withTranslations = false)
    {
        $dto = [
            'id' => $menuItem->id,
            'title' => $menuItem->title,
            'url' => $menuItem->url,
            'type' => $menuItem->type,
            'ref_id' => $menuItem->ref_id,
            'target' => $menuItem->target,
            'icon' => $menuItem->icon,

        ];
        // Menu "Chức năng hệ thống": tham chiếu + tình trạng hiện tại (ok|disabled|missing) để trình quản lý hiện cảnh báo.
        if ($menuItem->type === 'feature') {
            $resolved = app(FeatureRegistry::class)->resolve((string) $menuItem->feature_key, (string) $menuItem->feature_target);
            $dto['feature_key'] = $menuItem->feature_key;
            $dto['feature_target'] = $menuItem->feature_target;
            $dto['feature_status'] = $resolved['status'];
        }
        // Admin editor only: the other languages ride along as flat title_{locale} / url_{locale} fields.
        if ($withTranslations) {
            foreach (app(LocaleManager::class)->alternates() as $locale) {
                $translation = $menuItem->translation($locale);
                $dto['title_'.$locale] = (string) ($translation?->title ?? '');
                $dto['url_'.$locale] = (string) ($translation?->custom_url ?? '');
            }
        }
        if ($menuItem->ref) {
            $dto['ref']  = $menuItem->ref;
        }
        return (object) $dto;
    }

    /**
     * Menu loại "feature" chỉ lưu tham chiếu (feature_key, feature_target); URL luôn sinh từ route trong Feature Registry khi hiển thị.
     * Mục mới hoặc đổi tham chiếu phải trỏ tới chức năng + điểm đến có trong Registry. Mục cũ giữ nguyên tham chiếu thì được lưu
     * lại bình thường kể cả khi chức năng đang tắt hoặc definition không còn (menu không bị tự xóa).
     *
     * @param  array<string, mixed>  $data
     */
    private function normalizeFeatureReference(array &$data, ?MenuItem $existing): void
    {
        static $columns = null;
        $columns ??= Schema::hasColumn('menu_items', 'feature_key');
        if (! $columns) {
            unset($data['feature_key'], $data['feature_target']);

            return;
        }
        if (($data['type'] ?? null) !== 'feature') {
            $data['feature_key'] = null;
            $data['feature_target'] = null;

            return;
        }
        $key = trim((string) ($data['feature_key'] ?? ''));
        $target = trim((string) ($data['feature_target'] ?? ''));
        $unchanged = $existing !== null && $existing->type === 'feature' && $existing->feature_key === $key && $existing->feature_target === $target;
        if (! $unchanged && app(FeatureRegistry::class)->target($key, $target) === null) {
            throw ValidationException::withMessages(['structure' => 'Chức năng hoặc điểm đến của menu không hợp lệ.']);
        }
        $data['feature_key'] = $key;
        $data['feature_target'] = $target;
        $data['url'] = (string) ($data['url'] ?? ''); // chỉ là nhãn hiển thị trong trình quản lý, không phải nguồn của liên kết
        $data['ref_id'] = (int) ($data['ref_id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data one node of the posted tree
     */
    private function saveTranslations(MenuItem $menuItem, array $data): void
    {
        $locales = app(LocaleManager::class);
        $payload = [$locales->default() => ['title' => $menuItem->title, 'custom_url' => $menuItem->url]];
        foreach ($locales->alternates() as $locale) {
            // Nodes that did not come from the bilingual editor carry no such keys: leave that language alone.
            if (! array_key_exists('title_'.$locale, $data) && ! array_key_exists('url_'.$locale, $data)) {
                continue;
            }
            $title = trim((string) ($data['title_'.$locale] ?? ''));
            // A manual URL only exists for type "url"; post/page/category/... links are resolved per language from the linked object.
            $url = $menuItem->type === 'url' ? trim((string) ($data['url_'.$locale] ?? '')) : '';
            // The label is the presence marker of a menu translation. Clearing it removes the whole language,
            // even when a stale custom URL is still present in the modal.
            if ($title === '') {
                $payload[$locale] = ['title' => '', 'custom_url' => ''];

                continue;
            }
            if (mb_strlen($title) > 255 || mb_strlen($url) > 255) {
                throw ValidationException::withMessages(['structure' => 'Tên và đường dẫn của từng ngôn ngữ tối đa 255 ký tự.']);
            }
            if ($url !== '' && preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
                throw ValidationException::withMessages(['structure' => 'Đường dẫn không hợp lệ.']);
            }
            $payload[$locale] = ['title' => $title, 'custom_url' => $url];
        }
        app(TranslationService::class)->saveMenuItemTranslations($menuItem, $payload);
    }

    public function saveAll(int $menuId, $listMenuItemData)
    {
        $menuItems = MenuItem::whereMenuId($menuId)->get();

        // Tao menu item de quy
        $createMenuItem = function (int $menuId, $menuItemData, $parent, $createMenuItem_, $ordinal = 0) use ($menuItems) {
            $createdItems = collect([]);

            $icon = trim((string) ($menuItemData['icon'] ?? ''));
            if ($icon !== '' && ! preg_match('/^ri-[a-z0-9-]+$/D', $icon)) {
                throw ValidationException::withMessages(['structure' => 'Biểu tượng menu không hợp lệ.']);
            }
            $menuItemData['icon'] = $icon !== '' ? $icon : null;

            $menuItem = $menuItems->where('id', $menuItemData['id'])->first();
            $this->normalizeFeatureReference($menuItemData, $menuItem);
            if (!$menuItem) {
                $menuItem = new MenuItem;
            }
            $menuItem->fill($menuItemData);
            if ($menuItem->id <= 0) {
                $menuItem->id = null;
            }
            $menuItem->menu_id = $menuId;
            $menuItem->ordinal = $ordinal;
            $menuItem->parent_id = $parent ? $parent->id : 0;
            $menuItem->treepath = '/';
            $menuItem->save();
            if ($parent) {
                $menuItem->treepath = $parent->treepath . '/' . $menuItem->id;
            } else {
                $menuItem->treepath = '/' . $menuItem->id;
            }
            $menuItem->save();
            // Same transaction as the controller: vi mirrors the columns just written, other languages only live in menu_item_translations.
            $this->saveTranslations($menuItem, $menuItemData);

            $createdItems->push($menuItem);

            if (isset($menuItemData['childs'])) {
                // ordinal là thứ tự trong cùng một cha: mục con phải nhận vị trí của chính nó, không phải ordinal của mục cha.
                foreach (array_values($menuItemData['childs']) as $childIndex => $menuItemChildData) {
                    $createdChildItems = $createMenuItem_($menuId, $menuItemChildData, $menuItem, $createMenuItem_, $childIndex);
                    $createdItems = $createdItems->merge($createdChildItems);
                }
            }

            return $createdItems;
        };

        $createdItems = collect([]);
        foreach ($listMenuItemData as $index => $menuItemData) {
            // De quy insert
            $createdItems = $createdItems->merge($createMenuItem($menuId, $menuItemData, null, $createMenuItem, $index));
        }

        $removeMenuItems = $menuItems->where(function ($b) use ($createdItems) {
            return $createdItems->where('id', $b->id)->count() == 0;
        });
        foreach ($removeMenuItems as $removeMenuItem) {
            $removeMenuItem->delete();
        }
        return $createdItems;
    }
}
