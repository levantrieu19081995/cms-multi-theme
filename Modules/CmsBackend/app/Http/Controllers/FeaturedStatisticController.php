<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\LocaleManager;
use App\Services\OptionTranslations;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Support\HomeSettings;

class FeaturedStatisticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Quyền riêng của số liệu nổi bật (quyền cha sonoibat-manager gồm cả hai). Không còn dùng chung homepage-manager.
        $this->middleware('cms.check.permission:'.\Modules\CmsBackend\Enums\PermissionsEnum::SONOIBAT_VIEW->label())->only('index');
        $this->middleware('cms.check.permission:'.\Modules\CmsBackend\Enums\PermissionsEnum::SONOIBAT_UPDATE->label())->only('update');
    }

    public function index(): View
    {
        $locales = app(LocaleManager::class);
        // The form is a fixed set of slots; each stored row goes back into the slot it was saved in.
        $statistics = array_fill(0, HomeSettings::STATISTICS_MAX, ['label' => '', 'value' => '']);
        $rows = HomeSettings::statistics((array) config('cmsbackend.screens.themes.nam-binh-duong.statistics', []), $locales->default());
        foreach ($rows as $row) {
            $statistics[$row['slot']] = ['label' => $row['label'], 'value' => $row['value']];
        }
        // Labels already entered in the other languages (option_translations), per slot.
        foreach ($statistics as $slot => $row) {
            $translated = app(OptionTranslations::class)->forKey(HomeSettings::statisticLabelKey($slot));
            foreach ($locales->alternates() as $locale) {
                $statistics[$slot]['label_'.$locale] = (string) ($translated[$locale] ?? '');
            }
        }

        return view('cmsbackend::screens.pages.con-so-noi-bat.index', compact('statistics'));
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'statistics' => ['nullable', 'array', 'max:'.HomeSettings::STATISTICS_MAX],
            'statistics.*.label' => ['nullable', 'string', 'max:80'],
            'statistics.*.value' => ['nullable', 'string', 'max:30'],
        ];
        foreach (app(LocaleManager::class)->alternates() as $locale) {
            $rules['statistics.*.label_'.$locale] = ['nullable', 'string', 'max:80'];
        }
        $validated = $request->validate($rules);

        // Same transaction: the shared value and Vietnamese label in options, the other languages' labels in option_translations.
        DB::transaction(function () use ($validated) {
            $rows = (array) ($validated['statistics'] ?? []);
            HomeSettings::saveStatistics($rows);
            HomeSettings::saveStatisticLabels($rows);
        });

        Toastr::success(__('admin.message.luu_thanh_cong'));

        return redirect()->route('cms.dashboard.con-so-noi-bat.index');
    }
}
