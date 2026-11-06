<?php

namespace Modules\CmsBackend\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\CmsBackend\DataProviders\LoaiVanBanDataProvider;
use Modules\CmsBackend\DataProviders\NhomVanBanDataProvider;
use Modules\CmsBackend\DataProviders\VanBanDataProvider;
use Modules\CmsBackend\Models\VanBan;

class VanBanService extends BaseService
{
    protected VanBanDataProvider $vanBanDataProvider;

    protected LoaiVanBanDataProvider $loaiVanBanDataProvider;

    protected NhomVanBanDataProvider $nhomVanBanDataProvider;

    public function __construct(VanBanDataProvider $vanBanDataProvider, LoaiVanBanDataProvider $loaiVanBanDataProvider, NhomVanBanDataProvider $nhomVanBanDataProvider)
    {
        parent::__construct($vanBanDataProvider, $loaiVanBanDataProvider, $nhomVanBanDataProvider);
        $this->vanBanDataProvider = $vanBanDataProvider;
        $this->loaiVanBanDataProvider = $loaiVanBanDataProvider;
        $this->nhomVanBanDataProvider = $nhomVanBanDataProvider;
    }

    public function getList(Request $request)
    {

        $filter = trim($request->filter ?? '');
        $lvbId = $request->lvb_id ? Str::fromBase64($request->lvb_id) : -1;
        $nvbId = $request->nvb_id ?? -1;
        $tuNgay = $request->vb_ngay_ban_hanh_tu_ngay ? Carbon::parse($request->vb_ngay_ban_hanh_tu_ngay) : null;
        $denNgay = $request->vb_ngay_ban_hanh_den_ngay ? Carbon::parse($request->vb_ngay_ban_hanh_den_ngay) : null;

        $list = $this->vanBanDataProvider->paginateVB($tuNgay, $denNgay, $lvbId, $nvbId, $filter);
        foreach ($list as $item) {
            $item->fileDinhKem = $item->getMedia('fileDinhKem')->map(fn ($media) => $media->getUrl())->values();
        }

        return [
            'list' => $list,
            'listLoaiVanBan' => $this->loaiVanBanDataProvider->getDsLoaiVanBan(''),
            'listNhomVanBan' => $this->nhomVanBanDataProvider->getDsNhomVanBan(''),
            'filter' => $filter,
            'lvbId' => $lvbId,
            '$nvbId' => $nvbId,
            'tuNgayString' => $request->vb_ngay_ban_hanh_tu_ngay,
            'denNgayString' => $request->vb_ngay_ban_hanh_den_ngay,
        ];
    }

    public function prepareCreate()
    {
        $item = new VanBan([
            'vb_ngay_ban_hanh' => Carbon::now(),
            'vb_ngay_hieu_luc' => Carbon::now(),
            'vb_ngay_cong_bo' => Carbon::now(),
        ]);
        $item->fileDinhKem = $item->getMedia('fileDinhKem');

        return [
            'item' => $item,
            'listLoaiVanBan' => $this->loaiVanBanDataProvider->getDsLoaiVanBan(''),
            'listNhomVanBan' => $this->nhomVanBanDataProvider->getDsNhomVanBan(''),
        ];
    }

    public function store($request)
    {
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $vanBan = $this->vanBanDataProvider->create($data);

        $this->vanBanDataProvider->handleFiles($vanBan, $request->input('fileDinhKem'));
    }

    public function updateVB($request, VanBan $vanBan)
    {
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $this->vanBanDataProvider->updateVB($vanBan, $data);

        if ($request->has('fileDinhKem')) {
            $this->vanBanDataProvider->syncFiles($vanBan, $request->input('fileDinhKem'));
        }
    }

    public function prepareEdit(VanBan $vanBan)
    {
        $vanBan->fileDinhKem = $this->vanBanDataProvider->getFileUrls($vanBan);

        return [
            'item' => $vanBan,
            'listLoaiVanBan' => $this->loaiVanBanDataProvider->getDsLoaiVanBan(''),
            'listNhomVanBan' => $this->nhomVanBanDataProvider->getDsNhomVanBan(''),
        ];
    }
}
