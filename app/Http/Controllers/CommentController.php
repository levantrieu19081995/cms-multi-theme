<?php

namespace App\Http\Controllers;

use App\DataProviders\CommentDataProvider;
use App\Enums\CommentStatus;
use App\Enums\PermissionsEnum;
use App\Facades\Toastr;
use App\Http\Requests\Comment\CommentCreateRequest;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    protected CommentDataProvider $commentDataProvider;
    public function __construct(CommentDataProvider $commentDataProvider)
    {
        $this->commentDataProvider = $commentDataProvider;
    }

    // private $permissionsBase = [
    //     'canView' => true,
    //     'canApprove' => true,
    //     'canReject' => true,
    //     'canUpdate' => true,
    //     'canCreate' => true,
    //     'canDelete' => true,
    // ];

    // private function checkPermission()
    // {

    //     // Kiểm tra điều kiện hiển thị nút Duyệt
    //     $user = auth()->user();
    //     // if ($user->can(PermissionsEnum::COMMENT_CREATE->label())) {
    //     //     $this->permissionsBase['canCreate'] = true;
    //     // }
    //     // if ($user->can(PermissionsEnum::COMMENT_UPDATE->label())) {
    //     //     $this->permissionsBase['canUpdate'] = true;
    //     // }
    //     if ($user->can(PermissionsEnum::COMMENT_VIEW->label())) {
    //         $this->permissionsBase['canView'] = true;
    //     }
    //     if ($user->can(PermissionsEnum::COMMENT_APPROVE->label())) {
    //         $this->permissionsBase['canApprove'] = true;
    //     }
    //     if ($user->can(PermissionsEnum::COMMENT_REJECT->label())) {
    //         $this->permissionsBase['canReject'] = true;
    //     }
    //     if ($user->can(PermissionsEnum::COMMENT_DELETE->label())) {
    //         $this->permissionsBase['canDelete'] = true;
    //     }
    // }

    private $permissionsBase = [
        'canView' => false,
        'canApprove' => false,
        'canReject' => false,
        'canDelete' => false,
        'canReply' => false, // thêm nếu bạn muốn tách quyền reply
    ];

    private function checkPermission(): array
    {
        $user = auth()->user();

        $this->permissionsBase['canView'] = $user->can(PermissionsEnum::COMMENT_VIEW->label());
        $this->permissionsBase['canApprove'] = $user->can(PermissionsEnum::COMMENT_APPROVE->label());
        $this->permissionsBase['canReject'] = $user->can(PermissionsEnum::COMMENT_REJECT->label());
        $this->permissionsBase['canDelete'] = $user->can(PermissionsEnum::COMMENT_DELETE->label());

        // Reply: nếu bạn chưa có permission riêng thì cho nó dùng COMMENT_APPROVE hoặc COMMENT_VIEW tùy nghiệp vụ
        $this->permissionsBase['canReply'] = $this->permissionsBase['canApprove'] || $this->permissionsBase['canView'];

        return $this->permissionsBase;
    }


    public function index(Request $request)
    {
        $permissions = $this->checkPermission();
        $pageSize = $request->pageSize ?? 20;
        $filter = trim(strip_tags($request->query('filter')));
        $comments = $this->commentDataProvider->paginate($filter, $pageSize);
        $comments->appends($request->all());
        if ($request->ajax()) {
            return view('_partials.binh-luan.table-binh-luan', compact(['comments']));
        }
        return view('pages.binh-luan.index', compact('comments', 'permissions'));
    }

    // Phê duyệt bình luận
    public function approve($id)
    {

        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'approved']);
        return response()->json([
            'status' => 1,
            'message' => 'Phê duyệt thành công'
        ]);
    }
    // Thêm bình luận
    public function store(CommentCreateRequest $request)
    {
        Comment::create([
            'post_id' => $request->post_id,
            'user_id' => auth()->user()->id,
            'content' => $request->content,
            'status' => CommentStatus::PENDING->value, // Bình luận mặc định chờ phê duyệt
            'created_at' => now(),
            'parent_id' => $request->parent_id, // Nếu là trả lời, lưu parent_id
        ]);
        Toastr::success(__('Gửi thành công'));
        return redirect()->back();
    }
    // Từ chối bình luận
    public function reject($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'rejected']);

        return response()->json([
            'status' => 1,
            'message' => 'Từ chối bình luận thành công'
        ]);
    }
    // Xóa bình luận 
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();
        return response()->json([
            'status' => 1,
            'message' => 'Xóa thành công'
        ]);
    }
    public function reply(Request $request, $id)
    {
        //$this->checkPermission();

        //DB::transaction(function () use ($request, $id) {
        $request->validate([
            'reply_content' => 'required|string',
        ]);

        $comment = Comment::findOrFail($id);
        if ($request->has('approve_comment') && $request->approve_comment) {
            //if($this->permissionsBase['canApprove']){
            $comment->status = CommentStatus::APPROVED->value;
            //}
        }
        $comment->save();
        $comment_new = new Comment;
        $comment_new->content = $request->reply_content;
        $comment_new->parent_id = $id;
        $comment_new->user_id = auth()->user()->id;
        $comment_new->post_id = $comment->post_id;
        $comment_new->status = CommentStatus::APPROVED->value;
        $comment_new->save();
        return response()->json([
            'status' => 1,
            'message' => 'Đã trả lời và phê duyệt bình luận thành công.'
        ]);

        // });
    }
}
