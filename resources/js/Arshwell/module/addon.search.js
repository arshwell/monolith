$(document).ready(function () {
    var container   = ".arshmodule .arshmodule-addon-search";
    var form        = $(container).closest('form');

    $(container).find(".card-body button").on('click', function () {
        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'search['+$(container).find("select option:selected").val()+'][]')
            .attr('value', $(container).find('input[type="text"]').val())
            .appendTo($(container));

        if ($(form).length) {
            $(form).submit();
        }
    });

    $(container).find(".card-footer *[data-field][data-value] i").on('click', function (event) {
        var field = $(this).closest('[data-field]').data('field');
        var value = $(this).closest('[data-value]').data('value');

        $(container).find('input[type="hidden"][name="search['+field+'][]"][value="'+value+'"]').remove();

        if ($(form).length) {
            $(form).submit();
        }
    });
});
