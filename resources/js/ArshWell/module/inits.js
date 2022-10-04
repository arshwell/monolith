$(document).ready(function () {
    $('[data-tooltip="true"]').tooltip({
        container: 'body'
    });
    $('[data-confirmation="true"]').confirmation({
        btnOkLabel:     'DA',
        btnCancelLabel: 'Nu',
        singleton:  true,
        popout:     true,
        rootSelector: '[data-confirmation="true"]'
    });

    $('.arshmodule-html-piece-tbody [data-key="order"]').each(function () {
        $(this).closest('tbody').sortable({
            items:      'tr',
            axis:       "y",
            handle:     '[data-key="order"]',
            cancel:     null, // allow buttons as handlers
            opacity:    0.9,
            tolerance:  "pointer",
            containment: $(this).closest('.arshmodule'),
            cursor:     "move",
            revert:     true,
            scroll:     true,
            scrollSpeed: 10,
            forceHelperSize: true,
            forcePlaceholderSize: true,
            start: function (event, ui) {
                $(ui.placeholder).css({
                    'visibility':   'visible',
                    'height':       $(ui.helper).outerHeight()
                });
                $(ui.helper).css({
                    'display': 'table',
                    'border-collapse': 'collapse',
                    'table-layout': 'fixed'
                });
            },
            stop: function (event, ui) {
                $(ui.item).css({
                    'display': 'table-row',
                    'border-collapse': 'unset',
                    'table-layout': 'unset'
                });

                $.ajax({
                    url:        Web.url(),
                    type:       'POST',
                    dataType:   'JSON',
                    data:       {
                        ajax_token: Form.token('ajax'),
                        form_token: Form.token('form'),
                        ftr: 'order',
                        ids: $(ui.item).closest('.ui-sortable').find('[data-key="order"]').map(function () {
                            return this.getAttribute('data-id-table');
                        }).get()
                    }
                });
            }
        });
    });

    $('select[multiple][js-plugin-multiselect="true"]').multiselect({
        buttonWidth: '100%',
        buttonTextAlignment: 'left',
        nonSelectedText: 'Select',
        nSelectedText: ' selected',
        enableFiltering: true,
        filterPlaceholder: 'Search for...',
        enableCaseInsensitiveFiltering: true,
        includeFilterClearBtn: false,
        delimiterText: '|'
    });

    if (typeof $.fn.tagsinput != undefined) {
        $('[js-plugin-tagsinput="true"]').each(function () {
            var attributes = {};
            $.each(this.attributes, function (i, attr) {
                if (attr.specified && attr.name.startsWith("js-plugin-tagsinput-")) {
                    attributes[attr.name.substring(20)] = attr.value;
                }
            });

            $(this).tagsinput(attributes);

            var newInput = $(this).prev('.bootstrap-tagsinput').find('input')[0]
            for (let attr of this.attributes) {
                if (!['type', 'class', 'value'].includes(attr.name)) {
                    newInput.setAttribute(attr.name, attr.value);
                }
            }

            $(newInput).on('change paste', function () {
                var input = this;
                setTimeout(function () { // because PASTE updates after a short time
                    $(input).trigger('keydown');
                }, 10);
            });
        });
    }

    if (typeof tinyMCE == 'object') {
        var options = {
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            },
            branding:   false,
            height:     250,
            menubar:    true,
            menu: {
                /**
                 * Default menu controls: https://www.tiny.cloud/docs/general-configuration-guide/basic-setup/#defaultmenucontrols
                 *
                 * Started from default menu items.
                 *
                 * Changelog:
                 *  - 'fontformats' & 'fontsizes' removed
                 *  - 'hr' (Horizontal Line) added
                 */
                file: {
                    title: 'File',
                    items: 'newdocument restoredraft | preview | print | deleteallconversations'
                },
                edit: {
                    title: 'Edit',
                    items: 'undo redo | cut copy paste pastetext | selectall | searchreplace'
                },
                view: {
                    title: 'View',
                    items: 'code | visualaid visualchars visualblocks | spellchecker | preview fullscreen | showcomments'
                },
                insert: {
                    title: 'Insert',
                    items: 'image link media addcomment pageembed template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime'
                },
                format: {
                    title: 'Format',
                    items: 'bold italic underline strikethrough superscript subscript codeformat | formats blockformats align | forecolor backcolor | removeformat'
                },
                tools: {
                    title: 'Tools',
                    items: 'spellchecker spellcheckerlanguage | a11ycheck code wordcount'
                },
                table: {
                    title: 'Table',
                    items: 'inserttable | cell row column | advtablesort | tableprops deletetable'
                },
                help: {
                    title: 'Help',
                    items: 'help'
                }
            },
            toolbar: 'undo redo | formatselect | forecolor backcolor | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',

            /**
             * Text color map: https://www.tiny.cloud/docs-4x/plugins/textcolor/#textcolor_map
             *
             * Would allow only used colors in website.
             *
             * TODO: should be extracted from resources\scss\site\colors.scss
             */
            // textcolor_map: [
            //     "000000", "Black",
            //     "000080", "Navy Blue"
            // ],

            /**
             * Custom CSS: https://www.tiny.cloud/docs-3x/reference/Configuration3x/Configuration3x@content_css/
             *
             * This can be used to match the styling of your published content for a truer WYSIWYG experience.
             *
             * TODO: file with fonts, sizes used in site.
             */
            // content_css : "css/custom_content.css",

            plugins: [
                'advlist autolink lists link image charmap hr print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount textcolor'
            ],

            // remove custom font sizes from external sources
            paste_postprocess: function (plugin, args) {
                $(args.node).css('font-size', '').find('*').css('font-size', '');
            },

            default_link_target: "_blank",
            extended_valid_elements : "a[href|target=_blank]",
            forced_root_block: false, // no html tag parent
            images_upload_url: 'doesntmatter.php', // won't be actually used
            automatic_uploads: false, // we don't upload, so files stay as blob
            image_advtab: true // advanced image style (margin, border)
        };

        $('textarea[js-plugin-tinymce="true"]').each(function () {
            var attributes = {};
            $.each(this.attributes, function (i, attr) {
                if (attr.specified && attr.name.startsWith("js-plugin-tinymce-")) {
                    attributes[attr.name.substring(18)] = attr.value;
                }
            });

            tinyMCE.init({
                ...options,
                ...attributes,
                ...{
                    target: this
                }
            });
        });
    }

    if (typeof ClipboardJS == 'function') {
        let clipboards = new ClipboardJS('[data-clipboard-text]');

        clipboards.on('success', function (event) {
            event.clearSelection();

            if ($(event.trigger).attr('data-original-title') == undefined) {
                $(event.trigger).tooltip();
            }

            $(event.trigger).attr('data-original-title', 'Copiat!').tooltip('show');

            setTimeout(function () {
                $(event.trigger).attr('data-original-title', $(event.trigger).data('title'));
            }, 1500);
        });
        clipboards.on('error', function (event) {
            if ($(event.trigger).attr('data-original-title') == undefined) {
                $(event.trigger).tooltip();
            }

            $(event.trigger).attr('data-original-title', 'Type CTRL+C').tooltip('show');

            setTimeout(function () {
                $(event.trigger).attr('data-original-title', $(event.trigger).data('title'));
            }, 1500);
        });
    }

    // Add background to PNG images
    (function () {
        function getPixel (imgData, index) { // returns array [red, green, blue, alpha]
            var i = index*4, d = imgData.data;
            return [d[i],d[i+1],d[i+2],d[i+3]] // [R,G,B,A]
        }

        function getPixelXY (imgData, x, y) { // returns same array, using x,y
            return getPixel(imgData, y*imgData.width+x);
        }

        function addBackground () {
            var image = this;

            if (image.getAttribute('src')) {
                setTimeout(function () {
                    var width = Math.max(image.width, image.naturalWidth);
                    var height = Math.max(image.height, image.naturalHeight);

                    var canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = width;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(image, 0, 0, width, height);
                    var idt = ctx.getImageData(0, 0, width, height);

                    var isPNG = false;
                    var pixels = width*height;
                    var diffs = {};

                    for (var i = 0; i < pixels; i++) {
                        var rgb = getPixel(idt, i);

                        if (rgb[3] == 0) { // is transparent
                            isPNG = true;
                        }

                        if (rgb[3] > 0) { // not transparent
                            var average = parseInt((rgb[0]+rgb[1]+rgb[2])/3);

                            if (diffs.hasOwnProperty(average) == false) {
                                diffs[average] = [];
                            }

                            diffs[average].push(parseInt(Math.abs(rgb[0]-average) + Math.abs(rgb[1]-average) + Math.abs(rgb[2]-average)));
                        }
                    }

                    if (isPNG) {
                        var algorithm = {};

                        for (var key in diffs) {
                            algorithm[key] = 0;

                            for (var i = 0; i < diffs[key].length; i++) {
                                algorithm[key] += (255 - diffs[key][i]);
                            }
                        }

                        var colors = {
                            20: 0,
                            60: 0,
                            100: 0,
                            140: 0,
                            180: 0,
                            220: 0
                        };

                        for (var c in colors) {
                            for (var key in algorithm) {
                                colors[c] += (Math.abs(c - key) * algorithm[key]);
                            }
                        }

                        var color = parseInt(Object.keys(colors).reduce((a, b) => colors[a] > colors[b] ? a : b));
                        var rgb = "rgb("+color+", "+color+", "+color+")";

                        /**
                        * IMPORTANT: Don't edit 'background-image'!
                        * NOTE: Squares sizes depends on 'background-position' & 'background-size'.
                        */
                        $(image).parent().css({
                            'background-color': "rgb("+(color+15)+", "+(color+15)+", "+(color+15)+")",
                            'background-image': "linear-gradient(45deg, "+rgb+" 25%, transparent 25%, transparent 75%, "+rgb+" 75%, "+rgb+" 100%), linear-gradient(45deg, "+rgb+" 25%, transparent 25%, transparent 75%, "+rgb+" 75%, "+rgb+" 100%)",
                            'background-position': "0 0, 10px 10px",
                            'background-size': "20px 20px"
                        });
                    }
                    else { // is not PNG
                        $(image).parent().css({
                            'background-color':     "unset",
                            'background-image':     "unset",
                            'background-position':  "unset",
                            'background-size':      "unset"
                        });
                    }
                }, 50); // waiting 'width' and 'height' being set
            }
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    $(mutation.addedNodes[i]).find('img').on('load', addBackground).each(addBackground);
                }
            });
        });

        $('.arshmodule .arshmodule-html-field-images .row').each(function () {
            observer.observe(this, {
                childList: true,
                subtree: true,
                attributes: false,
                characterData: false
            });
        });

        $('.arshmodule .arshmodule-html-piece-tbody .arshmodule-table-image img').each(addBackground);
        $('.arshmodule .arshmodule-html-field-image img').on('load', addBackground).each(addBackground);
        $('.arshmodule .arshmodule-html-field-images img').on('load', addBackground).each(addBackground);
    })();
});
