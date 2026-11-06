@php
    // Param: mode [select, normal or else]
    if (!isset($mode)) {
        $mode = 'normal';
    }
@endphp

<div class="table-responsive text-nowrap">
    <table class="table table-bordered table-chuyen-muc" id="table-cm">
        <thead>
            <tr>
                <th style="width:5%">STT</th>
                <th>Tên chuyên mục</th>
                <th style="width: 30%">Chuyên mục cha</th>
                @if ($mode != 'select')
                <th style="width: 10%">{{ __('Hành động') }}</th>
                @endif
            </tr>
        </thead>
        <tbody class="table-border-bottom-0">
            @if ($categories)
                @foreach ($categories as $index => $cate)
                    <tr>
                        <th scope="row" class="text-center">{{ $index + 1 }}</th>
                        <td>
                            {{ $cate->term->name }}
                        </td>
                        <td>
                            @if ($cate->parent)
                                {{ $cate->parent->term->name }}
                            @endif
                        </td>
                        @if ($mode != 'select')
                        <td class="text-center">
                            <div class="gap-50">
                                <a href="{{ route('chuyen-muc.edit', $cate) }}"
                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                    data-bs-toggle="tooltip" title="Xem">
                                    <i class="ri-eye-line ri-20px"></i>
                                </a>
                                <button
                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-more-2-line ri-20px"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end m-0" style="">
                                    <a href="{{ route('chuyen-muc.edit', $cate) }}" class="dropdown-item">
                                        <i class="ri-eye-line me-2"></i><span>Xem</span>
                                    </a>
                                    <a href="{{ route('chuyen-muc.edit', $cate) }}" class="dropdown-item">
                                        <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                    </a>
                                    <a href="javascript:void(0)" class="dropdown-item delete-record"
                                        data-action="menu-delete" data-id="{{ $cate->id }}"
                                        data-route="{{ route('chuyen-muc.destroy', $cate) }}">
                                        <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                        @endif
                    </tr>
                @endforeach
            @endif
            @if ($categories->isEmpty())
                <tr>
                    <td colspan="100">Không tìm thấy dữ liệu</td>
                </tr>
            @endif

        </tbody>
    </table>
    <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
        {{ $categories->onEachSide(2)->links('layouts.paginate') }}
    </div>
</div>
