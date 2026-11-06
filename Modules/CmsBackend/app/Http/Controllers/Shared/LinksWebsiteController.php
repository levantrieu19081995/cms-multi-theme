<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\Http\Controllers\Controller;

use App\DataProviders\AdsDataProvider;
use App\DataProviders\OptionDataProvider;
use App\Facades\Toastr;
use App\Http\Requests\Ad\CreateAdRequest;
use App\Http\Requests\LinkWebsite\CreateLinkRequest;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class LinksWebsiteController extends Controller
{

    protected AdsDataProvider $adsDataProvider;
    protected OptionDataProvider $optionDataProvider;
    public function __construct(
        AdsDataProvider $adsDataProvider
    ) {
        $this->adsDataProvider = $adsDataProvider;
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

        $links = $this->adsDataProvider->paginate($filter, $pageSize);
        $links->appends($request->all());


        return view('pages.lien-ket.lien-ket-list', compact(['links', 'filter']));
    }
    public function create()
    {
        $link = new Advertisement;


        return view('pages.lien-ket.lien-ket-config', compact(['link']));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateLinkRequest $request)
    {
        DB::transaction(function () use ($request) {

            $ad = new Advertisement;
            $ad->fill($request->all());
            $ad->position = 'links_website';
            $ad->save();
            // Nếu có avatar, lưu avatar
            if ($request->has('anhQuangCao')) {
                if ($request->anhQuangCao != null || $request->anhQuangCao != '') {
                    $ad->addMediaFromUrl($request->anhQuangCao)->toMediaCollection('anhQuangCaos');
                }
            }

            Toastr::success(__('admin.message.them_moi_thanh_cong'));
        });

        return redirect()->route('lien-ket.index');
    }

    public function edit($id)
    {
        $link = $this->adsDataProvider->findById($id);
        return view('pages.lien-ket.lien-ket-config', compact(['link']));
    }
    public function update(CreateLinkRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {

            $ad = $this->adsDataProvider->findById($id);
            $ad->fill($request->all());
            $ad->position = 'links_website';
            $ad->save();
            // Nếu có avatar, lưu avatar
            if ($request->has('anhQuangCao')) {
                if (($request->anhQuangCao !== $ad->getFirstMediaUrl('anhQuangCaos')) && $request->anhQuangCao != null && $request->anhQuangCao != '') {

                    $ad->clearMediaCollection('anhQuangCaos');
                    $ad->addMediaFromUrl($request->anhQuangCao)->toMediaCollection('anhQuangCaos');
                }
            }

            Toastr::success(__('admin.message.cap_nhat_thanh_cong'));
        });
        return redirect()->back();
    }
    public function destroy(int $id)
    {
        $menu = Advertisement::findOrFail($id);
        $menu->delete();

        return response()->json([
            'status' => 1,
            'message' => __('admin.message.xoa_thanh_cong')
        ]);
    }
}
