@if ($mode == 'select')
    <script>
        function callback(res) {
            if (window.opener) {
                if (typeof window.opener._postSelectCallback_{{ (int) request()->state }} === 'function') {
                    window.opener._postSelectCallback_{{ (int) request()->state }}({{ (int) request()->state }}, JSON.parse(atob(
                        res)));
                    window.close();
                } else {
                    alert(@json(__('admin.js.parent_callback_missing')));
                }
            } else {
                alert(@json(__('admin.js.khong_tim_thay_cua_so_cha')));
            }
        }
    </script>
@endif
<table class="table table-bordered table-striped table-bai-viet" id="table-bai-viet" data-action="su-kien-delete">
    <thead>
        <tr>
            <th style="width: 5px">{{ __('column-name.index') }}</th>
            @if ($mode == 'select')
                <th>{{ __(\App\Enums\PostType::tryFrom($type)->label()) }}</th>
            @else
                <th>{{ __('admin.ui.thong_tin_su_kien') }}</th>
                <th style="width: 10%">{{ __('column-name.author') }}</th>
                <th style="width: 10%">{{ __('admin.ui.tu_ngay') }}</th>
                <th style="width: 10%">{{ __('admin.ui.den_ngay') }}</th>
                @if ($type != 'page')
                    <th style="width: 2%;word-break: break-all">{{ __('admin.ui.su_kien_noi_bat_2') }}</th>
                    <th style="width: 2%;word-break: break-all">{{ __('admin.ui.cho_phep_binh_luan') }}</th>
                @endif
                @if ($permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                    <th style="width: 5%;">{{ __('admin.ui.hanh_dong') }}</th>
                @endif
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
                            <p class="h6"><b><a
                                        href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.edit', $post) }}">{{ $post->title }}</a></b>
                            </p>
                            <p><i class="ri-information-line"></i> {{ __('admin.ui.trang_thai_2') }}
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
                        @endif
                        </p>
                    </td>
                    @if ($mode != 'select')
                        <td>{{ $post->author ? $post->author->name : '-' }}</td>
                        <td class="text-center">
                            {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') : '' }}
                        </td>
                        <td class="text-center">
                            {{ $post->publication_date_expires ? \Carbon\Carbon::parse($post->publication_date_expires)->format('d/m/Y') : '' }}
                        </td>
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
                                        class="switch-input-comment" {{ $post->comment_status == 'published' ? 'checked' : '' }} />
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
                        @if ($permissions['canUpdate'] === true || $permissions['canDelete'] === true)
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-50 justify-content-center">
                                    {{-- <button
                                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-line ri-20px"></i>
                                    </button> --}}

                                    {{-- <div class="dropdown-menu dropdown-menu-end m-0"> --}}
                                    @if ($permissions['canUpdate'] === true)
                                        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.edit', $post) }}" class="dropdown-item"
                                            data-bs-toggle="tooltip" title="{{ __('admin.record.edit') }}"
                                            title="{{ __('admin.record.edit') }}">
                                            <i class="ri-edit-box-line"></i>
                                        </a>
                                    @endif
                                    @if ($permissions['canDelete'] === true)
                                        <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                            data-action="su-kien-delete" data-id="{{ $post->id }}"
                                            data-route="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.destroy', $post) }}"
                                            title="{{ __('admin.ui.xoa') }}">
                                            <i class="ri-delete-bin-7-line me-2"></i>
                                        </a>
                                    @endif
                                    {{-- </div> --}}
                                </div>
                            </td>
                        @endif
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

<div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
    {{ $posts->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}
</div>
