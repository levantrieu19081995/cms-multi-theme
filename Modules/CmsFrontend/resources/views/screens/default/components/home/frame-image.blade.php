{{-- Ảnh hiện nguyên trong khung (không cắt): ảnh thật nằm giữa khung (contain), phần dư được lấp bằng chính ảnh đó làm mờ.
     Hai thẻ cùng một địa chỉ nên trình duyệt chỉ tải một lần; thẻ nền lười tải như thẻ ảnh (ảnh đầu trang thì cả hai tải ngay để không nháy trắng). Tham số: src, alt, width, height, priority (ảnh đầu trang). --}}
@php($priority = $priority ?? false)
<img class="cms-frame-bg" src="{{ $src }}" alt="" aria-hidden="true" @unless ($priority) loading="lazy" @endunless decoding="async" draggable="false">
<img class="cms-frame-img" src="{{ $src }}" alt="{{ $alt ?? '' }}" @if ($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async" @isset($width) width="{{ $width }}" height="{{ $height ?? $width }}" @endisset>
