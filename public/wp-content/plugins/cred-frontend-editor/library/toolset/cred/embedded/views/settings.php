<?php
/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/views/settings.php $
 * $LastChangedDate: 2015-03-03 15:08:45 +0100 (mar, 03 mar 2015) $
 * $LastChangedRevision: 32049 $
 * $LastChangedBy: francesco $
 *
 * @todo this is DEPRECATED and needs to be REMOVED
 */
if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}
$results = '';
$user_results = '';
$cred_import_file = null;
$show_generic_error = false;
if (isset($_POST['import']) && $_POST['import'] == __('Import', 'wp-cred') &&
        isset($_POST['cred-import-nonce']) &&
        wp_verify_nonce($_POST['cred-import-nonce'], 'cred-import-nonce')) {
    if (isset($_FILES['import-file'])) {
        $cred_import_file = $_FILES['import-file'];
        if ($cred_import_file['error'] > 0) {
            $show_generic_error = true;
            $cred_import_file = null;
        }
    } else {
        $cred_import_file = null;
    }

    if ($cred_import_file !== null && !empty($cred_import_file)) {
        $options = array();
        if (isset($_POST["cred-overwrite-forms"]))
            $options['overwrite_forms'] = 1;
        if (isset($_POST["cred-overwrite-settings"]))
            $options['overwrite_settings'] = 1;
        if (isset($_POST["cred-overwrite-custom-fields"]))
            $options['overwrite_custom_fields'] = 1;
        CRED_Loader::load('CLASS/XML_Processor');
        $results = CRED_XML_Processor::importFromXML($cred_import_file, $options);
    }
}

if (isset($_POST['import']) && $_POST['import'] == __('Import', 'wp-cred') &&
        isset($_POST['cred-user-import-nonce']) &&
        wp_verify_nonce($_POST['cred-user-import-nonce'], 'cred-user-import-nonce')) {
    if (isset($_FILES['import-file'])) {
        $cred_import_file = $_FILES['import-file'];

        if ($cred_import_file['error'] > 0) {
            $show_generic_error = true;
            $cred_import_file = null;
        }
    } else {
        $cred_import_file = null;
    }

    if ($cred_import_file !== null && !empty($cred_import_file)) {
        $options = array();
        if (isset($_POST["cred-overwrite-forms"]))
            $options['overwrite_forms'] = 1;
        if (isset($_POST["cred-overwrite-settings"]))
            $options['overwrite_settings'] = 1;
        if (isset($_POST["cred-overwrite-custom-fields"]))
            $options['overwrite_custom_fields'] = 1;
        CRED_Loader::load('CLASS/XML_Processor');
        $user_results = CRED_XML_Processor::importUserFromXML($cred_import_file, $options);
    }
}

$settings_model = CRED_Loader::get('MODEL/Settings');

//$url = admin_url('admin.php') . '?page=CRED_Settings';
$url = admin_url('admin.php') . '?page=toolset-settings';
$doaction = isset($_POST['cred_settings_action']) ? $_POST['cred_settings_action'] : false;

$settings = $settings_model->getSettings();
if ($doaction) {
    check_admin_referer('cred-settings-action', 'cred-settings-field');
    switch ($doaction) {
        case 'edit':
            $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : array();
            if (!isset($settings['wizard']))
                $settings['wizard'] = 0;
            $settings_model->updateSettings($settings);
            break;
    }
    // CRED_PostExpiration
    do_action('cred_settings_action', $doaction, $settings);
}
?>
<div class="wrap">
    <h1><?php _e('Settings', 'wp-cred') ?>

        <a class="cred-help-link" title="<?php echo esc_attr(CRED_CRED::$help['add_forms_to_site']['text']); ?>" href="<?php echo CRED_CRED::$help['add_forms_to_site']['link']; ?>" target="_blank">
            <i class="fa fa-question-circle"></i>
        </a>

    </h1>
    <br />
    <!-- use WP Tabs here -->
    <!--    <h2 class="nav-tab-wrapper">
            <a class='nav-tab' href="#cred-general-settings"><?php //_e('General Settings','wp-cred');                                       ?></a>
            <a class='nav-tab' href="#cred-import"><?php //_e('Import','wp-cred');                                        ?></a>
        </h2>
        <a id="cred-general-settings"></a>-->
    <form method="post" action="">
        <?php wp_nonce_field('cred-settings-action', 'cred-settings-field'); ?>
        <table id="cred_general_settings_table" class="cred-widefat">

            <tbody>
                <tr>
                    <td>
                        <table style="margin-left:-18px;">
                            <tr>
                                <td width="20%">
                                    <strong><?php _e('Toolset Forms Wizard', 'wp-cred'); ?></strong>
                                </td>
                                <td>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[wizard]" value="1" <?php if (isset($settings['wizard']) && $settings['wizard']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Create new forms using the Toolset Forms Wizard', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <strong><?php _e('Export settings', 'wp-cred'); ?></strong>
                                </td>
                                <td>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[export_settings]" value="1" <?php if (isset($settings['export_settings']) && $settings['export_settings']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Export also settings when exporting Toolset Forms', 'wp-cred'); ?></span></label>
                                    <div style="clear: both;"></div>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[export_custom_fields]" value="1" <?php if (isset($settings['export_custom_fields']) && $settings['export_custom_fields']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Export also Custom Fields when exporting Toolset Forms', 'wp-cred'); ?></span></label>

                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <strong><?php _e('Styling', 'wp-cred'); ?></strong>
                                </td>
                                <td>
									<?php
	                                /* Toolset Forms 1.8.8 back compatibility */
	                                if ( isset( $settings['use_bootstrap'] ) )
	                                { ?>
                                        <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[use_bootstrap]" value="1" <?php if ( isset( $settings['use_bootstrap'] ) && $settings['use_bootstrap'] ) {
				                                echo "checked='checked'";
			                                } ?> /><span class='cred-checkbox-replace'></span>
                                            <span><?php _e( 'Use bootstrap in Toolset Forms', 'wp-cred' ); ?></span></label>
                                        <div style="clear: both;"></div>
	                                <?php } ?>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[dont_load_bootstrap_cred_css]" value="1" <?php checked( $settings['dont_load_bootstrap_cred_css'], 1, true ); ?> /><span class='cred-checkbox-replace'></span>
                                            <span><?php _e('Do not load Toolset Forms style sheets on front-end', 'wp-cred'); ?></span></label>
                                    <div style="clear: both;"></div>
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[dont_load_cred_css]" value="0" <?php checked( $settings['dont_load_cred_css'], 0, true ); ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Load Toolset Forms legacy style sheets on front-end (used for old Toolset Forms)', 'wp-cred'); ?></span></label>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <strong><?php _e('Other settings:', 'wp-cred'); ?></strong>
                                </td>
                                <td>                                    
                                    <label class='cred-label'><input type="checkbox" class='cred-checkbox-invalid' name="settings[syntax_highlight]" value="1" <?php if (isset($settings['syntax_highlight']) && $settings['syntax_highlight']) echo "checked='checked'"; ?> /><span class='cred-checkbox-replace'></span>
                                        <span><?php _e('Enable Syntax Highlight for Toolset Forms', 'wp-cred'); ?></span></label>
                                    <div style="clear: both;"></div>
                                    <?php
                                    // CRED_PostExpiration
                                    do_action('cred_pe_general_settings', $settings);
                                    ?>
                                </td>
                            </tr>       

                            <tr>
                                <td>
                                    <strong><?php _e('reCAPTCHA API', 'wp-cred'); ?></strong>
                                </td>
                                <td>
                                    <table style="margin-left:-10px;">
                                        <tr>
                                            <td colspan="3">
                                                <p>
                                                    <?php _e('If you are willing to use reCAPTCHA to protect your Toolset Forms against bots\' entries please provide public and private keys for reCAPTCHA API:', 'wp-cred'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="20%"><?php _e('Secret Key', 'wp-cred'); ?></td>
                                            <td width="5%"><input type="text" size='50' name="settings[recaptcha][private_key]" value="<?php if (isset($settings['recaptcha']['private_key'])) echo $settings['recaptcha']['private_key']; ?>"  /></td>
                                            <td></td>
                                        </tr>        
                                        <tr>
                                            <td><?php _e('Site Key', 'wp-cred'); ?></td>
                                            <td><input type="text" size='50' name="settings[recaptcha][public_key]" value="<?php if (isset($settings['recaptcha']['public_key'])) echo $settings['recaptcha']['public_key']; ?>"  /></td>
                                            <td></td>
                                        </tr>        
                                        <tr>
                                            <td></td>
                                            <td align="right">
                                                <?php _e('Do not have reCAPTCHA API Keys?', 'wp-cred'); ?>
                                                <div style="clear: both;"></div>
                                                <a target="_blank" href='https://www.google.com/recaptcha/admin#whyrecaptcha'><?php _e('Sign Up to use reCAPTCHA API', 'wp-cred'); ?></a>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <strong><?php _e('Content Filter', 'wp-cred'); ?></strong>
                                </td>
                                <td>
                                    <?php _e('Toolset Forms filters the content that is submitted by a form.', 'wp-cred'); ?>
                                    <div style="clear: both;"></div>
                                    <div style="margin: 5px;">
                                        <input type="button" name="show_hide_allowed_tags" onclick="cred_settings_check_allowed_popup()" value="<?php echo esc_attr(__('Select allowed HTML tags', 'wp-cred')); ?>" class="button button-secondary button-large"/>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td colspan=2>
                                    <p>
                                        <input type="hidden" name="cred_settings_action" value="edit" />
                                        <input type="submit" name="submit" value="<?php echo esc_attr(__('Save Changes', 'wp-cred')); ?>" class="button button-primary button-large"/>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="my_allowed_tags" style="display: none;position:absolute;background-color: #fff;height: auto;width:800px;
             top: 10%; left: 20%;">
            <div id="all_checks" style="border:1px #eee solid;">
                <div style="width:96%;margin:10px;">
                    <div style="float: left;"><strong><?php _e('Select allowed HTML tags', 'wp-cred'); ?></strong></div>
                    <div style="float: right;"><strong><a style='text-decoration: none;color:#000;font-weight: bold;' href="javascript:void(0);" onclick="cred_settings_check_allowed_popup()">X</a></strong></div>

                    <div style="width: 100%; display: inline-block; margin: 5px;">
                        <?php
                        $_tags = wp_kses_allowed_html('post');
                        $_tags['select_all'] = 1;

                        if (!isset($settings['allowed_tags'])) {
                            $settings['allowed_tags'] = array();
                            foreach ($_tags as $key => $value) {
                                $settings['allowed_tags'][$key] = isset($settings['allowed_tags'][$key]) ? $settings['allowed_tags'][$key] : 0;
                            }
                        }
                        $allowed_tags = $settings['allowed_tags'];
                        ?>
                        <hr style="color:#eeffff;"/>
                        <div style="margin-top:10px;margin-bottom:10px;">
                            <input type="checkbox" id="check_uncheck_all" size='50' onclick="
                                    if (!this.checked) {
                                        jQuery('#all_checks').find('input:checked:not(#check_uncheck_all)').removeAttr('checked');
                                    } else {
                                        jQuery('#all_checks').find('input:not(#check_uncheck_all)').prop('checked', true);
                                    }" name="settings[allowed_tags][select_all]" value="1"  />
                            <strong id="first_chk"><?php echo "Select all" ?></strong>
                        </div>
                        <hr style="color:#eeffff;"/>
                        <?php
                        $i = 0;
                        $rows = 1;
                        $is_selected = 0;
                        foreach ($_tags as $key => $value) {
                            $checked = (isset($settings['allowed_tags'][$key]) && $settings['allowed_tags'][$key] == 1) ? "checked" : "";
                            if ($checked)
                                $is_selected++;
                            // If we've reached our last row, move over to a new div
                            if ($i > 0) {
                                echo "</div><div style=\"width: 150px; display: inline-block\">";
                            } else
                                echo "<div style=\"width: 150px; display: inline-block\">";

                            if ($key == 'select_all') {
                                
                            } else {
                                ?>
                                <div>
                                    <input <?php echo $checked; ?> type="checkbox" size='50' name="settings[allowed_tags][<?php echo $key; ?>]" value="1"  />
                                    <strong><?php echo $key; ?></strong>
                                </div>
                                <?php
                            }
                            $i++;
                        }

                        if ($is_selected == ($i - 1)) {
                            //If are all un_selected
                            ?>
                            <script>
                                jQuery(document).ready(function () {
                                    jQuery("#check_uncheck_all").prop('checked', true);
                                });
                            </script>
                            <?php
                        } else {
                            ?>
                            <script>
                                jQuery(document).ready(function () {
                                    jQuery("#check_uncheck_all").prop('checked', false);
                                });
                            </script>
                            <?php
                        }


                        echo "</div>";
                        ?>
                        <div style="clear: both;height: 10px;"></div>
                        <div style="clear: both;height: 10px;"></div>
                        <div style="float: left;margin-top:10px;"><strong><a style='text-decoration: none;color:#3399FF;' href="javascript:void(0);" onclick="cred_settings_check_allowed_popup()"><?php _e('Cancel', 'wp-cred'); ?></a></strong></div>
                        <div style="float: right;"><input type="submit" name="submit" value="<?php echo esc_attr(__('Apply', 'wp-cred')); ?>" class="button button-primary button-large"/></div>

                    </div>

                </div>
            </div>
        </div>
    </form>
    <?php
// CRED_PostExpiration
    do_action('cred_ext_metabox_settings', $settings);
    ?>

</div>

<script>
    var cred_settings_check_allowed_popup_visible = false;
    function cred_settings_check_allowed_popup() {
        cred_settings_check_allowed_popup_visible = !cred_settings_check_allowed_popup_visible;
        jQuery('.my_allowed_tags').toggle();
        return;
        if (cred_settings_check_allowed_popup_visible) {
            jQuery('#cred_general_settings_table').css('background-color', '#ddd');
        } else {
            jQuery('#cred_general_settings_table').css('background-color', '#fff');
        }
    }
</script>