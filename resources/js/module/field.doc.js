$(function () {
    var container = ".arshmodule .arshmodule-html-field-doc";

    $(container).find('.box').each(function () {
        $(this).find('[data-toggle="tooltip"]').tooltip({
            container: $(this) // for properly hover reaction
        });
    });

    // toggle .fa icons
    $(container).find('.video-actions button').on('click', function () {
        var icon = $(this).find('i');

        if (icon.data('toggle')) {
            icon.toggleClass(icon.data('toggle'));
        }
    });

    // Rename doc
    $(container).find(".action-rename").on('click', function () {
        var boxDoc = $(this).closest(".doc");

        $(boxDoc).toggleClass('visible');
        $(boxDoc).find(".doc-name > *").toggleClass('d-none');

        var input = $(boxDoc).find(".doc-name input");
        input.prop('disabled', !input.is(":disabled"));
    });

    // Delete doc
    $(container).find('.action-delete').on('click', function () {
        var boxDoc = $(this).closest(".doc");

        var buttons = $(boxDoc).find('button').not($(this));
        buttons.prop('disabled', !buttons.is(":disabled"));

        $(boxDoc).toggleClass('visible');

        var input = $(this).closest(".doc-actions").find("input"); // delete input
        input.prop('disabled', !input.is(":disabled"));

        // hide input text, if visible
        if (!$(boxDoc).find(".doc-name input").is(":disabled")) {
            $(boxDoc).find(".action-rename").trigger('click');
        }
    });

    // display uploaded doc
    $(container).find('input[type="file"]').on('change', function () {
        var input = $(this)[0];
        var boxDoc = $(this).closest(container).find('.doc-uploaded');

        if (input.files && input.files[0]) {
            $(boxDoc).find('> .btn')
                .attr('title', input.files[0].name)
                .html(input.files[0].name.split('.').pop().toUpperCase());

            if ($(boxDoc).hasClass('d-none')) {
                var paddingTop = $(boxDoc).css('padding-top');
                var paddingBottom = $(boxDoc).css('padding-bottom');
                var height = $(boxDoc).css('height');

                $(boxDoc)
                    .css({
                        'padding-top':      0,
                        'padding-bottom':   0,
                        'height':           0
                    })
                    .removeClass('d-none')
                    .animate({
                        'padding-top':      paddingTop,
                        'padding-bottom':   paddingBottom,
                        'height':           height
                    }, 350);
            }
        }
        else {
            $(boxDoc).addClass('d-none');
            $(boxDoc).find('img').attr('src', null);
        }
    });

    var form = $(container).closest('form');
    if ($(form).length) {
        Form.on($(form)[0], 'response.valid', function (values) {
            var boxDoc  = $(container).find(".box:not(.doc-uploaded)");
            var file    = values[$(container).find("> [form-error]").attr('form-error')];

            if (file != null) {
                var data = {
                    'language':     $(boxDoc).find('.doc').data('language'),
                    'uploads':      $(boxDoc).find('.doc').data('uploads'),
                    'folder':       $(boxDoc).find('.doc').data('folder'),
                    'smallest-size':$(boxDoc).find('.doc').data('smallest-size'),
                    'body':         file['name'].split('.').slice(0, -1).join('.'),
                    'ext':          file['name'].split('.').pop()
                };
                data['preview'] = data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ data['smallest-size'] +'/'+ file['name'];

                boxDoc.find('img').attr('src', data['preview']);
                boxDoc.find('[data-fancybox]')
                    .attr('href', data['preview'])
                    .attr('data-caption', file['name']);

                boxDoc.find(".doc .btn-group .dropdown-menu .dropdown-item").each(function () {
                    $(this).attr(
                        'href',
                        data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ $(this).data('size') +'/'+ file['name']
                    );
                });

                boxDoc.find(".doc-name small").html(file['name']).attr('title', file['name']);
                boxDoc.find(".doc-name .input-group input[type='text']").val(data['body']);
                boxDoc.find(".doc-name .input-group .input-group-text").html(data['ext']);

                $(container).find(".input-group .custom-file-trash").trigger('click');
            }
        });
    }
});
