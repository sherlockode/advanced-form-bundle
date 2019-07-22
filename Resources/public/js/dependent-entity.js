jQuery(function ($) {
    'use strict';

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
            element.children('option').remove();
            if (element.data('is-optional')) {
                element.append('<option value=""></option>');
            }

            var ajaxUrl = element.data('ajax-url');
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
