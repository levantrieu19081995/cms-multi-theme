<?php

namespace App\Http\Controllers;

use App\DataProviders\CategoryDataProvider;
use App\DataProviders\OptionDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Http\Requests\Option\OptionUpdateRequest;
use App\Enums\OptionEnum;
use App\Facades\Toastr;
use App\Enums\PostFormat;
use App\Events\ChangeOption;
use Illuminate\Support\Facades\Artisan;
use stdClass;

use App\Enums\PermissionsEnum;

class SettingController extends Controller
{

    private OptionDataProvider $optionDataProvider;

    private TaxonomyDataProvider $taxonomyDataProvider;

    private $optionsLoad = [
        OptionEnum::WEB_TITLE,
        OptionEnum::WEB_TITLE_COLOR, // 👈 thêm dòng này
        OptionEnum::WEB_DESCRIPTION,
        OptionEnum::WEB_LOGO,
        OptionEnum::ENABLE_GUEST_REGISTER,
        OptionEnum::CATEGORY_DEFAULT,
        OptionEnum::POST_FORMAT_DEFAULT
    ];

    public function __construct(OptionDataProvider $optionDataProvder, TaxonomyDataProvider $taxonomyDataProvider)
    {
        $this->optionDataProvider = $optionDataProvder;
        $this->taxonomyDataProvider = $taxonomyDataProvider;
        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('check.permission:' . PermissionsEnum::ABOUT_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $options = $this->optionDataProvider->getOptionByKey($this->optionsLoad);

        $option = new stdClass;
        foreach ($options as $optionItem) {
            $key = $optionItem->key;
            $option->$key = $optionItem->value;
        }

        // Load categories
        $categories = $this->taxonomyDataProvider->getCategoryAll();
        $postFormats = PostFormat::cases();

        return view('pages.setting.general', compact(['option', 'categories', 'postFormats']));
    }

    public function update(OptionUpdateRequest $request)
    {
        $allowOptionKeys = collect($this->optionsLoad)->pluck('value')->values();
        $data = $request->only($allowOptionKeys->toArray());

        $options = $this->optionDataProvider->getOptionByKey($this->optionsLoad);

        foreach ($options as $option) {
            $option->value = $data[$option->key] ?? '';
            $option->save();
        }

        event(new ChangeOption($options));

        Toastr::success(__('Lưu thành công'));

        return redirect()->route('setting.general');
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');

            Toastr::success(__('Xóa bộ nhớ đệm thành công'));
        } catch (\Throwable $th) {
            //throw $th;
            Toastr::warning(__('Xóa bộ nhớ đệm thất bại'));
        }
        return redirect()->back();
    }
}
