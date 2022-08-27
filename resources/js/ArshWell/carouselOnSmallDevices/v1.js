/**
 * carouselOnSmallDevices v1 - ArshWell js function for owl-carousel available only on smaller devices.

 * @author: https://github.com/arsavinel
 * @license MIT (https://github.com/arsavinel/ArshWell/blob/0.x/LICENSE.md)

 * @param integer maxWidthDevice
 * @param string maxWidthDevice
 * @param array maxWidthDevice
 */
function carouselOnSmallDevices (maxWidthDevice, section, owlConfig, callback = null) {
    var toggleCarousel = function () {
        if ($(window).width() <= maxWidthDevice) {
            var owl = $(section);

            $(owl).owlCarousel(owlConfig);
            owl.removeClass('owl-carousel-off');
        }
        else {
            var owl = $(section);

            owl.trigger('destroy.owl.carousel');
            owl.addClass('owl-carousel-off');
        }

        if (typeof callback == "function") {
            callback($(section), $(window).width());
        }
    }

    $(document).ready(toggleCarousel);
    $(window).resize(toggleCarousel);
}
