<?php

/*
*   Access_Database_Erase
*
*/

final class Access_Database_Erase
{
     public static function init() {
        add_action( 'init', array( __CLASS__, 'toolset_access_database_erase_init' ), 10 );
        add_action( 'toolset_enqueue_scripts', array( __CLASS__, 'toolset_enqueue_scripts' ) );
     }

     public static function toolset_access_database_erase_init(){
        global $wpcf_access;
        $settings = $wpcf_access->settings;
        if ( !empty($settings->types) || !empty($settings->tax) || !empty($settings->third_party) ){
            add_filter( 'toolset_filter_toolset_register_settings_section',	array( __CLASS__, 'register_settings_access_database_erase_section' ), 201 );
		    add_filter( 'toolset_filter_toolset_register_settings_access-database-erase_section',	array( __CLASS__, 'toolset_access_database_erase_section_content' ) );
        }
     }

     public static function register_settings_access_database_erase_section( $sections ) {
        $sections['access-database-erase'] = array(
            'slug'	=> 'access-database-erase',
            'title'	=> __( 'Access', 'wpcf-access' )
        );
        return $sections;
     }

     public static function toolset_access_database_erase_section_content( $sections ){

        $section_content = self::generate_section_content();
        $sections['access-database-erase-tool'] = array(
			'slug'		=> 'access-database-erase-tool',
			'title'		=> __( 'Reset Access settings', 'wpcf-access' ),
			'content'	=> $section_content
		);

		return $sections;
     }

     public static function generate_section_content(){
        $output = '<div class="js-toolset-access-erase-message-before-start">
            '. __("You can reset all Access settings here.", 'wpcf-access') .'<br>
            '. __("Have in mind that this action cannot be undone and you should create a copy of your database first.", 'wpcf-access') .'
            <p>
                <button class="button js-toolset-access-agree-erase-access-settings toolset-access-agree-erase-access-settings">'. __("Remove Access settings from my database", 'wpcf-access') .'</button>
            </p>
        </div>';

        $roles = Access_Helper::wpcf_get_editable_roles();
        $users_count = count_users();
        $access_roles = $access_roles_names = array();
        $total_users_to_reassign = 0;
        foreach( $roles as $role => $role_data ){
            if ( isset($role_data['capabilities']['wpcf_access_role']) ){
                $access_roles[] = $role;
                $access_roles_names[] = $role_data['name'];
                if ( isset($users_count['avail_roles'][$role]) ){
                    $total_users_to_reassign += $users_count['avail_roles'][$role];
                }
            }
        }
        $output .= '<div class="js-toolset-access-erase_database" style="display:none;"><table class="toolset-access-misc-form-process">
            <tr>
                <td><input type="checkbox" id="js-toolset-access-misc-remove-settings" value="1"></td>
                <td><label for="js-toolset-access-misc-remove-settings">'. __('Remove Access settings from the database', 'wpcf-access') .'</label></td>
            </tr>';

            if ( count($access_roles) > 0 ){
                $output .= '<tr>
                    <td><input type="checkbox" id="js-toolset-access-misc-remove-roles" value="1"></td>
                    <td><label for="js-toolset-access-misc-remove-roles">'. __('Remove Access custom roles', 'wpcf-access') .'</label></td>
                </tr>
                <tr class="js-toolset-access-misc-existing-users hidden">
                    <td colspan="2" style="padding-left:26px;">
                        '. __('These roles will be removed', 'wpcf-access') .': '. implode( ', ', $access_roles_names ) .'<br>
                        '. __('Total users to reassign', 'wpcf-access') .': '. $total_users_to_reassign .'
                    </td>
                </tr>';
            }

            if ( $total_users_to_reassign > 0 ){
                $output .= '<tr class="js-toolset-access-misc-reasign-users  hidden">
                    <td colspan="2" style="padding-left:26px;">'. __('Assign existing users to', 'wpcf-access') .': 
                    <select>
                        <option value="">'. __('Select Role ', 'wpcf-access') .'</option>';
                        foreach( $roles as $role => $role_data ){
                            if ( !isset($role_data['capabilities']['wpcf_access_role']) ){
                                $output .= '<option value="'. $role .'">'. $role .'</option>';
                            }
                        }
                $output .= '
                    </select>
                    <input type="hidden" value="'. $total_users_to_reassign .'" class="js-toolset-access-misc-total-users">
                    <input type="hidden" value="0" class="js-toolset-access-misc-total-users-processed">
                    </td>
                </tr>';
            }
            $output .= '<tr>
                <td><input type="checkbox" id="js-toolset-access-misc-disable-plugin" value="1"></td>
                <td><label for="js-toolset-access-misc-disable-plugin">'. __('Deactivate Toolset Access plugin', 'wpcf-access') .'</label></td>
            </tr>           
            <tr>
                <td colspan="2">
                    <p><button class="button js-toolset-access-misc-start toolset-access-misc-start">'. __('Reset Access settings', 'wpcf-access') .'</button><br>
                    '.__('Remember that this action cannot be undone', 'wpcf-access').'</p>
                    <div class="js-error-container"></div>
                </td>
            </tr>
        </table>
        <p class="js-toolset-access-misc-spiner"></p>
        </div>';

        $output .= wp_nonce_field('wpcf-access-edit', 'wpcf-access-edit', true, false);

        return $output;
     }

     public static function toolset_enqueue_scripts( $current_page ){

        switch ( $current_page ) {
            case 'toolset-settings':
                TAccess_Loader::loadAsset('STYLE/wpcf-access-dev', 'wpcf-access');
                TAccess_Loader::loadAsset('SCRIPT/wpcf-access-settings', 'wpcf-access');
                break;
        }
    }
}