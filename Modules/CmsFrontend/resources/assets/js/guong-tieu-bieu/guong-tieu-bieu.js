'use strict';
import Swiper from 'swiper';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import 'swiper/css';
import 'swiper/css/navigation';

import { Pagination, Autoplay } from 'swiper/modules';

const swiperGTB = new Swiper('.control-Swiper', {
  modules: [Pagination, Autoplay],
  slidesPerView: 2,
  spaceBetween: 30,
  // 👇 Cấu hình autoplay
  autoplay: {
    delay: 4000, // thời gian chờ trước khi chuyển
    disableOnInteraction: false
  },

  // 👇 Cấu hình hiệu ứng mượt
  speed: 1000, // thời gian scroll mượt: 1000ms = 1s
  autoHeight: true, // ✅ Thêm dòng này
  pagination: {
    el: '.custom-swiper-pagination',
    clickable: true
  },

  breakpoints: {
    0: {
      slidesPerView: 1
    },
    768: {
      slidesPerView: 2
    }
  }
});

// Gương tiêu biểu: fade + up + scale + rotate + expo
gsap.registerPlugin(ScrollTrigger);
gsap.from('.section-guong-tieu-bieu .section-guong-tieu-bieu-item', {
  opacity: 0,
  y: 70,
  scale: 0.92,
  rotate: gsap.utils.distribute({ base: -6, amount: 12 }),
  duration: 1.15,
  ease: 'expo.out',
  stagger: {
    each: 0.05,
    from: "end"
  },
  scrollTrigger: {
    trigger: '.section-guong-tieu-bieu',
    start: 'top 85%',
    toggleActions: 'play none none none',
  }
});
