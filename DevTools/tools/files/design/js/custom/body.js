/*******************************************************************************
    This file (body.js) is used at least by:
        - DevTools/helpers/functions/html.php - html()
        - Arsh\Core\layout::compileJSFooter()
*******************************************************************************/
"use strict";
document.body.setAttribute("browser", /Edge\/\d+/.test(navigator.userAgent) ? "edge" : /MSIE 9/.test(navigator.userAgent) || /MSIE 10/.test(navigator.userAgent) || /MSIE 11/.test(navigator.userAgent) || /MSIE\s\d/.test(navigator.userAgent) || /rv\:11/.test(navigator.userAgent) ? "ie" : /Firefox\W\d/.test(navigator.userAgent) ? "firefox" : /Chrom(e|ium)\W\d|CriOS\W\d/.test(navigator.userAgent) ? "chrome" : /\bSafari\W\d/.test(navigator.userAgent) ? "safari" : /\bOpera\W\d/.test(navigator.userAgent) || /\bOPR\W\d/i.test(navigator.userAgent) ? "opera" : typeof MSPointerEvent !== "undefined" ? "ie" : "undefined");

document.body.setAttribute("os", /Windows NT 10/.test(navigator.userAgent) ? "win10" : /Windows NT 6\.0/.test(navigator.userAgent) ? "winvista" : /Windows NT 6\.1/.test(navigator.userAgent) ? "win7" : /Windows NT 6\.\d/.test(navigator.userAgent) ? "win8" : /Windows NT 5\.1/.test(navigator.userAgent) ? "winxp" : /Windows NT [1-5]\./.test(navigator.userAgent) ? "winnt" : /Mac/.test(navigator.userAgent) ? "mac" : /Linux/.test(navigator.userAgent) ? "linux" : /X11/.test(navigator.userAgent) ? "nix" : "undefined");

document.body.setAttribute("touch", "ontouchstart" in document.documentElement);

document.body.setAttribute("mobile", /IEMobile|Windows Phone|Lumia/i.test(navigator.userAgent) ? "windows" : /iPhone|iP[oa]d/.test(navigator.userAgent) ? "ios" : /Android/.test(navigator.userAgent) ? "android" : /BlackBerry|PlayBook|BB10/.test(navigator.userAgent) ? "blackberry" : /Mobile Safari/.test(navigator.userAgent) || /webOS|Mobile|Tablet|Opera Mini|\bCrMo\/|Opera Mobi/i.test(navigator.userAgent) ? "undefined" : 0);

document.body.setAttribute("tablet", /Tablet|iPad/i.test(navigator.userAgent));

(function () {
    let observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type == "attributes" && mutation.attributeName == "type" && mutation.target.attributes.type.value != "password") {
                mutation.target.setAttribute("type", "password");
            }
        });
    });

    document.querySelectorAll("input[type=\'password\']").forEach(function (node) {
        observer.observe(node, {
            attributes: true
        });
    });
})();
