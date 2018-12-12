var CREDFileUpload = CREDFileUpload || {};

CREDFileUpload.init = function () {
    var self = this;

    /**
     * @param file_path
     * @returns {string}
     */
    self.credGetFilePathExtension = function (file_path) {
        var found = file_path.lastIndexOf('.') + 1;
        return (found > 0 ? file_path.substr(found) : "");
    };

    /**
     * @param file
     * @returns {boolean}
     */
    self.isImage = function (file) {
        switch (self.credGetFilePathExtension(file)) {
            //if .jpg/.gif/.png do something
            case 'jpg':
            case 'gif':
            case 'png':
            case 'jpeg':
            case 'bmp':
            case 'svg':
                return true;
                break;
        }
        return false;
    };

    /**
     * Update progress bar by id, form selector and progress percentage
     *
     * @param id
     * @param $current_form
     * @param progress
     */
    self.credUpdateProgressBar = function (id, $current_form, progress) {
        jQuery('#' + id + ' .progress-bar', $current_form).css(
            {'width': progress + '%'}
        );
    };

    /**
     * Hide progress bar by id and form selector
     *
     * @param id
     * @param $current_form
     */
    self.credHideProgressBar = function (id, $current_form) {
        self.credUpdateProgressBar(id, $current_form, 0);
        jQuery('#' + id, $current_form).hide();
    };

    self.credFileFuInit = function () {
        jQuery('input[type="file"]:visible', '.cred-form, .cred-user-form').each(self.initFileField);

        jQuery(document).off('click', '.js-wpt-credfile-delete, .js-wpt-credfile-undo', null);
        jQuery(document).on('click', '.js-wpt-credfile-delete, .js-wpt-credfile-undo', function (e) {
            jQuery('input[type="file"]:visible', '.cred-form, .cred-user-form').each(self.initFileField);
        });

        //AddRepetitive add event
        wptCallbacks.addRepetitive.add(function () {
            jQuery('input[type="file"]:visible', '.cred-form, .cred-user-form').each(self.initFileField);
        });

        //AddRepetitive remove event
        wptCallbacks.addRepetitive.remove(function () {
            //console.log("TODO: delete file related before removing")
        });
    };

    /**
     * @param i
     * @param file
     */
    self.initFileField = function (i, file) {
        var current_file = file;
        var url = settings.ajaxurl;
        var nonce = settings.nonce;
        var $current_form = jQuery(current_file).closest('form');
        var $current_file = jQuery(current_file, $current_form);
        var id = $current_file.attr('id');
        var validation = ($current_file.attr('data-wpt-validate')) ? $current_file.attr('data-wpt-validate') : '[]';
        var validationSettings = jQuery.parseJSON(validation);

        var validationMimeTypeArgs = (validationSettings.mime_type && validationSettings.mime_type.args && validationSettings.mime_type.args[0]) ? validationSettings.mime_type.args[0] : '';
        var validationExtensionArgs = (validationSettings.extension && validationSettings.extension.args && validationSettings.extension.args[0]) ? validationSettings.extension.args[0] : '';
        var validationMessage = (validationSettings.mime_type && validationSettings.mime_type.message) ? validationSettings.mime_type.message : '';

        var current_post_id = jQuery("input[name='_cred_cred_prefix_post_id']", $current_form).val();
        var current_form_id = jQuery("input[name='_cred_cred_prefix_form_id']", $current_form).val();

        jQuery(file).fileupload({
            url: url + '?action=' + settings.action + '&id=' + current_post_id + '&formid=' + current_form_id + '&nonce=' + nonce,
            dataType: 'json',
            cache: false,
            maxChunkSize: 0,
            drop: function (e, response) {
                return false
            },
            dragover: function (e) {
                return false
            },
            formData: {id: current_post_id, formid: current_form_id},
            done: function (e, response) {
                //progress bar hiding
                var $file_selector = jQuery('#' + id, $current_form);
                var wpt_id = $file_selector.siblings(".meter").attr("id");
                var $current_field_id = jQuery('#' + wpt_id, $current_form);
                var $progress_bar_selector = jQuery('#' + wpt_id + ' .progress-bar', $current_form);

                $current_field_id.show();
                $progress_bar_selector.css(
                    {'width': '0%'}
                );
                $current_field_id.hide();

                if (response.result.success
                    && response.result.files) {
                    jQuery.each(response.result.files, function (index, file) {
                        var wpt_id = id.replace("_file", "");

                        var hidden_id = wpt_id + '_hidden';
                        var element_number = 0;
                        if (id.toLowerCase().indexOf("wpt-form-el") >= 0) {
                            element_number = id.replace(/[^0-9]/g, '');
                            var new_element_number = element_number - 1;
                            hidden_id = "wpt-form-el" + new_element_number;
                        }

                        var is_repetitive = $file_selector.parent().parent().hasClass("js-wpt-repetitive");
                        if (is_repetitive) {
                            var element_name = wpt_id.replace(element_number, "[" + element_number + "]");
                            jQuery('input[name="' + element_name + '"]#' + wpt_id, $current_form).val(file);
                        } else {
                            jQuery('input[name=' + wpt_id + ']#' + wpt_id, $current_form).val(file);
                        }

                        //hidden text set
                        var $file_hidden_selector = jQuery('#' + hidden_id, $current_form);
                        $file_hidden_selector.val(file);
                        $file_hidden_selector.prop('disabled', false);

                        //file field disabled and hided
                        $file_selector.hide();
                        $file_selector.prop('disabled', true);

                        //remove restore button
                        $file_selector.siblings(".js-wpt-credfile-undo").hide();

                        var preview_span = $file_selector.siblings(".js-wpt-credfile-preview");
                        var $preview_span_selector = jQuery(preview_span, $current_form);
                        jQuery('.wpt-form-error', preview_span.parent()).remove();

                        //add image/file uploaded and button to delete
                        if (self.isImage(file)
                            && response.result.previews) {
                            var preview = response.result.previews[index];
                            var attachid = response.result.attaches[index];

                            if (typeof preview_span !== 'undefined') {
                                $preview_span_selector.find('.js-wpt-uploaded-file').remove();
                                if ($preview_span_selector.find("img").length > 0 &&
                                    $preview_span_selector.find("input").length > 0) {
                                    $preview_span_selector.find("img").attr("src", preview);
                                    $preview_span_selector.find("input").attr("rel", preview);
                                } else {
                                    //append new image and delete button to the span
                                    jQuery("<img id='loaded_" + wpt_id + "' src='" + preview + "'>", $current_form).prependTo(preview_span);
                                }

                                if ($file_hidden_selector.attr('name') === '_featured_image') {
                                    var _featured_image_name = $file_hidden_selector.attr('name');
                                    if (jQuery("#attachid_" + _featured_image_name, $current_form).lenght > 0) {
                                        jQuery("#attachid_" + _featured_image_name, $current_form).attr("value", attachid);
                                    } else {
                                        jQuery("<input id='attachid_" + _featured_image_name + "' name='attachid_" + _featured_image_name + "' type='hidden' value='" + attachid + "'>", $current_form).appendTo(preview_span.parent());
                                    }
                                }
                            }

                        } else {
                            $preview_span_selector.find('.js-wpt-uploaded-file').remove();
                            $preview_span_selector.find('img').remove();
                            jQuery("<a class='js-wpt-uploaded-file' id='loaded_" + wpt_id + "' href='" + file + "' target='_blank'>" + file + "</a>", $current_form).prependTo(preview_span);
                        }

                        if (typeof preview_span !== 'undefined') {
                            $preview_span_selector.show();
                        }

                        wptCredfile.init('body');
                    });

                    self.credFileFuInit();
                } else {
                    alert(response.result.message);
                }
            },
            add: function (e, data) {
                if (data) {
                    if (validationMimeTypeArgs
                        && validationExtensionArgs) {
                        var uploadErrors = [];
                        var wpt_id = id.replace("_file", "");
                        var $file_selector = jQuery('#' + id, $current_form);
                        var file_mime_type = data.originalFiles[0]['type'];
                        var file_extension = self.credGetFilePathExtension(data.originalFiles[0]['name']);
                        var allowed_mime_types = validationMimeTypeArgs.split('|');
                        var allowed_extensions = validationExtensionArgs.split('|');

                        //file uploaded mime_type and extension validation
                        if (jQuery.inArray(file_mime_type, allowed_mime_types) === -1
                            && jQuery.inArray(file_extension, allowed_extensions) === -1) {
                            var mime_type_information_add_on = "";
                            if ('' !== file_mime_type) {
                                mime_type_information_add_on = " (the type of file that you uploaded is " + file_mime_type + ")";
                            }
                            uploadErrors.push(validationMessage + mime_type_information_add_on);
                        }

                        //check file size at runtime
                        if (data.originalFiles[0].size
                            && data.originalFiles[0].size > settings.max_upload_size) {
                            uploadErrors.push(settings.failed_upload_too_large_file_alert_text + settings.human_readable_max_upload_size);
                        }

                        //check file errors
                        if (uploadErrors.length > 0) {
                            self.credHideProgressBar('progress_' + wpt_id, $current_form);
                            alert(uploadErrors.join("\n"));
                        } else {
                            data.submit();
                        }
                    } else {
                        data.submit();
                    }
                } else {
                    alert(settings.failed_upload_generic_alert_text);
                }
            },
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                var $file_selector = jQuery('#' + id, $current_form);
                var wpt_id = $file_selector.siblings(".meter").attr("id");
                var $current_field = jQuery('#' + wpt_id, $current_form);
                $current_field.show();
                self.credUpdateProgressBar(wpt_id, $current_form, progress)
            },
            fail: function (e, data) {
                var $file_selector = jQuery('#' + id, $current_form);
                var next_id = $file_selector.next('.meter').attr('id');
                self.credHideProgressBar(next_id, $current_form);
                alert(settings.failed_upload_generic_alert_text);
            }
        }).prop('disabled', !jQuery.support.fileInput)
            .parent().addClass(jQuery.support.fileInput ? undefined : 'disabled');

        jQuery(document).bind('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    };

    //Fix the not visible field under false conditional
    jQuery(document).off('click', 'input[type="file"]', null);
    jQuery(document).on('click', 'input[type="file"]', function () {
        self.credFileFuInit();
    });

    self.credFileFuInit();
};

jQuery(document).ready(function () {
    var credFileUpload = new CREDFileUpload.init();
});
