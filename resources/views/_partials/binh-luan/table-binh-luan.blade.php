<table class="table table-bordered table-striped table-bai-viet" id="table-bai-viet">
    <thead>
        <tr>
            <th style="width: 5px">{{ __('STT') }}</th>

            <th style="width: 150px;">{{ __('Tác giả') }}</th>

            <th>{{ __('Bình luận') }}</th>

            {{-- <th style="width: 230px;">{{ __('Trả lời cho') }}</th> --}}

            <th style="width: 230px;">{{ __('Thuộc bài viết') }}</th>

            <th style="width: 230px;">{{ __('Ngày tạo') }}</th>
            <th style="width: 230px;">{{ __('Trạng thái') }}</th>

            <th style="width: 5px;">{{ __('Hành động') }}</th>

        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
        @if (!isset($comments) || $comments->isEmpty())
            <tr>
                <td colspan="5" class="text-center">Không tìm thấy dữ liệu</td>
            </tr>
        @else
            @foreach ($comments as $index => $comment)
                <tr>
                    <th scope="row" class="text-center">
                        {{ ($comments->currentPage() - 1) * $comments->perPage() + $loop->index + 1 }}</th>
                    @if ($comment->user != null)
                        <td class=""> {{ $comment->user->name }}</td>
                    @else
                        <td class=""></td>
                    @endif
                    <td class=""> {{ $comment->content }}</td>
                    <td class=""><a style="color:var(--bs-table-color);"
                            href="{{ route('bai-viet.edit', $comment->post) }}"> {{ $comment->post->title }}</a> </td>
                    <td class=""> {{ $comment->created_at }}</td>
                    <td class="">
                        @if ($comment->status === App\Enums\CommentStatus::PENDING->value)
                            <span
                                class="badge bg-label-secondary">{{ App\Enums\CommentStatus::PENDING->label() }}</span>
                        @elseif ($comment->status === App\Enums\CommentStatus::APPROVED->value)
                            <span
                                class="badge bg-label-success">{{ App\Enums\CommentStatus::APPROVED->label() }}</span>
                        @elseif ($comment->status === App\Enums\CommentStatus::REJECTED->value)
                            <span class="badge bg-label-danger">{{ App\Enums\CommentStatus::REJECTED->label() }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{-- <div class="gap-50">
                                @if ($comment->status !== App\Enums\CommentStatus::APPROVED->value)
                                <a style="color: white"
                                    class="btn btn-sm btn-icon btn-text-success  rounded-pill waves-effect"
                                    data-bs-toggle="tooltip" title="{{ __('Phê duyệt') }}"
                data-action="menu-approval" data-id="{{ $comment->id }}">
                <i class=" ri-check-fill ri-20px"></i>
                </a>
                @endif
                <button
                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-more-2-line ri-20px"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end m-0">
                    <a class="dropdown-item" data-action="menu-reject" data-id="{{ $comment->id }}">
                        <i class="ri-stop-circle-line me-2"></i><span>{{ __('Không duyệt') }}</span>
                    </a>
                    <a href="javascript:void(0)" class="dropdown-item delete-record"
                        data-action="menu-delete" data-id="{{ $comment->id }}"
                        data-route="{{ route('binh-luan.destroy', $comment) }}">
                        <i class="ri-delete-bin-7-line me-2"></i><span>{{ __('Xóa') }}</span>
                    </a>
                </div>
                </div> --}}
                        <div class="gap-50">
                            {{-- @if ($comment->status !== App\Enums\CommentStatus::APPROVED->value)
                                <a style="color: white"
                                    class="btn btn-sm btn-icon btn-text-success rounded-pill waves-effect"
                                    data-bs-toggle="tooltip" title="{{ __('Phê duyệt') }}" data-action="menu-approval"
                                    data-id="{{ $comment->id }}">
                                    <i class="ri-check-fill ri-20px"></i>
                                </a>
                            @endif --}}

                            @if (($permissions['canApprove'] ?? false) && $comment->status !== App\Enums\CommentStatus::APPROVED->value)
                                <a style="color: white"
                                class="btn btn-sm btn-icon btn-text-success rounded-pill waves-effect"
                                data-bs-toggle="tooltip"
                                title="{{ __('Phê duyệt') }}"
                                data-action="menu-approval"
                                data-id="{{ $comment->id }}">
                                    <i class="ri-check-fill ri-20px"></i>
                                </a>
                            @endif

                            <!-- Reply Button -->
                            {{-- <button class="btn btn-sm btn-icon btn-text-primary rounded-pill waves-effect btn-reply"
                                data-comment-id="{{ $comment->id }}" data-trang-thai="{{ $comment->status }}"
                                title="{{ __('Trả lời') }}">
                                <i class="ri-reply-fill ri-20px"></i>
                            </button> --}}

                            @if (($permissions['canReply'] ?? false))
                                <button class="btn btn-sm btn-icon btn-text-primary rounded-pill waves-effect btn-reply"
                                    data-comment-id="{{ $comment->id }}"
                                    data-trang-thai="{{ $comment->status }}"
                                    title="{{ __('Trả lời') }}">
                                    <i class="ri-reply-fill ri-20px"></i>
                                </button>
                            @endif


                            {{-- <button
                                class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-2-line ri-20px"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end m-0">
                                <a class="dropdown-item" data-action="menu-reject" data-id="{{ $comment->id }}">
                                    <i class="ri-stop-circle-line me-2"></i><span>{{ __('Không duyệt') }}</span>
                                </a>
                                <a href="javascript:void(0)" class="dropdown-item delete-record"
                                    data-action="menu-delete" data-id="{{ $comment->id }}"
                                    data-route="{{ route('binh-luan.destroy', $comment) }}">
                                    <i class="ri-delete-bin-7-line me-2"></i><span>{{ __('Xóa') }}</span>
                                </a>
                            </div> --}}

                            @php
                            $canReject = $permissions['canReject'] ?? false;
                            $canDelete = $permissions['canDelete'] ?? false;
                            @endphp

                            @if ($canReject || $canDelete)
                            <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-2-line ri-20px"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end m-0">
                                @if ($canReject)
                                    <a class="dropdown-item" data-action="menu-reject" data-id="{{ $comment->id }}">
                                        <i class="ri-stop-circle-line me-2"></i><span>{{ __('Không duyệt') }}</span>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <a href="javascript:void(0)" class="dropdown-item delete-record"
                                        data-action="menu-delete"
                                        data-id="{{ $comment->id }}"
                                        data-route="{{ route('binh-luan.destroy', $comment) }}">
                                        <i class="ri-delete-bin-7-line me-2"></i><span>{{ __('Xóa') }}</span>
                                    </a>
                                @endif
                            </div>
                            @endif

                        </div>
                    </td>

                </tr>
            @endforeach
        @endif
    </tbody>
</table>

<div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
    {{ $comments->onEachSide(2)->links('layouts.paginate') }}
</div>
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel">{{ __('Trả lời bình luận') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="replyForm" method="POST">

                <input type="hidden" name="comment_id" id="comment_id">
                <div class="modal-body">
                    <textarea class="form-control" name="reply_content" id="reply_content" rows="4"
                        placeholder="{{ __('Nhập nội dung trả lời') }}" required></textarea>
                    <div class="form-check mt-3" id="check-approved">
                        <input class="form-check-input" type="checkbox" name="approve_comment" id="approve_comment">
                        <label class="form-check-label" for="approve_comment">
                            {{ __('Phê duyệt bình luận') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('Đóng') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Gửi trả lời') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
