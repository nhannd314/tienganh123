// Full-screen slideshow (resources/views/slides/show.blade.php): arrows, space, swipe and the buttons move between
// slides, F or the button toggles the browser's full screen.
const presenter = document.querySelector('[data-slide-presenter]');

if (presenter) {
    const slides = [...presenter.querySelectorAll('[data-slide]')];
    const previous = presenter.querySelector('[data-slide-prev]');
    const next = presenter.querySelector('[data-slide-next]');
    const counter = presenter.querySelector('[data-slide-counter]');
    let current = 0;

    const show = (index) => {
        current = Math.min(Math.max(index, 0), slides.length - 1);
        slides.forEach((slide, position) => {
            slide.hidden = position !== current;
        });
        counter.textContent = `${current + 1} / ${slides.length}`;
        previous.disabled = current === 0;
        next.disabled = current === slides.length - 1;
        history.replaceState(null, '', `#${current + 1}`);
    };

    const toggleFullscreen = () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen?.();
        }
    };

    previous.addEventListener('click', () => show(current - 1));
    next.addEventListener('click', () => show(current + 1));
    presenter.querySelector('[data-slide-fullscreen]').addEventListener('click', toggleFullscreen);

    document.addEventListener('keydown', (event) => {
        if (['ArrowRight', 'ArrowDown', 'PageDown', ' '].includes(event.key)) {
            event.preventDefault();
            show(current + 1);
        } else if (['ArrowLeft', 'ArrowUp', 'PageUp'].includes(event.key)) {
            event.preventDefault();
            show(current - 1);
        } else if (event.key === 'Home') {
            show(0);
        } else if (event.key === 'End') {
            show(slides.length - 1);
        } else if (event.key.toLowerCase() === 'f') {
            toggleFullscreen();
        }
    });

    let touchStartX = null;
    presenter.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0].clientX;
    }, { passive: true });
    presenter.addEventListener('touchend', (event) => {
        if (touchStartX === null) {
            return;
        }
        const distance = event.changedTouches[0].clientX - touchStartX;
        touchStartX = null;
        if (Math.abs(distance) > 50) {
            show(current + (distance < 0 ? 1 : -1));
        }
    });

    show((parseInt(location.hash.slice(1), 10) || 1) - 1);
    presenter.focus();
}
