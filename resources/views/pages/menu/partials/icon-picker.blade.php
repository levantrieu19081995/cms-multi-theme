@php
    $menuIcons = [
        'ri-home-5-fill' => 'Trang chủ',
        'ri-information-line' => 'Thông tin',
        'ri-newspaper-line' => 'Tin tức',
        'ri-calendar-event-line' => 'Sự kiện',
        'ri-survey-line' => 'Khảo sát',
        'ri-questionnaire-line' => 'Câu hỏi',
        'ri-question-answer-line' => 'Hỏi đáp',
        'ri-customer-service-2-line' => 'Liên hệ',
        'ri-book-open-line' => 'Đào tạo / Tài liệu',
        'ri-graduation-cap-line' => 'Tuyển sinh',
        'ri-file-text-line' => 'Văn bản',
        'ri-folder-open-line' => 'Thư mục',
        'ri-gallery-line' => 'Thư viện ảnh',
        'ri-video-line' => 'Video',
        'ri-image-line' => 'Hình ảnh',
        'ri-service-line' => 'Dịch vụ',
        'ri-team-line' => 'Tập thể',
        'ri-user-line' => 'Cá nhân',
        'ri-building-4-line' => 'Đơn vị',
        'ri-government-line' => 'Tổ chức',
        'ri-flask-line' => 'Nghiên cứu',
        'ri-lightbulb-flash-line' => 'Sáng tạo',
        'ri-computer-line' => 'Công nghệ',
        'ri-briefcase-4-line' => 'Việc làm',
        'ri-links-line' => 'Liên kết',
        'ri-global-line' => 'Quốc tế',
        'ri-map-pin-line' => 'Địa điểm',
        'ri-phone-line' => 'Điện thoại',
        'ri-mail-line' => 'Email',
        'ri-download-cloud-2-line' => 'Tải xuống',
        'ri-search-line' => 'Tra cứu',
        'ri-notification-3-line' => 'Thông báo',
        'ri-bar-chart-box-line' => 'Thống kê',
        'ri-settings-3-line' => 'Cài đặt',
    ];
@endphp

<div class="cms-menu-icon-picker mt-4" data-menu-icon-picker>
    <input type="hidden" name="icon" value="">
    <label class="form-label fw-medium mb-2">Biểu tượng</label>
    <details class="border rounded-3 p-2">
        <summary class="cms-menu-icon-summary d-flex align-items-center gap-2 px-2 py-1">
            <i class="ri-magic-line fs-5" data-menu-icon-preview aria-hidden="true"></i>
            <span data-menu-icon-label>Tự động theo tên menu</span>
        </summary>
        <div class="pt-3">
            <input type="search" class="form-control form-control-sm mb-3" data-menu-icon-search placeholder="Tìm biểu tượng..." autocomplete="off">
            <div class="cms-menu-icon-grid" data-menu-icon-grid>
                <button type="button" class="cms-menu-icon-option is-active" data-icon="" data-label="Tự động theo tên menu" title="Tự động theo tên menu">
                    <i class="ri-magic-line" aria-hidden="true"></i><span>Tự động</span>
                </button>
                @foreach ($menuIcons as $menuIcon => $menuIconLabel)
                    <button type="button" class="cms-menu-icon-option" data-icon="{{ $menuIcon }}" data-label="{{ $menuIconLabel }}" title="{{ $menuIconLabel }}">
                        <i class="{{ $menuIcon }}" aria-hidden="true"></i><span>{{ $menuIconLabel }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </details>
    <small class="text-muted">Để tự động, giao diện sẽ chọn icon theo tên menu như hiện tại.</small>
</div>

@once
    <style>
        .cms-menu-icon-summary{cursor:pointer;list-style:none}.cms-menu-icon-summary::-webkit-details-marker{display:none}
        .cms-menu-icon-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;max-height:230px;overflow:auto;padding:2px}
        .cms-menu-icon-option{display:flex;min-height:64px;align-items:center;justify-content:center;flex-direction:column;gap:3px;border:1px solid var(--bs-border-color);border-radius:8px;background:var(--bs-body-bg);color:var(--bs-body-color);font-size:11px;text-align:center;padding:7px 4px}
        .cms-menu-icon-option i{font-size:22px}.cms-menu-icon-option:hover,.cms-menu-icon-option.is-active{border-color:var(--bs-primary);color:var(--bs-primary);background:rgba(var(--bs-primary-rgb),.06)}
        @media(max-width:575.98px){.cms-menu-icon-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    </style>
    <script>
        (() => {
            const syncPicker = picker => {
                const input = picker.querySelector('input[name="icon"]');
                const value = input?.value || '';
                const selected = [...picker.querySelectorAll('[data-icon]')].find(button => button.dataset.icon === value)
                    || picker.querySelector('[data-icon=""]');
                picker.querySelectorAll('[data-icon]').forEach(button => button.classList.toggle('is-active', button === selected));
                const preview = picker.querySelector('[data-menu-icon-preview]');
                const label = picker.querySelector('[data-menu-icon-label]');
                if (preview) preview.className = value || 'ri-magic-line fs-5';
                if (preview && value) preview.classList.add('fs-5');
                if (label) label.textContent = selected?.dataset.label || 'Tự động theo tên menu';
            };

            window.syncMenuIconPickers = root => (root || document).querySelectorAll('[data-menu-icon-picker]').forEach(syncPicker);

            document.addEventListener('click', event => {
                const option = event.target.closest('.cms-menu-icon-option');
                if (! option) return;
                const picker = option.closest('[data-menu-icon-picker]');
                picker.querySelector('input[name="icon"]').value = option.dataset.icon || '';
                syncPicker(picker);
            });

            document.addEventListener('input', event => {
                if (! event.target.matches('[data-menu-icon-search]')) return;
                const query = event.target.value.trim().toLocaleLowerCase('vi');
                event.target.closest('[data-menu-icon-picker]').querySelectorAll('.cms-menu-icon-option').forEach(option => {
                    const content = `${option.dataset.label || ''} ${option.dataset.icon || ''}`.toLocaleLowerCase('vi');
                    option.hidden = query !== '' && ! content.includes(query);
                });
            });
        })();
    </script>
@endonce
