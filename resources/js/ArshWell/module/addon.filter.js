$(document).ready(function () {
    var container   = ".arshmodule .arshmodule-addon-filter";
    var form        = $(container).closest('form');

    $(container).find(".card-body select:not([data-key]):not([disabled])").on('change', function () {
        $(container).find('.card-body').find('select[data-key], select[disabled]').addClass('d-none');
        $(container).find('.card-body').find('select[data-key="'+$(this).find('option:selected').val()+'"]').removeClass('d-none');
    });

    $(container).find(".card-body button").on('click', function () {
        if ($(container).find('.card-body select[data-key]:not(.d-none)').length) {
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'filter['+$(container).find("select:not([data-key]):not([disabled]) option:selected").val()+'][]')
                .attr('value', $(container).find('select[data-key]:not(.d-none) option:selected').val())
                .appendTo($(container));

            if ($(form).length) {
                $(form).submit();
            }
        }
    });

    $(container).find(".card-footer *[data-field][data-value] i").on('click', function (event) {
        var field = $(this).closest('[data-field]').data('field');
        var value = $(this).closest('[data-value]').data('value');

        $(container).find('input[type="hidden"][name="filter['+field+'][]"][value="'+value+'"]').remove();

        if ($(form).length) {
            $(form).submit();
        }
    });
});
