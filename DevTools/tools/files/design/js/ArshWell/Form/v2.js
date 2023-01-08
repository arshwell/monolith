/**
 * Form function (acting as a class) for manipulating AJAX responses.
 * It helps you with any need about JS forms.

 * @author: https://github.com/arsavinel
 * @license MIT (https://github.com/arsavinel/ArshWell/blob/0.x/LICENSE.md)

 * This file is used at least by:
 *      - Arsavinel\Arshwell\Layout::compileJSFooter()
 *      - Arsavinel\Arshwell\DevTool\DevToolHTML::html()
 */
function Form (form, response = null) { // the form is required

    this.dom = (typeof(form) == "string" ? document.querySelector(form) : form);
    this.json = {
        'response': response
    };

    if ((this.dom).nodeName != "FORM") {
        throw new Error("Form class accepts only <form> nodes."); // error
    }

    // listeners
    if (form && response) {
        var closures = Form.listeners((response.valid ? "response.valid" : "response.invalid"), form);

        for (let name in closures) {
            (closures[name])(response.values);
        }
    }

    this.serialize = function (extra = {}, formData = false) { // returns a good to use json/formData, for AJAX
        var serializeIndexed = function (form) { // if you want a json, for AJAX
            var field, select_len, array = [];

            if (typeof form == "object") {
                var form_len = form.elements.length;

                for (var i=0; i<form_len; i++) {
                    field = form.elements[i];

                    if (field.name && !field.disabled && field.type != "file" && field.type != "reset" && field.type != "submit" && field.type != "button") {
                        if (field.type == "select-multiple") {
                            select_len = field.options.length;
                            for (var j=0; j<select_len; j++) {
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
                            if (field.type == "select-multiple" || field.name.endsWith('[]')) {
                                select_len = field.options.length;
                                if (!array.hasOwnProperty(field.name)) {
                                    array[field.name] = [];
                                }
                                for (let j=0; j<select_len; j++) {
                                    if (field.options[j].selected) {
                                        array[field.name].push(field.options[j].value);
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
                if (typeof(extra[key]) == "object") {
                    for (var k in extra[key]) {
                        formData.append(key + '['+k+']', extra[key][k]);
                    }
                }
                else {
                    formData.append(key, extra[key]);
                }
            }

            var elements = serializeAssociative(this.dom);

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
            return $.extend({}, Web.query(serializeIndexed(this.dom)), extra);
        }
    };

    this.disable = function () { // disable every field of the form
        for (let field of (this.dom).querySelectorAll("*[name]:not([name='']):not([disabled])")) {
            field.disabled = "disabled";
            field.setAttribute('rshwll-disabled', 'true');
        }
    };
    this.enable = function () { // enable every field of the form
        for (let field of (this.dom).querySelectorAll("*[name][rshwll-disabled]:not([name=''])")) {
            field.removeAttribute('disabled');
            field.removeAttribute('rshwll-disabled');
        }
    };
    this.response = function (json, trigger = true) { // required for manipulating field values or errors
        this.json.response = json;

        if (trigger == true) {
            // listeners
            var closures = Form.listeners((json.valid ? "response.valid" : "response.invalid"), this.dom);

            for (let name in closures) {
                (closures[name])(json.values);
            }
        }
    };
    this.trigger = function (listener = null) {
        if (this.json.response) {
            if (!listener) {
                listener = (this.json.response.valid ? "response.valid" : "response.invalid");
            }

            if (listener in Form.vars.listeners) {
                // listeners
                var closures = Form.listeners(listener, this.dom);

                for (let name in closures) {
                    (closures[name])(this.json.response.values);
                }

                return true;
            }
        }
        return false;
    };
    this.syncValues = function () { // updating field values where needed
        let element;
        for (let name in this.json.response["tags"]) {
            if ((element = (this.dom).querySelector(name +':not([form-valid-update="false"]):not(select):not([type="radio"]):not([type="checkbox"]):not([type="file"])'))
            && (element.value != this.json.response["tags"][name])) {
                element.value = this.json.response["tags"][name];
            }
        }
    };
    this.syncErrors = function (closure) { // showing every error
        for (let element of (this.dom).querySelectorAll('[form-error]')) {
            let name = element.getAttribute('form-error');

            closure(
                element,
                (this.json.response && ('errors' in this.json.response) && (name in this.json.response['errors'])
                    ? this.json.response["errors"][name] : ''
                )
            );
        }
    };
    this.empty = function (closure = null) { // it empties every field of the form
        for (let input of (this.dom).querySelectorAll('input[name]:not([form-empty="false"]):not([type="hidden"]):not([type="file"]):not([type="radio"]):not([type="checkbox"]), textarea[name]:not([form-empty="false"])')) {
            if (closure == null || closure(input) == true) {
                input.value = null;
            }
        }
        for (let input of (this.dom).querySelectorAll('input[name][type="checkbox"]:not([form-empty="false"])')) {
            if (closure == null || closure(input) == true) {
                input.checked = false;
            }
        }
        for (let input of (this.dom).querySelectorAll('input[name][type="file"]:not([form-empty="false"])')) {
            if (closure == null || closure(input) == true) {
                input.value = null;
                input.dispatchEvent(new Event('change'));
            }
        }
        for (let option of (this.dom).querySelectorAll('select[name]:not([form-empty="false"]) option')) {
            if (closure == null || closure(option) == true) {
                option.selected = false;
            }
        }
    };

    this.valid = function () { // bool
        return this.json.response["valid"];
    };
    this.invalid = function () { // bool
        return !this.json.response["valid"];
    };
    this.expired = function () { // bool
        return this.json.response["expired"];
    };

    this.value = function (field) {
        return this.json.response["values"][field];
    };
    this.array = function (field) {
        return this.json.response["values"][field] || [];
    };
    this.error = function (field) {
        return this.json.response["errors"][field];
    };
}

Form.vars = {
    listeners: {
        'response.valid':   [],
        'response.invalid': [],
        'sync.values':      [],
        'sync.errors':      [],
        'empty':            [],
        'disable':          []
    }
};

Form.on = function (form, type, closure) {
    if (typeof(form) == "string") {
        form = document.querySelector(form);
    }
    if (form.nodeName != "FORM") {
        throw new Error("Form class accepts only <form> nodes."); // error
    }

    var xpath = VanillaJS.getPathTo(form);
    if (typeof(Form.vars.listeners[type][xpath]) == "undefined") {
        Form.vars.listeners[type][xpath] = [];
    }
    Form.vars.listeners[type][xpath].push(closure);
};

Form.off = function (form, type) {
    delete Form.vars.listeners[type][VanillaJS.getPathTo(document.querySelector(form))];
};

Form.listeners = function (type, form) {
    if (typeof(form) == "string") {
        form = document.querySelector(form);
    }

    if (document.body.contains(form)) {
        return Form.vars.listeners[type][VanillaJS.getPathTo(form)];
    }
    else {
        return [];
    }
};

Form.token = function (type) {
    return {
        "form": document.querySelector('html head meta[name="csrf-form-token"][content]').getAttribute("content"),
        "ajax": document.querySelector('html head meta[name="csrf-ajax-token"][content]').getAttribute("content")
    }[type];
};
