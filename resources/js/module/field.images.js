$(function () {
    var container = ".arshmodule .arshmodule-html-field-images";

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

    $(container)
        .on('click', '.image-actions .action-rename', function () { // Rename image
            var boxImage = $(this).closest(".image");

            $(boxImage).toggleClass('visible');
            $(boxImage).find(".image-name > *").toggleClass('d-none');

            var input = $(boxImage).find('.image-name input[type="text"]'); // renaming input
            input.prop('disabled', !input.is(":disabled"));
        })
        .on('click', '.image-actions .action-delete', function () { // Delete image
            var boxImage = $(this).closest(".image");

            // hide input text
            if (!$(boxImage).find('.image-name input[type="text"]').is(":disabled")) { // renaming input
                $(boxImage).find(".image-actions .action-rename").trigger('click');
            }

            var buttons = $(boxImage).find('button').not($(this));
            buttons.prop('disabled', !buttons.is(":disabled"));

            $(boxImage).toggleClass('visible');

            var input = $(boxImage).find('.image-actions input[type="hidden"]'); // deletion input
            input.prop('disabled', !input.is(":disabled"));
        });

    $(container).find('input[type="file"]').on('change', function () {
        var input = this;
        var piece = $(this).closest(container);

        if (input.files.length) {
            $(piece).find('.image-uploaded:not(.d-none)').remove();

            for (let i=0; i<input.files.length; i++) {
                let reader = new FileReader();
                let image_uploaded = $(piece).find('.image-uploaded.d-none').clone();

                reader.onload = function (e) {
                    image_uploaded.find('img').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[i]); // convert to base64 string

                image_uploaded.find('input[type="hidden"]').attr(
                    'name',
                    image_uploaded.find('input[type="hidden"]').attr('name') +'['+ input.files[i].name +']'
                );
                image_uploaded.find("[form-error]").attr(
                    'form-error',
                    image_uploaded.find("[form-error]").attr('form-error') +'.'+ input.files[i].name
                );
                image_uploaded.find(".image-name small")
                    .attr('title', input.files[i].name)
                    .html(input.files[i].name);
                image_uploaded.find('.image-name input[type="text"]').attr(
                    'name',
                    image_uploaded.find('.image-name input[type="text"]').attr('name') +'['+ input.files[i].name +']'
                );

                image_uploaded.appendTo($(piece).find('.row'));

                var image_box_uploaded = image_uploaded.find('.box');

                var paddingTop = $(image_box_uploaded).css('padding-top');
                var paddingBottom = $(image_box_uploaded).css('padding-bottom');
                var height = $(image_box_uploaded).css('height');

                $(image_box_uploaded).css({
                    'padding-top':      0,
                    'padding-bottom':   0,
                    'height':           0
                });
                $(image_uploaded).removeClass('d-none');
                $(image_box_uploaded).animate({
                    'padding-top':      paddingTop,
                    'padding-bottom':   paddingBottom,
                    'height':           height
                }, 350);
            }
        }
        else {
            var image_uploaded = $(piece).find('.image-uploaded:not(.d-none)');

            $(image_uploaded).find('.box').animate({
                'padding-top':      0,
                'padding-bottom':   0,
                'height':           0
            }, 350);

            setTimeout(function () {
                $(image_uploaded).remove();
            }, 350);
        }
    });

    var form = $(container).closest('form');
    if ($(form).length) {
        Form.on($(form)[0], 'response.valid', function (values) {
            $(container).each(function () {
                var piece = $(this);

                var boxImage    = $(piece).find("div:not(.image-uploaded) .box");
                var files       = values[$(piece).find('> [form-error]').attr('form-error')];
                var regex       = /([^\/]+)(\.[^\/.]+)$/;

                if ($(boxImage).find('.image').data('language')) {
                    var data = {
                        'language':     $(boxImage).find('.image').data('language'),
                        'uploads':      $(boxImage).find('.image').data('uploads'),
                        'folder':       $(boxImage).find('.image').data('folder'),
                        'smallest-size':$(boxImage).find('.image').data('smallest-size')
                    };

                    // Remove boxes for deleted images or overwitten images.
                    // Update names for renamed images.
                    $(piece).find("> .row > div:not(.image-uploaded):not(.d-none)").each(function () {
                        if (!$(this).find('.image-actions input[type="hidden"]').is(":disabled")) { // deletion input
                            $(this).remove();
                        }
                        else {
                            let input = $(this).find('.image-name .input-group input[type="text"]');

                            if (!input.is(":disabled")) { // renaming input
                                let fancybox = $(this).find('.image-actions [data-fancybox]');
                                $(fancybox).attr({
                                    'href':         fancybox.attr('href').replace(regex, input.val() + "$2"),
                                    'data-caption': fancybox.data('caption').replace(regex, input.val() + "$2")
                                });

                                $(this).find('.image-actions .btn-group .dropdown-menu .dropdown-item').attr(
                                    'href', function (i, value) {
                                        return value.replace(regex, input.val() + "$2");
                                    }
                                );

                                let filename = $(this).find(".image-name small")
                                $(filename)
                                .attr('title', filename.attr('title').replace(regex, input.val() + "$2"))
                                .html(filename.html().replace(regex, input.val() + "$2"));
                            }

                            if (files != null && files['name'].includes($(this).find(".image-name small").attr('title'))) {
                                $(this).remove();
                            }
                        }
                    });

                    if (files) {
                        var i = 0;

                        // Update boxes for added images.
                        $(piece).find(".image-uploaded:not(.d-none)").each(function () {
                            data['body']    = files['name'][i].split('.').slice(0, -1).join('.');
                            data['ext']     = files['name'][i].split('.').pop();
                            data['preview'] = data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ data['smallest-size'] +'/'+ files['name'][i];

                            $(this).find('img').attr('src', data['preview']);
                            $(this).find('[data-fancybox]')
                            .attr('href', data['preview'])
                            .attr('data-caption', files['name'][i])
                            .attr('data-fancybox', $(container).find('> [form-error]').attr('form-error'));

                            $(this).find(".image .btn-group .dropdown-menu .dropdown-item").each(function () {
                                $(this).attr(
                                    'href',
                                    data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ $(this).data('size') +'/'+ files['name'][i]
                                );
                            });

                            $(this).find(".image-name .input-group input").val(data['body']);
                            $(this).find(".image-name .input-group .input-group-text").html(data['ext']);

                            $(this).find('[data-toggle="tooltip"]').tooltip({
                                container: $(this) // for properly hover reaction
                            });

                            $(this).find('.d-none:not(.input-group), .action-crop').toggleClass('d-none');
                            $(this).removeClass('image-uploaded');

                            i++; // going to next image
                        });
                    }
                }
            });

            $(container).find(".input-group .custom-file-trash").trigger('click');
        });
    }
});
