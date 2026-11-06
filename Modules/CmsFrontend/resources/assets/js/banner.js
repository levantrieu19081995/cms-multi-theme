import { projectUrl } from './support/project-url.js';
// 'use strict';
// import Swiper from 'swiper/bundle';
// import 'swiper/css/bundle';
// import 'swiper/css/pagination';
// import 'swiper/css/navigation';
// import gsap from 'gsap';
// import { ScrollTrigger } from 'gsap/ScrollTrigger';

// (function () {
//   function setBannerBackground(slideEl, imageUrl, fallbackUrl) {
//     const img = new Image();
//     img.onload = function () {
//       slideEl.style.backgroundImage = `url('${imageUrl}')`;
//     };
//     img.onerror = function () {
//       slideEl.style.backgroundImage = `url('${fallbackUrl}')`;
//     };
//     img.src = imageUrl;
//   }

//   // Bật/tắt overlay & ảnh tròn
//   const SHOW_OVERLAY = false; // tắt overlay SVG
//   const SHOW_CIRCLE = false; // tắt ảnh vòng tròn

//   function getDsBanner() {
//     fetch(projectUrl('/api/banners/getDsBanner'))
//       .then(r => r.json())
//       .then(({ code, data }) => {
//         if (code !== 200) return;

//         const wrapper = document.querySelector('#bannerSection .swiper-wrapper');
//         wrapper.innerHTML = '';

//         const dsBanner = data.data || [];

//         dsBanner.forEach(item => {
//           const imageUrl = item.image || `https://picsum.photos/1600/520?random=${(Math.random() * 1e6) | 0}`;
//           const fallbackUrl = `https://picsum.photos/1600/520?random=${(Math.random() * 1e6) | 0}`;

//           const slide = document.createElement('div');
//           slide.className = 'swiper-slide bg-image-banner';

//           // đặt style nền & căn giữa ngay tại slide
//           slide.style.cssText = `
//           background-size: cover;
//           background-position: center center;
//         `;

//           // nút (nếu có)
//           const btn =
//             item.btn_text && item.btn_text.trim()
//               ? `<a href="${(item.slug || '').endsWith('.htm') ? item.slug : (item.slug || '') + '.htm'}" class="btn-start">
//                ${item.btn_text}
//                <i class="fa fa-arrow-right mx-2" aria-hidden="true"></i>
//              </a>`
//               : '';

//           // KHÔNG overlay, KHÔNG circle/ảnh bên phải
//           slide.innerHTML = `
//           <div class="banner-content"
//                style="display:flex;align-items:center;height:100%;padding:0 48px;">
//             <div class="content-left banner-text" style="margin:0">
//               <h1 class="text-theme" style="margin:0 0 12px">${item.title || ''}</h1>
//               <h5 class="discount">${item.description || ''}</h5>
//               ${btn}
//             </div>
//           </div>
//         `;

//           wrapper.appendChild(slide);

//           // set background với fallback
//           const img = new Image();
//           img.onload = () => {
//             slide.style.backgroundImage = `url('${imageUrl}')`;
//           };
//           img.onerror = () => {
//             slide.style.backgroundImage = `url('${fallbackUrl}')`;
//           };
//           img.src = imageUrl;
//         });

//         // Inject CSS cố định banner 1920x400
//         const bannerStyle = document.createElement('style');
//         bannerStyle.innerHTML = `
//           #bannerSection {
//               width: 100% !important;
//           }

//           #bannerSection .swiper-slide {
//               height: 400px !important;        /* ép cứng đúng 1920x400 */
//               min-height: 400px !important;
//               max-height: 400px !important;

//               background-size: cover !important;
//               background-position: center !important;
//               background-repeat: no-repeat !important;

//               position: relative !important;
//               overflow: hidden !important;
//           }

//           #bannerSection .banner-content {
//               position: absolute !important;
//               inset: 0 !important;
//               display: flex !important;
//               align-items: center !important;
//               padding: 0 48px !important;
//           }

//           #bannerSection .content-left {
//               z-index: 10 !important;
//           }
//         `;
//         document.head.appendChild(bannerStyle);

//         // re-init swiper
//         if (window.bannerSwiper) window.bannerSwiper.destroy(true, true);
//         window.bannerSwiper = new Swiper('#bannerSection', {
//           loop: true,
//           pagination: { el: '.banner-swiper-pagination', clickable: true }
//         });

//         if (typeof animateBanner === 'function') animateBanner();
//       })
//       .catch(err => console.error('Error fetching banners:', err));
//   }

//   function animateBanner() {
//     const swiper = new Swiper('.banner', {
//       slidesPerView: 'auto',
//       centeredSlides: true,
//       spaceBetween: 30,
//       loop: true,
//       crossFade: true,
//       autoplay: {
//         delay: 4000,
//         disableOnInteraction: false
//       },
//       pauseOnMouseEnter: true,
//       speed: 1200,
//       grabCursor: false,
//       effect: 'slide',
//       pagination: {
//         el: '.banner-swiper-pagination',
//         clickable: true
//       },
//       breakpoints: {
//         320: { slidesPerView: 1, spaceBetween: 20 },
//         480: { slidesPerView: 1, spaceBetween: 30 },
//         640: { slidesPerView: 1, spaceBetween: 0 }
//       }
//     });

//     const listPageEl = document.getElementById('listing-section');
//     if (listPageEl) {
//       swiper.destroy();
//     }

//     gsap.registerPlugin(ScrollTrigger);
//     gsap.from('.banner', {
//       opacity: 0,
//       y: 60,
//       scale: 0.98,
//       duration: 1.2,
//       ease: 'power3.out',
//       scrollTrigger: {
//         trigger: '.banner',
//         start: 'top 80%',
//         toggleActions: 'play none none none',
//         markers: false
//       }
//     });

//     // Animate left content (text)
//     gsap.from('.banner-content .content-left', {
//       opacity: 0,
//       x: -80,
//       y: 30,
//       scale: 0.96,
//       duration: 1.3,
//       ease: 'power4.out',
//       delay: 0.2,
//       scrollTrigger: {
//         trigger: '.banner',
//         start: 'top 85%',
//         toggleActions: 'play none none none'
//       }
//     });

//     // Animate right image
//     gsap.from('.banner-content .circle-image-wrapper', {
//       opacity: 0,
//       x: 100,
//       rotate: 10,
//       scale: 0.92,
//       duration: 1.3,
//       ease: 'elastic.out(1, 0.6)',
//       delay: 0.4,
//       scrollTrigger: {
//         trigger: '.banner',
//         start: 'top 85%',
//         toggleActions: 'play none none none'
//       }
//     });
//   }

//   // Gọi hàm để lấy danh sách banner
//   getDsBanner();
// })();

'use strict';
import Swiper from 'swiper/bundle';
import 'swiper/css/bundle';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

(function () {
  //---------------------------------------------------------
  // HÀM AUTO-ADJUST BANNER THEO TỈ LỆ MÀN HÌNH
  //---------------------------------------------------------
  function adjustBanner(slideEl, imgWidth, imgHeight) {
    const screenRatio = window.innerWidth / window.innerHeight;
    const imageRatio = imgWidth / imgHeight;

    if (screenRatio < imageRatio) {
      // Màn hình hẹp → fit ảnh vào theo chiều ngang
      slideEl.style.backgroundSize = 'contain';
      slideEl.style.backgroundRepeat = 'no-repeat';
      slideEl.style.backgroundColor = '#fff';
      slideEl.style.backgroundPosition = 'center center';
    } else {
      // Màn hình rộng → crop nhẹ (cover)
      slideEl.style.backgroundSize = 'cover';
      slideEl.style.backgroundPosition = 'center center';
    }
  }
  // AUTO ASPECT RATIO
  function autoResizeBanner(slideEl, imgWidth, imgHeight) {
    const ratio = imgHeight / imgWidth;
    const newHeight = window.innerWidth * ratio;

    slideEl.style.height = newHeight + 'px';
    slideEl.style.minHeight = newHeight + 'px';
  }

  //---------------------------------------------------------
  // LẤY DANH SÁCH BANNER
  //---------------------------------------------------------
  function getDsBanner() {
    fetch(projectUrl('/api/banners/getDsBanner'))
      .then(r => r.json())
      .then(({ code, data }) => {
        if (code !== 200) return;

        const wrapper = document.querySelector('#bannerSection .swiper-wrapper');
        wrapper.innerHTML = '';

        const dsBanner = data.data || [];

        dsBanner.forEach(item => {
          const imageUrl = item.image || `https://picsum.photos/1600/520?random=${(Math.random() * 1e6) | 0}`;
          const fallbackUrl = `https://picsum.photos/1600/520?random=${(Math.random() * 1e6) | 0}`;

          const slide = document.createElement('div');
          slide.className = 'swiper-slide bg-image-banner';

          slide.innerHTML = `
          <div class="banner-content"
               style="display:flex;align-items:center;height:100%;padding:0 48px;">
            <div class="content-left banner-text" style="margin:0">
              <h1 class="text-theme" style="margin:0 0 12px">${item.title || ''}</h1>
              <h5 class="discount">${item.description || ''}</h5>
              ${
                item.btn_text
                  ? `<a href="${(item.slug || '').endsWith('.htm') ? item.slug : (item.slug || '') + '.htm'}" class="btn-start">
                        ${item.btn_text}
                        <i class="fa fa-arrow-right mx-2"></i>
                      </a>`
                  : ''
              }
            </div>
          </div>
        `;

          wrapper.appendChild(slide);

          //---------------------------------------------------------
          // LOAD IMAGE → AUTO ADAPT THEO TỈ LỆ
          //---------------------------------------------------------
          const img = new Image();
          img.onload = () => {
            slide.style.backgroundImage = `url('${imageUrl}')`;
            adjustBanner(slide, img.width, img.height);
            // AUTO CHIỀU CAO THEO ẢNH
            autoResizeBanner(slide, img.width, img.height);
          };
          img.onerror = () => {
            slide.style.backgroundImage = `url('${fallbackUrl}')`;
            adjustBanner(slide, 1920, 400);
          };
          img.src = imageUrl;
        });
        window.addEventListener('resize', () => {
          document.querySelectorAll('#bannerSection .swiper-slide').forEach(slide => {
            const bg = slide.style.backgroundImage;
            if (!bg) return;

            const img = new Image();
            img.onload = () => {
              autoResizeBanner(slide, img.width, img.height);
            };
            img.src = bg.replace(/url\(['"]?|['"]?\)/g, '');
          });
        });

        //---------------------------------------------------------
        // KHỞI TẠO SWIPER
        //---------------------------------------------------------
        if (window.bannerSwiper) window.bannerSwiper.destroy(true, true);
        window.bannerSwiper = new Swiper('#bannerSection', {
          loop: true,
          pagination: { el: '.banner-swiper-pagination', clickable: true }
        });

        animateBanner();
      })
      .catch(err => console.error('Error fetching banners:', err));
  }
  const overrideBannerCSS = document.createElement('style');
  overrideBannerCSS.innerHTML = `
  #bannerSection,
  #bannerSection.banner,
  .banner {
      height: auto !important;
      min-height: 0 !important;
  }
`;
  document.head.appendChild(overrideBannerCSS);

  //---------------------------------------------------------
  // ANIMATION
  //---------------------------------------------------------
  function animateBanner() {
    const swiper = new Swiper('.banner', {
      slidesPerView: 'auto',
      centeredSlides: true,
      spaceBetween: 30,
      loop: true,
      autoplay: { delay: 4000, disableOnInteraction: false },
      speed: 1200,
      pagination: { el: '.banner-swiper-pagination', clickable: true }
    });

    gsap.registerPlugin(ScrollTrigger);

    gsap.from('.banner', {
      opacity: 0,
      y: 60,
      duration: 1.2,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: '.banner',
        start: 'top 80%'
      }
    });
  }

  //---------------------------------------------------------
  // CHẠY
  //---------------------------------------------------------
  getDsBanner();
})();
