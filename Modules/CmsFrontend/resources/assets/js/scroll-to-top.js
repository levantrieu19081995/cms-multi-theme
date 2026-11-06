'use strict';

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById("scrollToTopWrapper");
        const fabContacts = document.getElementById("fab-contacts");
        const indicator = document.getElementById("progressIndicator");
        const footer = document.querySelector('.landing-footer');
        const toggleFabBtn = document.getElementById('toggleFabBtn');
        const closeFabBtn = document.getElementById('closeFabBtn');
        const totalLength = 2 * Math.PI * 18;
        let fabHidden = true;

        // Hide contacts initially
        fabContacts.classList.add('fab-hidden');
        showToggleButton();

        function updateProgress() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const percent = Math.min(scrollTop / scrollHeight, 1);

            const offset = totalLength * (1 - percent);
            indicator.style.strokeDashoffset = offset;

            if (scrollTop > 200) {
                wrapper.style.visibility = "visible";
                wrapper.style.display = "block";
                wrapper.classList.remove('hidden-scroll');
                toggleFabBtn.classList.remove('in-scrollto-position');
            } else {
                wrapper.style.visibility = "hidden";
                wrapper.style.display = "none";
                wrapper.classList.add('hidden-scroll');
                toggleFabBtn.classList.add('in-scrollto-position');
            }

            if (percent >= 1) {
                wrapper.classList.add("at-bottom");
            } else {
                wrapper.classList.remove("at-bottom");
            }
        }

        function checkFooterOverlap() {
            if (!footer) return;
            const footerRect = footer.getBoundingClientRect();
            const btnRect = wrapper.getBoundingClientRect();
            const isOverlapping = btnRect.bottom > footerRect.top;

            if (isOverlapping) {
                showToggleButton();
                toggleFabBtn.classList.add('footer-overlap');
                wrapper.classList.add('footer-overlap');
                fabContacts.classList.add('fab-hidden');
                fabHidden = true;
            } else {
                toggleFabBtn.classList.remove('footer-overlap');
                wrapper.classList.remove('footer-overlap');
                if (!fabHidden) {
                    fabContacts.classList.remove('fab-hidden');
                    showCloseButton();
                } else {
                    fabContacts.classList.add('fab-hidden');
                    showToggleButton();
                }
            }
        }

        function showToggleButton() {
            // Fade out close, fade in toggle
            closeFabBtn.classList.add('hide');
            setTimeout(() => {
                closeFabBtn.classList.add('d-none');
                toggleFabBtn.classList.remove('d-none');
                setTimeout(() => {
                    toggleFabBtn.classList.remove('hide');
                }, 50);
            }, 100);
        }

        function showCloseButton() {
            // Fade out toggle, fade in close
            toggleFabBtn.classList.add('hide');
            setTimeout(() => {
                toggleFabBtn.classList.add('d-none');
                closeFabBtn.classList.remove('d-none');
                setTimeout(() => {
                    closeFabBtn.classList.remove('hide');
                }, 50);
            }, 100);
        }

        toggleFabBtn.addEventListener('click', function () {
            fabContacts.classList.remove('fab-hidden');
            fabHidden = false;
            showCloseButton();
        });

        closeFabBtn.addEventListener('click', function () {
            fabContacts.classList.add('fab-hidden');
            fabHidden = true;
            showToggleButton();
        });

        window.addEventListener("scroll", updateProgress);
        window.addEventListener('scroll', checkFooterOverlap);
        window.addEventListener('resize', checkFooterOverlap);
        updateProgress();
        checkFooterOverlap();

        window.scrollToTop = function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        };
    });
})();