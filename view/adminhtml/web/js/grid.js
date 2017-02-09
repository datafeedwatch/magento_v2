require([
    'jquery',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($) {
    DataFeedWatchGrid = {};
    DataFeedWatchGrid = Class.create();
    DataFeedWatchGrid.prototype = {
        initialize: function (config) {
            this.config = config;
            this.getInheritanceGrid();
        },

        getInheritanceGrid: function (page, limit) {
            $.ajax({
                method: 'post',
                url: this.config.url,
                context: $('#inheritance_grid'),
                showLoader: true,
                data: {form_key: window.FORM_KEY, page: page, limit: limit, 'isAjax': true},
                success: function (response) {
                    DataFeedWatchGrid.config.itemContainer.innerHTML = response;
                },
                error: function (response) {
                    alert(response);
                }
            });
        },

        saveInheritance: function (attributeCode, value) {
            $.ajax({
                method: 'post',
                url: this.config.saveInheritanceUrl,
                context: $('#inheritance_grid'),
                showLoader: true,
                data: {form_key: window.FORM_KEY, attribute_code: attributeCode, value: value, 'isAjax': true},
                error: function (response) {
                    alert(response);
                }
            });
        },

        saveImport: function (attributeCode, value) {
            $.ajax({
                method: 'post',
                url: this.config.saveImportUrl,
                context: $('#inheritance_grid'),
                showLoader: true,
                data: {form_key: window.FORM_KEY, attribute_code: attributeCode, value: value, 'isAjax': true},
                error: function (response) {
                    alert(response);
                }
            });
        }
    };
});