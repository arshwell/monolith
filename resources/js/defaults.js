(function () {
    // First we get the viewport height and we multiple it by 1% to get a value for a vh unit
    let vh = window.innerHeight * 0.01;
    // Then we set the value in the --vh custom property to the root of the document
    document.documentElement.style.setProperty('--vh', `${vh}px`);

    // // We listen to the resize event
    window.addEventListener('resize', () => {
        // We execute the same script as before
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    });

    jQuery.extend(jQuery.expr[':'], {
        "attrStartsWith": function (el, i, p, n) {
            var pCamel = p[3].replace(/-([a-z])/ig, function (m, $1) {
                return $1.toUpperCase();
            });

            return Object.values(el.attributes).map(function (attr) { return attr.name }).some(function (i) {
                return i.indexOf(pCamel) > -1;
            });
        }
    }); // ex: $('#app *:attrStartsWith(ajax-)')
})();
