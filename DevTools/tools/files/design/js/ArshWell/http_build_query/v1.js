/**
 * Do the same as PHP function with same name does.

 * @author: https://github.com/arsavinel
 * @license MIT (https://github.com/arshwell/monolith/blob/0.x/LICENSE.md)

 * This file is used at least by:
 *      - Arshwell\Monolith\Layout::compileJSFooter()
 *      - Arshwell\Monolith\DevTool\DevToolHTML::html()
 */
function http_build_query (obj) {
    return Object.entries(obj).map(pair => pair.map(encodeURIComponent).join("=")).join("&");
}
