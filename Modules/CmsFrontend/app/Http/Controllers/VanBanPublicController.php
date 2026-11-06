<?php

namespace Modules\CmsFrontend\Http\Controllers;

use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\CmsBackend\DataProviders\LoaiVanBanDataProvider;
use Modules\CmsBackend\DataProviders\VanBanDataProvider;
use Illuminate\Support\Str;
use Modules\CmsBackend\Models\VanBan;
use Modules\CmsBackend\Services\LoaiVanBanService;
use Modules\CmsBackend\Services\NhomVanBanService;
use Modules\CmsBackend\Services\VanBanService;

class VanBanPublicController extends Controller
{
    protected VanBanService $vanBanService;

    protected LoaiVanBanService $loaiVanBanService;
    protected NhomVanBanService $nhomVanBanService;

    public function __construct(VanBanService $vanBanService, LoaiVanBanService $loaiVanBanService, NhomVanBanService $nhomVanBanService)
    {
        $this->vanBanService = $vanBanService;
        $this->loaiVanBanService = $loaiVanBanService;
        $this->nhomVanBanService = $nhomVanBanService;
    }

     public function index(Request $request, $slug = null)
    {
        if ($slug !== null) {
        $nvb= $this->nhomVanBanService->getNVBBySlug($slug);
        $request->nvb_id =  $nvb->nvb_id;
        }
        $data = $this->vanBanService->getList($request);
        $data['nvb'] = $nvb ?? null;
        return view('cmsfrontend::screens.pages.frontend.van-ban.index', $data);
    }



   public function show($id)
{
    $request = new Request();
    // Lấy văn bản
    $item = $this->vanBanService->find($id);

    // Lấy danh sách file đính kèm
    $item->fileDinhKem = $item->getMedia('fileDinhKem')
        ->map(fn($media) => $media->getUrl())
        ->values();
    $dsNhomVanBan=$this->nhomVanBanService->getDsNhomVanBan('');
    // Lấy loại văn bản
    $request->nvb_id = $item->nvb_id;
    // Lấy danh sách văn bản cùng nhóm
    $related = $this->vanBanService->getList($request);

    // Lọc ra trừ văn bản hiện tại
    $list = $related['list']
        ->filter(fn($vb) => $vb->vb_id != $item->vb_id)
        ->values();

    return view('cmsfrontend::screens.pages.frontend.van-ban.show', compact('item', 'list', 'dsNhomVanBan'));
}

}
