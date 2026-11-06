<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Helpers\ResponseHelper;
use Modules\CmsBackend\Http\Controllers\Shared\PageController;
use App\Http\Requests\Post\PostUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CDNTrangController extends PageController
{
    public function index(Request $request)
    {
        // dump($request->toArray());
        $parentIndex = parent::index($request);
        // Cửa sổ chọn trang (menu, liên kết...) mở ?mode=select: dùng đúng view chọn của controller cha, không dựng lại trang quản lý.
        if ($request->input('mode') === 'select') {
            return $parentIndex;
        }
        // dump($parentIndex);
        $data = $parentIndex->getData();
        // dump($data);
        // Trả về view tùy chỉnh của module CmsBackend
        return view('cmsbackend::screens.pages.trang.index', $data);
    }
    public function create()
    {
        $parentCreate = parent::create();
        $data = $parentCreate->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return response()->view('cmsbackend::screens.pages.trang.edit', $data);
    }
    public function store(PostUpdateRequest $request)
    {
        parent::store($request);
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.trang.index')->with('success', __('admin.message.them_moi_thanh_cong'));
    }
    public function edit($id)
    {
        $parentEdit = parent::edit($id);
        $data = $parentEdit->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        // dump($data);
        return response()->view('cmsbackend::screens.pages.trang.edit', $data);
    }
    public function update(PostUpdateRequest $request, $id): RedirectResponse
    {
        parent::update($request, $id);
        // Trả về view tùy chỉnh của module CmsBackend
        return redirect()->route('cms.dashboard.cdn.trang.index')->with('success', __('admin.message.cap_nhat_thanh_cong'));
    }
    public function destroy($id)
    {
        parent::destroy($id);
        // $parentIndex = parent::index(new Request());
        // $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CmsBackend
        return ResponseHelper::responseJson(200, 'success', __('admin.message.xoa_thanh_cong'));
    }
}
