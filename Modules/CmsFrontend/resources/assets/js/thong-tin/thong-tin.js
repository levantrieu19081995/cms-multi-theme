
// import Swiper bundle with all modules installed
import Swiper from 'swiper/bundle';

import 'swiper/css/bundle';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

'use strict';
(function () {
    var swiperThongTin = new Swiper(".thongtin", {
        slidesPerView: 1,
        spaceBetween: 20,
        grabCursor: true,
        autoHeight: true,
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        speed: 1000,
        navigation: {
            nextEl: ".button-next-thongtin",
            prevEl: ".button-prev-thongtin",
        },
        breakpoints: {
            0: { slidesPerView: 1 },
            640: { slidesPerView: 2 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 4 }
        }
    });

    gsap.registerPlugin(ScrollTrigger);
    // Khoá học: scale + fade + bounce + rotate
    gsap.from('.section-thongtin .section-thongtin-item', {
        opacity: 0,
        scale: 0.7,
        y: 60,
        delay: 0.6,
        rotate: gsap.utils.distribute({ base: -10, amount: 20 }),
        duration: 1.2,
        ease: 'bounce.out',
        stagger: {
            each: 0.14,
            from: "edges"
        },
        scrollTrigger: {
            trigger: '.section-thongtin',
            start: 'top 85%',
            toggleActions: 'play none none none',
        }
    });
})();