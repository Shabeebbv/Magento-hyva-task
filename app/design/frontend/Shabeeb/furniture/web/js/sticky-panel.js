define([], function () {
    'use strict';

    /**
     * The product page purchase panel is position: sticky in CSS; this only sets its top offset
     * (--shabeeb-sticky-top). When the panel fits in the window the offset is a fixed gap. When it is taller,
     * a fixed offset would hide its lower part, so the offset follows the scroll within
     * [window height - panel height - gap, gap]: scrolling down reveals the bottom of the panel, scrolling up
     * brings the title, price and Add to Cart straight back. Recomputed on scroll, resize and panel size changes
     * (e.g. an accordion section opening).
     */
    return function (config, element) {
        var gap = config.gap || 20,
            offset = gap,
            lastY = window.pageYOffset,
            frame;

        function update() {
            var y = window.pageYOffset,
                lowest = Math.min(gap, window.innerHeight - element.offsetHeight - gap);

            frame = null;
            offset = Math.max(lowest, Math.min(gap, offset - (y - lastY)));
            lastY = y;
            element.style.setProperty('--shabeeb-sticky-top', Math.round(offset) + 'px');
        }

        function schedule() {
            if (!frame) {
                frame = window.requestAnimationFrame(update);
            }
        }

        update();
        window.addEventListener('scroll', schedule, {passive: true});
        window.addEventListener('resize', schedule);
        if (window.ResizeObserver) {
            new window.ResizeObserver(schedule).observe(element);
        }
    };
});
