<?php

namespace App\Http\Controllers;

use App\DataProviders\TaxonomyDataProvider;
use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\DataProviders\VideoDataProvider;
use App\Enums\PermissionsEnum;
use App\Enums\PostFormat;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use App\Facades\Toastr;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Http\Requests\Video\VideoUpdateRequest;
use App\Models\Feedback;
use App\Models\Post;
use App\Traits\PostHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VideoController extends AbstractPostController
{

    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider)
    {

        $this->middleware(function ($request, $next) use ($postDataProvider, $taxonomyDataProvider, $tagDataProvider) {
            $this->user = Auth::user();
            $permissionsBase = [
                'canView' =>   $this->user->hasAnyPermission([PermissionsEnum::POST_VIEW->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canReject' => $this->user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::POST_UPDATE->label()]),
                'canSaveDraft' => $this->user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canSubmitForApproval' => $this->user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canPublishNow' => $this->user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
                'canStopPublishing' => $this->user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
            ];
            parent::__construct($postDataProvider, $taxonomyDataProvider, $tagDataProvider, PostType::VIDEO, '_partials.video.table-video', __('video'), 'pages.video', 'video.index',   $permissionsBase);

            return $next($request);
        });
    }


    // public function store(PostUpdateRequest $request)
    // {

    //     DB::transaction(function () use ($request) {
    //         $post = $this->save($request);
    //         // dd("post", $post);
    //     });

    //     return redirect()->route('video.index');
    // }

    public function store(PostUpdateRequest $request)
    {
        // Kiểm tra trùng tiêu đề
        $existingPost = Post::where('title', $request->title)->first();
        if ($existingPost) {
            return back()->withErrors(['title' => __('Tiêu đề bài viết đã tồn tại')]);
        }

        // Nếu không có lỗi, tiến hành lưu bài viết mới
        DB::transaction(function () use ($request) {
            $post = $this->save($request); // Giả sử đây là hàm lưu bài viết
        });

        return redirect()->route('video.index');
    }

    // public function store(PostUpdateRequest $request)
    // {
    //     // Kiểm tra nếu form đã được gửi trước đó (dùng session để lưu trạng thái)
    //     if (session('form_submitted', false)) {
    //         // Nếu đã gửi rồi, trả về thông báo lỗi hoặc thông báo cho người dùng
    //         return back()->withErrors(['form' => __('Bạn đã gửi form trước đó. Vui lòng đợi vài giây để tiếp tục.')]);
    //     }

    //     // Đặt cờ "form_submitted" trong session để ngăn gửi form lại
    //     session(['form_submitted' => true]);

    //     // Kiểm tra trùng tiêu đề
    //     $existingPost = Post::where('title', $request->title)->first();
    //     if ($existingPost) {
    //         // Nếu bài viết với tiêu đề đã tồn tại, trả về với lỗi
    //         session()->forget('form_submitted'); // Hủy cờ khi có lỗi
    //         return back()->withErrors(['title' => __('Tiêu đề bài viết đã tồn tại')])->withInput();
    //     }

    //     // Nếu không có lỗi, tiến hành lưu bài viết mới
    //     DB::transaction(function () use ($request) {
    //         $post = $this->save($request); // Giả sử đây là hàm lưu bài viết
    //     });

    //     // Sau khi lưu thành công, bỏ cờ "form_submitted" đi để cho phép gửi lại form nếu cần
    //     session()->forget('form_submitted');

    //     // Chuyển hướng sau khi lưu thành công
    //     return redirect()->route('video.index');
    // }
}
