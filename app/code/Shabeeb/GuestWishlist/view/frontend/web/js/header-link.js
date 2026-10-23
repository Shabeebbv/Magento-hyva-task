define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function (Component, ko, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            defaultUrl: ''
        },

        /** @inheritdoc */
        initialize: function () {
            var section;

            this._super();
            section = customerData.get('shabeeb-wishlist');
            this.count = ko.pureComputed(function () {
                return section().count || 0;
            });
            this.url = ko.pureComputed(function () {
                return section().url || this.defaultUrl;
            }, this);

            return this;
        }
    });
});
