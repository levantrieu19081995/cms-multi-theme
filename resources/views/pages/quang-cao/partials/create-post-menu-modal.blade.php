@php
    // Param: type
    if (!isset($type)) {
        $type = \App\Enums\PostType::POST->value;
    }
    $postLabel = \App\Enums\PostType::tryFrom($type)->label();
@endphp

<div id="modal-div">
    <div class="modal fade" id="create_{{ $type }}_menu_modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span class="action-mode">Thêm</span> bài viết</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <input type="hidden" name="ref_id">
                                <input type="hidden" name="id">
                                <input type="hidden" name="type">
                                <input type="text" class="form-control" placeholder="Văn bản hiển thị"
                                    name="title">
                                <label>Văn bản hiển thị</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <x-input-post-select label="" name="url" callback="actionSelectPost"
                                        displayMode="input"
                                        placeholder="{{ $postLabel }}"
                                        type="{{ $type }}"/>
                                <label>{{ $postLabel }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <select class="form-control" name="target">
                                    <option value="_self">Cửa sổ hiện tại</option>
                                    <option value="_blank">Cửa sổ mới</option>
                                </select>
                                <label>Mở tại</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary waves-effect"
                        data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light create_menu_item"
                        data-form="create_{{ $type }}_menu_modal">Lưu</button>
                </div>
            </div>
        </div>
    </div>
</div>
