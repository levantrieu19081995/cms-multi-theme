'use strict';
import Swiper from 'swiper/bundle';
import 'swiper/css/grid';

(function () {
    const swiperDichVu = document.querySelector('.swiper-lienket');
    if (!swiperDichVu) return;

    var swiper = new Swiper(swiperDichVu, {
        slidesPerView: 4,
        spaceBetween: 24,
        grid: {
            rows: 2,
            fill: 'row', // hoặc 'column' nếu muốn
        },
        grabCursor: true,
        autoHeight: false,
        loop: false,
        autoplay: {
            delay: 2000,
            disableOnInteraction: false,
        },
        speed: 1000,
        breakpoints: {
            1200: {
                slidesPerView: 4,
                grid: { rows: 2 }
            },
            992: {
                slidesPerView: 3,
                grid: { rows: 2 }
            },
            768: {
                slidesPerView: 2,
                grid: { rows: 2 }
            },
            0: {
                slidesPerView: 1,
                grid: { rows: 2 }
            }
        }
    });

    // Dừng autoplay khi hover vào
    swiperDichVu.addEventListener('mouseenter', () => {
        swiper.autoplay.stop();
    });

    swiperDichVu.addEventListener('mouseleave', () => {
        swiper.autoplay.start();
    });

})();
