define(['jquery'], function ($) {
    'use strict';

    /**
     * Product page gallery: clicking a thumbnail shows that image (thumbnails are only visible on mobile;
     * wider screens show every image in a grid).
     */
    return function (config, element) {
        var $root = $(element),
            $items = $root.find('[data-role=gallery-item]'),
            $thumbs = $root.find('[data-role=gallery-thumb]');

        $root.on('click', '[data-role=gallery-thumb]', function () {
            var index = $thumbs.index(this);

            $items.removeClass('active').eq(index).addClass('active');
            $thumbs.removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');
        });
    };
});
