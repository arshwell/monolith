$(function () {
    var feature = ".arshmodule .arshmodule-html-feature-popup";

    $(feature).find('iframe').on('load', function () {
        $(this).closest('.modal').modal('show');

        var icon = $(this).closest(feature).find('i');

        if (icon.data('toggle')) {
            // $(icon).parent().prop('disabled', false);
            icon.toggleClass(icon.data('toggle'));
        }
    });
});
