var credFrontEndViewModel = {
    /**
     * @description JSON object that holds AJAX messages to be sent when forms are fully initialized to prevent getting responses before observable bindings are made.
     */
    messagesQueue: {},
    /**
     * @description Array of Toolset Forms present in the currnet page
     */
    credForms: [],
    /**
     * @description a list of Toolset Form IDs that's ready for third-party actions
     */
    readyCREDForms: [],

    /**
     * @description Loading Spinner class
     */
    loadingSpinnerClass: "loading-spinner",

    /**
     * @description Loading spinner image for ajax forms
     */
    loadingSpinnerImagePath: "/wp-admin/images/wpspin_light-2x.gif",

    /**
     * @description Recaptcha widget id list for each cred form belongs to
     */
    recaptcha_widget_ids: [],

    /**
     * Reload and init reCaptha component after a cred form ajax call
     *
     * @param $current_form
     *
     * @since 1.9.3
     */
    tryToReloadReCAPTCHA: function ($current_form) {
        if (typeof grecaptcha !== 'undefined') {
            var formID = $current_form.attr('id');
            var $recaptcha_selector = $current_form.find('div.g-recaptcha');
            if ($recaptcha_selector.length) {
                var _sitekey = $recaptcha_selector.data('sitekey');
                if (typeof _sitekey !== 'undefined') {
                    var recaptcha_widget_id = grecaptcha.render($recaptcha_selector.attr('id'), {sitekey: _sitekey});
                    //init current recaptcha widget id
                    if (typeof this.recaptcha_widget_ids[formID] === 'undefined') {
                        this.recaptcha_widget_ids[formID] = [];
                    }
                    this.recaptcha_widget_ids[formID] = recaptcha_widget_id;
                }
            }
        }
    },

    /**
     * Show and Hide the ReCAPTHA error messsage at run time
     *
     * @param $form
     *
     * @returns boolean
     *
     * @since 1.9.3
     */
    handleReCAPTCHAErrorMessage: function ($form) {
        if (typeof grecaptcha !== 'undefined') {
            var $error_selector = $form.find('div.recaptcha_error');
            var formID = $form.attr('id');
            if (typeof this.recaptcha_widget_ids[formID] !== 'undefined') {
                if (grecaptcha.getResponse(this.recaptcha_widget_ids[formID]) == '') {
                    $error_selector.show();
                    setTimeout(function () {
                        $error_selector.hide();
                    }, 5000);
                    return false;
                } else {
                    //reset recapatcha widget_id
                    this.recaptcha_widget_ids[formID] = undefined;
                }
            }

            $error_selector.hide();
        }
        return true;
    },

    /**
     * Set disabled the submit button
     *
     * @param $form
     *
     * @since 1.9.3
     */
    disableSubmitForm: function (formID, isValidForm, credSettings) {
        var $form = jQuery(formID);
        if (isValidForm) {
            $form.find('.wpt-form-submit').prop('disabled', true);
        }
    },

    /**
     * Enable the submit form
     *
     * @param $form
     *
     * @since 1.9.3
     */
    enableSubmitForm: function ($form) {
        $form.find('.wpt-form-submit').prop('disabled', false);
    },

    /**
     * @type {bool}
     */
    isWpEditorAvailable: null,


    /**
     * Check whether wp.editor is available
     *
     * @return {bool}
     */
    checkWpEditorAvailable: function() {
        if ( null == this.isWpEditorAvailable ) {
            this.isWpEditorAvailable = (
                _.has( window, 'wp' )
                && _.has( window.wp, 'editor' )
                && _.has( window.wp.editor, 'remove' )
                && _.has( window.wp.editor, 'initialize' )
            );
        }
        return this.isWpEditorAvailable;
    },

    /**
     * Reload and re-init tinyMCE after a cred form ajax call
     * 
     * @param {object} $container Node to reload inner tinyMCE editors
     *
     * @since 1.9.3
     */
    reloadTinyMCE: function( $container ) {
        $container = ( typeof $container !== 'undefined' ) ? $container : jQuery( document );
        var currentInstance = this;
        jQuery( 'textarea.wpt-wysiwyg', $container ).each( function( index ) {
            var $area = jQuery( this ),
                area_id = $area.prop('id');

            if ( currentInstance.checkWpEditorAvailable() ) {
                // WordPress over 4.8, hence wp.editor is available and included
                wp.editor.remove( area_id );
                var tinymceSettings = (
                        _.has( window, 'tinyMCEPreInit' )
                        && _.has( window.tinyMCEPreInit, 'mceInit' )
                        && _.has( window.tinyMCEPreInit.mceInit, area_id )
                    ) ? window.tinyMCEPreInit.mceInit[ area_id ] : true,
                    qtSettings = (
                        _.has( window, 'tinyMCEPreInit' )
                        && _.has( window.tinyMCEPreInit, 'qtInit' )
                        && _.has( window.tinyMCEPreInit.qtInit, area_id )
                    ) ? window.tinyMCEPreInit.qtInit[ area_id ] : true,
                    hasMediaButton = ! jQuery( 'textarea#' + area_id ).hasClass( 'js-toolset-wysiwyg-skip-media' ),
                    hasToolsetButton = ! jQuery( 'textarea#' + area_id ).hasClass( 'js-toolset-wysiwyg-skip-toolset' ),
                    mediaButtonsSettings = ( hasMediaButton || hasToolsetButton );
                
                wp.editor.initialize( area_id, { tinymce: tinymceSettings, quicktags: qtSettings, mediaButtons: mediaButtonsSettings } );

                if ( mediaButtonsSettings ) {
                    var $mediaButtonsContainer = jQuery( '#wp-' + area_id + '-wrap .wp-media-buttons' );
                    $mediaButtonsContainer.attr( 'id', 'wp-' + area_id + '-media-buttons' );

                    if ( ! hasMediaButton ) {
                        $mediaButtonsContainer.find( '.insert-media.add_media' ).remove();
                    }
                    if ( hasToolsetButton ) {
                        /**
                         * Broadcasts that the WYSIWYG field initialization was completed,
                         * only if the WYSIWYG field should include Toolset buttons.
                         *
                         * @param {string} area_id The underlying textarea id attribute
                         *
                         * @event toolset:forms:wysiwygFieldInited
                         * 
                         * @since 2.1.2
                         */
                        jQuery( document ).trigger( 'toolset:forms:wysiwygFieldInited', [ area_id ] );
                    }
                }
    
            } else {
                // WordPress below 4.8, hence wp-editor is not available
                // so we turn those fields into simple textareas
                jQuery( '#wp-' + area_id + '-editor-tools' ).remove();
                jQuery( '#wp-' + area_id + '-editor-container' )
                    .removeClass( 'wp-editor-container' )
                    .find( '.mce-container' )
                    .remove();
                jQuery( '#qt_' + area_id + '_toolbar' ).remove();
                jQuery( '#' + area_id )
                    .removeClass( 'wp-editor-area' )
                    .show()
                    .css( { width: '100%' } );
            }
        });
    },

    /**
     * Method that handles the cred form ajax call when cred form is an ajax form
     *
     * @param formID
     * @param isAjaxForm
     * @param cred_settings
     *
     * @since 1.9.3
     */
    onValidatedSubmitAjaxForm: function (formID, isAjaxForm, cred_settings) {
        var thiz = this;
        var $form = jQuery(formID);
        var credSettings = cred_settings;
        var site_url = cred_settings.site_url;

        if (isAjaxForm) {
            jQuery('<input value="cred_ajax_form" name="action">').attr('type', 'hidden').appendTo(formID);
            jQuery('<input value="true" name="form_submit">').attr('type', 'hidden').appendTo(formID);

            thiz.startLoading($form.find('.wpt-form-submit'), site_url);

            jQuery.ajax({
                type: 'post',
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: 'json',
                complete: function (response) {
                    thiz.stopLoading();

                    /**
                     * cred_form_ajax_completed
                     * Event that is triggered once cred form ajax call is completed
                     *
                     * @since 1.9.3
                     */
                    Toolset.hooks.doAction('cred_form_ajax_completed', formID);
                },
                success: function (response) {
                    if (response) {
                        $form.replaceWith(response.output);

                        if ('ok' === response.result) {

                            /**
                             * cred_form_ajax_error
                             * Event that is triggered once cred form ajax call is in success state
                             *
                             * @since 1.9.3
                             */
                            Toolset.hooks.doAction('cred_form_ajax_success', formID);
                        } else {
                            /**
                             * cred_form_ajax_error
                             * Event that is triggered once cred form ajax call is in error state
                             *
                             * @since 1.9.3
                             */
                            Toolset.hooks.doAction('cred_form_ajax_error', formID);
                        }
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(credSettings.operation_ko);

                    /**
                     * cred_form_ajax_error
                     * Event that is triggered once cred form ajax call is in error state
                     *
                     * @since 1.9.3
                     */
                    Toolset.hooks.doAction('cred_form_ajax_error', formID);
                }
            });
        }
    },

    /**
     * Append wp native spinner next to a selector (by defualt is submit button)
     *
     * @param $selector_to_append
     * @param site_url
     *
     * @since 1.9.3
     */
    startLoading: function ($selector_to_append, site_url) {
        var $body = jQuery("body");
        $body.addClass("wpt-loading");
        var loading_icon = site_url + this.loadingSpinnerImagePath;
        $selector_to_append.after('<span class="' + this.loadingSpinnerClass + '" style="margin-left:5px;"><img class="cred-form-loading-spinner-image" src="' + loading_icon + '"></span>');
    },

    /**
     * @since 1.9.3
     */
    stopLoading: function () {
        var $body = jQuery("body");
        $body.removeClass("wpt-loading");
        jQuery('.' + this.loadingSpinnerClass).remove();
    },

    /**
     * Function called when an Ajax Form is validated and submitted
     *
     * @param formID
     *
     * @since 1.9.3
     */
    onAjaxFormSubmit: function( formID ) {
        $form_selector = jQuery(formID);
        this.enableSubmitForm($form_selector);
        this.initColorPicker($form_selector);

        //Reset initialised validation forms
        if (window.hasOwnProperty('initialisedCREDForms')) {
            initialisedCREDForms = [];
        }

        //Reapply bindings for the form
        this.applyViewModelBindings();
        this.activatePreviewMode();
        this.reloadTinyMCE( $form_selector );
        this.tryToReloadReCAPTCHA($form_selector);
    },

    /**
     * @description Updates Toolset Forms auto-draft post ID
     */
    updateFormsPostID: function () {
        this.getAllForms();

        for (var single_form in this.credForms) {
            single_form = this.credForms[single_form];
            var form_data = this.extractFormData(single_form);
            this.assignDynamicObservableID(form_data);
        }
    },

    /**
     * @description Returns all Toolset Forms in document
     */
    getAllForms: function () {
        var document_forms = jQuery('.cred-form, .cred-user-form', document);

        for (var form_index in document_forms) {
            if (isNaN(form_index)) {
                break;
            }
            this.credForms.push(document_forms[form_index]);
        }
        return this.credForms;

    },

    /**
     * @description Assigns an observable binding ID to each Toolset Form to be updated dynamically when observable value changes.
     * @param form_data JSON object returned from extractFormData method
     */
    assignDynamicObservableID: function (form_data) {
        if (form_data.post_id_node !== undefined && form_data.post_id_node !== null) {
            form_data.binding_property_name = "post_id_observable_" + this.uniqueID() + this.uniqueID();

            this[form_data.context_id][form_data.binding_property_name] = ko.observable(form_data.post_id);
            this[form_data.context_id][form_data.binding_property_name + "_submit"] = ko.computed(function () {
                return (this[form_data.context_id][form_data.binding_property_name]() === undefined);
            }, this);

            jQuery(form_data.post_id_node).attr('data-bind', 'value: ' + form_data.binding_property_name);
            jQuery(form_data.form_submit_node).attr('disabled', 'disabled');

            var cred_check_id_ajax_data = {
                action: 'check_post_id',
                post_id: form_data.post_id,
                form_id: form_data.form_id,
                binding_property_name: form_data.binding_property_name,
                form_index: form_data.binding_property_name,
                form_context_id: form_data.context_id
            };

            this.messagesQueue[form_data.binding_property_name] = cred_check_id_ajax_data;
        }
    },

    /**
     * @description Returns a JSON object with useful form information including form_id, auto-draft post_id, the hidden input where auto-draft post_id is saved, and the submit button for the form.
     * @param form HTML form node
     */
    extractFormData: function (form) {
        //Setup a knockout context for each form
        var form_context_binding_id = 'cred_form_context_' + this.uniqueID();
        this[form_context_binding_id] = {};

        jQuery(form).attr('data-bind', 'with: ' + form_context_binding_id);

        return {
            form_id: (jQuery(form).children("input[name='_cred_cred_prefix_form_id']") ? jQuery(form).children("input[name='_cred_cred_prefix_form_id']").val() : null),
            post_id: (jQuery(form).children("input[name='_cred_cred_prefix_post_id']") ? jQuery(form).children("input[name='_cred_cred_prefix_post_id']").val() : null),
            post_id_node: jQuery(form).children("input[name='_cred_cred_prefix_post_id']"),
            form_submit_node: jQuery(form).children('.wpt-form-submit'),
            context_id: form_context_binding_id
        };
    },

    /**
     * @description Returns a uniqueID
     */
    uniqueID: function () {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    },

    /**
     * @description Sends out all messages in the messagesQueue via AJAX
     */
    initQueue: function () {
        var queue_keys = Object.keys(this.messagesQueue);
        if (queue_keys.length > 0) {
            for (var key in queue_keys) {
                var message = this.messagesQueue[queue_keys[key]];
                jQuery.post(cred_frontend_settings.ajaxurl, message, function (callback_data) {
                    if (callback_data != "" && callback_data != 0) {
                        try {
                            var callback_data = JSON.parse(callback_data);
                            credFrontEndViewModel[callback_data.form_context_id][callback_data.observable_id](callback_data.pid);
                        } catch (err) {
                            console.error('Toolset Forms: Error parsing callback data for `check_post_id` ');
                        }
                    }
                });
            }
        }
    },

    /**
     * @description Looks up all Toolset Forms file buttons and assigns event listeners for undo, delete and upload actions.
     */
    initCREDFile: function () {
        jQuery('.js-wpt-credfile-delete, .js-wpt-credfile-undo').on('click', function (e) {
            e.preventDefault();

            var that = jQuery(this),

                credfile_action = that.data('action'),
                credfile_container = that.closest('.wpt-repctl');

            if (credfile_container.length < 1) {
                credfile_container = that.closest('.js-wpt-field-items');
            }

            var that_delete_button = jQuery('.js-wpt-credfile-delete', credfile_container),

                that_undo_button = jQuery('.js-wpt-credfile-undo', credfile_container),
                that_hidden_input = jQuery('.js-wpv-credfile-hidden', credfile_container),
                that_file_input = jQuery('.js-wpt-credfile-upload-file', credfile_container),
                that_preview = jQuery('.js-wpt-credfile-preview', credfile_container),
                that_existing_value = that_hidden_input.val();


            var field_id = that_hidden_input.attr('name');
            if (credfile_action == 'delete') {
                that_file_input.val('').prop('disabled', false).prop('alt', '').prop('res', '').show();
                that_hidden_input.val('').prop('disabled', true);
                that_preview.hide();

                if (that_existing_value != '') {
                    //that_undo_button.show();
                } else {
                    that_undo_button.hide();
                }

                if (field_id == '_featured_image') {
                    jQuery('input[name="attachid_' + field_id + '"').val('');
                } else {
                    if (that.closest('.js-wpt-repetitive').length > 0) {
                    } else {
                        jQuery('#' + field_id).prop('disabled', false);
                    }
                }
                if (jQuery('.js-wpt-credfile-preview > img', credfile_container).length > 0) {
                    jQuery('.js-wpt-credfile-preview > img', credfile_container).remove();
                }
                that_file_input.trigger('change');
            } else if (credfile_action == 'undo') {
                that_file_input.prop('disabled', true).hide();
                that_hidden_input.prop('disabled', false);
                that_file_input.trigger('change');
                that_preview.show();
                //that_delete_button.show();
                that_undo_button.hide();
                if (field_id == '_featured_image')
                    jQuery('#attachid_' + field_id).val(jQuery("input[name='_cred_cred_prefix_post_id']").val());
                else {
                    if (that.closest('.js-wpt-repetitive').length > 0) {
                    } else
                        jQuery('#' + field_id).prop('disabled', false);
                }
            }
        });

        jQuery('.js-wpt-credfile-upload-file').on('change', function (e) {
            e.preventDefault();
            var that = jQuery(this),
                credfile_container = that.closest('.wpt-repctl');
            if (credfile_container.length < 1) {
                credfile_container = that.closest('.js-wpt-field-items');
            }
            var that_delete_button = jQuery('.js-wpt-credfile-delete', credfile_container),
                that_undo_button = jQuery('.js-wpt-credfile-undo', credfile_container),
                that_hidden_input = jQuery('.js-wpv-credfile-hidden', credfile_container),
                that_preview = jQuery('.js-wpt-credfile-preview', credfile_container),
                that_existing_value = that_hidden_input.val();

            if (that_existing_value === '' || that_existing_value != that.val()) {
                that_undo_button.hide();
            }
        });
    },

    /**
     * @description Adds IDs for both labels and inputs for accessibility support
     * @since 1.8.6
     */
    addAccessibilityIDs: function () {
        var $cred_form_labels = jQuery('.cred-form .form-group label');
        for (var form_label_index in $cred_form_labels) {
            if (isNaN(form_label_index)) {
                break;
            }

            var $form_label = jQuery($cred_form_labels[form_label_index]);
            var accessibility_id = this.uniqueID();

            $input_array = [];

            $input_array.push($form_label.parent().find(':input:not(:button)'));
            $input_array.push($form_label.parent().find('select')[0]);
            $input_array.push($form_label.parent().find('textarea')[0]);

            if ($input_array.length > 0) {
                for (var input in $input_array) {
                    if ($input_array[input] !== undefined) {
                        $input_array[input] = jQuery($input_array[input]);
                        if ($input_array[input].attr('id') !== undefined && $input_array[input].attr('id') !== null && $input_array[input].attr('id') != "") {
                            $form_label.attr('for', $input_array[input].attr('id'));
                        } else {
                            $input_array[input].attr('id', accessibility_id);
                            $form_label.attr('for', accessibility_id);
                        }
                    }
                }
            }
        }
    },

    /**
     * @description Cleans all Toolset Form nodes and apply the bindings
     * @since 1.9
     */
    applyViewModelBindings: function () {
        //Clear initialised Toolset Form, only from KO bindings and not from custom ones
        this.readyCREDForms = [];

        for (var cred_form in this.credForms) {
            var original = ko.utils.domNodeDisposal['cleanExternalData'];
            ko.utils.domNodeDisposal['cleanExternalData'] = function () {
            };
            ko.cleanNode(this.credForms[cred_form]);
            ko.utils.domNodeDisposal['cleanExternalData'] = original;
            ko.applyBindings(this, this.credForms[cred_form]);

            var cred_form_id = jQuery(this.credForms[cred_form]).attr('id');
            this.readyCREDForms.push(cred_form_id);

            jQuery('.js-wpt-validate', '#' + cred_form_id).removeClass('js-wpt-validate');
            jQuery(document).trigger('cred_form_ready', {
                form_id: cred_form_id
            });
        }
    },

    /**
     * @description Disables file inputs while the form is in preview mode
     * @since 1.9
     */
    activatePreviewMode: function () {
        //disable media buttons in preview mode
        if (window.hasOwnProperty('cred_form_preview_mode') && window.cred_form_preview_mode == true) {
            jQuery('#insert-media-button').prop('disabled', true);
            jQuery('.insert-media').prop('disabled', true);
            jQuery('.cred-form input[type="file"]').attr('onclick', 'return false');
            jQuery('.cred-user-form input[type="file"]').attr('onclick', 'return false');

            jQuery(document).on('toolset_repetitive_field_added', function () {
                jQuery('input[type="file"]', $parent).attr('onclick', 'return false');
                jQuery('input[type="file"]', $parent).attr('onclick', 'return false');
            });

        }
    },

    /**
     * Init Color Picker
     *
     * @param $form
     */
    initColorPicker: function ($form) {
        if (typeof(wptColorpicker) !== 'undefined') {
            wptColorpicker.init($form);
        }
    }
};


(function () {
    //Add observable IDs and prepare messages queue
    credFrontEndViewModel.updateFormsPostID();

    //Apply bindings and init ajax requests to update forms
    setTimeout(function () {
        var isPreview = jQuery('#lbl_preview').length > 0;
        if (!isPreview) {
            credFrontEndViewModel.applyViewModelBindings();
        }
        credFrontEndViewModel.initCREDFile();
    }, 200);

    setTimeout(function () {
        credFrontEndViewModel.initQueue();
    }, 300);

    jQuery(document).ready(function () {
        credFrontEndViewModel.addAccessibilityIDs();
        credFrontEndViewModel.activatePreviewMode();

        /**
         * @description: JS code to fix attachment post_id when media upload (up to wysiwyg fields) is opened
         * Each media attached will referrer to the post_id created by the cred form
         * @since 1.9.1
         */
        jQuery(document.body).on('click', 'form.cred-form .wp-media-buttons > .button.insert-media.add_media, form.cred-user-form .wp-media-buttons > .button.insert-media.add_media', function () {
            if (wp && wp.hasOwnProperty('media')) {
                var $current_form = jQuery(this).closest('form');
                var current_cred_form_post_id = jQuery("input[name='_cred_cred_prefix_post_id']", $current_form).val();
                if ($current_form
                    && current_cred_form_post_id
                    && wp.media.model.settings.post.id !== current_cred_form_post_id
                ) {
                    wp.media.model.settings.post.id = current_cred_form_post_id;
                }
            }
        });
    });

    //Once the cred form is ready
    jQuery(document).on('cred_form_ready', function (evt, form_data) {
        var $form = jQuery("#" + form_data.form_id);

        //uncheck generic checkboxes
        jQuery('input[type="checkbox"][cred_generic="1"]').each(function (index, checkbox) {
            if (jQuery(checkbox).attr('default_checked') != 1) {
                jQuery(checkbox).prop('checked', false);
            } else {
                jQuery(checkbox).prop('checked', true);
            }
        });

        //Queue after conditional and validation init
        setTimeout(function () {
            jQuery('.form-submit', $form).attr('disabled', false);
        }, 4);

        credFrontEndViewModel.initColorPicker($form);

        $form.on('submit', function () {
            //If recaptcha is not valid stops the submit
            if (!credFrontEndViewModel.handleReCAPTCHAErrorMessage(jQuery(this))) {
                return false;
            }
        });

    });

    jQuery( document ).on( 'js_event_wpv_pagination_completed js_event_wpv_parametric_search_results_updated', function( event, data ) {
        jQuery( '.cred-form, .cred-user-form', data.layout ).each( function() {
            var $form = jQuery( this );
            credFrontEndViewModel.initColorPicker( $form );
            credFrontEndViewModel.reloadTinyMCE( $form );
            credFrontEndViewModel.tryToReloadReCAPTCHA( $form );
        });
    });

    //bounding onAjaxFormSubmit
    var boundOnAjaxFormSubmit = _.bind(credFrontEndViewModel.onAjaxFormSubmit, credFrontEndViewModel);
    //After Ajax submit form call is completed (with success or error)
    Toolset.hooks.addAction('cred_form_ajax_completed', boundOnAjaxFormSubmit);

    //bounding onValidatedSubmitAjaxForm
    var boundOnValidatedSubmitAjaxForm = _.bind(credFrontEndViewModel.onValidatedSubmitAjaxForm, credFrontEndViewModel);
    //After cred form submit validation success
    Toolset.hooks.addAction('toolset-form-onsubmit-validation-success', boundOnValidatedSubmitAjaxForm);

    //bounding boundDisableSubmitForm
    var boundDisableSubmitForm = _.bind(credFrontEndViewModel.disableSubmitForm, credFrontEndViewModel);
    //If Form is ajax disable submit button on toolset-ajax-submit event
    Toolset.hooks.addAction('toolset-ajax-submit', boundDisableSubmitForm);

})();

//Method recaptcha callback
var onLoadRecaptcha = function () {
    //Init of all recaptcha
    jQuery.each(jQuery('.g-recaptcha'), function (i, recaptcha_selector) {
        var $current_form = jQuery(recaptcha_selector).closest('form');
        credFrontEndViewModel.tryToReloadReCAPTCHA($current_form);
    });
};