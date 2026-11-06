<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\DataProviders\AdsDataProvider;
use App\DataProviders\OptionDataProvider;
use Modules\CmsBackend\Http\Controllers\Shared\LinksWebsiteController;
use App\Http\Requests\LinkWebsite\CreateLinkRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Enums\PermissionsEnum;

class CDNLinksWebsiteController extends LinksWebsiteController
{
    protected AdsDataProvider $adsDataProvider;
    protected OptionDataProvider $optionDataProvider;
    public function __construct(
        AdsDataProvider $adsDataProvider
    ) {
        $this->adsDataProvider = $adsDataProvider;

        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('cms.check.permission:' . PermissionsEnum::LINK_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();
        // dump($data);
        // Trả về view tùy chỉnh của module CmsBackend
        return view('cmsbackend::screens.pages.lien-ket.lien-ket-list', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentCreate = parent::create();
        $data = $parentCreate->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return view('cmsbackend::screens.pages.lien-ket.lien-ket-config', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateLinkRequest $request)
    {
        parent::store($request);
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.lien-ket.index')->with('success', __('admin.message.them_moi_thanh_cong'), $data);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cmsbackend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $parentEdit = parent::edit($id);
        $data = $parentEdit->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        // dump($data);
        return view('cmsbackend::screens.pages.lien-ket.lien-ket-config', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateLinkRequest $request, $id)
    {
        parent::update($request, $id);
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.lien-ket.index')->with('success', __('admin.message.cap_nhat_thanh_cong'), $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        parent::destroy($id);
        // $parentIndex = parent::index(new Request());
        // $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return ResponseHelper::responseJson(200, 'success', __('admin.message.xoa_thanh_cong'));
    }
    public function getAllLinksView()
    {
        $links = $this->adsDataProvider->all();
        return view('cmsbackend::screens.pages.lien-ket.lien-ket-list', ['links' => $links]);
    }
}
