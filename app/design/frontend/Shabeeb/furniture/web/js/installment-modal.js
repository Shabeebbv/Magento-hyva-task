define([], function () {
    'use strict';

    /**
     * Opens the example instalment plans in a native <dialog>: showModal() keeps focus inside and closes on Escape.
     * Adds closing from the overlay and the close button, locks page scroll while open and returns focus to the trigger.
     */
    return function (config, element) {
        var trigger = element.querySelector('[data-role=installments-open]'),
            dialog = element.querySelector('[data-role=installments-modal]'),
            root = document.documentElement;

        if (!trigger || !dialog || typeof dialog.showModal !== 'function' || element.dataset.installmentsReady) {
            return;
        }
        element.dataset.installmentsReady = '1';

        trigger.addEventListener('click', function () {
            if (dialog.open) {
                return;
            }
            // Keep the page from shifting sideways when the scrollbar disappears
            root.style.setProperty('--shabeeb-scrollbar', window.innerWidth - root.clientWidth + 'px');
            root.classList.add('shabeeb-modal-open');
            dialog.showModal();
            dialog.querySelector('[data-role=installments-close]').focus();
        });

        dialog.addEventListener('click', function (event) {
            // The dialog element itself is only hit outside its content, i.e. on the overlay
            if (event.target === dialog || event.target.closest('[data-role=installments-close]')) {
                dialog.close();
            }
        });

        // Fires for close(), the Escape key and form-less dismissals alike
        dialog.addEventListener('close', function () {
            root.classList.remove('shabeeb-modal-open');
            root.style.removeProperty('--shabeeb-scrollbar');
            trigger.focus();
        });
    };
});
