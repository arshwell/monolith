$(document).ready(function () {
    var container   = ".arshmodule .arshmodule-html-piece-thead";
    var form        = $(container).closest('form');

    $(container).find('table thead span[type="button"]').on('click', function () {
        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'sort['+$(this).data('key')+']')
            .attr('value', $(this).data('sort'))
            .appendTo($(container));

        if ($(form).length) {
            $(form).submit();
        }
    });

    $(container).find('table thead i[type="button"]').on('click', function (event) {
        $(container).find('input[type="hidden"][name="sort['+$(this).data('key')+']"]').remove();

        if ($(form).length) {
            $(form).submit();
        }
    });

    $('.arshmodule .arshmodule-piece-pagination select[name="limit"]').on('change', function () {
        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'limit')
            .attr('value', $(this).find('option:selected').val())
            .appendTo($(container));

        if ($(form).length) {
            $(form).submit();
        }
    });

});
