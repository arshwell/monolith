$(function () {
    var container = ".arshmodule .arshmodule-html-field-image";

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

    // Rename image
    $(container).find(".action-rename").on('click', function () {
        var boxImage = $(this).closest(".image");

        $(boxImage).toggleClass('visible');
        $(boxImage).find(".image-name > *").toggleClass('d-none');

        var input = $(boxImage).find(".image-name input");
        input.prop('disabled', !input.is(":disabled"));
    });

    // Delete image
    $(container).find('.action-delete').on('click', function () {
        var boxImage = $(this).closest(".image");

        var buttons = $(boxImage).find('button').not($(this));
        buttons.prop('disabled', !buttons.is(":disabled"));

        $(boxImage).toggleClass('visible');

        var input = $(this).closest(".image-actions").find("input"); // delete input
        input.prop('disabled', !input.is(":disabled"));

        // hide input text, if visible
        if (!$(boxImage).find(".image-name input").is(":disabled")) {
            $(boxImage).find(".action-rename").trigger('click');
        }
    });

    // display uploaded image
    $(container).find('input[type="file"]').on('change', function () {
        var input = $(this)[0];
        var boxImage = $(this).closest(container).find('.image-uploaded');

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $(boxImage).find('img').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]); // convert to base64 string

            if ($(boxImage).hasClass('d-none')) {
                var paddingTop = $(boxImage).css('padding-top');
                var paddingBottom = $(boxImage).css('padding-bottom');
                var height = $(boxImage).css('height');

                $(boxImage)
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
        else if ($(boxImage).hasClass('d-none') == false) {
            var paddingTop = $(boxImage).css('padding-top');
            var paddingBottom = $(boxImage).css('padding-bottom');
            var height = $(boxImage).css('height');

            $(boxImage).animate({
                'padding-top':      0,
                'padding-bottom':   0,
                'height':           0
            }, 350);

            setTimeout(function () {
                $(boxImage)
                    .addClass('d-none')
                    .css({
                        'padding-top':      paddingTop,
                        'padding-bottom':   paddingBottom,
                        'height':           height
                    });
            }, 400); // At 350, animation is not ready
        }
    });

    var form = $(container).closest('form');
    if ($(form).length) {
        Form.on($(form)[0], 'response.valid', function (values) {
            $(container).each(function () {
                var piece = $(this);

                var boxImage    = $(piece).find(".box:not(.image-uploaded)");
                var file        = values[$(piece).find("> [form-error]").attr('form-error')];
                var regex       = /([^\/]+)(\.[^\/.]+)$/;

                if (file != null) {
                    if ($(boxImage).find('.image').data('language')) {
                        var data = {
                            'language':     $(boxImage).find('.image').data('language'),
                            'uploads':      $(boxImage).find('.image').data('uploads'),
                            'folder':       $(boxImage).find('.image').data('folder'),
                            'smallest-size':$(boxImage).find('.image').data('smallest-size'),
                            'body':         file['name'].split('.').slice(0, -1).join('.'),
                            'ext':          file['name'].split('.').pop()
                        };
                        data['preview'] = data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ data['smallest-size'] +'/'+ file['name'];

                        boxImage.find('img').attr('src', data['preview']);
                        boxImage.find('[data-fancybox]')
                            .attr('href', data['preview'])
                            .attr('data-caption', file['name']);

                        boxImage.find(".image .btn-group .dropdown-menu .dropdown-item").each(function () {
                            $(this).attr(
                                'href',
                                data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ $(this).data('size') +'/'+ file['name']
                            );
                        });

                        boxImage.find(".image-name small").html(file['name']).attr('title', file['name']);
                        boxImage.find(".image-name .input-group input[type='text']").val(data['body']);
                        boxImage.find(".image-name .input-group .input-group-text").html(data['ext']);
                    }

                    $(piece).find(".input-group .custom-file-trash").trigger('click');
                }
                else {
                    let input = $(piece).find('.image-name .input-group input[type="text"]');

                    if (input.length && input.is(":disabled") == false) { // renaming input
                        let fancybox = $(piece).find('.image-actions [data-fancybox]');
                        $(fancybox).attr({
                            'href':         fancybox.attr('href').replace(regex, input.val() + "$2"),
                            'data-caption': fancybox.data('caption').replace(regex, input.val() + "$2")
                        });

                        $(piece).find('.image-actions .btn-group .dropdown-menu .dropdown-item').attr(
                            'href', function (i, value) {
                                return value.replace(regex, input.val() + "$2");
                            }
                        );

                        let filename = $(piece).find(".image-name small")
                        $(filename)
                            .attr('title', filename.attr('title').replace(regex, input.val() + "$2"))
                            .html(filename.html().replace(regex, input.val() + "$2"));
                    }
                }
            });
        });
    }
});
