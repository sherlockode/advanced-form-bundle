(function() {
'use strict';
let jQuery;

if (typeof module === "object" && module.exports) {
    jQuery = require("jquery");
} else {
    jQuery = window.jQuery;
}

jQuery(function ($) {
    // for JS-handled forms
    $('body').on('dependent_entity_created', 'select.dependent-entity', function(e) {
        handleElement($(e.target))
    });
    $('select.dependent-entity').each(function() {
        handleElement($(this));
    });

    function handleElement(element) {
        if (element.data('dependent_entity_init')) {
            return;
        }
        element.data('dependent_entity_init', true);
        if (element.prop('disabled')) {
            return;
        }
        var dependOnFieldId = element.data('depend-on-element');
        var dependOnElement = $('#' + dependOnFieldId);
        var values = element.data('mapping');

        dependOnElement.on('change', function () {
            refreshList($(this).val());
        });
        refreshList(dependOnElement.val(), element.val());
        function refreshList(key, selectedValue) {
            // remove all options except the empty value (if present)
            element.children('option:not([value=""])').remove();

            var ajaxUrl = element.data('dependent-ajax-url');
            if (ajaxUrl) {
                if (key !== '') {
                    $.get(ajaxUrl, {id: key},function (values) {
                        fillSelectElement(element, values, selectedValue);
                        element.change();
                    });
                } else {
                    element.change();
                }
            } else {
                if (key !== '' && values[key]) {
                    fillSelectElement(element, values[key], selectedValue);
                }
                element.change();
            }
        }

        function fillSelectElement(element, values, selectedValue) {
            $.each(values, function (k, v) {
                var valueId = v.id + ''; // cast to string
                var selected = Array.isArray(selectedValue) ? selectedValue.indexOf(valueId) !== -1 : selectedValue === valueId;
                element.append('<option value="' + v.id + '"' + (selected ? 'selected' : '') + '>' + v.label + '</option>');
            });
        }
    }
});

})();
