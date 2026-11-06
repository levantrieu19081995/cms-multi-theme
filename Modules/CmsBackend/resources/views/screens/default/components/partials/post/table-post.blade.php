@php
    // Param: mode, type, route, table attribute
    // table attribute: thuộc tính bảng, dùng cho chức năng xác nhận xóa
    // Mode is: [select, normal or else]
    use Modules\CmsBackend\Enums\PermissionsEnum as CDNPerm;  
    use App\Enums\PermissionsEnum as CorePerm;   

    use App\Enums\PostType;
    use App\Enums\PostStatus;
    use Carbon\Carbon;

    $abilities = match ($type) {
    'post' => [
        'update' => CDNPerm::BAIVIET_UPDATE->label(), // hoặc ->value nếu hệ thống dùng value
        'delete' => CDNPerm::BAIVIET_DELETE->label(),
    ],
    'page' => [
        'update' => CorePerm::PAGE_UPDATE->label(),
        'delete' => CorePerm::PAGE_DELETE->label(),
    ],
    'banner' => [
        'update' => CorePerm::BANNER_UPDATE->label(),
        'delete' => CorePerm::BANNER_DELETE->label(),
    ],
    // 'image' => [  // Thêm quyền cho image mà không sửa enum
    //     'update' => 'Quyền cập nhật hình ảnh', // Quyền cập nhật hình ảnh, thay bằng giá trị thực tế
    //     'delete' => 'Quyền xóa hình ảnh', // Quyền xóa hình ảnh, thay bằng giá trị thực tế
    // ],
    // 'cms.video' => [  // Thêm quyền cho image mà không sửa enum
    //     'update' => 'Quyền cập nhật video',  
    //     'delete' => 'Quyền xóavideo', 
    // ],
     default => [
        'update' => null,
        'delete' => null,
    ],
    
};

    $mode = $mode ?? (request()->mode ?? 'normal');
    $type = $type ?? 'post';
    $route = $route ?? 'bai-viet';
    $tableId = isset($tableId) ? $tableId . '-table' : 'table-bai-viet';
if (!function_exists('postStatusBadge')) {
    function postStatusBadge($status)
    {
        return match ($status) {
            PostStatus::DRAFT->value => '<span class="badge bg-label-secondary">' .
                PostStatus::DRAFT->label() .
                '</span>',
            PostStatus::PENDING_APPROVAL->value => '<span class="badge bg-label-info">' .
                PostStatus::PENDING_APPROVAL->label() .
                '</span>',
            PostStatus::APPROVED->value => '<span class="badge bg-label-primary">' .
                PostStatus::APPROVED->label() .
                '</span>',
            PostStatus::PUBLISHED->value => '<span class="badge bg-label-success">' .
                PostStatus::PUBLISHED->label() .
                '</span>',
            PostStatus::NOT_APPROVED->value,
            PostStatus::STOP_PUBLISHING->value
                => '<span class="badge bg-label-danger">' . PostStatus::tryFrom($status)?->label() . '</span>',
            default => '',
        };
    }
}
    if (!function_exists('approvedBadge')) {
    function approvedBadge($post) {
        $isApproved = !empty($post->approved_at)
            || $post->status === \App\Enums\PostStatus::APPROVED->value
            || $post->status === \App\Enums\PostStatus::PUBLISHED->value;

        $approvedAt = $post->approved_at ? \Carbon\Carbon::parse($post->approved_at)->format('d/m/Y H:i') : null;

        if ($isApproved) {
            return '<span class="badge bg-label-success">'.e(__('admin.ui.approved')).'</span>'
                 . ($approvedAt ? '<div class="text-muted small mt-1">'.$approvedAt.'</div>' : '');
        }
        if ($post->status === \App\Enums\PostStatus::PENDING_APPROVAL->value) {
            return '<span class="badge bg-label-warning">'.e(__('admin.ui.pending_approval_short')).'</span>';
        }
        if ($post->status === \App\Enums\PostStatus::NOT_APPROVED->value) {
            return '<span class="badge bg-label-danger">'.e(__('admin.ui.rejected')).'</span>';
        }
        return '<span class="badge bg-label-secondary">'.e(__('admin.ui.not_yet')).'</span>';
    }
}

// if (!function_exists('publishedBadge')) {
//     function publishedBadge($post) {
//         // CHỈ hiển thị __('admin.ui.da_xuat_ban') khi status đang là PUBLISHED
//         if ($post->status === \App\Enums\PostStatus::PUBLISHED->value) {
//             $publishedAt = $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') : null;
//             // return '<span class="badge bg-primary">{{ __('admin.ui.da_xuat_ban') }}</span>'. ($publishedAt ? '<div class="text-muted small mt-1">'.$publishedAt.'</div>' : '');
//             return '<span class="badge bg-primary">{{ __('admin.ui.da_xuat_ban') }}</span>';
//         }
//         if ($post->status === \App\Enums\PostStatus::STOP_PUBLISHING->value) {
//             return '<span class="badge bg-label-secondary">{{ __('admin.ui.da_ngung_xb') }}</span>';
//         }
//         // Các trạng thái còn lại coi như "chưa xuất bản"
//         return '<span class="badge bg-label-secondary">{{ __('admin.ui.not_yet') }}</span>';
//     }
// }

if (!function_exists('postStatusBadge')) {
    function postStatusBadge($status) {
        return match ($status) {
            \App\Enums\PostStatus::DRAFT->value => '<span class="badge bg-label-secondary">'.\App\Enums\PostStatus::DRAFT->label().'</span>',
            \App\Enums\PostStatus::PENDING_APPROVAL->value => '<span class="badge bg-label-info">'.\App\Enums\PostStatus::PENDING_APPROVAL->label().'</span>',
            \App\Enums\PostStatus::APPROVED->value => '<span class="badge bg-label-primary">'.\App\Enums\PostStatus::APPROVED->label().'</span>',
            \App\Enums\PostStatus::PUBLISHED->value => '<span class="badge bg-label-success">'.\App\Enums\PostStatus::PUBLISHED->label().'</span>',
            \App\Enums\PostStatus::NOT_APPROVED->value,
            \App\Enums\PostStatus::STOP_PUBLISHING->value
                => '<span class="badge bg-label-danger">'.\App\Enums\PostStatus::tryFrom($status)?->label().'</span>',
            default => '',
        };
    }
}
@endphp 

@if ($mode == 'select')
    <script>
        function callback(res) {
            if (window.opener) {
                if (typeof window.opener._postSelectCallback_{{ request()->state }} === 'function') {
                    window.opener._postSelectCallback_{{ request()->state }}({{ request()->state }}, JSON.parse(atob(
                        res)));
                    window.close();
                } else {
                    alert(@json(__('admin.js.parent_callback_missing')));
                }
            } else {
                alert(@json(__('admin.js.parent_window_missing')));
            }
        }
    </script>
@endif

<table id="{{ $tableId }}" data-table-id="{{ $tableId }}"
    data-action="{{ $tableId . '-delete' ?? 'bai-viet-delete' }}"
    class="table table-bordered table-striped table-bai-viet">

    <thead>
        <tr>
            <th style="width: 5px">{{ __('admin.ui.sequence') }}</th>
            @if ($mode == 'select')
                <th>{{ $type === 'page' ? __('admin.ui.information_page') : __('admin.ui.information_article') }}</th>
            @else
                <th>{{ $type === 'page' ? __('admin.ui.information_page') : __('admin.ui.information_article') }}</th>
                @if ($type == 'post'  )
                    <th style="width: 10%; text-align: center">{{ __('admin.ui.tac_gia') }}</th>
                @endif
                @if ($type == 'post')
                    <th style="width: 20%">{{ __('admin.ui.chuyen_muc') }}</th>
                @endif  
                <th style="width: 2%;word-break: break-all">{{ __('admin.ui.hien_thi_o_trang_chu') }}</th>
                    
                @if ($type == 'post'  )
                    <th style="width: 2%;word-break: break-all">{{ __('admin.ui.allow_comments') }}</th>
                @endif
                <th style="width: 10%; text-align:center">{{ __('admin.ui.approval') }}</th>
                {{-- <th style="width: 10%; text-align:center">Xuất bản</th> --}}
                <th style="width: 5%;">{{ __('admin.ui.thao_tac') }}</th>
            @endif
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
        @if (!isset($posts) || $posts->isEmpty())
            <tr>
                <td colspan="5" class="text-center">{{ __('admin.ui.not_found') }}</td>
            </tr>
        @else
            @foreach ($posts as $index => $post)
                <tr>
                    <th scope="row" class="text-center">
                        {{ ($posts->currentPage() - 1) * $posts->perPage() + $loop->index + 1 }}</th>
                    <td class="td-thong-tin-bai-viet">
                        @if ($mode == 'select')
                            <a href="javascript:void(0)"
                                onclick="callback('{{ base64_encode(json_encode(['id' => $post->id, 'title' => $post->title, 'slug' => $post->slug])) }}');">{{ $post->title }}</a>
                        @else
                            <p class="h6">
                                <strong>
                                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($route . '.edit', $post) }}">{{ $post->title }}</a></strong>
                            </p>
                            <p><i class="ri-calendar-event-line"></i>
                                {{ $post->published_at 
                                    ? __('admin.ui.published_at').': ' . Carbon::parse($post->published_at)->format('d/m/Y H:i:s')
                                    : __('admin.ui.created_at').': ' . Carbon::parse($post->created_at)->format('d/m/Y H:i:s') }}
                            </p>
                            <p><i class="ri-information-line"></i> {{ __('admin.ui.trang_thai_2') }}
                                {{-- @dd($post->status) --}}
                                {!! postStatusBadge($post->status) !!}
                            </p>
                            @if ($type == 'post')
                                <p><i class="ri-eye-line"></i> {{ __('admin.ui.views') }}: {{ $post->views }}
                                {{-- @elseif($type == 'image')
                                <p><i class="ri-numbers-line"></i> Số lượng hình ảnh:
                                    {{ $post->getMedia('images')->count() }}</p> --}}
                            @endif
                        @endif
                        </p>
                    </td>
                    @if ($mode !== 'select')
                        @if ($type == 'post')
                        {{-- <td class="text-center">{{ $post->author ? $post->author->name : '-' }}</td>  --}}
                        <td class="text-center" style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;  hyphens: auto;  ">{{ $post->author ? $post->author_names: '-' }}</td> 
                            <td style="text-align: center">
                                @foreach ($post->taxonomies as $taxonomy)
                                    @if ($taxonomy->taxonomy == 'category')
                                        <span class="badge bg-warning mb-1">{{ $taxonomy->term->name }}</span><br />
                                    @endif
                                @endforeach
                            </td>
                        @endif 
                        <td class="text-center"> <label class="switch switch-success">
                            <input type="checkbox" name="show_home_page" data-post-id="{{ $post->id }}"
                                class="switch-input" {{ $post->show_home_page == '1' ? 'checked' : '' }} />
                            <span class="switch-toggle-slider">
                                <span class="switch-on">
                                    <i class="ri-check-line"></i>
                                </span>
                                <span class="switch-off">
                                    <i class="ri-close-line"></i>
                                </span>
                            </span>

                        </label>
                    </td> 
                            @if ($type == 'post')
                            <td class="text-center">
                                <label class="switch switch-success">
                                    <input type="checkbox" name="comment_status" data-post-id="{{ $post->id }}"
                                        class="switch-input-comment"
                                        {{ $post->comment_status == 'published' ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider">
                                        <span class="switch-on">
                                            <i class="ri-check-line"></i>
                                        </span>
                                        <span class="switch-off">
                                            <i class="ri-close-line"></i>
                                        </span>
                                    </span>
                                </label>
                            </td>
                        @endif

                        <td class="text-center">{!! approvedBadge($post) !!}</td>
                        {{-- <td class="text-center">{!! publishedBadge($post) !!}</td> --}}
                        <td class="text-center">
                            <div class="d-flex align-items-center gap-50 justify-content-center">
                               @can($abilities['update'])
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($route . '.edit', $post) }}"
                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                    data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}">
                                    <i class="ri-edit-box-line ri-20px"></i>
                                </a>
                               @endcan
                            
                               @can($abilities['delete'])
                                <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                    data-action="{{ $tableId . '-delete' }}" data-id="{{ $post->id }}"
                                    data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route($route . '.destroy', $post) }}" data-bs-toggle="tooltip"
                                    title="{{ __('admin.ui.xoa') }}">
                                    <i class="ri-delete-bin-7-line me-2"></i>
                                </a>
                                @endcan
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

<div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
    {{ $posts->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
</div>
