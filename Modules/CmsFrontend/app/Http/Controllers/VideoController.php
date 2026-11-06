<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CmsBackend\Support\ModuleUrl;
use Modules\CmsFrontend\DataProviders\VideoDataProvider;
use Modules\CmsFrontend\Support\VideoSource;

class VideoController extends Controller
{
    protected VideoDataProvider $videoDataProvider;

    public function __construct(VideoDataProvider $videoDataProvider)
    {
        $this->videoDataProvider = $videoDataProvider;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Nếu cần dữ liệu mặc định thì load ở đây, không thì chỉ render view
        return view('cmsfrontend::screens.pages.frontend.video.index');
    }

    public function danhSach(Request $request)
    {
        $perPage = (int) $request->get('per_page', 12);

        // Lấy Paginator:
        $raw = $this->videoDataProvider->getTatCaVideo($perPage);

        // Cards link to the video page, which holds the player (VideoSource decides file / YouTube).
        $videos = $raw->through(fn ($v) => $this->card($v));

        return view('cmsfrontend::screens.pages.frontend.video.danh-sach', compact('videos'));
    }


    // public function showBySlug($slug)
    // {

    //     $video = $this->videoDataProvider->getVideoBySlug($slug);

    //     if (!$video) {
    //         abort(404, __('messages.video_missing'));
    //     }

    //     // Chuẩn hoá dữ liệu
    //     $videoData = [
    //         'title' => $video->title ?? '',
    //         'type'  => 'file',
    //         'embed_url' => $video->getFirstMediaUrl('videos'),
    //         'raw_url'   => null,
    //         'url_id'    => null,
    //     ];

    //     if (empty($videoData['embed_url'])) {
    //         $url = trim((string) ($video->content ?? ''));
    //         $id  = $video->url_id ?? null;

    //         if (!$id && $url !== '') {
    //             if (preg_match('~youtu\.be/([\w-]{6,})~', $url, $m)) {
    //                 $id = $m[1];
    //             } elseif (($q = parse_url($url, PHP_URL_QUERY)) && preg_match('~(?:^|&)v=([\w-]{6,})~', $q, $m)) {
    //                 $id = $m[1];
    //             } elseif (preg_match('~youtube\.com/embed/([\w-]{6,})~', $url, $m)) {
    //                 $id = $m[1];
    //             }
    //         }

    //         $videoData = [
    //             'title'     => $video->title ?? '',
    //             'type'      => $id ? 'youtube' : 'url',
    //             'embed_url' => $id ? "https://www.youtube.com/embed/{$id}" : $url,
    //             'raw_url'   => $url,
    //             'url_id'    => $id,
    //         ];
    //     }

    //     return view('cmsfrontend::screens.pages.frontend.video.show', compact('videoData'));
    // }

    public function showBySlug($slug)
    {
        $video = $this->videoDataProvider->getVideoBySlug($slug);

        if (!$video) {
            abort(404, __('messages.video_missing'));
        }

        $source = VideoSource::of($video);
        $related = $this->videoDataProvider->getTatCaVideo()
            ->where('id', '!=', $video->id)->take(6)
            ->map(fn ($v) => $this->card($v))->values();

        return view('cmsfrontend::screens.pages.frontend.video.show', compact('video', 'source', 'related'));
    }

    /** @return array{title: string, url: string, poster: ?string, date: ?string, type: string} */
    private function card($video): array
    {
        $source = VideoSource::of($video);
        return [
            'title' => (string) ($video->title ?? ''),
            'url' => ModuleUrl::route('cms.video.show', ['slug' => $video->slug]),
            'poster' => $source['poster'],
            'date' => $video->published_at ? \Illuminate\Support\Carbon::parse($video->published_at)->format('d/m/Y') : null,
            'type' => $source['type'],
        ];
    }
}
