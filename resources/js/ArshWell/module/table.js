$(document).ready(function () {
    var container = ".arshmodule .arshmodule-html-piece-tbody .arshmodule-html-features";

    $(container).find('a[href]:not([target="_blank"]):not([data-confirmation="true"]), button:not([data-confirmation="true"])').on('click', function (event) {
        var icon = $(this).find('i');

        if (icon.data('toggle')) {
            icon.toggleClass(icon.data('toggle'));
        }
    });
});
