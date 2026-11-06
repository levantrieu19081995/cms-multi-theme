'use strict';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const sections = document.querySelectorAll('.content[id]');
        const categoriesListItems = document.querySelectorAll('.categories-list-item');
        const navLinks = document.querySelectorAll('.categories-list-item a');

        function setInitialActive() {
            for (const section of sections) {
                const rect = section.getBoundingClientRect();
                if (rect.top <= window.innerHeight * 0.4 && rect.bottom >= window.innerHeight * 0.2) {
                    clearActive();
                    const id = section.getAttribute('id');
                    const activeLink = document.querySelector(`.categories-list-item a[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                        activeLink.parentElement.classList.add('active');
                    }
                    break;
                }
            }
        }

        function clearActive() {
            navLinks.forEach(link => link.classList.remove('active'));
            categoriesListItems.forEach(li => li.classList.remove('active'));
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    clearActive();
                    const id = entry.target.getAttribute('id');
                    const activeLink = document.querySelector(
                        `.categories-list-item a[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                        activeLink.parentElement.classList.add('active');
                    };
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -60% 0px',
            threshold: 0.2
        });

        sections.forEach(section => observer.observe(section));
        setInitialActive();

        // gsap
        gsap.registerPlugin(ScrollTrigger);
        
        document.querySelectorAll('.content').forEach(section => {
            gsap.from(section, {
                opacity: 0,
                y: 60,
                scale: 0.96,
                duration: 1.1,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: section,
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                }
            });
        });

        gsap.from('.categories', {
            opacity: 0,
            x: 80,
            duration: 1.2,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.categories',
                start: 'top 90%',
                toggleActions: 'play none none none',
            }
        });
    });
})();