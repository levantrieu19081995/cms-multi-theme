<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CmsFrontend\DataProviders\ImageLibraryDataProvider;

class HinhAnhController extends Controller
{
    protected ImageLibraryDataProvider $imageLibraryDataProvider;

    public function __construct(ImageLibraryDataProvider $imageLibraryDataProvider)
    {
        $this->imageLibraryDataProvider = $imageLibraryDataProvider;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cmsfrontend::screens.pages.frontend.hinh-anh.index');
    }

    public function danhSach(Request $request)
    {
        $id = $request->id ?? 0;
        $albums = $this->imageLibraryDataProvider->getDanhSachThuVienHinhAnh($id);
        foreach ($albums as $key => $album) {
            $album->image = $album->media->map(function ($media) {
                return [
                    'url' => $media->getUrl(),
                    'description' => $media->getCustomProperty('description') ?? '',
                    'index' => $media->getCustomProperty('index') ?? 0
                ];
            })->sortBy('index')->values();
        }
        return view('cmsfrontend::screens.pages.frontend.hinh-anh.danh-sach', compact('albums', 'id'));
    }

    public function showBySlug($slug)
    {
        // dd($slug);
        $album = $this->imageLibraryDataProvider->getThuVienHinhAnhBySlug($slug);
        // dd($album);
        if (!$album) {
            abort(404, __('messages.album_missing'));
        }

        $album->image = $album->media->map(function ($media) {
            return [
                'url' => $media->getUrl(),
                'description' => $media->getCustomProperty('description') ?? '',
                'index' => $media->getCustomProperty('index') ?? 0,
                'ratio' => \Modules\CmsFrontend\Support\MediaSize::ratio($media),
            ];
        })->sortBy('index')->values();

        return view('cmsfrontend::screens.pages.frontend.hinh-anh.show', compact('album'));
    }
}
