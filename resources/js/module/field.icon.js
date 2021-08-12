$(function () {
    var container = ".arshmodule .arshmodule-html-field-icon";

    var update = function () {
        var select  = $(this).closest(container).find('select');
        var hidden  = $(this).closest(container).find('input[type="hidden"]');
        var text    = $(this).closest(container).find('input[type="text"]');
        var link    = $(this).closest(container).find('a');
        var icon    = $(this).closest(container).find('i');

        hidden.val('fa'+select.val() +' fa-'+text.val());
        icon.attr('class', 'fa'+select.val() + ' fa-fw ' + 'fa-'+text.val());

        link.attr('href', 'https://fontawesome.com/icons?d=gallery&m=free&q=' + text.val());
    };

    $(document).on('change', container + ' select', update);
    $(document).on('change paste keyup', container + ' input[type="text"]', update);

    // when input[hidden] got disabled, we make also the others disabled
    let observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type == "attributes" && mutation.attributeName == "disabled") {
                var parent = $(mutation.target).parent();
                var disabled = $(mutation.target).prop('disabled');

                $(parent).find('select, input[type="text"]').prop('disabled', disabled);
            }
        });
    });

    document.querySelectorAll(container + ' input[type="hidden"]').forEach(function (node) {
        observer.observe(node, {
            attributes: true
        });
    });
});
