'use strict';
import Swiper from 'swiper/bundle';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

(function () {
  const swiperTinTuc = document.querySelector('.swiper-tintuc');
  let swiperInstance = null;

  // Hàm xác định hướng trượt
  function getDirection() {
    return window.innerWidth < 768 ? 'horizontal' : 'vertical';
  }

  // Hàm khởi tạo Swiper
  function initSwiper() {
    if (swiperInstance) {
      swiperInstance.destroy(true, true);
    }

    swiperInstance = new Swiper(swiperTinTuc, {
      direction: getDirection(),
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
        nextEl: '.next-tintuc',
        prevEl: '.prev-tintuc'
      },
      breakpoints: {
        0: { slidesPerView: 1 },
        640: { slidesPerView: 1 },
        768: { slidesPerView: 1 },
        1024: { slidesPerView: 4 }
      }
    });

    // Dừng autoplay khi hover
    swiperTinTuc.addEventListener('mouseenter', () => {
      swiperInstance.autoplay.stop();
    });
    swiperTinTuc.addEventListener('mouseleave', () => {
      swiperInstance.autoplay.start();
    });
  }

  // Khởi tạo ban đầu
  initSwiper();

  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      initSwiper();
    }, 300);
  });

  // GSAP animation
  gsap.registerPlugin(ScrollTrigger);
  gsap.from('.section-tintuc .section-tin-tuc-item', {
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
      trigger: '.section-tintuc',
      start: 'top 85%',
      toggleActions: 'play none none none'
    }
  });
})();

function updateArrowIcons() {
  const isMobile = window.innerWidth < 768;

  const prevIcon = document.querySelector('.prev-tintuc i');
  const nextIcon = document.querySelector('.next-tintuc i');

  if (isMobile) {
    prevIcon.classList.remove('ri-arrow-up-line');
    prevIcon.classList.add('ri-arrow-left-line');

    nextIcon.classList.remove('ri-arrow-down-line');
    nextIcon.classList.add('ri-arrow-right-line');
  } else {
    prevIcon.classList.remove('ri-arrow-left-line');
    prevIcon.classList.add('ri-arrow-up-line');

    nextIcon.classList.remove('ri-arrow-right-line');
    nextIcon.classList.add('ri-arrow-down-line');
  }
}

// Gọi khi load và khi resize
updateArrowIcons();
window.addEventListener('resize', updateArrowIcons);
