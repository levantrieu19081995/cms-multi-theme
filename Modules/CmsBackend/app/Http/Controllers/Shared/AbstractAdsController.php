<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\DataProviders\AdsDataProvider;
use App\DataProviders\OptionDataProvider;
use App\Facades\Toastr;
use App\Http\Requests\Ad\CreateAdRequest;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

abstract class AbstractAdsController extends Controller
{
    protected AdsDataProvider $adsDataProvider;
    protected OptionDataProvider $optionDataProvider;
    private $type;
    public function __construct(
        AdsDataProvider $adsDataProvider, OptionDataProvider $optionDataProvider, string  $type
    ) {
        $this->adsDataProvider = $adsDataProvider;
        $this->optionDataProvider = $optionDataProvider;
        $this->type = $type;
    }


    public function getModuleThemeAds()
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
                    && isset($moduleThemeConfigData->ads)
                    && $moduleThemeConfigData->ads
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

        $ads = $this->adsDataProvider->paginate($filter, $pageSize);
        $ads->appends($request->all());

        // Extend - Xử lý hiển thị vị trí menu
        $moduleThemes = $this->getModuleThemeAds();
        $adsKeys = [];
        foreach ($moduleThemes as $module) {
            foreach ($module->ext->ads as $adsKey => $adsName) {
                $adsKeys[] = $adsKey;
            }
        }
       // $options = $this->optionDataProvider->getOptionByKey($adsKeys);
       
        foreach ($ads as $ad) {

                    foreach ($moduleThemes as $module) {
                        $breakLoop = false;
                        foreach ($module->ext->ads as $adKey => $adName) {
                            if ($adKey ==$ad->position) {
                                $ad->position=$adName;
                                $breakLoop = true;
                                break;
                            }
                        }
                        if ($breakLoop) {
                            break;
                        }
        }
    }
        $type = $this->type;

        return view('pages.quang-cao.quang-cao-list', compact(['ads', 'filter','type']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ad = new Advertisement;

        $moduleThemes = $this->getModuleThemeAds();

        return view('pages.quang-cao.quang-cao-config', compact(['ad', 'moduleThemes']));
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAdRequest $request)
    {
        DB::transaction(function () use ($request) {
          
                $ad = new Advertisement;
                $ad->fill($request->all());
                $ad->save();
                 // Nếu có avatar, lưu avatar
            if($request->has('anhQuangCao')){
                if($request->anhQuangCao!=null||$request->anhQuangCao!=''){
                    $ad->addMediaFromUrl($request->anhQuangCao)->toMediaCollection('anhQuangCaos');
                }

            }
   
            Toastr::success(__('Thêm mới thành công'));
        });

        return redirect()->route('quang-cao.index');
    }

    public function edit($id)
    {
        $ad = $this->adsDataProvider->findById($id);   
        $moduleThemes = $this->getModuleThemeAds();
        return view('pages.quang-cao.quang-cao-config', compact(['ad', 'moduleThemes']));
    }
    public function update(CreateAdRequest $request,$id)
    {
      
        DB::transaction(function () use ($request,$id) {
          
            $ad = $this->adsDataProvider->findById($id);   
            $ad->fill($request->all());
            $ad->save();
             // Nếu có avatar, lưu avatar
             if ($request->has('anhQuangCao')) {
                if(($request->anhQuangCao!== $ad->getFirstMediaUrl('anhQuangCaos')) &&$request->anhQuangCao!=null && $request->anhQuangCao!=''){
                 
                    $ad->clearMediaCollection('anhQuangCaos');
                    $ad->addMediaFromUrl($request->anhQuangCao)->toMediaCollection('anhQuangCaos');
                }

            }

        Toastr::success(__('Cập nhật thành công'));
     });
     return redirect()->back();
    }
    public function destroy(int $id)
    {
        $menu = Advertisement::findOrFail($id);
        $menu->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Xóa thành công'
        ]);
    }
}
