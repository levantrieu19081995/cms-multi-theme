
<div class="position-fixed bottom-0 end-0 p-3 d-flex flex-column align-items-end gap-3" id="contact-icons">
    <!-- Facebook -->
    <div id="fab-contacts">
        @foreach ($floatingServices as $index => $card)
            <div class="contact-item position-relative">
                <span class="contact-label">{{ $card['title'] }}</span>
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::url($card['link']) }}" target="_blank"
                    class="btn rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px;">
                    <i class="ri-{{ $card['icon'] }}"></i>
                </a>
            </div>
        @endforeach
    </div>
    <!-- Nút chính mở nhóm -->
    <button id="toggleFabBtn"
        class="btn toggleFabBtn d-none rounded-circle shadow d-flex align-items-center justify-content-center">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Nút đóng -->
    <button id="closeFabBtn"
        class="btn closeFabBtn d-none rounded-circle shadow d-flex align-items-center justify-content-center">
        <i class="fas fa-times"></i>
    </button>
    <div id="scrollToTopWrapper" title="{{ __('frontend.ui.len_dau_trang') }}" onclick="scrollToTop()">
        <svg id="progressCircle" width="45" height="45">
            <circle cx="22.5" cy="22.5" r="18" stroke="#eee" stroke-width="4" fill="none" />
            <circle id="progressIndicator" cx="22.5" cy="22.5" r="18" stroke="#0d6efd" stroke-width="4"
                fill="none" stroke-linecap="round" stroke-dasharray="113.1" stroke-dashoffset="113.1" />
        </svg>
        <i class="fas fa-chevron-up"></i>
    </div>
</div>
