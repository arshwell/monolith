/**
 * Form class for manipulating AJAX responses.

 * @author: https://github.com/arsavinel
 * @license MIT (https://github.com/arsavinel/ArshWell/blob/0.x/LICENSE.md)

 * This file is used at least by:
 *      - Arsavinel\Arshwell\Layout::compileJSFooter()
 *      - Arsavinel\Arshwell\DevTool\DevToolHTML::html()
 */
class Form { // helps you with any need about JS forms
    #form;
    #response;
    static #listeners = {
        'response.valid':   [],
        'response.invalid': [],
        'sync.values':      [],
        'sync.errors':      [],
        'empty':            [],
        'disable':          []
    };

    static on (form, type, closure) {
        if (typeof(form) == "string") {
            form = document.querySelector(form);
        }
        if (form.nodeName != "FORM") {
            throw new Error("Form class accepts only <form> nodes."); // error
        }

        var xpath = VanillaJS.getPathTo(form);
        if (typeof(this.#listeners[type][xpath]) == "undefined") {
            this.#listeners[type][xpath] = [];
        }
        this.#listeners[type][xpath].push(closure);
    }

    static off (form, type) {
        delete this.#listeners[type][VanillaJS.getPathTo(document.querySelector(form))];
    }

    static listeners (type, form) {
        if (typeof(form) == "string") {
            form = document.querySelector(form);
        }
        return this.#listeners[type][VanillaJS.getPathTo(form)];
    }

    constructor (form, response = null) { // the form is required
        this.#form      = (typeof(form) == "string" ? document.querySelector(form) : form);
        this.#response  = response;

        if ((this.#form).nodeName != "FORM") {
            throw new Error("Form class accepts only <form> nodes."); // error
        }

        // listeners
        if (form && response) {
            var closures = Form.listeners((response.valid ? "response.valid" : "response.invalid"), form);

            for (let name in closures) {
                (closures[name])(response.values);
            }
        }
    }

    serialize (extra = {}, formData = false) { // returns a good to use json/formData, for AJAX
        var serializeIndexed = function (form) { // if you want a json, for AJAX
            var field, select_len, array = [];

            if (typeof form == "object") {
                var form_len = form.elements.length;

                for (var i=0; i<form_len; i++) {
                    field = form.elements[i];

                    if (field.name && !field.disabled && field.type != "file" && field.type != "reset" && field.type != "submit" && field.type != "button") {
                        if (field.type == "select-multiple") {
                            select_len = form.elements[i].options.length;
                            for (j=0; j<select_len; j++) {
                                if (field.options[j].selected) {
                                    array.push(field.name +"="+ encodeURIComponent(field.options[j].value));
                                }
                            }
                        }
                        else if ((field.type != "checkbox" && field.type != "radio") || field.checked) {
                            array.push(field.name +"="+ encodeURIComponent(field.value));
                        }
                    }
                }

                // Used by ArshWell to return the properly css/js files for every device.
                array.push("arsavinel-arshwell-mxdvcwdthflg=" + Math.max(
                    window.screen.availWidth || screen.width || window.outerWidth || window.innerWidth,
                    window.screen.availHeight || screen.height || window.outerHeight || window.innerHeight
                ));
            }
            return array.join("&");
        };
        var serializeAssociative = function (form) { // if you want a formData, for AJAX
            var field, select_len, array = [];

            if (typeof form == "object") {
                var form_len = form.elements.length;

                for (var i=0; i<form_len; i++) {
                    field = form.elements[i];

                    if (field.name && !field.disabled && field.type != "reset" && field.type != "submit" && field.type != "button") {
                        if (field.type != "file") {
                            if (field.type == "select-multiple") {
                                select_len = field.options.length;
                                for (let j=0; j<select_len; j++) {
                                    if (field.options[j].selected) {
                                        array[field.name] = field.options[j].value;
                                    }
                                }
                            }
                            else if ((field.type != "checkbox" && field.type != "radio") || field.checked) {
                                array[field.name] = field.value;
                            }
                        }
                        else if (field.files.length) {
                            array[field.name] = field.files;
                        }
                    }
                }

                // Used by ArshWell to return the properly css/js files.
                array['arsavinel-arshwell-mxdvcwdthflg'] = Math.max(
                    window.screen.availWidth || screen.width || window.outerWidth || window.innerWidth,
                    window.screen.availHeight || screen.height || window.outerHeight || window.innerHeight
                );
            }
            return array;
        };

        if (typeof(tinyMCE) == "object") {
            tinyMCE.triggerSave();
        }

        if (formData) {
            var formData = new FormData();

            for (var key in extra) {
                formData.append(key, extra[key]);
            }

            var elements = serializeAssociative(this.#form);

            for (var key in elements) {
                if (typeof(elements[key]) == "object") {
                    for (var k in elements[key]) {
                        formData.append(key, elements[key][k]);
                    }
                }
                else {
                    formData.append(key, elements[key]);
                }
            }

            return formData;
        }
        else {
            return $.extend({}, Web.query(serializeIndexed(this.#form)), extra);
        }
    }

    disable () { // disable every field of the form
        for (let field of (this.#form).querySelectorAll("*[name]:not([name=''])")) {
            field.disabled = "disabled";
        }
    }
    enable () { // enable every field of the form
        for (let field of (this.#form).querySelectorAll("*[name]:not([name=''])")) {
            field.removeAttribute('disabled');
        }
    }
    response (json) { // required for manipulating field values or errors
        this.#response = json;

        // listeners
        var closures = Form.listeners((json.valid ? "response.valid" : "response.invalid"), this.#form);

        for (let name in closures) {
            (closures[name])(json.values);
        }
    }
    syncValues () { // updating field values where needed
        let element;
        for (let name in this.#response["tags"]) {
            if ((element = (this.#form).querySelector(name +':not([form-valid-update="false"]):not(select):not([type="radio"]):not([type="checkbox"]):not([type="file"])'))
            && (element.value != this.#response["tags"][name])) {
                element.value = this.#response["tags"][name];
            }
        }
    }
    syncErrors (closure) { // showing every error
        for (let element of (this.#form).querySelectorAll('[form-error]')) {
            let name = element.getAttribute('form-error');

            closure(
                element,
                (this.#response && ('errors' in this.#response) && (name in this.#response['errors'])
                    ? this.#response["errors"][name] : ''
                )
            );
        }
    }
    empty (closure = null) { // it empties every field of the form
        for (let field of (this.#form).querySelectorAll('input[name]:not([type="hidden"]):not([type="radio"]):not([type="checkbox"]), textarea[name]')) {
            if (closure == null || closure(field) == true) {
                field.value = null;
            }
        }
        for (let field of (this.#form).querySelectorAll('input[name][type="checkbox"]')) {
            if (closure == null || closure(field) == true) {
                field.checked = false;
            }
        }
        for (let field of (this.#form).querySelectorAll("select[name] option")) {
            if (closure == null || closure(field) == true) {
                field.selected = false;
            }
        }
    }

    valid () { // bool
        return this.#response["valid"];
    }
    invalid () { // bool
        return !this.#response["valid"];
    }
    expired () { // bool
        return this.#response["expired"];
    }

    value (field) {
        return this.#response["values"][field];
    }
    array (field) {
        return this.#response["values"][field] || [];
    }
    error (field) {
        return this.#response["errors"][field];
    }

    static token (type) {
        return {
            "form": document.querySelector('html head meta[name="csrf-form-token"][content]').getAttribute("content"),
            "ajax": document.querySelector('html head meta[name="csrf-ajax-token"][content]').getAttribute("content")
        }[type];
    }
}
