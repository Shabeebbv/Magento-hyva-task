define(['jquery'], function ($) {
    'use strict';

    /**
     * −/+ buttons around a quantity input; keeps the native input (and its validation) as the source of truth.
     */
    return function (config, element) {
        var $root = $(element),
            $input = $root.find('input.qty'),
            min = parseFloat($input.attr('min')) > 0 ? parseFloat($input.attr('min')) : 1;

        $root.on('click', '[data-role=qty-step]', function (event) {
            var step = parseFloat($(this).data('step')),
                current = parseFloat($input.val()) || min;

            event.preventDefault();
            $input.val(Math.max(min, current + step)).trigger('change');
        });
    };
});
