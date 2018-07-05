jQuery(function ($) {
    'use strict';

    $('select.dependent-entity').each(function() {
        handleElement($(this));
    });
    // for JS-handled forms
    $('body').on('dependent_entity_created', 'select.dependent-entity', function(e) {
        if (!$(e.target).data('dependent_entity_created')) {
            $(e.target).data('dependent_entity_created', true);
            handleElement($(e.target))
        }
    });
    function handleElement(element) {
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
        function refreshList(key, selected) {
            element.children('option').remove();
            if (element.data('is-optional')) {
                element.append('<option value=""></option>');
            }
            if (key !== '') {
                $.each(values[key], function (k, v) {
                    element.append('<option value="' + k + '"' + (selected === k ? 'selected' : '') + '>' + v + '</option>');
                });
            }
            element.change();
        }
    }
});
