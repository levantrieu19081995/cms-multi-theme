// Entry tự khởi chạy: slide mờ dần của banner đầu trang ([data-cms-hero]). Chỉ được nạp khi cụm banner khai báo preset crossfade-slider.
import { initCrossfadeSlider } from '../crossfade-slider/index.js';

const scope = document.querySelector('.cms-public .cms-home');
if (scope) initCrossfadeSlider(scope);
