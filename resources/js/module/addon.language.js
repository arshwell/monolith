$(document).ready(function () {
    // all lg selectors
    (function () {
        var addonContainer = ".arshmodule .arshmodule-addon-language";

        var languageColors = localStorage.getItem('cms.languages.colors');

        function generateLanguageColors () {
            languageColors = {};

            $(addonContainer).find(".dropdown-menu .dropdown-item").each(function () {
                do {
                    var color = tinycolor.random().toHexString();
                } while (tinycolor(color).getBrightness() > 100 || tinycolor(color).isLight());

                languageColors[$(this).data('lg')] = color;
            });

            localStorage.setItem('cms.languages.colors', JSON.stringify(languageColors));

            return languageColors;
        }

        function setLanguageColors (languageColors) {
            var styleElem = document.head.appendChild(document.createElement("style"));
            styleElem.setAttribute('type', "text/css");

            for (var language in languageColors) {
                styleElem.innerHTML += `
                    .arshmodule`+ ' ' +`.arshmodule-addon-language`+ ' ' +`.dropdown`+ ' ' +`.dropdown-toggle[data-lg='`+language+`'] {
                        background-color: `+languageColors[language]+`;
                    } \n
                    .arshmodule`+ ' ' +`.arshmodule-addon-language`+ ' ' +`.dropdown`+ ' ' +`.dropdown-toggle[data-lg='`+language+`']:hover {
                        background-color: `+languageColors[language]+`99;
                    } \n
                    .arshmodule`+ ' ' +`.arshmodule-addon-language`+ ' ' +`.dropdown`+ ' ' +`.dropdown-menu`+ ' ' +`.dropdown-item[data-lg='`+language+`'] {
                        background-color: `+languageColors[language]+`;
                    } \n
                    .arshmodule`+ ' ' +`.arshmodule-addon-language`+ ' ' +`.dropdown`+ ' ' +`.dropdown-menu`+ ' ' +`.dropdown-item[data-lg='`+language+`']:hover {
                        background-color: `+languageColors[language]+`99;
                    } \n
                    .arshmodule`+ ' ' +`form.arshmodule-form`+ ' ' +`div[data-key][data-lg='`+language+`']`+ ' ' +`div[language]::after {
                        background-color: `+languageColors[language]+`;
                    } \n
                    .arshmodule`+ ' ' +`.arshmodule-html-piece-dialog`+ ' ' +`.arshmodule-modal-languages   span[data-lg='`+language+`'] {
                        background-color: `+languageColors[language]+`;
                    }
                `;
            }
        }

        try {
            // try to convert in JSON
            // if invalid or undefined text, it will fail
            languageColors = JSON.parse(languageColors);

            if (languageColors == null || jQuery.isEmptyObject(languageColors)) {
                languageColors = generateLanguageColors();
            }

            setLanguageColors(languageColors);
        }
        catch (e) {
            languageColors = generateLanguageColors();

            setLanguageColors(languageColors);
        }
    })();

    // select-table only
    (function () {
        var container   = ".arshmodule .arshmodule-addon-language.submit";
        var form        = $(container).closest('form');

        $(container).find('.dropdown-menu button').on('click', function () {
            $(container).find('input[type="hidden"][name="lg"]').val($(this).data('lg'));

            if ($(form).length) {
                $(form).submit();
            }
        });
    })();

    // feature-update & action-insert only
    (function () {
        var container = ".arshmodule .arshmodule-addon-language:not(.submit)";

        $(container).find('.dropdown-menu button').on('click', function () {
            $(this).closest(container).find('.dropdown-toggle')
                .attr('data-lg', $(this).data('lg'))
                .html($(this).html());
            $(this).closest(container).find('.dropdown-menu').removeClass('show');

            // show fields for selected language
            $(this).closest('.card').find('.card-body *[data-lg]:not([data-lg=""])').fadeOut(0);
            $(this).closest('.card').find('.card-body *[data-lg="'+ $(this).data('lg') +'"]').fadeIn(350);
        });

        $(".arshmodule [data-lg][data-key] [language]:not([language=''])").on('click', function (event) {
            if (event.offsetX > this.offsetWidth) { // ::after was clicked
                var container = $(this).closest('[data-lg][data-key]');

                $(this).closest('.card').find('.card-body [data-lg="'+ container.data('lg') +'"][data-key="'+ container.data('key') +'"]').fadeOut(0);

                var lg = (
                    $(container).next('[data-lg][data-key]:not([data-lg=""]):not([data-lg="'+ container.data('lg') +'"])').length
                    ?
                    $(container).next('[data-lg][data-key]:not([data-lg=""]):not([data-lg="'+ container.data('lg') +'"])').data('lg')
                    :
                    $(container).parent().find('[data-lg][data-key]:not([data-lg=""]):not([data-lg="'+ container.data('lg') +'"])').first().data('lg')
                );

                $(this).css('pointer-events', 'none');

                $(this).closest('.card').find('.card-body [data-lg="'+ lg +'"][data-key="'+ container.data('key') +'"]').fadeIn(350, function () {
                    $(this).find('[language]').css('pointer-events', 'auto');
                });
            }
        });
    })();
});
