'use strict';

import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';


(function () {
    document.addEventListener('DOMContentLoaded', () => {
        
        gsap.registerPlugin(ScrollTrigger);
        gsap.from('.section-tuyenDung-viecLam .tuyenDung-viecLam-item', {
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
                from: "start"
            },
            scrollTrigger: {
                trigger: '.tuyenDung-viecLam-item',
                start: 'top 85%',
                toggleActions: 'play none none none',
            }
        });
        gsap.from('.section-divDangKy .divDangKy-items', {
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
                from: "start"
            },
            scrollTrigger: {
                trigger: '.divDangKy-items',
                start: 'top 85%',
                toggleActions: 'play none none none',
            }
        });

        // Đối tác: fade + slide + rotate + elastic
        gsap.from('.section-lienket .section-lienket-item', {
            opacity: 0,
            x: 80,
            y: gsap.utils.distribute({ base: 0, amount: 30 }),
            rotation: gsap.utils.distribute({ base: 8, amount: -16 }),
            scale: 0.92,
            duration: 1.2,
            delay: 0.5,
            ease: 'elastic.out(1, 0.5)',
            stagger: {
                each: 0.11,
                from: "center"
            },
            scrollTrigger: {
                trigger: '.section-lienket',
                start: 'top 85%',
                toggleActions: 'play none none none',
            }
        });


        // Giảng viên: rotate + fade + elastic + y
        gsap.from('.section-tintuc .section-tintuc-item', {
            opacity: 0,
            rotation: gsap.utils.distribute({ base: -12, amount: 24 }),
            y: 50,
            scale: 0.9,
            duration: 1.2,
            delay: 0.4,
            ease: 'elastic.out(1, 0.6)',
            stagger: {
                each: 0.13,
                from: "random"
            },
            scrollTrigger: {
                trigger: '.section-tintuc',
                start: 'top 85%',
                toggleActions: 'play none none none',
            }
        });


        gsap.from('.section-footer .section-footer-item', {
            opacity: 0,
            y: 70,
            scale: 0.92,
            duration: 1.15,
            delay: 0.12,
            ease: 'power4.out',
            stagger: {
                each: 0.10,
                from: "start"
            },
            scrollTrigger: {
                trigger: '.section-footer',
                start: 'top 85%',
                toggleActions: 'play none none none',
            }
        });
    });
})();