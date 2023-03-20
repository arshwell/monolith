/**
 * Web class having some functions existent in PHP class Arshwell\Monolith\Web.

 * @author: https://github.com/arsavinel
 * @license MIT (https://github.com/arshwell/monolith/blob/0.x/LICENSE.md)

 * This file is used at least by:
 *      - Arshwell\Monolith\Layout::compileJSFooter()
 *      - Arshwell\Monolith\DevTool\DevToolHTML::html()
 */
class Web {
    static #site;
    static #statics;
    static #key;
    static #route;
    static #routes;
    // Custom Route Vars // are filled, by Layout, for every route

    static #queries = {};

    static query (str = null) {
        if (str == null) {
            str = window.location.search.substr(1)
        }
        if (!this.#queries.hasOwnProperty(str)) {
            this.#queries[str] = (function (str) {
                var strArr = String(str)
                    .replace(/^&/, "")
                    .replace(/&$/, "")
                    .split("&"),
                    sal = strArr.length,
                    i, j, ct, p, lastObj, obj, lastIter, undef, chr, tmp, key, value,
                    postLeftBracketPos, keys, keysLen,
                    fixStr = function(str) {
                        return decodeURIComponent(str.replace(/\+/g, "%20"));
                    };

                var array = {};

                for (i = 0; i < sal; i++) {
                    tmp = strArr[i].split("=");
                    key = fixStr(tmp[0]);
                    value = (tmp.length < 2) ? "" : fixStr(tmp[1]);

                    while (key.charAt(0) === " ") {
                        key = key.slice(1);
                    }
                    if (key.indexOf("\x00") > -1) {
                        key = key.slice(0, key.indexOf("\x00"));
                    }
                    if (key && key.charAt(0) !== "[") {
                        keys = [];
                        postLeftBracketPos = 0;
                        for (j = 0; j < key.length; j++) {
                            if (key.charAt(j) === "[" && !postLeftBracketPos) {
                                postLeftBracketPos = j + 1;
                            }
                            else if (key.charAt(j) === "]") {
                                if (postLeftBracketPos) {
                                    if (!keys.length) {
                                        keys.push(key.slice(0, postLeftBracketPos - 1));
                                    }
                                    keys.push(key.substr(postLeftBracketPos, j - postLeftBracketPos));
                                    postLeftBracketPos = 0;
                                    if (key.charAt(j + 1) !== "[") {
                                        break;
                                    }
                                }
                            }
                        }
                        if (!keys.length) {
                            keys = [key];
                        }
                        for (j = 0; j < keys[0].length; j++) {
                            chr = keys[0].charAt(j);
                            if (chr === " " || chr === "." || chr === "[") {
                                keys[0] = keys[0].substr(0, j) + "_" + keys[0].substr(j + 1);
                            }
                            if (chr === "[") {
                                break;
                            }
                        }

                        obj = array;
                        for (j = 0, keysLen = keys.length; j < keysLen; j++) {
                            key = keys[j].replace(/^[\'"]/, "").replace(/[\'"]$/, "");
                            lastIter = j !== keys.length - 1;
                            lastObj = obj;
                            if ((key !== "" && key !== " ") || j === 0) {
                                if (obj[key] === undef) {
                                    obj[key] = {};
                                }
                                obj = obj[key];
                            }
                            else { // To insert new dimension
                                ct = -1;
                                for (p in obj) {
                                    if (obj.hasOwnProperty(p)) {
                                        if (+p > ct && p.match(/^\d+$/g)) {
                                            ct = +p;
                                        }
                                    }
                                }
                                key = ct + 1;
                            }
                        }
                        lastObj[key] = value;
                    }
                }

                return array;
            })(str);
        }
        return this.#queries[str];
    }

    static site () {
        return ((window.location.protocol || location.protocol) + '//' + this.#site);
    }

    static statics (name, path = null) {
        return (location.protocol +"//"+ this.#statics[name] + (path || ""));
    }

    static key () {
        return this.#key;
    }

    // static page () {
    //     var regex = {};
    //
    //     for (i in regex) {
    //         var matches = window.location.pathname.match(new RegExp(regex[i]));
    //     }
    // }

    static url (key = null, values = null, page = 0, $_request = null) {
        var route = (key == null ? this.#route : this.#routes[key]);

        for (var param in values) {
            route.url = (route.url).replace(
                new RegExp("\\\\["+ param +":[^\\[\\]]+\\]", "g"),
                values[param]
            );
        }
        route.url = (Number.isInteger(page) && page > 1 ? (route.url).replace("[page]", (route.pagination[document.querySelector("html").getAttribute("lang")]).replace(/\(.*\)/, page)) : (route.url).replace(/(\s)?\[page\]/, ""));

        if ($_request) {
            route.url += ("?"+ (typeof $_request === "string" ? ($_request+"&") : (Object.keys($_request).length ? (http_build_query($_request)+"&") : "")) + "arsavinel-arshwell-mxdvcwdthflg="+ Math.max(
                window.screen.availWidth || screen.width || window.outerWidth || window.innerWidth,
                window.screen.availHeight || screen.height || window.outerHeight || window.innerHeight
            ));
        }

        return ((window.location.protocol || location.protocol) +"//"+ route.url);
    }

    static go (url, container, pushState = true) {
        let xhr = new XMLHttpRequest();
        xhr.responseType = "json";
        xhr.open("POST", url);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                document.querySelector(container).innerHTML = xhr.response.html;
                document.querySelector("html > head > link[rel][type][href]").href = xhr.response.media.css;
                document.querySelector("html > head > script[type][src]").src = xhr.response.media.js.header;
                document.querySelector("html > body > script[type][src]").src = xhr.response.media.js.footer;
                document.querySelector("head > title").innerHTML = xhr.response.title;

                if (typeof go.popstatelistener == "undefined") {
                    history.replaceState(
                        {href: window.location.href, container: container},
                        document.querySelector("html head title").innerHTML,
                        window.location.href
                    );

                    window.addEventListener("popstate", function(event) {
                        if (event.state != null) {
                            go(event.state["href"], event.state["container"], false);
                        }
                    }, true);

                    go.popstatelistener = true;
                }

                if (pushState) {
                    history.pushState({href: url, container: container}, xhr.response.title, url);
                }
            }
        };

        xhr.send("ajax_token=" + document.querySelector("html > body").getAttribute("ajax_token"));
    }
}
