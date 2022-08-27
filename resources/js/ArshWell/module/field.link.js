$(function () {
    var container = ".arshmodule .arshmodule-html-field-link";

    var change = function () {
        var hidden  = $(this).closest(container).find('input[type="hidden"]');
        var current = $(this).closest(container).find('[data-type]').addClass('d-none')
            .filter('[data-type="'+$(this).val()+'"]').removeClass('d-none');

        hidden.val(current.val());
    };

    var update = function () {
        var hidden  = $(this).closest(container).find('input[type="hidden"]');
        var select  = $(this).closest(container).find('select.input-group-prepend');

        hidden.val($(this).closest(container).find('[data-type="'+$(select).val()+'"]').val());
    };

    $(document).on('change', container + ' select.input-group-prepend', change);
    $(document).on('change', container + ' select[data-type="page"]', update);
    $(document).on('change paste keyup', container + ' input[type="text"][data-type="link"]', update);

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
