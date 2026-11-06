'use strict';
import Swiper from 'swiper/bundle';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

(function () {
  const swiperDichVu = document.querySelector('.swiper-dichvu');
  var swiper = new Swiper(swiperDichVu, {
    slidesPerView: 1,
    spaceBetween: 20,
    grabCursor: true,
    autoHeight: true,
    loop: true,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false
    },
    speed: 1000,
    navigation: {
      nextEl: '.next-dichVu',
      prevEl: '.prev-dichVu'
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      640: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 4 }
    }
  });

  // ✅ Dừng autoplay khi hover vào
  swiperDichVu.addEventListener('mouseenter', () => {
    swiper.autoplay.stop();
  });

  swiperDichVu.addEventListener('mouseleave', () => {
    swiper.autoplay.start();
  });

  gsap.registerPlugin(ScrollTrigger);
  gsap.from('.section-dichvu .section-dich-vu-item', {
    opacity: 0,
    x: gsap.utils.distribute({ base: -80, amount: 40 }),
    y: 40,
    scale: 0.85,
    delay: 0.25,
    rotate: gsap.utils.distribute({ base: -8, amount: 16 }),
    duration: 1.3,
    ease: 'back.out(1.7)',
    stagger: {
      each: 0.13,
      from: 'start'
    },
    scrollTrigger: {
      trigger: '.section-dichvu',
      start: 'top 85%',
      toggleActions: 'play none none none'
    }
  });
})();
3;
