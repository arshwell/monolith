$(document).ready(function () {
    // select-table
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

    // feature-update & action-insert
    (function () {
        var container = ".arshmodule .arshmodule-addon-language:not(.submit)";

        $(container).find('.dropdown-menu button').on('click', function () {
            $(this).closest(container).find('.dropdown-toggle').html($(this).html());
            $(this).closest(container).find('.dropdown-menu').removeClass('show');
            $(this).closest(container).find('.dropdown-item').removeClass('bg-danger text-light');
            $(this).addClass('bg-danger text-light');

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
