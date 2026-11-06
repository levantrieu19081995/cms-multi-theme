
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('create_url_menu_modal');
    const titleInput = modal.querySelector('input[name="title"]');
    const errorDiv = modal.querySelector('#title-error');
    const saveBtn = modal.querySelector('.create_menu_item');

    if (titleInput && errorDiv && saveBtn) {
        // Bắt sự kiện nhập liệu
        titleInput.addEventListener('input', function () {
            const maxLength = 255;
            const currentLength = titleInput.value.length;

            if (currentLength > maxLength) {
                errorDiv.innerText = `Không được vượt quá ${maxLength} ký tự. Đã nhập ${currentLength} ký tự.`;
                errorDiv.style.display = 'block';
                saveBtn.disabled = true;
            } else {
                errorDiv.innerText = '';
                errorDiv.style.display = 'none';
                saveBtn.disabled = false;
            }
        });

        // Kiểm tra lại lần cuối trước khi Lưu
        saveBtn.addEventListener('click', function (e) {
            const currentLength = titleInput.value.length;
            const maxLength = 255;

            if (currentLength > maxLength) {
                e.preventDefault();
                errorDiv.innerText = `Không được vượt quá ${maxLength} ký tự. Đã nhập ${currentLength} ký tự.`;
                errorDiv.style.display = 'block';
                return;
            }
        });
    }
});
</script>

<div id="modal-div">
    <div class="modal fade" id="create_url_menu_modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span class="action-mode">Thêm</span> menu cha</h4>
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
                            <div id="title-error" class="text-danger mt-1" style="display: none;"></div>
                            
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col mb-6 mt-2">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" placeholder="https://xxx.xxx hoặc /vi-du"
                                    name="url">
                                <label>Đường dẫn</label>
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
                    @include('pages.menu.partials.icon-picker')
                    @include('pages.menu.partials.english-fields', ['withUrl' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary waves-effect"
                        data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light create_menu_item"
                        data-form="create_url_menu_modal">Lưu</button>
                </div>
            </div>
        </div>
    </div>
</div>
