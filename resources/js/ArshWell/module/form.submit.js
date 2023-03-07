$(document).ready(function () {
    function communicate (form, info) {
        var parent = $(form).closest('.arshmodule').find('.arshmodule-html-piece-dialog').eq(0);

        if (parent.length == 0) {
            alert("No modal dialog found. \nInclude it from module pieces \n(ArshWell\\Monolith\\Module\\HTML\\Piece::dialog())!");
            return;
        }

        var modal = {
            'header':           $(parent).find('.modal-header'),
            'header.title':     $(parent).find('.modal-header .modal-title'),
            'body':             $(parent).find('.modal-body'),
            'body.info':        $(parent).find('.modal-body .arshmodule-modal-info'),
            'body.errors':      $(parent).find('.modal-body .arshmodule-modal-errors'),
            'body.bug':         $(parent).find('.modal-body .arshmodule-modal-bug'),
            'footer':           $(parent).find('.modal-footer'),
            'footer.languages': $(parent).find('.modal-footer .arshmodule-modal-languages')
        };

        // hide all
        $(modal['body']).hide();
        $(modal['body.info']).hide();
        $(modal['body.errors']).hide();
        $(modal['body.bug']).hide();
        $(modal['footer']).hide();

        if (info.bug == true) {
            $(modal['body']).show();
            $(modal['body.bug']).show();
        }
        else if (info.valid == false) {
            $(modal['body']).show();

            if (info.errors) {
                $(modal['body.errors']).show();

                var list = $(modal['body.errors']).find("ul");
                var item = $(list).find('li').clone();

                list.find('li:not(.d-none)').remove();
                item.removeClass('d-none');

                var errors_added = [];

                for (var key in info.errors) {
                    if (errors_added.indexOf(info.errors[key]) == -1 && info.errors[key]) {
                        item.html(info.errors[key]);
                        list.append(item[0].outerHTML);

                        errors_added.push(info.errors[key]);
                    }
                }
            }

            var languages = [];

            for (var key in info.errors) {
                if (info.errors[key]) {
                    var match = key.match(/(.+)\.([a-z]{2})$/);

                    if (match && info.errors.hasOwnProperty(match[1]) && !languages.includes(match[2])) {
                        languages.push(match[2]);
                    }
                }
            }

            if (languages.length) {
                $(modal['footer']).show();
                $(modal['footer.languages']).html(
                    languages.map(function (lg) {
                        return '<span data-lg="'+ lg +'">'+ lg.toUpperCase() +'</span>';
                    }).join(' & ')
                );
            }
        }

        if (info.message) {
            if (('type' in info.message) && ('text' in info.message)) {
                $(modal['header.title'])
                    .removeClass(function (index, className) {
                        return (className.match(/(^|\s)text-\S+/g) || []).join(' ');
                    })
                    .addClass('text-' + info.message.type)
                    .html(info.message.text);
            }
            if ('info' in info.message) { // valid true
                $(modal['body']).show();
                $(modal['body.info']).show().html(info.message.info);
            }
        }

        $(parent).modal('show');
    }

    $(document).on('submit', '.arshmodule form:not([target])[method]:not([method="GET"])', function (event) {
        event.preventDefault();

        var inputs = this.querySelectorAll('input[type="file"]');

        for (var i in inputs) {
            if (inputs[i].files) {
                for (var f in inputs[i].files) {
                    if (inputs[i].files[f].size > 209710670) { // 200 MB
                        communicate($(this), {
                            bug: false,
                            valid: false,
                            message: {
                                type: 'warning',
                                text: "Do not upload very large files (less than 200 MB)"
                            },
                            errors: []
                        });
                        return;
                    }
                }
            }
        }

        var triggers = $(this).find('[type="submit"]');

        triggers.prop('disabled', true).addClass('progress-bar-striped progress-bar-animated');

        var form = new Form(this);

        form.syncErrors(function (element) {
            $(element).css('opacity', '0');
        });

        $.ajax({
            url:            $(this).attr('action') || Web.url(),
            type:			$(this).attr('method') || 'POST',
			processData:	false,
			contentType:	false,
			dataType:		'JSON',
			cache:			false,
            data:		form.serialize({
                ajax_token: Form.token('ajax'),
                form_token: Form.token('form')
            }, true),
            beforeSend: function() {
                form.disable();
                if (typeof tinyMCE == 'object') {
                    for (var i = 0; i < tinymce.editors.length; i++) {
                        if (form.dom.isSameNode(tinymce.editors[i].formElement)) { // from our form
                            tinymce.editors[i].mode.set('readonly');
                        }
                    }
                }
            },
            success: function (json) {
                form.response(json, false); // disabling firing Form listener here
                form.syncValues();

                var info = {
                    bug: false,
                    valid: form.valid(),
                    message: {
                        type: null,
                        text: null
                    },
                    errors: json.errors
                };

                if (form.invalid()) {
                    for (var key in json.errors) {
                        if (json.errors[key] && $(form.dom).find('[form-error="'+ key +'"]').length == 0) {
                            info.bug = true;
                            break;
                        }
                    }

                    form.syncErrors(function (element, error) {
                        $(element).html(error).animate({opacity: 1});
                    });
                }
                else { // is valid
                    if (triggers.closest('form[method]').find('input[type="checkbox"]#asrhmodule-form-preservation').length
                    && !triggers.closest('form[method]').find('input[type="checkbox"]#asrhmodule-form-preservation:checked').length) {
                        form.empty(function (field) {
                            return (field.tagName != 'OPTION');
                        });
                        $(form.dom).find('.bootstrap-tagsinput [data-role="remove"]').trigger('click');
                    }

                    if (form.value('redirect')) {
                        window.location.href = form.value('redirect');
                    }
                }

                // if no message, means we dont need communicate
                if (form.value('message')) {
                    info.message = form.value('message');

                    communicate(form.dom, info);
                }

                // that should be done after communicate()
                if (form.value('remove')) {
                    $(triggers).confirmation('dispose');
                    $(triggers).closest(form.value('remove')).fadeOut(500, function () {
                        $(this).find('[data-tooltip="true"]').tooltip('dispose');
                        $(this).remove();
                    });
                }
                else if (form.value('html')) {
                    $(form.value('html')).replaceAll($(triggers).tooltip('dispose').closest('.arshmodule-html'))
                        .find('[data-tooltip="true"]').tooltip({
                            container: 'body'
                        });
                }

                if (!form.value('redirect')) {
                    setTimeout(function () {
                        form.enable();
                        form.trigger(); // triggering Form listener here because we need out inputs enabled again

                        if (typeof tinyMCE == 'object') {
                            for (var i = 0; i < tinymce.editors.length; i++) {
                                if (form.dom.isSameNode(tinymce.editors[i].formElement)) { // from our form
                                    tinymce.editors[i].mode.set('design');
                                }
                            }
                        }
                        triggers.prop('disabled', false).removeClass('progress-bar-striped progress-bar-animated');

                        triggers.find('i').toggleClass(triggers.find('i').data('toggle')); // happens if data-toggle exists
                    }, 100);
                }
            },
            error: function (response, type, error) {
                if (response.status == 0
                || (typeof navigator !== 'undefined' && navigator.onLine == 0)) {
                    communicate($(form.dom), {
                        bug: false,
                        valid: false,
                        message: {
                            type: 'warning',
                            text: "Possible connection problem",
                            info: "Check your internet connection."
                        }
                    });
                    setTimeout(function () {
                        form.enable();

                        if (typeof tinyMCE == 'object') {
                            for (var i = 0; i < tinymce.editors.length; i++) {
                                if (form.dom.isSameNode(tinymce.editors[i].formElement)) { // from our form
                                    tinymce.editors[i].mode.set('design');
                                }
                            }
                        }
                        triggers.prop('disabled', false).removeClass('progress-bar-striped progress-bar-animated');
                    }, 100);
                }
                else if (response.status == 401 || response.status == 403) {
                    alert("Session timed out");
                }
                else {
                    communicate($(form.dom), {
                        bug: true,
                        valid: false,
                        message: {
                            type: 'warning',
                            text: "Something wrong occurred"
                        }
                    });
                }
            }
        });
    });
});
