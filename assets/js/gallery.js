(function () {
    'use strict';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    document.querySelectorAll('[data-gallery-carousel]').forEach(function (carousel) {
        var track = carousel.querySelector('.home-gallery-track');
        var viewport = carousel.querySelector('.home-gallery-viewport');
        var slides = Array.prototype.slice.call(carousel.querySelectorAll('.home-gallery-slide'));
        var controls = carousel.querySelector('[data-carousel-controls]');
        var current = 0;
        var timer = null;
        var touchStart = null;

        if (!track || !viewport || !controls || slides.length < 2) {
            return;
        }

        function maxIndex() {
            var maxOffset = track.scrollWidth - viewport.clientWidth;
            var step = slides[1].offsetLeft - slides[0].offsetLeft;
            return step > 0 ? Math.max(0, Math.floor((maxOffset + 1) / step)) : 0;
        }

        function show(index) {
            var lastIndex = maxIndex();
            current = (index + lastIndex + 1) % (lastIndex + 1);
            var offset = slides[current].offsetLeft - slides[0].offsetLeft;
            track.style.transform = 'translateX(-' + offset + 'px)';
            Array.prototype.forEach.call(controls.children, function (control, controlIndex) {
                control.setAttribute('aria-current', controlIndex === current ? 'true' : 'false');
            });

            slides.forEach(function (slide, slideIndex) {
                var slideStart = slide.offsetLeft - slides[0].offsetLeft;
                var slideEnd = slideStart + slide.offsetWidth;
                var visible = slideEnd > offset && slideStart < offset + viewport.clientWidth;
                slide.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        }

        function createControls() {
            controls.textContent = '';
            for (var index = 0; index <= maxIndex(); index += 1) {
                var button = document.createElement('button');
                button.className = 'gallery-carousel-dot';
                button.type = 'button';
                button.setAttribute('aria-label', controls.getAttribute('data-label').replace('%d', index + 1));
                button.addEventListener('click', function (event) {
                    show(Array.prototype.indexOf.call(controls.children, event.currentTarget));
                    play();
                });
                controls.appendChild(button);
            }
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function play() {
            stop();
            if (!reducedMotion.matches && !document.hidden) {
                timer = window.setInterval(function () {
                    show(current + 1);
                }, 5000);
            }
        }

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', play);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', function (event) {
            if (!carousel.contains(event.relatedTarget)) {
                play();
            }
        });
        carousel.addEventListener('touchstart', function (event) {
            touchStart = event.changedTouches[0].clientX;
            stop();
        }, { passive: true });
        carousel.addEventListener('touchend', function (event) {
            if (touchStart !== null) {
                var distance = event.changedTouches[0].clientX - touchStart;
                if (Math.abs(distance) > 45) {
                    show(current + (distance < 0 ? 1 : -1));
                }
            }
            touchStart = null;
            play();
        }, { passive: true });
        document.addEventListener('visibilitychange', play);
        window.addEventListener('resize', function () {
            createControls();
            show(current);
        });

        createControls();
        show(0);
        play();
    });

    var galleryItems = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-item]'));
    var openers = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-open]'));

    if (!galleryItems.length) {
        return;
    }

    var lightbox = document.createElement('div');
    lightbox.className = 'gallery-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Photo viewer');
    lightbox.hidden = true;
    lightbox.innerHTML =
        '<button class="gallery-lightbox-close" type="button" aria-label="Close photo viewer">×</button>' +
        '<button class="gallery-lightbox-prev" type="button" aria-label="Previous photo">‹</button>' +
        '<figure class="gallery-lightbox-figure">' +
            '<img class="gallery-lightbox-image" src="" alt="">' +
            '<figcaption class="gallery-lightbox-caption"></figcaption>' +
            '<span class="gallery-lightbox-counter"></span>' +
        '</figure>' +
        '<button class="gallery-lightbox-next" type="button" aria-label="Next photo">›</button>';
    document.body.appendChild(lightbox);

    var image = lightbox.querySelector('.gallery-lightbox-image');
    var caption = lightbox.querySelector('.gallery-lightbox-caption');
    var counter = lightbox.querySelector('.gallery-lightbox-counter');
    var closeButton = lightbox.querySelector('.gallery-lightbox-close');
    var currentItems = [];
    var currentIndex = 0;
    var returnFocus = null;

    function itemsInGroup(group) {
        return galleryItems.filter(function (item) {
            return item.getAttribute('data-gallery-group') === group;
        });
    }

    function renderLightbox() {
        var item = currentItems[currentIndex];
        image.src = item.href;
        image.alt = item.getAttribute('data-alt') || '';
        caption.textContent = item.getAttribute('data-caption') || '';
        caption.hidden = !caption.textContent;
        counter.textContent = (currentIndex + 1) + ' / ' + currentItems.length;
        lightbox.classList.toggle('is-single', currentItems.length < 2);
    }

    function openLightbox(group, startItem, opener) {
        currentItems = itemsInGroup(group);
        if (!currentItems.length) {
            return;
        }

        currentIndex = startItem ? Math.max(0, currentItems.indexOf(startItem)) : 0;
        returnFocus = opener;
        renderLightbox();
        lightbox.hidden = false;
        document.documentElement.classList.add('gallery-lightbox-open');
        closeButton.focus();
    }

    function closeLightbox() {
        lightbox.hidden = true;
        image.src = '';
        document.documentElement.classList.remove('gallery-lightbox-open');
        if (returnFocus) {
            returnFocus.focus();
        }
    }

    function move(step) {
        currentIndex = (currentIndex + step + currentItems.length) % currentItems.length;
        renderLightbox();
    }

    galleryItems.forEach(function (item) {
        item.addEventListener('click', function (event) {
            event.preventDefault();
            openLightbox(item.getAttribute('data-gallery-group'), item, item);
        });
    });

    openers.forEach(function (opener) {
        opener.addEventListener('click', function (event) {
            event.preventDefault();
            openLightbox(opener.getAttribute('data-gallery-open'), null, opener);
        });
    });

    closeButton.addEventListener('click', closeLightbox);
    lightbox.querySelector('.gallery-lightbox-prev').addEventListener('click', function () { move(-1); });
    lightbox.querySelector('.gallery-lightbox-next').addEventListener('click', function () { move(1); });
    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (lightbox.hidden) {
            return;
        }
        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowLeft') move(-1);
        if (event.key === 'ArrowRight') move(1);
    });
}());
