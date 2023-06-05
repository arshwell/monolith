$(function () {
    var container = ".arshmodule .arshmodule-html-field-video";

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

    // Play video
    $(container).find(".action-play").on('click', function () {
        var video = $(this).closest('.box').find('video')[0];

        if (video.paused) {
            video.play();
        }
        else {
            video.pause();
        }
    });

    // Replay video
    $(container).find(".action-replay").on('click', function () {
        var video = $(this).closest('.box').find('video')[0];

        video.currentTime = 0;
    });

    // (Un)Mute video
    $(container).find(".action-volume").on('click', function () {
        var video = $(this).closest('.box').find('video')[0];

        video.muted = !video.muted;
    });

    // Rename video
    $(container).find(".action-rename").on('click', function () {
        var boxVideo = $(this).closest(".video");

        $(boxVideo).toggleClass('visible');
        $(boxVideo).find(".video-name > *").toggleClass('d-none');

        var input = $(boxVideo).find(".video-name input");
        input.prop('disabled', !input.is(":disabled"));
    });

    // Delete video
    $(container).find('.action-delete').on('click', function () {
        var boxVideo = $(this).closest(".video");

        var buttons = $(boxVideo).find('button').not($(this));
        buttons.prop('disabled', !buttons.is(":disabled"));

        $(boxVideo).toggleClass('visible');

        var input = $(this).closest(".video-actions").find("input"); // delete input
        input.prop('disabled', !input.is(":disabled"));

        // hide input text, if visible
        if (!$(boxVideo).find(".video-name input").is(":disabled")) {
            $(boxVideo).find(".action-rename").trigger('click');
        }
    });

    // display uploaded image
    $(container).find('input[type="file"]').on('change', function () {
        var input = $(this)[0];
        var boxVideo = $(this).closest(container).find('.video-uploaded');

        if (input.files && input.files[0]) {
            $(boxVideo).find('video source')
                .attr('src', URL.createObjectURL(this.files[0]))
                .parent()[0].load();

            if ($(boxVideo).hasClass('d-none')) {
                var paddingTop = $(boxVideo).css('padding-top');
                var paddingBottom = $(boxVideo).css('padding-bottom');
                var height = $(boxVideo).css('height');

                $(boxVideo)
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
            $(boxVideo).addClass('d-none');
            $(boxVideo).find('video source').attr('src', null);
        }
    });

    var form = $(container).closest('form');
    if ($(form).length) {
        Form.on($(form)[0], 'response.valid', function (values) {
            $(container).each(function () {
                var piece = $(this);

                var boxVideo    = $(piece).find(".box:not(.video-uploaded)");
                var file        = values[$(piece).find("> [form-error]").attr('form-error')];
                var regex       = /([^\/]+)(\.[^\/.]+)$/;

                if (file != null) { // file was uploaded
                    var data = {
                        'language':     $(boxVideo).find('.video').data('language'),
                        'uploads':      $(boxVideo).find('.video').data('uploads'),
                        'folder':       $(boxVideo).find('.video').data('folder'),
                        'body':         file['name'].split('.').slice(0, -1).join('.'),
                        'ext':          file['name'].split('.').pop()
                    };
                    data['preview'] = data['uploads'] + data['folder'] +'/'+ data['language'] +'/'+ file['name'];

                    boxVideo.find('video source').attr('src', data['preview']);
                    boxVideo.find('.video-actions a[href]')
                        .attr('href', data['preview']);

                    boxVideo.find(".video-name small").html(file['name']).attr('title', file['name']);
                    boxVideo.find(".video-name .input-group input[type='text']").val(data['body']);
                    boxVideo.find(".video-name .input-group .input-group-text").html(data['ext']);

                    $(piece).find(".input-group .custom-file-trash").trigger('click');
                }
                else {
                    let input = $(piece).find('.video-name .input-group input[type="text"]');

                    if (input.length && input.is(":disabled") == false) { // renaming input
                        $(piece).find('.video-actions a[href]').attr(
                            'href', function (i, value) {
                                return value.replace(regex, input.val() + "$2");
                            }
                        );

                        let filename = $(piece).find(".video-name small")
                        $(filename)
                            .attr('title', filename.attr('title').replace(regex, input.val() + "$2"))
                            .html(filename.html().replace(regex, input.val() + "$2"));
                    }
                }
            });
        });
    }
});
