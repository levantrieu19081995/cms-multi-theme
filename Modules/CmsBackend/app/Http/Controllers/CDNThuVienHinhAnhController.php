<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Helpers\ResponseHelper;
use Modules\CmsBackend\Http\Controllers\Shared\ImageLibraryController;
use App\Http\Requests\Post\PostUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CDNThuVienHinhAnhController extends ImageLibraryController
{
    public function index(Request $request)
    {
        $parentIndex = parent::index($request);
        // Cửa sổ chọn ảnh mở ?mode=select: dùng view chọn của controller cha.
        if ($request->input('mode') === 'select') {
            return $parentIndex;
        }
        $data = $parentIndex->getData();
        // dump($data);
        // Trả về view tùy chỉnh của module CmsBackend
        return view('cmsbackend::screens.pages.thu-vien-hinh-anh.index', $data);
    }
    public function create()
    {
        $parentCreate = parent::create();
        $data = $parentCreate->getData();

        // Trả về view tùy chỉnh của module CmsBackend
        return response()->view('cmsbackend::screens.pages.thu-vien-hinh-anh.edit', $data);
    }
    public function store(PostUpdateRequest $request)
    {
        parent::store($request);
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.thu-vien-hinh-anh.index')->with('success', __('admin.message.them_moi_thanh_cong'));
    }
    public function edit($id)
    {
        $parentEdit = parent::edit($id);
        $data = $parentEdit->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return response()->view('cmsbackend::screens.pages.thu-vien-hinh-anh.edit', $data);
    }
    public function update(PostUpdateRequest $request, $id): RedirectResponse
    {
        parent::update($request, $id);
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.thu-vien-hinh-anh.index')->with('success', __('admin.message.cap_nhat_thanh_cong'));
    }
    public function destroy($id)
    {
        parent::destroy($id);
        // Trả về view tùy chỉnh của module CmsBackend
        return ResponseHelper::responseJson(200, 'success', __('admin.message.xoa_thanh_cong'));
    }
}
