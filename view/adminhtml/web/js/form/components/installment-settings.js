define([
    'jquery',
    'Magento_Ui/js/form/components/html',
    'ko'
], function ($, Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Iyzico_Iyzipay/installment-settings',
            availableInstallments: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            selectedInstallments: []
        },

        /**
         * Initialize component
         *
         * @returns {Object}
         */
        initObservable: function () {
            this._super();
            this.observe(['selectedInstallments']);

            console.log('Component initialized');

            // Form gönderiminde değerleri izle
            $(document).on('submit', 'form', function() {
                console.log('Form submit - selected installments:', this.selectedInstallments());
            }.bind(this));

            return this;
        },

        /**
         * Taksit seçimini aç/kapat
         *
         * @param {Number} installment
         */
        toggleInstallment: function(installment) {
            var index = this.selectedInstallments.indexOf(installment);
            if (index === -1) {
                this.selectedInstallments.push(installment);
                console.log('Added installment:', installment);
            } else {
                this.selectedInstallments.splice(index, 1);
                console.log('Removed installment:', installment);
            }
            console.log('Current installments:', this.selectedInstallments);
        },

        /**
         * Taksit seçili mi kontrol et
         *
         * @param {Number} installment
         * @return {Boolean}
         */
        isSelected: function (installment) {
            return this.selectedInstallments.indexOf(installment) !== -1;
        },

        /**
         * Taksit ayarlarını yükle
         */
        getInstallmentSettings: function () {
            var settings = $('#iyzico_installment_settings').data('settings');
            console.log('Initial Settings:', settings);

            if (settings && Array.isArray(settings)) {
                this.selectedInstallments(settings);
            } else if (typeof settings === 'string') {
                try {
                    var parsedSettings = JSON.parse(settings);
                    if (Array.isArray(parsedSettings)) {
                        this.selectedInstallments(parsedSettings);
                    }
                } catch (e) {
                    console.error('Failed to parse settings:', e);
                }
            }
        }
    });
});
