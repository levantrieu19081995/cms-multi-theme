@php
    // Param: mode, type, route
    // Mode is: [select, normal or else]
    if (!isset($mode)) {
        if (request()->mode) {
            $mode = request()->mode;
        } else {
            $mode = 'normal';
        }
    }

    if (!isset($type)) {
        $type = 'post';
    }

    if (!isset($route)) {
        $route = 'bai-viet';
    }
@endphp

@if ($mode == 'select')
    <script>
        function callback(res) {
            if (window.opener) {
                if (typeof window.opener._postSelectCallback_{{ (int) request()->state }} === 'function') {
                    window.opener._postSelectCallback_{{ (int) request()->state }}({{ (int) request()->state }}, JSON.parse(atob(
                        res)));
                    window.close();
                } else {
                    alert('Cửa sổ cha chưa định nghĩa callback _postSelectCallback_{{ (int) request()->state }}(state, res)');
                }
            } else {
                alert('Không tìm thấy của sổ cha');
            }
        }
    </script>
@endif

<table class="table table-bordered table-striped table-bai-viet" id="table-bai-viet"> 
    <thead>
        <tr>
            <th style="width: 5px">{{ __('column-name.index') }}</th>
            @if ($mode == 'select')
                <th>{{ __(\App\Enums\PostType::tryFrom($type)->label()) }}</th>
            @else
                <th>{{ __('Thông tin ' . \App\Enums\PostType::tryFrom($type)->label()) }}</th>
                <th style="width: 10%">{{ __('column-name.author') }}</th>
                @if ($type == 'post')
                    <th style="width: 20%">{{ __('column-name.category') }}</th>
                @endif
                @if ($type != 'page')
                    <th style="width: 2%;word-break: break-all">{{ __('Hiển thị trang chủ') }}</th>
                    <th style="width: 2%;word-break: break-all">{{ __('Cho phép bình luận') }}</th>
                @endif
                <th style="width: 5%;">{{ __('Hành động') }}</th>
            @endif
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
        @if (!isset($posts) || $posts->isEmpty())
            <tr>
                <td colspan="5" class="text-center">Không tìm thấy dữ liệu</td>
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
                            <p class="h6"><b><a
                                        href="{{ route($route . '.edit', $post) }}">{{ $post->title }}</a></b></p>
                            <p><i class="ri-calendar-event-line"></i>
                                {{ $post->published_at ? 'Ngày xuất bản: ' . $post->published_at : 'Ngày tạo: ' . $post->created_at }}
                            </p>
                            <p><i class="ri-information-line"></i> Trạng thái:
                                @if ($post->status == App\Enums\PostStatus::DRAFT->value)
                                    <span
                                        class="badge bg-label-secondary">{{ App\Enums\PostStatus::DRAFT->label() }}</span>
                                @elseif ($post->status == App\Enums\PostStatus::PENDING_APPROVAL->value)
                                    <span
                                        class="badge bg-label-info">{{ App\Enums\PostStatus::PENDING_APPROVAL->label() }}</span>
                                @elseif ($post->status == App\Enums\PostStatus::APPROVED->value)
                                    <span
                                        class="badge bg-label-primary">{{ App\Enums\PostStatus::APPROVED->label() }}</span>
                                @elseif ($post->status == App\Enums\PostStatus::PUBLISHED->value)
                                    <span
                                        class="badge bg-label-success">{{ App\Enums\PostStatus::PUBLISHED->label() }}</span>
                                @elseif ($post->status == App\Enums\PostStatus::NOT_APPROVED->value)
                                    <span
                                        class="badge bg-label-danger">{{ App\Enums\PostStatus::NOT_APPROVED->label() }}</span>
                                @elseif ($post->status == App\Enums\PostStatus::STOP_PUBLISHING->value)
                                    <span
                                        class="badge bg-label-danger">{{ App\Enums\PostStatus::STOP_PUBLISHING->label() }}</span>
                                @endif
                            </p>
                            @if ($type == 'post')
                                <p><i class="ri-eye-line"></i> Lượt xem: {{ $post->views }}
                                @elseif($type == 'image')
                                <p><i class="ri-numbers-line"></i> Số lượng hình ảnh:
                                    {{ $post->getMedia('images')->count() }}</p>
                            @endif
                        @endif
                        </p>
                    </td>
                    @if ($mode != 'select')
                        <td>{{ $post->author ? $post->author->name : '-' }}</td>
                        @if ($type == 'post')
                            <td>
                                @foreach ($post->taxonomies as $taxonomy)
                                    @if ($taxonomy->taxonomy == 'category')
                                        <span class="badge bg-warning mb-1">{{ $taxonomy->term->name }}</span><br />
                                    @endif
                                @endforeach
                            </td>
                        @endif
                        @if ($type != 'page')
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
                            <td class="text-center"> <label class="switch switch-success">
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
                        <td class="text-center">
                            <div class="d-flex align-items-center gap-50 justify-content-center">
                                <a href="{{ route($route . '.edit', $post) }}"
                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                    data-bs-toggle="tooltip" title="{{ __('Sửa') }}">
                                    <i class="ri-edit-box-line ri-20px"></i>
                                </a>
                                {{-- <button
                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ri-more-2-line ri-20px"></i>
                    </button> --}}
                                {{-- <div class="dropdown-menu dropdown-menu-end m-0">
                        <a href="#" class="dropdown-item">
                            <i class="ri-edit-box-line me-2"></i><span>{{ __('Sửa') }}</span>
                        </a> --}}
                                <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                    data-action="menu-delete" data-id="{{ $post->id }}"
                                    data-route="{{ route($route . '.destroy', $post) }}" data-bs-toggle="tooltip"
                                    title="{{ __('Xóa') }}">
                                    <i class="ri-delete-bin-7-line me-2"></i>
                                </a>
                                {{-- </div> --}}
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

{{-- <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
    {{ $posts->onEachSide(2)->links('layouts.paginate') }}
</div> --}}
<div class="form-layout-footer" style="margin-top:20px;margin-right:20px;margin-left:20px;">
  {{ $posts->onEachSide(2)->appends(request()->query())->links('layouts.paginate') }}
</div>
