// import Swiper from "swiper";
// import { Autoplay, Navigation, Pagination } from "swiper/modules";
// import "swiper/css";


// document.addEventListener("DOMContentLoaded", () => {
//     new Swiper("#bannerSection", {
//         modules: [Autoplay, Navigation, Pagination],

//         loop: true,
//         speed: 600,

//         autoplay: {
//             delay: 4000,
//             disableOnInteraction: false,
//         },

//         pagination: {
//             el: ".swiper-pagination",
//             clickable: true,
//         },

//         navigation: {
//             nextEl: ".swiper-button-next",
//             prevEl: ".swiper-button-prev",
//         },

//         slidesPerView: 1,
//     });
// });

import Swiper from "swiper";
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import "swiper/css";

document.addEventListener("DOMContentLoaded", () => {

    new Swiper("#bannerSection", {
        modules: [Autoplay, Navigation, Pagination],
        loop: true,
        speed: 600,

        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },

        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },

        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },

        slidesPerView: 1
    });

});
