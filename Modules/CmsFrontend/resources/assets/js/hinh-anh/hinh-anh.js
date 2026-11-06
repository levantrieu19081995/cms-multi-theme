'use strict';
import Swiper from 'swiper';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import 'swiper/css';
import 'swiper/css/navigation';

(function () {
  new Swiper('.event-swiper', {
    slidesPerView: 1.1,
    spaceBetween: 20,
    loop: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false
    },
    grabCursor: true,
    navigation: {
      nextEl: '.event-next',
      prevEl: '.event-prev'
    },
    breakpoints: {
      0: {
        slidesPerView: 1, // Mobile
        spaceBetween: 16
      },
      768: {
        slidesPerView: 2, // Tablet
        spaceBetween: 24
      },
      1024: {
        slidesPerView: 3, // Desktop
        spaceBetween: 30
      }
    }
  });

  new Swiper('.hinhAnhSwiper', {
    slidesPerView: 4,
    spaceBetween: 16,
    loop: true,
    grabCursor: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false
    },
    speed: 1000,
    navigation: {
      nextEl: '.suKien-next',
      prevEl: '.suKien-prev'
    },
    breakpoints: {
      0: {
        slidesPerView: 1, // Mobile
        spaceBetween: 16
      },
      768: {
        slidesPerView: 2, // Tablet
        spaceBetween: 24
      },
      1024: {
        slidesPerView: 3, // Desktop
        spaceBetween: 30
      },
      1400: { slidesPerView: 4 }
    }
  });

  gsap.registerPlugin(ScrollTrigger);
  // Sự kiện: fade + up + scale + power4
  gsap.from('.section-hinh-anh .section-hinh-anh-item', {
    opacity: 0,
    y: 70,
    scale: 0.92,
    duration: 1.15,
    delay: 0.3,
    ease: 'power4.out',
    stagger: {
      each: 0.12,
      from: 'start'
    },
    scrollTrigger: {
      trigger: '.section-hinh-anh',
      start: 'top 85%',
      toggleActions: 'play none none none'
    }
  });
})();
