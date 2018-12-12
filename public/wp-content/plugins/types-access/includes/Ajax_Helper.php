<?php
final class Access_Ajax_Helper
{
    public static function init()
    {
        
        /*
         * AJAX calls.
         */
        add_action( 'wp_ajax_wpcf_access_save_settings',					array( __CLASS__, 'wpcf_access_save_settings' ) );// @deprecated
		add_action( 'wp_ajax_wpcf_access_save_settings_section',			array( __CLASS__, 'wpcf_access_save_settings_section' ) );
        add_action( 'wp_ajax_wpcf_access_ajax_reset_to_default',			array( __CLASS__, 'wpcf_access_ajax_reset_to_default' ) );
        add_action( 'wp_ajax_wpcf_access_suggest_user',						array( __CLASS__, 'wpcf_access_wpcf_access_suggest_user_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_ajax_set_level',					array( __CLASS__, 'wpcf_access_ajax_set_level' ) );
        add_action( 'wp_ajax_wpcf_access_add_role',							array( __CLASS__, 'wpcf_access_add_role_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_delete_role',						array( __CLASS__, 'wpcf_access_delete_role_ajax' ) );
		add_action( 'wp_ajax_wpcf_access_show_error_list',					array( __CLASS__, 'wpcf_access_show_error_list_ajax' ) );
		add_action( 'wp_ajax_wpcf_access_add_new_group_form',				array( __CLASS__, 'wpcf_access_add_new_group_form_ajax' ) );
		add_action( 'wp_ajax_wpcf_process_new_access_group',				array( __CLASS__, 'wpcf_process_new_access_group_ajax' ) );
		add_action( 'wp_ajax_wpcf_process_modify_access_group',				array( __CLASS__, 'wpcf_process_modify_access_group_ajax' ) );
		add_action( 'wp_ajax_wpcf_remove_group',							array( __CLASS__, 'wpcf_remove_group_ajax' ) );
		add_action( 'wp_ajax_wpcf_remove_group_process',					array( __CLASS__, 'wpcf_remove_group_process_ajax' ) );
		add_action( 'wp_ajax_wpcf_search_posts_for_groups',					array( __CLASS__, 'wpcf_search_posts_for_groups_ajax' ) );
		add_action( 'wp_ajax_wpcf_remove_postmeta_group',					array( __CLASS__, 'wpcf_remove_postmeta_group_ajax' ) );
		add_action( 'wp_ajax_wpcf_select_access_group_for_post',			array( __CLASS__, 'wpcf_select_access_group_for_post_ajax' ) );
		add_action( 'wp_ajax_wpcf_process_select_access_group_for_post',	array( __CLASS__, 'wpcf_process_select_access_group_for_post_ajax' ) );
		add_action( 'wp_ajax_wpcf_access_change_role_caps',					array( __CLASS__, 'wpcf_access_change_role_caps_ajax' ) );
		add_action( 'wp_ajax_wpcf_process_change_role_caps',				array( __CLASS__, 'wpcf_process_change_role_caps_ajax' ) );
		add_action( 'wp_ajax_wpcf_access_show_role_caps',					array( __CLASS__, 'wpcf_access_show_role_caps_ajax' ) );
		add_action( 'wp_ajax_wpcf_create_new_cap',							array( __CLASS__, 'wpcf_create_new_cap' ) );
		add_action( 'wp_ajax_wpcf_delete_cap',								array( __CLASS__, 'wpcf_delete_cap' ) );
        add_action( 'wp_ajax_wpcf_hide_max_fields_message',					array( __CLASS__, 'wpcf_hide_max_fields_message' ) );
        add_action( 'wp_ajax_wpcf_access_delete_role_form',					array( __CLASS__, 'wpcf_access_delete_role_form' ) );
        add_action( 'wp_ajax_wpcf_access_change_advanced_mode',				array( __CLASS__, 'wpcf_access_change_advanced_mode_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_create_wpml_group_dialog',			array( __CLASS__, 'wpcf_access_create_wpml_group_dialog_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_wpml_group_save',					array( __CLASS__, 'wpcf_access_wpml_group_save_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_load_permission_table',			array( __CLASS__, 'wpcf_access_load_permission_table' ) );
        add_action( 'wp_ajax_toolset_access_specific_users_popup',			array( __CLASS__, 'toolset_access_specific_users_popup_ajax' ) );
        add_action( 'wp_ajax_toolset_access_suggest_users',					array( __CLASS__, 'toolset_access_suggest_users_ajax' ) );
        add_action( 'wp_ajax_toolset_access_add_specific_users_to_settings',					array( __CLASS__, 'toolset_access_add_specific_users_to_settings_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_save_section_status',					array( __CLASS__, 'wpcf_access_save_section_status_ajax' ) );
        add_action( 'wp_ajax_wpcf_access_clean_up_database',					array( __CLASS__, 'wpcf_access_clean_up_database_ajax' ) );

        
        if ( class_exists('WPDD_Layouts_Users_Profiles') && !method_exists('WPDD_Layouts_Users_Profiles','wpddl_layouts_capabilities') ){
            add_filter('wpcf_access_custom_capabilities', 'wpcf_access_layouts_capabilities', 12);
        }
        add_filter('wpcf_access_custom_capabilities', 'wpcf_access_general_capabilities', 9);
        add_filter('wpcf_access_custom_capabilities', 'wpcf_access_wpml_capabilities', 10);
        add_filter('wpcf_access_custom_capabilities', 'wpcf_access_woocommerce_capabilities', 13);
        add_filter('wpcf_access_custom_capabilities', 'wpcf_access_access_capabilities', 11);

    }

    /**
    * Hide mewssage about input fields limit
    */
    
    public static function wpcf_hide_max_fields_message()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
        if (
            isset($_POST['_wpnonce']) &&
            wp_verify_nonce($_POST['_wpnonce'], 'wpcf-access-edit')
        )
        {
            update_option('wpcf_hide_max_fields_message', 1);
        }
        die();
    }
    
    /*
	 * Delete role confirmation dialog
	 */
	public static function wpcf_access_delete_role_form(){

        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
        $output = '';

        if ( !isset($_POST['role']) || empty($_POST['role']) ){
            die();
        }
        $role = $_POST['role'];

        if ( !class_exists('Access_Admin_Edit') ){
            require_once('Admin_Edit.php');
        }

			$output .= '<div class="wpcf-access-reassign-role-popup">';
			$users = get_users('role=' . $role . '&number=5');
			$users_txt = array();
			foreach ($users as $user)
			{
				$users_txt[] = $user->display_name;
			}
			if (!empty($users))
			{
				$users_txt = implode('</li><li> ', $users_txt);
				$output .= sprintf(__('Assign current %s users to another role: ',
								'wpcf-access'), '<ul><li>' . $users_txt . '</li></ul>');
				$output .= Access_Admin_Edit::wpcf_access_admin_roles_dropdown(Access_Helper::wpcf_get_editable_roles(),
						'wpcf_reassign', array(),
						__('-- Select role --', 'wpcf-access'), true, array($role));
			} else {
				$output .= '<input type="hidden" name="wpcf_reassign" class="js-wpcf-reassign-role" value="ignore" />';
				$output .= '<strong>'. __('Do you really want to remove this role?', 'wpcf-access') .'</strong>';
			}
			$output .= '</div> <!-- .wpcf-access-reassign-role-popup -->';

			$output = '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
					<div class="toolset-access-alarm-wrap-right">'. $output .'</div>';
		echo $output;
		die();
	}
    
    /**
    * Show WPML Settings
    */
    public static function wpcf_access_create_wpml_group_dialog_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
        if (!isset($_POST['wpnonce'])
                || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
              
        $group_name = $group_info = $original_name = $group_nice = '';
        $exclude_languages = $include_languages = $disabled_language_list = array();
        $model = TAccess_Loader::get('MODEL/Access');
        $settings_access = $model->getAccessTypes();
        $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
        $action = 'add';
        $output = '';
        if ( isset($_POST['group_id']) && !empty($_POST['group_id']) ){
            $action = 'modify';
            $group_info = $settings_access[$_POST['group_id']];
            $group_name = $original_name = $group_info['title'];
            $exclude_languages = isset($group_info['languages'])?$group_info['languages']:array();
        }
       
        foreach ( $settings_access as $group_slug => $group_data) {
            if ( strpos($group_slug, 'wpcf-wpml-group-') !== 0 ) {
              continue;
            }
            if ( $_POST['group_id'] == $group_slug ){
               continue;
            }
            if ( isset($group_data['languages']) && !empty($group_data['languages']) ){
                foreach ( $group_data['languages'] as $lang => $lang_active) {                    
                    $disabled_language_list[$group_data['post_type']][$lang] = 1;
                }
            }
        }

        $output .= '
            <input type="hidden" value="'. $_POST['group_id'] .'" id="wpcf-access-wpml-group-nice">
			<input type="hidden" value="'. $action .'" id="wpcf-access-group-action">
            <input type="hidden" value="'. ( isset($_POST['group_id']) && !empty($_POST['group_id']) ? $_POST['group_id'] : '' ) .'" id="wpcf-access-group-id">
            <input type="hidden" value="'. esc_js(json_encode($disabled_language_list)) .'" id="wpcf-wpml-group-disabled-languages">';

        $managed_post_types = array();
        $output .= '<h3>'.  __('Post Type', 'wpcf-access') .'</h3>'.
            '<select id="wpcf-wpml-group-post-type" '. (isset($group_info['post_type']) ? ' disabled="disabled"' : '') .'>';
        foreach( $_post_types as $post_type => $post_type_data ){
            if ( ! in_array( $post_type, array( 'attachment', 'cred-form', 'cred-user-form' ) )
                    && apply_filters( 'wpml_is_translated_post_type', null, $post_type )
                    && ( isset( $settings_access[ $post_type ] ) && $settings_access[ $post_type ]['mode'] != 'not_managed' ) ){
                $output .= '<option ' . ( isset( $group_info['post_type'] ) && $group_info['post_type'] == $post_type ? ' selected="selected"' : '' )
                . ' value="' . $post_type . '">' . $post_type_data['labels']['name'] . '</option>';
	            $managed_post_types[] = $post_type;
            }
        }
        $output .= '</select>';

        //Show notification that group post type not managed by Access
        if( isset( $group_info['post_type'] ) && ! in_array( $group_info['post_type'], $managed_post_types ) ) {
	        $message = sprintf(	__( 'WPML group for %s is not active.', 'wpcf-access' ), $group_info['post_type'] );
	        $details = __( 'Set the post type managed by Access in Post Types tab, in order to activate it.', 'wpcf-access' );
	        self::send_group_disabled_message( $message, $details );
        }

        //Show notification that no post types manged by Access
        if( count( $managed_post_types ) === 0 ) {
            $message = __( 'No post types are currently managed by Access.', 'wpcf-access' );
            $details = __( 'Set at least one post type managed by Access in the Post Types tab.', 'wpcf-access' );
            self::send_group_disabled_message( $message, $details );
        }
        
        $output .= '<h3>'.  __('Languages', 'wpcf-access') .'</h3>'.
            '<ul class="wpcf-available-languages">';
        
        $wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
        foreach( $wpml_active_languages as $language => $language_data ){
            $checked = '';
            if ( !empty($group_info) && isset($group_info['languages'][$language]) ){
                $checked = ' checked="checked" ';
            }
            $language_name = ( isset($language_data['translated_name'])?$language_data['translated_name']:$language_data['english_name'] );
            $output .= '<li><label><input type="checkbox" value="'.$language.'" '. $checked .' name="group_language_list"> '.$language_name .'</lable></li>';
        }
        
        $output .= '</ul>';           
        
        echo $output;
        
        die();
    }

	/**
     *
	 * @param $message
	 * @param $details
     *
     * @since 2.4.2
	 */
	private static function send_group_disabled_message( $message, $details ) {
		$output = sprintf(
            '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
            <div class="toolset-access-alarm-wrap-right"><h4>%s</h4>%s</div>
            <input type="hidden" value="1" id="wpcf-access-wpml-group-disabled">',
			esc_html( $message ),
			esc_html( $details )
		);

		die( $output );
	}


	/*
	* Save WPML settings
	*/
    public static function wpcf_access_wpml_group_save_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
        if (!isset($_POST['wpnonce'])
                || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
        
        $model = TAccess_Loader::get('MODEL/Access');
        $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );

		$languages = array();
        $title_languages_array = array();
        $wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
        $settings_access = $model->getAccessTypes();
		if ( isset($_POST['languages']) ) {
            for ($i=0, $count_lang = count($_POST['languages']); $i<$count_lang; $i++){
                $languages[$_POST['languages'][$i]['value']] = 1;
                $title_languages_array[] = $wpml_active_languages[$_POST['languages'][$i]['value']]['translated_name'];
            }
		}
        if(count($title_languages_array)>1)
        {
            $title_languages = implode(', ' , array_slice($title_languages_array,0,count($title_languages_array)-1)) . ' and ' . end($title_languages_array);
        }
        else
        {
            $title_languages = implode(', ' , $title_languages_array);
        }
        
        
        if ( !empty($_POST['group_nice'])){
            $nice = $_POST['group_nice'];
            $_POST['group_name'] = $title_languages .' '. $_post_types[$_POST['post_type']]['labels']['name']; 
        }else{
            $_POST['group_name'] = $title_languages .' '. $_post_types[$_POST['post_type']]['labels']['name'];
            $nice = sanitize_title( 'wpcf-wpml-group-' . md5( $_POST['group_name'] ) );
        }
        if ( isset($settings_access['post']['permissions']['read']['roles']) && 2 == 1 ){
            $read = $settings_access['post']['permissions']['read']['roles'];
            $edit_any = $settings_access['post']['permissions']['edit_any']['roles'];
            $delete_any = $settings_access['post']['permissions']['delete_any']['roles'];
            $edit_own = $settings_access['post']['permissions']['edit_own']['roles'];
            $delete_own = $settings_access['post']['permissions']['delete_own']['roles'];
            $publish = $settings_access['post']['permissions']['publish']['roles'];
        }else{
            TAccess_Loader::load('CLASS/Admin_Edit');
            $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
            
            $edit = $read = array();

            foreach( $ordered_roles as $role => $roles_data ){
                 $option_enabled =  Access_Admin_Edit::toolset_access_check_for_cap( 'read', $roles_data );
                 if ( $option_enabled ){
                    $read[] = $role;
                 }

                 $option_enabled =  Access_Admin_Edit::toolset_access_check_for_cap( 'edit_posts', $roles_data );
                 if ( $option_enabled ){
                    $edit[] = $role;
                 }
            }
            $edit_any = $delete_any = $edit_own = $delete_own = $publish = $edit;
        }
        if ( $_POST['form_action'] == 'add' ){
            $groups[$nice] = array(
                'title' => sanitize_text_field($_POST['group_name']),
                'mode' => 'permissions',
                'permissions' => array(
                    'read'=>array('roles'=>$read),
                    'edit_any'=>array('roles'=>$edit_any),
                    'delete_any'=>array('roles'=>$delete_any),
                    'edit_own'=>array('roles'=>$edit_own),
                    'delete_own'=>array('roles'=>$delete_own),
                    'publish'=>array('roles'=>$publish),
                    ),
                 'languages' => $languages,
                 'post_type' => $_POST['post_type']
                );
        }else{
            $group_id = $_POST['group_id'];
            $settings_access[$group_id]['title'] = sanitize_text_field($_POST['group_name']);
            $settings_access[$group_id]['languages'] = $languages;
            $model->updateAccessTypes( $settings_access );
            echo sanitize_text_field($_POST['group_name']);
            die();
        }
        $process = true;
		if ( 
			! empty( $settings_access ) 
			&& isset( $settings_access[ $nice ] )
		) {
			$process = false;
		}
                
        if ( !$process ){
			echo 'error';
			die();
		}
        
        TAccess_Loader::load('CLASS/Admin_Edit');
		$roles = Access_Helper::wpcf_get_editable_roles();
		$settings_access = array_merge( $settings_access, $groups);
		$model->updateAccessTypes( $settings_access );
        $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
        $wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
        $languages_list = array();
        foreach( $languages as $lang => $lang_data ){
                    $languages_list[] = $wpml_active_languages[$lang]['native_name'];
        }
        if ( $_POST['form_action'] == 'modify' ){
            echo $_POST['group_name'];
            die();
        }
        
			$enabled = true;
			$group['id'] = $nice;


        
		echo $group['id'];
        
        
        die();
    }

    /*
     * Load permission tables
    */
    public static function wpcf_access_load_permission_table() {
        if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'wpcf-access-error-pages' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }
		
        $output = '';
        $section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : '';
        if ( $section == '' ) {
            $section = "post-type";
        }

        if ( ! class_exists('Access_Admin_Edit') ) {
            include('Admin_Edit.php');
        }
		
		switch ( $section ) {
			case 'post-type';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_posts();
				break;
			case 'taxonomy';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_taxonomies();
				break;
			case 'third-party';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_third_party();
				break;
			case 'custom-group';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_custom_groups();
				break;
			case 'wpml-group';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_wpml();
				break;
			case 'custom-roles';
				$output = Access_Admin_Edit::otg_access_get_permission_table_for_custom_roles();
				break;
			default;
				$extra_tabs = apply_filters( 'types-access-tab', array() );
				if ( isset( $extra_tabs[ $section ] ) ) {
					$output .= Access_Admin_Edit::otg_access_get_permission_table_for_third_party( $section );
				}
				break;
		}

		$data = array(
			'output'	=> $output
		);
		wp_send_json_success( $data );
		
    }
    
    /**
     * Change advanced mode true/false
     */
    public static function wpcf_access_change_advanced_mode_ajax() {
		if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'otg_access_general_nonce' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

		TAccess_Loader::load('CLASS/Admin_Edit');
        $advanced_mode = get_option( 'otg_access_advaced_mode', 'false' );
        $new_mode = 'false';
        if ( $advanced_mode === 'false' ) {
            $new_mode = 'true';
        }else{
            $new_mode = 'false';
        }
        update_option('otg_access_advaced_mode', $new_mode);
        
		$data = array(
			'message'	=> Access_Admin_Edit::otg_access_get_permission_table_for_custom_roles()
		);
        wp_send_json_success( $data );
    }

	/**
	 * sanitise and save custom error and custom archive errors
	 *
	 * @since 2.2.3
	 * @param string $key
 	 * @param array $types
	 * @param array $settings_access_types_previous
	 * @param object $model
	 *
	 * @return array
	 */
    public static function toolset_access_update_custom_error( $key, $types, $settings_access_types_previous, $model ){
        if ( ! empty( $types ) ) {
			foreach ( $types as $type => $data ) {
				$type = str_replace( '%', '(-37;-)', $type );
				$type = sanitize_text_field( $type );
				$type = str_replace( '(-37;-)', '%', $type );
				$settings_access_types_previous = self::clean_empty_custom_error( $settings_access_types_previous, $data, $key, $type );
			}
			$model->updateAccessTypes( $settings_access_types_previous );
		}
		return $settings_access_types_previous;
    }

	/**
     * Remove role index from an array if custom error is empty
	 * @param array $settings_access_types_previous
	 * @param array $data
	 * @param string $key
	 * @param string $type
	 *
	 * @return array
	 */
    private static function clean_empty_custom_error( $settings_access_types_previous, $data, $key, $type ) {
	    foreach( $data['permissions']['read'] as $role => $value ) {
		    if ( empty( $value ) ) {
			    unset( $data['permissions']['read'][ $role ] );
		    }
	    }
	    if ( empty( $data['permissions']['read'] ) ){
		    unset( $settings_access_types_previous[ $key ][ $type ] );
	    }else {
		    $settings_access_types_previous[ $key ][ $type ] = $data;
	    }
	    return $settings_access_types_previous;
    }

	/**
     * Saves Access settings by section
     */
    public static function wpcf_access_save_settings_section() {
        if ( ! current_user_can( 'manage_options' ) ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }
		
		if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'otg-access-edit-sections' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

		$model = TAccess_Loader::get('MODEL/Access');

		$access_bypass_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses the same name for singular name and plural name. Access can't control access to this object. Please use a different name for the singular and plural names.", 'wpcf-access')."</p></div>";
		$access_conflict_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default Wordpress capabilities. Access can not manage this entity, try changing entity's name and / or slug", 'wpcf-access')."</p></div>";
		$access_notices = '';
		$_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
		$_taxonomies=Access_Helper::wpcf_object_to_array( $model->getTaxonomies() );

		// start empty
		$settings_access_types_previous = $model->getAccessTypes();
		$settings_access_taxs_previous = $model->getAccessTaxonomies();
		$settings_access_thirdparty_previous = $model->getAccessThirdParty();

		//Update custom errors
		$custom_errors_keys = array(
			'types_access_error_type' => '_custom_read_errors',
			'types_access_error_value' => '_custom_read_errors_value',
			'types_access_archive_error_type' => '_archive_custom_read_errors',
			'types_access_archive_error_value' => '_archive_custom_read_errors_value'
			);
		foreach( $custom_errors_keys as $key => $original_key ){
			if ( isset( $_POST[ $key ]['types'] ) ){
				$settings_access_types_previous = self::toolset_access_update_custom_error( $original_key, $_POST[ $key ]['types'], $settings_access_types_previous, $model );
			}
		}

		// Post Types
		if ( ! empty( $_POST['types_access']['types'] ) ) {
			$caps = Access_Helper::wpcf_access_types_caps_predefined();


			foreach ( $_POST['types_access']['types'] as $type => $data ) {

				$mode = isset( $data['mode'] ) ? $data['mode'] : 'not_managed';

				// Use saved if any and not_managed
				if ( 
					isset( $data['mode'] ) 
					&& ( $data['mode'] == 'not_managed' || $data['mode'] == 'follow' )
					&& isset( $settings_access_types_previous[ $type ] )
				) {
					$data = $settings_access_types_previous[ $type ];
				}
				
				$data['mode'] = $mode;
				if ( strpos( $type, 'wpcf-custom-group-') === 0 ) {
					$data['title'] = $settings_access_types_previous[ $type ]['title'];
				}
				
				if ( strpos( $type, 'wpcf-wpml-group-') === 0 ) {
					$data['title'] = $settings_access_types_previous[ $type ]['title'];
					$data['post_type'] = $settings_access_types_previous[ $type ]['post_type'];
					$data['languages'] = $settings_access_types_previous[ $type ]['languages'];
				}
				if ( !isset($settings_access_types_previous[ $type ]) ){
					$settings_access_types_previous[ $type ] = array();
				}
				$data['permissions'] = Access_Helper::wpcf_access_parse_permissions( $data, $caps, false, $settings_access_types_previous[ $type ] );

				if (
					isset( $_post_types[ $type ]['__accessIsNameValid']) 
					&& ! $_post_types[ $type ]['__accessIsNameValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_bypass_template, __('Post Type','wpcf-access'), $_post_types[$type]['labels']['singular_name'] );
				}

				if (
					isset( $_post_types[ $type ]['__accessIsCapValid'] ) 
					&& ! $_post_types[ $type ]['__accessIsCapValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_conflict_template, __('Post Type','wpcf-access'), $_post_types[$type]['labels']['singular_name'] );
				}
				//$settings_access_types[$type] = $data;
				$settings_access_types_previous[ $type ] = $data;
			}
			
			// update settings
			$model->updateAccessTypes( $settings_access_types_previous );
		}

		// Taxonomies
		$caps = Access_Helper::wpcf_access_tax_caps();
		// when a taxonomy is unchecked, no $_POST data exist, so loop over all existing taxonomies, instead of $_POST data
		foreach ( $_taxonomies as $tax => $_taxdata ) {
			if ( 
				isset( $_POST['types_access']['tax'] ) 
				&& isset( $_POST['types_access']['tax'][ $tax ] )
			) {
				$data = $_POST['types_access']['tax'][ $tax ];

				if ( ! isset( $data['not_managed'] ) ) {
					$data['mode'] = 'not_managed';
				}

				if ( ! isset( $data['mode'] ) ) {
					$data['mode'] = 'permissions';
				}

				$data['mode'] = isset( $data['mode'] ) ? $data['mode'] : 'not_managed';


				// Prevent overwriting
				if ( $data['mode'] == 'not_managed' ) {
					if ( isset( $settings_access_taxs_previous[ $tax ] ) ) {
						$data = $settings_access_taxs_previous[ $tax ];
						$data['mode'] = 'not_managed';
					}
				} elseif ( $data['mode'] == 'follow' ) {
					if ( ! isset( $data['permissions'] ) ) {
						// add this here since it is needed elsewhere
						// and it is missing :P
						$data['permissions'] = Access_Helper::wpcf_get_taxs_caps_default();
					}
					$tax_post_type = '';
					if ( isset( $tax_post_type ) ) {
						$tax_arr = array_values( $_taxdata['object_type'] );
						if ( is_array( $tax_arr ) ) {
							//$tax_post_type = array_shift( $tax_arr );
							for( $i = 0; $i < count( $tax_arr); $i++ ) {
								if (
									isset( $settings_access_types_previous[ $tax_arr[ $i ] ] )
									&& 'permissions' == $settings_access_types_previous [ $tax_arr[ $i ] ]['mode']
								) {
									$tax_post_type = $tax_arr[ $i ];
								}
							}
							if ( empty( $tax_post_type ) ){
								$tax_post_type = $tax_post_type = array_shift( $tax_arr );
							}
						}
					}
					//$tax_post_type = array_shift(array_values($_taxdata['object_type']));
					$follow_caps = array();
					// if parent post type managed by access, and tax is same as parent
					// translate and hardcode the post type capabilities to associated tax capabilties
					if ( isset( $settings_access_types_previous[ $tax_post_type ] )
					     && 'permissions' == $settings_access_types_previous [ $tax_post_type ]['mode'] ) {
						$follow_caps = Access_Helper::wpcf_types_to_tax_caps( $tax, $_taxdata,  $settings_access_types_previous[ $tax_post_type ] );
					}
					//taccess_log(array($tax, $follow_caps));

					if ( ! empty( $follow_caps ) ) {
						$data['permissions'] = $follow_caps;
					} else {
						$data['mode']='not_managed';
						if ( ! empty( $tax_post_type ) ) {
							$access_notices = sprintf(
								__( '%s cannot have same permissions as %s because at least one assigned post type should be managed by Access.', 'wpcf-access' ),
								$_taxonomies[ $tax ]['labels']['singular_name'],
								$_taxonomies['category']['labels']['singular_name']
							);
						} else {
							$access_notices = sprintf(
								__( '%s cannot have same permissions as %s because it should be assigned to at least one post type.', 'wpcf-access' ),
								$_taxonomies[ $tax ]['labels']['singular_name'],
								$_taxonomies['category']['labels']['singular_name']
							);
						}
					}

				}
				if ( !isset($settings_access_taxs_previous[$tax]) ){
					$settings_access_taxs_previous[$tax] = array();
				}
				$data['permissions'] = Access_Helper::wpcf_access_parse_permissions( $data,  $caps, false, $settings_access_taxs_previous[$tax] );

				if (
					isset( $_taxonomies[ $tax ]['__accessIsNameValid'] ) 
					&& ! $_taxonomies[ $tax ]['__accessIsNameValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_bypass_template, __('Taxonomy','wpcf-access'), $_taxonomies[ $tax ]['labels']['singular_name'] );
				}
				if (
					isset( $_taxonomies[ $tax ]['__accessIsCapValid'] ) 
					&& ! $_taxonomies[ $tax ]['__accessIsCapValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_conflict_template, __('Taxonomy','wpcf-access'), $_taxonomies[ $tax ]['labels']['singular_name'] );
				}

				$settings_access_taxs_previous[ $tax ] = $data;

			}

		}
		// update settings
		$model->updateAccessTaxonomies( $settings_access_taxs_previous );
		unset( $settings_access_taxs_previous );

		// 3rd-Party
		if ( ! empty( $_POST['types_access'] ) ) {
			// start empty
			$third_party = $settings_access_thirdparty_previous;
			if ( ! is_array( $third_party ) ) {
				$third_party = array();
			}
			foreach ( $_POST['types_access'] as $area_id => $area_data ) {
				// Skip Types
				if (
					$area_id == 'types' 
					|| $area_id == 'tax'
				) {
					continue;
				}
				if ( 
					! isset( $third_party[ $area_id ] ) 
					|| empty( $third_party[ $area_id ] ) 
				) {
					$third_party[ $area_id ] = array();
				}

				foreach ( $area_data as $group => $group_data ) {
					$group = sanitize_text_field( $group );                        
					// Set user IDs
					if ( !isset($settings_access_thirdparty_previous[$area_id]) ){
						$settings_access_thirdparty_previous[$area_id] = array();
					}
					if ( !isset($settings_access_thirdparty_previous[$area_id][$group]) ){
						$settings_access_thirdparty_previous[$area_id][$group] = array();
					}
					$group_data['permissions'] = Access_Helper::wpcf_access_parse_permissions( $group_data, $caps, true, $settings_access_thirdparty_previous[$area_id][$group] );

					$third_party[ $area_id ][ $group ] = $group_data;
					$third_party[ $area_id ][ $group ]['mode'] = 'permissions';
				}
			}
			// update settings
			$model->updateAccessThirdParty($third_party);
		}

		// Roles
		if ( ! empty( $_POST['roles'] ) ) {
			$access_roles = $model->getAccessRoles();
			foreach ( $_POST['roles'] as $role => $level ) {
				$role = sanitize_text_field( $role );
				$level = sanitize_text_field( $level );
				$role_data = get_role( $role );
				if ( ! empty( $role_data ) ) {
					$level = (int) $level;
					for ( $index = 0; $index < 11; $index++ ) {
						if ( $index <= $level ) {
							$role_data->add_cap( 'level_' . $index, 1 );
						} else {
							$role_data->remove_cap( 'level_' . $index );
						}

						if ( isset( $access_roles[ $role ] ) ) {
							if ( isset( $access_roles[ $role ]['caps'] ) ) {
								if ( $index <= $level ) {
									$access_roles[ $role ]['caps'][ 'level_' . $index ] = true;
								} else {
									unset( $access_roles[ $role ]['caps'][ 'level_' . $index ] );
								}
							}
						}
					}
				}
			}
			$model->updateAccessRoles( $access_roles );
		}
		
		do_action( 'types_access_save_settings' );
		
		$data = array(
			'message' => $access_notices
		);
		wp_send_json_success( $data );
    }

    /**
     * AJAX set levels default call.
     */
    public static function wpcf_access_ajax_set_level() {
		if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'otg_access_general_nonce' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }
        TAccess_Loader::load('CLASS/Admin_Edit');
        $model = TAccess_Loader::get('MODEL/Access');

		$default_caps = getDefaultCaps();
		$default_wordpress_caps = $default_caps[10];

        if ( ! empty( $_POST['roles'] ) ) {
            $access_roles = $model->getAccessRoles();
            foreach ( $_POST['roles'] as $role => $level ) {
                $role = sanitize_text_field($role);
                $level = sanitize_text_field($level);
				$add_caps = array();
				$clone_from = 'subscriber';
				if ( $level == 1){
					$clone_from = 'contributor';
				}
				if ( $level >= 2 && $level < 7 ){
					$clone_from = 'author';
				}
				if ( $level >= 7 && $level < 10 ){
					$clone_from = 'editor';
				}
				if ( $level == 10 ){
					$clone_from = 'administrator';
				}
				$temp_role_data = get_role($clone_from);

				$role_data = get_role($role);
				foreach( $role_data->capabilities as $role_cap => $role_status){
					$role_data->remove_cap($role_cap);
				}

				foreach( $temp_role_data->capabilities as $role_cap => $role_status){
					$role_data->add_cap($role_cap);
				}
				$role_data->add_cap('wpcf_access_role');


                $role_data = get_role($role);

                if (!empty(/*$role*/$role_data))
                {
                    $level = intval($level);
                    for ($index = 0; $index < 11; $index++)
                    {
                        if ($index <= $level) {
                            $role_data->add_cap('level_' . $index, 1);
                        } else {
                            $role_data->remove_cap('level_' . $index);
                        }
                        if (isset($access_roles[$role]))
                        {
                            if (isset($access_roles[$role]['caps']))
                            {
                                if ($index <= $level)
                                {
                                    $access_roles[$role]['caps']['level_' . $index]=true;
                                }
                                else
                                {
                                    unset($access_roles[$role]['caps']['level_' . $index]);
                                }
                            }
                        }
                    }

                }
            }
            $model->updateAccessRoles( $access_roles );
        }
        $data = array(
			'message'	=> Access_Admin_Edit::otg_access_get_permission_table_for_custom_roles()
		);
		wp_send_json_success( $data );
    }


    /**
     * Suggest user AJAX.
     */
    public static function wpcf_access_wpcf_access_suggest_user_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

        if (
            isset($_POST['wpnonce']) &&
            wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')
        )
        {
            global $wpdb;
            $users = array();
            $q = Access_Helper::wpcf_esc_like(trim($_POST['q']));
            $q = Access_Helper::wpcf_esc_like($q);
            $found = $wpdb->get_results("SELECT ID, display_name, user_login FROM $wpdb->users WHERE user_nicename LIKE '%%$q%%' OR user_login LIKE '%%$q%%' OR display_name LIKE '%%$q%%' OR user_email LIKE '%%$q%%' LIMIT 10");
            if (!empty($found)) {
                foreach ($found as $user) {
                    $users[$user->ID] = $user->display_name . ' (' . $user->user_login . ')';
                }
            }
            echo json_encode($users);
        }
        die();
    }

    /**
     * Adds new custom role.
     */
    public static function wpcf_access_add_role_ajax() {
		if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'otg_access_general_nonce' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }
		global $wp_roles;

        TAccess_Loader::load('CLASS/Admin_Edit');
        $model = TAccess_Loader::get('MODEL/Access');
        $access_roles = $model->getAccessRoles();


        $copy_of = 'subscriber';
        if ( isset($_POST['copy_of']) && !empty($_POST['copy_of']) && isset($wp_roles->roles[$_POST['copy_of']]) ){
            $copy_of = $_POST['copy_of'];
        }
        $capabilities['wpcf_access_role'] = true;
        foreach ( $wp_roles->roles[$copy_of]['capabilities'] as $cap => $data ) {
            $capabilities[$cap] = true;
        }
		if ( preg_match("/[\,\&\"\']+/", $_POST['role']) ){
			$data = array(
				'type'		=> 'error',
				'message'	=> __( 'The symbols <b>, & " \'</b> cannot be used in the role name.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
			die();
		}
        $role_slug = str_replace( '-', '_', sanitize_title( $_POST['role'] ) );
        $role_slug = str_replace( array('%', ',', '&', "'", '"'), '', $role_slug );
        $success = add_role( $role_slug, sanitize_text_field( $_POST['role'] ), $capabilities );

		if ( is_null( $success ) ) {
			$data = array(
				'type'		=> 'error',
				'message'	=> __( 'The new role could not be created because that role name is already being used.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
		} else {
            $access_roles[ $role_slug ] = array(
                'name'	=> sanitize_text_field( $_POST['role'] ),
                'caps'	=> $capabilities
            );
            $model->updateAccessRoles( $access_roles );
            Access_Helper::toolset_access_add_role_to_settings( $role_slug, $copy_of );
			$data = array(
				'message'	=> Access_Admin_Edit::otg_access_get_permission_table_for_custom_roles()
			);
			wp_send_json_success( $data );
        }
		die();
    }

    /**
     * Deletes custom role.
     */
    public static function wpcf_access_delete_role_ajax() {
		if ( ! current_user_can('manage_options') ) {
			$data = array(
				'type'		=> 'capability',
				'message'	=> __( 'You do not have permissions for that.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if (
			! isset( $_POST['wpnonce'] )
            || ! wp_verify_nonce( $_POST['wpnonce'], 'otg_access_general_nonce' )
		) {
			$data = array(
				'type'		=> 'nonce',
				'message'	=> __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        }

        if ( in_array( strtolower( trim( $_POST['wpcf_access_delete_role'] ) ), Access_Helper::wpcf_get_default_roles() ) ) {
            $data = array(
				'type'		=> 'capability',
				'message'	=> __( 'That role can not be deleted.', 'wpcf-access' )
			);
			wp_send_json_error( $data );
        } else {
            $delete_role = sanitize_text_field( $_POST['wpcf_access_delete_role'] );
            TAccess_Loader::load('CLASS/Admin_Edit');
            $model = TAccess_Loader::get('MODEL/Access');
            $access_roles = $model->getAccessRoles();
            if ( $_POST['wpcf_reassign'] != 'ignore' ) {
                $users = get_users( 'role=' . $delete_role );
                foreach ( $users as $user ) {
                    $user = new WP_User( $user->ID );
                    $user->add_role( sanitize_text_field( $_POST['wpcf_reassign'] ) );
					$user->remove_role( $delete_role );
                }
            }
            remove_role( $delete_role );
            if ( isset( $access_roles[ $delete_role ] ) ) {
                unset( $access_roles[ $delete_role ] );
            }
            $model->updateAccessRoles( $access_roles );

            $data = array(
				'message'	=> Access_Admin_Edit::otg_access_get_permission_table_for_custom_roles()
			);
			wp_send_json_success( $data );
        }
    }
    
    

	/*
	 * Load list of Errors page (404, Ct, PHP tempaltes)
	 */
	public static function wpcf_access_show_error_list_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if ( !isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages') ) {
            die('verification failed');
        }
	    global $wpcf_access;
	    $types_settings = $wpcf_access->settings->types;
		$post_type = sanitize_text_field($_POST['posttype']);
		$is_archive = sanitize_text_field($_POST['is_archive']);
	    $role = ( isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '' );
		$out = '
			<form method="" id="wpcf-access-set_error_page">
				<input type="hidden" value="'. esc_attr($_POST['access_type']) .'" name="typename">
				<input type="hidden" value="'. esc_attr($_POST['access_value']) .'" name="valuename">';
		if ( $is_archive == 1){
		$out .= '<input type="hidden" value="'. esc_attr($_POST['access_archivetype']) .'" name="archivetypename">
				<input type="hidden" value="'. esc_attr($_POST['access_archivevalue']) .'" name="archivevaluename">';
		}

		$out .= '<h2>'. __('What to display for single-posts when there is no read permission','wpcf-access') .'</h2>';
		$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == '' )?' checked="checked" ':'';
		if ( $_POST['forall'] != 1){
		$out .= '
				<p>
					<label>
						<input type="radio" value="default" name="error_type" class="js-wpcf-access-type"'.$checked.'> '. __('Default error','wpcf-access') .'
					</label>
				</p>';
		}
		$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_404' )?' checked="checked" ':'';
		if ( $_POST['forall'] == 1 && isset($_POST['cur_type']) && $_POST['cur_type'] == '' ){
			$checked = ' checked="checked" ';
		}

		$out .= '
				<p>
					<label>
						<input type="radio" value="error_404" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show 404 - page not found','wpcf-access') .'
					</label>
				</p>';
		if( class_exists('WP_Views') && ! defined('WPDDL_VERSION') ){
			$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_ct' )?' checked="checked" ':'';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_ct" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show Content Template','wpcf-access') .'
					</label>
					<select name="wpcf-access-ct" class="hidden" class="js-wpcf-error-ct-value">
						<option value="">'.__('None','wpcf-access').'</option>';
			$wpv_args = array('post_type' => 'view-template','posts_per_page' => -1,'order' => 'ASC','orderby' => 'title','post_status' => 'publish');
			$content_tempaltes = get_posts( $wpv_args );
			foreach ( $content_tempaltes as $post ) :
				$selected = ( isset($_POST['cur_value']) && $_POST['cur_value'] == $post->ID )?' selected="selected" ':'';
				$out .= '
						<option value="'.esc_attr($post->ID).'"'. $selected .'>'.$post->post_title.'</option>';
			endforeach;
			$out .= '
					</select>
				</p>';
		}

		if( defined('WPDDL_VERSION') ){
			$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == 'error_layouts' ) ? ' checked="checked" ' : '';
			$layouts_settings = Access_Admin_Edit::toolset_access_get_layouts_list();

			if ( ! empty( $layouts_settings ) ){
				$out .= '
					<p>
						<label>
							<input type="radio" value="error_layouts" name="error_type"' . $checked . ' class="js-wpcf-access-type"> ' . __( 'Show Template Layout', 'wpcf-access' ) . '
						</label>
						<select name="wpcf-access-layouts" class="hidden" class="js-wpcf-error-layouts-value">
							<option value="">' . __( 'None', 'wpcf-access' ) . '</option>';
							foreach( $layouts_settings as $id => $layout ){
								$selected = ( isset( $_POST['cur_value'] ) && $_POST['cur_value'] == $id ) ? ' selected="selected" ' : '';
								$out .= '
										<option value="' . esc_attr( $id ) . '"' . $selected . '>' . $layout['post_title'] . '</option>';
							}
							$out .= '
						</select>
					</p>';
			}
		}
		
		$templates = wp_get_theme()->get_page_templates();
		if ( !empty($templates) ){
			$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_php' )?' checked="checked" ':'';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_php" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show Page template','wpcf-access') .'
					</label>
					<select name="wpcf-access-php" class="hidden" class="js-wpcf-error-php-value">
						<option value="">'.__('None','wpcf-access').'</option>';
							foreach ( $templates as $template_name => $template_filename ) {
							   $selected = ( isset($_POST['cur_value']) && $_POST['cur_value'] == $template_filename )?' selected="selected" ':'';
							   $out .= '<option value="'.esc_attr($template_filename).'"'. $selected .'>'.$template_filename.'</option>';
							}
			$out .= '
					</select>
				</p>';
		}

	    if ( strpos( $post_type, 'wpcf-custom-group' ) !== FALSE ) {
		    global $wpcf_access;
		    $link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $wpcf_access->settings->types[ $post_type ]['title'] );
		    $single_post_preview_url = self::get_single_cpt_preview_link( $post_type, 'post-group' );
	    } else if ( strpos( $post_type, 'wpcf-wpml-group' ) !== FALSE ) {
		    global $wpcf_access;
		    $link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $wpcf_access->settings->types[ $post_type ]['title'] );
		    $single_post_preview_url = self::get_single_cpt_preview_link( $post_type, 'wpml-group' );
	    } else {
		    $single_post_preview_url = self::get_single_cpt_preview_link( $post_type, 'post' );
		    $model = TAccess_Loader::get('MODEL/Access');
		    $_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
		    $link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $_post_types[ $post_type ]['labels']['singular_name'] );
	    }
	    if ( ! empty( $single_post_preview_url ) && $_POST['forall'] != 1 && 'not_managed' != $single_post_preview_url ) {
		    //Show preview single post link
		    $out .= '<div class="align-right">
					<a  href="#preview_error" class="js-toolset-access-preview-single" data-role="' . $role . '" 
					data-posttype="' . $post_type . '" data-url="' . esc_attr( $single_post_preview_url ) . '">'
		            . $link_title . '
		            <i class="icon-external-link fa fa-external-link icon-small"></i></a>
				</div>';
	    } else if( empty( $single_post_preview_url ) && $_POST['forall'] != 1 ) {
		    $out .= '<div class="align-right"><a href="" class="toolset-access-disabled-link">' . __( 'No posts for preview', 'wpcf-access' ) . '</a></div>';
	    }

		$show_php_tempaltes = true;

		if ( $is_archive == 1){
			$archive_out = '';
			//Hide php templates
			$show_php_tempaltes = true;
			$out .= '<h2>'. __('What to display for archives when there is no read permission','wpcf-access') .'</h2>';

			if( class_exists('WP_Views') && function_exists('wpv_force_wordpress_archive') && !class_exists('WPDD_Layouts') ){
				global $WPV_view_archive_loop, $WP_Views;

				$show_php_tempaltes = false;

				$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'error_ct' )?' checked="checked" ':'';
				$has_items = wpv_check_views_exists('archive');
				if ( $has_items ){
						$archive_out .= '<p><label>
						<input type="radio" value="error_ct" name="archive_error_type" '.$checked.'class="js-wpcf-access-type-archive">
						'. __('Choose a different WordPress archive for people without read permission','wpcf-access') .'<br />';
						$archive_out .= '</label>';
					$wpv_args = array( // array of WP_Query parameters
						'post_type' => 'view',
						'post__in' => $has_items,
						'posts_per_page' => -1,
						'order' => 'ASC',
						'orderby' => 'title',
						'post_status' => 'publish'
					);
					$wpv_query = new WP_Query( $wpv_args );
					$wpv_count_posts = $wpv_query->post_count;
					if ( $wpv_count_posts > 0 ) {
						$archive_out .= '<select name="wpcf-access-archive-ct" class="js-wpcf-error-ct-value">
						<option value="">'.__('None','wpcf-access').'</option>';
						while ($wpv_query->have_posts()) :
							$wpv_query->the_post();
							$post_id = get_the_id();

							$post = get_post($post_id);
							$selected = ( isset($_POST['cur_archivevalue']) && $_POST['cur_archivevalue'] == $post->ID )?' selected="selected" ':'';
							$archive_out .= '<option value="'.esc_attr($post->ID).'" '.$selected.'>'.$post->post_title.'</option>';
						endwhile;
						$archive_out .= '</select>';
					}else{
						$archive_out = '<p>'. __('Sorry, no alternative WordPress Archives. First, create a new WordPress Archive, then return here to choose it.','wpcf-access') .'</p>';
					}

				}
				else {
					$archive_out .= '<p>'. __('Sorry, no alternative WordPress Archives. First, create a new WordPress Archive, then return here to choose it.','wpcf-access') .'</p>';
				}

			}

			if( class_exists('WPDD_Layouts') ){
				$checked = ( isset( $_POST['cur_archivetype'] ) && $_POST['cur_archivetype'] == 'error_layouts' ) ? ' checked="checked" ' : '';
				$layouts_settings = Access_Admin_Edit::toolset_access_get_layouts_list();
				if ( ! empty( $layouts_settings ) ){
					$archive_out .= '
						<p>
							<label>
								<input type="radio" value="error_layouts" name="archive_error_type"' . $checked . ' class="js-wpcf-access-type"> '.
								__( 'Show Template Layout', 'wpcf-access' ) .'<br />
							</label>
							<select name="wpcf-access-archive-layouts" class="js-wpcf-archive-error-layouts-value">
								<option value="">' . __( 'None', 'wpcf-access' ) . '</option>';
								foreach( $layouts_settings as $id => $layout ){
									$selected = ( isset( $_POST['cur_archivevalue'] ) && $_POST['cur_archivevalue'] == $id ) ? ' selected="selected" ' : '';
									$archive_out .= '
											<option value="' . esc_attr( $id ) . '"' . $selected . '>' . $layout['post_title'] . '</option>';
								}
								$archive_out .= '
							</select>
						</p>';
				} else {
					$archive_out = '<p>'. __('Sorry, no Template Layouts found. First, create new Template Layout, then return here to choose it.','wpcf-access') .'</p>';
				}

				$show_php_tempaltes = false;

			}



			if ( $show_php_tempaltes ){
				$theme_files = array();
				if ( isset($_POST['cur_archivevalue']) ){
					$_POST['cur_archivevalue'] = urldecode($_POST['cur_archivevalue']);
					$_POST['cur_archivevalue'] = str_replace("\\\\","\\",$_POST['cur_archivevalue']);
				}

				if ( is_child_theme() ){
					$child_theme_dir = get_stylesheet_directory();
					$theme_files = self::wpcf_access_scan_dir( $child_theme_dir, $theme_files);
				}
				$theme_dir = get_template_directory().'/';

				if ( file_exists( $theme_dir.'archive-'.$post_type.'.php') ){
					$curent_file = 'archive-'.$post_type.'.php';
				}elseif ( file_exists( $theme_dir.'archive.php') ){
					$current_file = 'archive.php';
				}else{
					$current_file = 'index.php';
				}
				$error_message  =	sprintf(
							__( 'This custom post archive displays with the PHP template "%s".', 'wpcf-access' ), $current_file );
				$theme_files = self::wpcf_access_scan_dir( $theme_dir, $theme_files, $current_file);
				$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'error_php' )?' checked="checked" ':'';

				$archive_out .= '<p><label>
					<input type="radio" value="error_php" name="archive_error_type"'.$checked.' class="js-wpcf-access-type-archive"> '
					. __('Choose a different PHP template for people without read permission','wpcf-access') .'
					</label>
					<p class="toolset-alert toolset-alert- js-wpcf-error-php-value-info" style="display: none; opacity: 1;">
					'. $error_message .'
					</p><select name="wpcf-access-archive-php" class="js-wpcf-error-php-value hidden">
					<option value="">'.__('None','wpcf-access').'</option>';
						for ( $i=0,$limit=count($theme_files);$i<$limit;$i++){
							$selected = ( isset($_POST['cur_archivevalue']) && $_POST['cur_archivevalue'] == $theme_files[$i] )?' selected="selected" ':'';
							$archive_out .= '<option value="'.esc_attr($theme_files[$i]).'" '.$selected.'>'.preg_replace("/.*(\/.*\/)/","$1",$theme_files[$i]).'</option>';
						}
					$archive_out .= '</select></p>';
			}

			//Default error, use for everyone if set.
			if ( $_POST['forall'] != 1){
				$checked = ( empty($checked) )?' checked="checked" ':'';
				$out .= '<p><label>
				<input type="radio" value="default" name="archive_error_type" class="js-wpcf-access-type-archive"'.$checked.'> '
				. __('Default error','wpcf-access') .'
				</label></p>';
			}

			//Show post not found message'
			//Set post type not queryable
			$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'default_error' || empty($checked) )?' checked="checked" ':'';
			$out .= '<p><label>
			<input type="radio" value="default_error" name="archive_error_type" class="js-wpcf-access-type-archive"'.$checked.'> '
			. __('Display: "No posts found"','wpcf-access') .'
			</label></p>';

			$out .= $archive_out;

			$archive_preview_url = get_bloginfo('url') . '/?post_type=' . $post_type;
			if ( $_POST['forall'] == 0 && isset( $types_settings[ $post_type ]['mode'] ) &&  'permissions' == $types_settings[ $post_type ]['mode'] ) {
				//Show preview archive link
				$out .= '<div class="align-right">
					<a  href="#preview_error" class="js-toolset-access-preview-archive" data-role="' . $role . '" 
					data-posttype="' . $post_type . '" data-url="' . esc_attr( $archive_preview_url ) . '">'
				        . sprintf( __( 'Preview error for archive %s', 'wpcf-access' ), $post_type ) . '
		            <i class="icon-external-link fa fa-external-link icon-small"></i></a>
				</div>';
			}

		}//End check if post type have archive

		$out .= '</form>';        
		echo $out;
		die();
	}

	public static function get_archive_preview_link( $post_type ) {

		$url = get_post_type_archive_link( $post_type );

		return $url;
	}

	/**
	 * @param $post_type | Post type
	 * @param $post_section | string, post - native post, post_group - post assigned to post group, language - WPML group
	 * @param string $language
	 *
	 * @return mixed
	 */
	public static function get_single_cpt_preview_link( $post_type, $post_section ) {
		global $wpcf_access;
		$wpcf_access->wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );
		$types_settings = $wpcf_access->settings->types;
		$url = '';
		if ( $post_section == 'post' ) {
			if ( !isset( $types_settings[ $post_type ]['mode'] ) || $types_settings[ $post_type ]['mode'] != 'permissions' ) {
				return 'not_managed';
			}

			if ( $wpcf_access->wpml_installed ) {
				// Exclude languages used in WPML Groups
				$active_languages = $wpcf_access->active_languages;
				global $sitepress;
				$model = TAccess_Loader::get('MODEL/Access');
				$_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
				$current_language = ICL_LANGUAGE_CODE;
				foreach ( $types_settings as $group_slug => $group_data) {
					if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
						continue;
					}
					if ( ! isset( $_post_types[ $group_data['post_type'] ] ) || $group_data['post_type'] != $post_type ) {
						continue;
					}
					if ( isset( $group_data['languages'] ) ) {
						$language_keys = array_keys( $group_data['languages'] ) ;
						for ( $i = 0; $i < count( $language_keys ); $i++ ) {
							if ( ! array_key_exists( $language_keys[ $i ], $active_languages ) ) {
								unset( $active_languages[ $language_keys[ $i ] ] );
							}
						}
					}
				}
				if ( count($active_languages) > 0 ) {
					$active_languages = reset( $active_languages );
					$sitepress->switch_lang( $active_languages['code'] );
				} else {
					return '';
				}
			}


			$args =  array(
				'post_type' => $post_type,
				'posts_per_page' => 1,
				'post_status' => 'publish',
				'meta_query' => array(
					array(
						'key' => '_wpcf_access_group',
						'value' => '',
					),
					array(
						'key' => '_wpcf_access_group',
						'compare' => 'NOT EXISTS'
					),
					'relation' => 'OR'
				)
			);
			$posts_array = query_posts( $args );

			if ( $wpcf_access->wpml_installed ) {
				$sitepress->switch_lang( $current_language );
			}

		}
		else if ( $post_section == 'post-group' ) {

			$args =  array(
				'post_type' => 'any',
				'posts_per_page' => 1,
				'post_status' => 'publish',
				'meta_key' => '_wpcf_access_group',
				'meta_value' => $post_type,
			);
			$posts_array = query_posts( $args );

		} else if ( $post_section == 'wpml-group' ) {

			$types_settings = $wpcf_access->settings->types;
			$group_post_type = $types_settings[ $post_type ]['post_type'];
			$group_languages =  $types_settings[ $post_type ]['languages'];
			$current_language = ICL_LANGUAGE_CODE;
			$language_keys = array_keys( $group_languages) ;
			global $sitepress;
			for ( $i = 0; $i < count( $language_keys ); $i++ ) {
				$sitepress->switch_lang( $language_keys[ $i ] );
				$args        = array(
					'post_type'      => $group_post_type,
					'posts_per_page' => 1,
					'post_status' => 'publish',
					'meta_query'     => array(
						array(
							'key'   => '_wpcf_access_group',
							'value' => '',
						),
						array(
							'key'     => '_wpcf_access_group',
							'compare' => 'NOT EXISTS'
						),
						'relation' => 'OR'
					)
				);
				$posts_array = query_posts( $args );
				if ( count( $posts_array ) > 0 ) {
					$i = count( $language_keys ) +1;
				}
			}
			$sitepress->switch_lang( $current_language );
		}



		if ( count( $posts_array ) > 0 ) {
			$url = add_query_arg( 'toolset_access_preview', 1, get_permalink( $posts_array[0] ) );
		}
		return $url;
	}


	/*
	 * Scan directory for php files.
	 */
	public static function wpcf_access_scan_dir( $dir, $files = array(), $exclude = ''){
        
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		$file_list = scandir($dir);
		foreach($file_list as $file){
	        if($file != '.' && $file != '..' && preg_match("/\.php/",$file) && !preg_match("/^comments|^single|^image|^functions|^header|^footer|^page/",$file) && $file != $exclude ){

	            if( !is_dir($dir.$file) ){
	            	$files[] = 	$dir.$file;
				}
				else{
					$files = self::wpcf_access_scan_dir($dir.$file.'/', $files);
				}
	        }
	    }
		return $files;
	}

	/**
	 * Retrun an array of third party settings exceptions
	 *
	 * @return array
	 * @since 2.4
	 */
	private static function get_third_party_exception_array( ) {
		return array( '__FIELDS','__CRED_CRED','__CRED_CRED_USER', '__USERMETA_FIELDS' );
	}

    /*
	 * Specific users popup
	 */
	public static function toolset_access_specific_users_popup_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],'wpcf-access-error-pages')) {
            die('verification failed');
        }

        global $wpcf_access;
        if ( !isset($_POST['id']) || !isset($_POST['groupid']) || !isset($_POST['option_name']) ){
            return;
        }
        $id = $_POST['id'];
        $groupid = $_POST['groupid'];
        $option = $_POST['option_name'];
		$out = '<form method="" id="wpcf-access-set_error_page">';
		$out .= '
			<p>
				<label for="toolset-access-user-suggest-field">'. __('Search user','wpcf-access') .':</label>
				<select id="toolset-access-user-suggest-field"></select>
			</p>';

        $out .= '<div class="js-otgs-access-posts-listing otgs-access-posts-listing otgs-access-users-listing">';

        $settings = '';
        if ( in_array( $groupid, self::get_third_party_exception_array() ) !== FALSE ){
            if ( isset($wpcf_access->settings->third_party[$groupid]) ){
            	$settings = $wpcf_access->settings->third_party[$groupid];
			}
        }else{
            $settings = $wpcf_access->settings->$groupid;
        }

        if ( !empty($settings) && isset($settings[$id]['permissions'][$option]['users']) && count($settings[$id]['permissions'][$option]['users']) > 0 ) {
            $users = $settings[$id]['permissions'][$option]['users'];
            $args = array(
			    'orderby' => 'user_login',
                'include' => $users
			);
		    $user_query = new WP_User_Query( $args );
            foreach ( $user_query->results as $user ) {
                $out .= '<div class="js-assigned-access-item js-assigned-access-item-'. $user->ID .'" data-newitem="1" data-itemid="'. $user->ID .'">'.
					$user->data->user_login .' <a href="" class="js-wpcf-unassign-access-item" data-id="'. $user->ID .'"><i class="fa fa-times"></i></a></div>';
            };
		}

        $out .= '</div>';


        $out .= '</div>';
		$out .= '</form>';

		echo $out;
		die();
	}

	/**
	 * Clean up Access settings from database
	 */
	public static function wpcf_access_clean_up_database_ajax(){
		global $wpdb;
		if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-edit')) {
            die('verification failed');
        }

        $cleanup_completed = true;

		// Clean up settings
        if ( isset($_POST['remove_settings']) && $_POST['remove_settings'] == 'true' ){
 			$wpdb->query("Delete from $wpdb->usermeta where meta_key = 'toolset_access_conversion_ignore_notice' or meta_key = 'wpcf_access_section_status' ");
			$wpdb->query("Delete from $wpdb->postmeta where meta_key = '_wpcf_access_group'");
			$wpdb->query("Delete from $wpdb->options where option_name = 'toolset-access-options' or option_name = 'wpcf-access-version-check' or 
				 option_name = 'wpcf-access-types' or  option_name = 'wpcf-access-taxonomies' or option_name = 'wpcf-access-3rd-party' or 
				 option_name = 'wpcf_access_custom_caps' or  option_name = 'wpcf-access-roles' or option_name = 'otg_access_advaced_mode'");

		}


		//Remove roles and reassign users
		if ( isset($_POST['remove_roles']) && $_POST['remove_roles'] == 'true' ){
			$maximum_users_to_reassign = 5;
			$total_processed_users = 0;
			$role_to_assign = ( isset($_POST['role_to_assign'])?$_POST['role_to_assign']:'');
			$roles = Access_Helper::wpcf_get_editable_roles();
			$access_roles = array();
			foreach( $roles as $role => $role_data ){
				if ( isset($role_data['capabilities']['wpcf_access_role']) ){
					$access_roles[] = $role;
				}
			}

			if ( count($access_roles) > 0 && !empty($role_to_assign) ){
				$capabilities_array = serialize(array( $role_to_assign => true ));
				$roles_to_update = array();
				$user_query = new WP_User_Query( array( 'role__in' => $access_roles, 'number' => ($maximum_users_to_reassign+1) ) );
				if ( ! empty( $user_query->results ) ) {
					foreach ( $user_query->results as $user ) {
						$total_processed_users++;
						$roles_to_update[] = $user->ID;
						if ( $total_processed_users >= $maximum_users_to_reassign ){
							$cleanup_completed = false;
							break;
						}
					}
					$sql = "UPDATE $wpdb->usermeta SET meta_value='$capabilities_array' WHERE meta_key='wp_capabilities' and user_id IN (". implode(',', $roles_to_update).")";
					$wpdb->query( $sql );
				}
			}

			if ( $cleanup_completed ){
				foreach( $roles as $role => $role_data ){
					if ( isset($role_data['capabilities']['wpcf_access_role']) ){
						remove_role($role);
					}
				}
			}
		}

		//Disable plugin
		if ( isset($_POST['disable_plugin']) && $_POST['disable_plugin'] == 'true' && $cleanup_completed ){
			$file = str_replace('includes/Ajax_Helper.php', '', plugin_basename( __FILE__ )) . 'types-access.php';
        	deactivate_plugins( $file );
		}

		if ( $cleanup_completed ){
			if ( isset($_POST['remove_roles']) && $_POST['remove_roles'] == 'true' && isset($_POST['remove_settings']) && $_POST['remove_settings'] != 'true' ){
				$output = array( 'status' => 1, 'message' => __('All done! Access user roles are back to default now','wpcf-access') );
			}
			elseif ( isset($_POST['remove_roles']) && $_POST['remove_roles'] != 'true' && isset($_POST['remove_settings']) && $_POST['remove_settings'] == 'true' ){
				$output = array( 'status' => 1, 'message' => __('All done! Access settings are back to default now','wpcf-access') );
			}else{
				$output = array( 'status' => 1, 'message' => __('All done! Access settings and user roles are back to default now','wpcf-access') );
			}
		}else{
			$output = array( 'status' => 2, 'message' => __('Reassigning users in progress, %n of %t','wpcf-access'), 'assigned_users' => $total_processed_users );
		}
		echo json_encode($output);
        die();
	}

	/*
	 * Suggest users with select2
	 */
	public static function toolset_access_suggest_users_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

		if (!isset($_GET['wpnonce']) || !wp_verify_nonce($_GET['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
    	$out = array();
    	$query = Access_Helper::wpcf_esc_like($_POST['q']);


        $users = array();
        if ( isset($_POST['assigned_users']) && is_array($_POST['assigned_users']) ){
            $assigned_users_array = $_POST['assigned_users'];
            for ( $i=0,$count=count($assigned_users_array); $i<$count; $i++ ){
                $users[] = intval($assigned_users_array[$i]);
            }
        }
        /*
		$args = array(
			'number' => 10,
			'search' => '*'.Access_Helper::wpcf_esc_like($_POST['q']).'*',
			'search_columns' => array( 'user_login' ),
			'role__not_in' => array( 'Administrator' ),
            'exclude' => $users
			);
		$user_query = new WP_User_Query( $args );
		$total = 0;
		$out['items'] = array();
		if ( ! empty( $user_query->results ) ) {
            foreach ( $user_query->results as $user ) {
                $total++;
				$out['items'][] = array( 'id' => esc_attr($user->data->ID) , 'name' => esc_js($user->data->user_login) );
            }
        }
        */

            global $wpdb;

            $total = 0;
            $q = Access_Helper::wpcf_esc_like(trim($_POST['q']));
            $found = $wpdb->get_results("SELECT ID, display_name, user_login FROM $wpdb->users WHERE user_nicename LIKE '%%$q%%' OR user_login LIKE '%%$q%%' OR display_name LIKE '%%$q%%'  LIMIT 10");
            if (!empty($found)) {
                foreach ($found as $user) {
                    $total++;
				    $out['items'][] = array( 'id' => esc_js($user->ID) , 'name' => esc_js($user->user_login) );
                }
            }

        $out['total_count'] = $total;
        $out['incomplete_results'] = 'false';
		print json_encode($out);
		die();
	}

	public static function toolset_access_add_specific_users_to_settings_ajax(){
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }

        global $wpcf_access;
		$updated = array();
        if (  !isset($_POST['id']) || !isset($_POST['groupid']) || !isset($_POST['option_name']) ){
            return;
        }
        $users = ( isset($_POST['users']) && is_array($_POST['users'])? $_POST['users']: array());
        $id = $_POST['id'];
        $groupid = $_POST['groupid'];
        $option = $_POST['option_name'];
        $settings = '';
        if ( in_array( $groupid, self::get_third_party_exception_array() ) !== FALSE ){
            if ( isset($wpcf_access->settings->third_party[$groupid]) ){
            	$settings = $wpcf_access->settings->third_party[$groupid];
			}
        }else{
            $settings = $wpcf_access->settings->$groupid;
        }



        if ( !isset($settings[$id]) ){
            $settings[$id] = array( 'mode' => 'not_managed', 'permissions' => array() );
        }
        $settings[$id]['permissions'][$option]['users'] = array();
        for ( $i=0; $i < count($users); $i++ ){
           $settings[$id]['permissions'][$option]['users'][] = intval($users[$i]);
        }
       
        $users = $settings[$id]['permissions'][$option]['users'];
        $output['options_texts'][$option] = '';
        if ( count($users) > 0 ){
            $args = array(
                'orderby' => 'user_login',
                'include' => array_slice($users, 0, 2)
            );
            $user_query = new WP_User_Query( $args );
            foreach ( $user_query->results as $user ) {
                $output['options_texts'][$option] .= $user->data->user_login.'<br>';
            }
            $output['options_texts'][$option] .= ( (count($users) > 2)? 'and '.(count($users)-2).' more':'');
        }
        if ( in_array( $groupid, array( 'types', 'tax' ) ) ){
            $dep = Access_Helper::wpcf_access_dependencies();
            $dep = $dep[$option];

            $updated = array();
            //Add users from $dep
            if ( isset($dep['true_allow']) && is_array($dep['true_allow']) ){
                //List options related to current option
                for($i=0; $i<count($dep['true_allow']); $i++){
                    $option_name = $dep['true_allow'][$i];
                    if ( !isset($settings[$id]['permissions'][$option_name]['users']) || !is_array($settings[$id]['permissions'][$option_name]['users'])  ){
                        $settings[$id]['permissions'][$option_name]['users'] = array();
                    }
                    for ( $j=0; $j<count($users); $j++ ){
                        if ( in_array($users[$j], $settings[$id]['permissions'][$option_name]['users']) === FALSE ){
                            $settings[$id]['permissions'][$option_name]['users'][] = $users[$j];
                            if ( in_array($option_name, $updated) === FALSE ){
                                    $updated[] = $option_name;
                            }
                        }
                    }
                    $output['options_texts'][$option_name] = '';
                    if ( count($settings[$id]['permissions'][$option_name]['users']) > 0 ){
                        $args = array(
                            'orderby' => 'user_login',
                            'include' => array_slice($settings[$id]['permissions'][$option_name]['users'], 0, 2)
                        );
                        $user_query = new WP_User_Query( $args );
                        foreach ( $user_query->results as $user ) {
                            $output['options_texts'][$option_name] .= $user->data->user_login.'<br>';
                        }
                        $output['options_texts'][$option_name] .= ( (count($settings[$id]['permissions'][$option_name]['users']) > 2)? 'and '.(count($settings[$id]['permissions'][$option_name]['users'])-2).' more':'');
                    }
                }
            }

            //Remove user to $dep
            if ( isset($dep['false_disallow']) && is_array($dep['false_disallow']) ){
                //List options related to current option
                for($i=0; $i<count($dep['false_disallow']); $i++){
                    $option_name = $dep['false_disallow'][$i];
                    if ( isset($settings[$id]['permissions'][$option_name]['users']) && is_array($settings[$id]['permissions'][$option_name]['users'])  ){
                        for ( $j=0; $j<count($settings[$id]['permissions'][$option_name]['users']); $j++ ){
                            if ( in_array($settings[$id]['permissions'][$option_name]['users'][$j], $users) === FALSE ){
                                unset($settings[$id]['permissions'][$option_name]['users'][$j]);
                                if ( in_array($option_name, $updated) === FALSE ){
                                    $updated[] = $option_name;
                                }
                            }
                        }
                        $output['options_texts'][$option_name] = '';
                        if ( count($settings[$id]['permissions'][$option_name]['users']) > 0 ){
                            $args = array(
                                'orderby' => 'user_login',
                                'include' => array_slice($settings[$id]['permissions'][$option_name]['users'], 0, 2)
                            );
                            $user_query = new WP_User_Query( $args );
                            foreach ( $user_query->results as $user ) {
                                $output['options_texts'][$option_name] .= $user->data->user_login.'<br>';
                            }
                            $output['options_texts'][$option_name] .= ( (count($settings[$id]['permissions'][$option_name]['users']) > 2)? 'and '.(count($settings[$id]['permissions'][$option_name]['users'])-2).' more':'');
                        }
                    }

                }
            }


        }
        if ( count($updated) > 0 ){
            $output['updated_sections'] = "Since you updated '$option', '". implode("','", $updated)."' has also been updated.";
        }

        if ( in_array( $groupid, self::get_third_party_exception_array() ) !== FALSE ){
            $wpcf_access->settings->third_party[$groupid] = $settings;
        }else{
           $wpcf_access->settings->$groupid = $settings;
        }

        $model = TAccess_Loader::get('MODEL/Access');
        $model->updateAccessSettings($wpcf_access->settings);
        echo json_encode($output);

	    die();
    }

	/*
	 * Add new custom group form
	 */
	public static function wpcf_access_add_new_group_form_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],'wpcf-access-error-pages')) {
            die('verification failed');
        }
	    global $wpcf_access;
		$out = '<form method="" id="wpcf-access-set_error_page">';
		$act = 'Add';
		$title = $id = '';
	    $model = TAccess_Loader::get('MODEL/Access');
	    $settings_access = $model->getAccessTypes();

		if ( isset($_POST['modify']) ) {
			$act = 'Modify';
			$id = $_POST['modify'];
            $current_role = $settings_access[$id];
			$title = $current_role['title'];
		}

		$out .= '
			<p>
				<label for="wpcf-access-new-group-title">'. __('Group title','wpcf-access') .'</label><br>
				<input type="text" id="wpcf-access-new-group-title" value="'.$title.'">
			</p>
			<div class="js-error-container"></div>
			<input type="hidden" value="add" id="wpcf-access-new-group-action">
			<input type="hidden" value="'. $id .'" id="wpcf-access-group-slug">';

            $out .= '<div class="otgs-access-search-posts-container">
                <label for="wpcf-access-new-group-title">'. __('Choose which posts belongs to this group','wpcf-access') .'</label><br>
                <select class="js-otgs-access-suggest-posts otgs-access-suggest-posts" style="width:72%;">                  
                </select>
                <select class="js-otgs-access-suggest-posts-types otgs-access-suggest-posts-types" style="width:25%;">
                  <option selected="selected" value="">'. __('All post types','wpcf-access') .'</option>';
            $post_types = get_post_types( array('public'=> true), 'object' );
            $post_types_array = array();
		    foreach ( $post_types  as $post_type ) {
				if ( $post_type->name != 'attachment' ) {
					$is_option_disabled = (
						! isset( $settings_access[ $post_type->name ] )
						|| 'not_managed' === $settings_access[ $post_type->name ]['mode']
					);
					$out .= '<option value="' . $post_type->name . '" ' . disabled( $is_option_disabled, true, false ) . '>' . $post_type->labels->name . '</option>';
					$post_types_array[] = $post_type->name;
				}
            }

            $out .= '</select>
            </div>
            <div class="js-otgs-access-posts-listing otgs-access-posts-listing">';
                if ( $act == 'Modify' ) {
                    $args = array(
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'post_type' => $post_types_array,
                        'meta_query' => array(array(
                                    'key' => '_wpcf_access_group',
                                    'value' => $id
                                )
                            )
						);
						$the_query = new WP_Query( $args );
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post();
								$out .= '<div class="js-assigned-access-post js-assigned-access-post-'.esc_attr(get_the_ID()).'" data-postid="'.esc_attr(get_the_ID()).'">'.get_the_title().'
								 <a href="" class="js-wpcf-unassign-access-post" data-id="'.esc_attr(get_the_ID()).'"> <i class="fa fa-times"></i></a></div>';
							};
						}
                }
            $out .= '</div>';


        $out .= '</div>';
		$out .= '</form>';

		echo $out;
		die();
	}

	/*
	 * Proccss new access group
	 */
	public static function wpcf_process_new_access_group_ajax() {
    
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$nice = 'wpcf-custom-group-' . md5( sanitize_title( $_POST['title'] ) );
		$posts = array();
		if ( isset($_POST['posts']) ) {
			$posts = array_map('intval',$_POST['posts']);
		}

		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

        if ( isset($settings_access['post']['permissions']['read']['roles']) ){
            $roles = $settings_access['post']['permissions']['read']['roles'];
        }else{
            TAccess_Loader::load('CLASS/Admin_Edit');
            $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
            $roles = array_keys($ordered_roles);
        }
		$groups[$nice] = array(
			'title' => sanitize_text_field($_POST['title']),
			'mode' => 'permissions',
			'permissions' => array( 'read'=>array('roles'=>$roles) ),
			);

		$process = true;
		if ( !empty($settings_access) ){
			foreach ($settings_access as $permission_slug => $data){
				if ( $permission_slug === $nice ){
					$process = false;
				}
			}
		}

		if ( !$process ){
			echo 'error';
			die();
		}

		for ($i=0, $limit=count($posts);$i<$limit;$i++){
			update_post_meta($posts[$i],'_wpcf_access_group', $nice);
		}
		TAccess_Loader::load('CLASS/Admin_Edit');
		$settings_access = array_merge( $settings_access, $groups);
		$model->updateAccessTypes( $settings_access );
		$group['id'] = $nice;
		echo $group['id'];
		die();
	}

	/*
	 * Process modify group
	 */
	public static function wpcf_process_modify_access_group_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
        $_POST['id'] = str_replace('%','--ACCESS--',$_POST['id']);
		$nice = str_replace('--ACCESS--','%',sanitize_text_field($_POST['id']));
        $_POST['id'] = str_replace('--ACCESS--','%',$_POST['id']);
		$posts = array();
		if ( isset($_POST['posts']) ){
			$posts = array_map('intval',$_POST['posts']);
		}

		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
    	$process = true;
		if ( isset($settings_access[$nice]) ){
			foreach ($settings_access as $permission_slug => $data){
				if ( isset($data['title']) && $data['title'] == sanitize_text_field($_POST['title']) && $permission_slug != $nice ){
					$process = false;
				}
			}
		}else{
			$process = false;
		}

		$settings_access[$nice]['title'] = sanitize_text_field($_POST['title']);
		TAccess_Loader::load('CLASS/Admin_Edit');
		$roles = Access_Helper::wpcf_get_editable_roles();
		$model->updateAccessTypes( $settings_access );



		if ( !$process ){
			echo 'error';
			die();
		}

		for ($i=0,$posts_limit=count($posts);$i<$posts_limit;$i++){
			update_post_meta($posts[$i],'_wpcf_access_group', $nice);
		}
		$group_output = '';
		$_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
		$post_types_array = array();
		foreach ( $_post_types  as $post_type ) {
			$post_types_array[] = $post_type['name'];
		}
		$args = array( 'post_type' => $post_types_array, 'posts_per_page' => 0, 'meta_key' => '_wpcf_access_group', 'meta_value' =>$nice);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			$group_output .= '<strong>'. __('Posts in this Post Group', 'wpcf-access') .':</strong> ';
			$posts_list = '';
			$show_assigned_posts = 4;
			while ( $the_query->have_posts() && $show_assigned_posts != 0  ) {
				$the_query->the_post();
				$posts_list .= get_the_title().', ';
				$show_assigned_posts --;
			}
			$group_output .= substr($posts_list, 0, -2);
			if ( $the_query->found_posts > 4 ){
				$group_output .= sprintf( __( ' and %d more', 'wpcf-access' ), ($the_query->found_posts - 2));
			}
		}
		if ( !empty($group_output) ){
			echo $group_output;
		}
		die();
	}

	/*
	 * Remove group
	 */
	public static function wpcf_remove_group_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$out = '<form method="">
		<p>'. __('Are you sure want to remove this group?','wpcf-access') .'</p>
		</form>';
		$out = '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
					<div class="toolset-access-alarm-wrap-right">'. $out .'</div>';
		echo $out;
		die();
	}

	/*
	 * Remove group process
	 */
	public static function wpcf_remove_group_process_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		$group_id = sanitize_text_field( $_POST['group_id'] );
		if ( isset( $settings_access[ $group_id ] ) ) {
			unset( $settings_access[ $group_id ] );
		}
	    $args = array(
	            'posts_per_page' => -1,
                'offset'=> 0,
                'meta_key' => '_wpcf_access_group',
                'meta_value' => $group_id,
                'post_type' => 'any',
	            'fields' => 'ids'
        );

	    $query = new WP_Query( $args );

	    if ( count( $query->posts ) > 0 ) {
		    foreach( $query->posts as $post ) {
			    delete_post_meta( $post, '_wpcf_access_group' );
		    }
	    }

		$model->updateAccessTypes( $settings_access );

		die();
	}

	/*
	 * Search post for group
	 */
	public static function wpcf_search_posts_for_groups_ajax()
    {
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_GET['wpnonce']) || !wp_verify_nonce($_GET['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
	    $model = TAccess_Loader::get('MODEL/Access');
	    $settings_access = $model->getAccessTypes();

    	$out = array();
		$post_types_array = array();
		if ( isset($_POST['post_type']) && !empty($_POST['post_type']) ){
            $post_types_array[] = sanitize_text_field($_POST['post_type']);
        }else{
            $post_types = get_post_types( array('public'=> true), 'names' );
            foreach ( $post_types  as $post_type ) {
	            if ( isset( $settings_access[ $post_type ] ) && $settings_access[ $post_type ]['mode'] != 'not_managed' ) {
		            $post_types_array[] = $post_type;
	            }
            }
        }
        $assigned_posts = array();
        if ( isset($_POST['assigned_posts']) && is_array($_POST['assigned_posts']) ){
            $assigned_posts_array = $_POST['assigned_posts'];
            for ( $i=0,$count=count($assigned_posts_array); $i<$count; $i++ ){
                $assigned_posts[] = intval($assigned_posts_array[$i]);
            }
        }
		$args = array(
			'posts_per_page' => '10',
			'post_status' => 'publish',
			'post_type' => $post_types_array,
			's' => Access_Helper::wpcf_esc_like($_POST['q']),
            'post__not_in' => $assigned_posts
			);

		$total = 0;
		$out['items'] = array();
		if ( count( $post_types_array ) > 0 ) {
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$total ++;
					$out['items'][] = array( 'id' => esc_attr( get_the_ID() ), 'name' => esc_js( get_the_title() ) );
				};
			}
		}
        $out['total_count'] = $total;
        $out['incomplete_results'] = 'false';
		print json_encode($out);
		die();
	}

	/*
	 * Remove post from group
	 */
	public static function wpcf_remove_postmeta_group_ajax(){
    
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		delete_post_meta(sanitize_text_field($_POST['id']), '_wpcf_access_group');
		die();
	}

	/*
	 * Set group for post
	 */
	public static function wpcf_select_access_group_for_post_ajax(){
    
        if ( !current_user_can('manage_options') && !current_user_can('access_change_post_group') && !current_user_can('access_create_new_group') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$group = get_post_meta(sanitize_text_field($_POST['id']), '_wpcf_access_group', true);
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		$out = '<form method="#" id="wpcf-access-set_error_page">';

		$groups_list = '';
		foreach ($settings_access as $permission_slug => $data){
			if ( strpos( $permission_slug, 'wpcf-custom-group-') === 0 ){
				$checked = ( $permission_slug == $group )?' selected="selected" ':'';
				$groups_list .= '
						<option value="'.$permission_slug.'"'.$checked.'>'.$data['title'].'</option>';
			}
		}
		$checked = ( isset($group) && !empty($group) && isset($settings_access[$group]) )?' checked="checked" ':'';
		$out .= '<div class="otg-access-dialog-wraper">
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-existing-group" value="existing_group" '.$checked.' '.(empty($groups_list)?'disabled="disabled"':'').'>
					<label for="wpcf-access-group-method-existing-group">'. __('Select existing group','wpcf-access').'</label>
					<select name="wpcf-access-existing-groups" class="hidden">
						<option value="">- '.__('None','wpcf-access').' -</option>';
		$out .= $groups_list;
    	$process = true;

		$out .= '
					</select>
				</p>
		';
        if ( current_user_can('manage_options') || current_user_can('access_create_new_group') ){
		$out .= '
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-new-group" value="new_group" '.(empty($groups_list)?'checked="checked"':'').'>
					<label for="wpcf-access-group-method-new-group">'. __('Create new group','wpcf-access').'</label>
					<input type="text" name="wpcf-access-new-group" class="'.(!empty($groups_list)?'hidden"':'').'">
					<div class="js-error-container"></div>
				</p>';
        }
		$out .= '</div></form>';
		print $out;
		die();
	}

	/*
	 *
	 */
	public static function wpcf_process_select_access_group_for_post_ajax() {
    
        if ( !current_user_can('manage_options') && !current_user_can('access_change_post_group') && !current_user_can('access_create_new_group') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		if ( $_POST['methodtype'] == 'existing_group' ){

			update_post_meta( sanitize_text_field($_POST['id']), '_wpcf_access_group', sanitize_text_field($_POST['group']));
			if ( $_POST['group'] != ''){
			$message = sprintf(
					__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), esc_attr($settings_access[$_POST['group']]['title']) ).'</p>';
					if ( current_user_can('manage_options') ){
                        $message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'.
                        sprintf(__( 'Edit %s group privileges', 'wpcf-access' ), $settings_access[sanitize_text_field($_POST['group'])]['title']).'</a></p>';
                    }
			}else{
				$message =  __( 'No group selected.', 'wpcf-access' );
			}
		}else{
            if ( !current_user_can('manage_options') && !current_user_can('access_create_new_group') ){
                 _e('There are security problems. You do not have permissions.','wpcf-access');
                die();
            }
			$nice = 'wpcf-custom-group-' . md5( sanitize_title( $_POST['new_group'] ) );
			$groups[$nice] = array(
				'title' => sanitize_text_field($_POST['new_group']),
				'mode' => 'permissions',
				'permissions' => array( 'read' => array( 'roles' => Access_Helper::toolset_access_get_roles_by_role('guest') )),
			);

			$process = true;
			if ( isset( $settings_access[ $nice ] ) ) {
				$process = false;
			}

			if ( !$process ){
				echo 'error';
				die();
			}
			update_post_meta( sanitize_text_field($_POST['id']), '_wpcf_access_group', $nice);
			TAccess_Loader::load('CLASS/Admin_Edit');
			$settings_access = array_merge( $settings_access, $groups);
			$model->updateAccessTypes( $settings_access );
			$message = sprintf(
					__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), esc_attr($_POST['new_group']) ).'</p>';
                if ( current_user_can('manage_options') ){
                    $message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'.sprintf(__( 'Edit %s group privileges', 'wpcf-access' ), esc_attr($_POST['new_group']) ).'</a></p>';
                }         
		}

		print $message;
		die();
	}

	/*
	 * Show popup for custom roles: caps
	 */
	public static function wpcf_access_change_role_caps_ajax() {
    
        if ( ! current_user_can( 'manage_options' ) ) {
             _e( 'There are security problems. You do not have permissions.', 'wpcf-access' );
             die();
        }
        
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'wpcf-access-error-pages' ) ) {
            die( 'verification failed' );
        }
		$role = sanitize_text_field( $_POST['role'] );
		$out = '<div class="otg-access-change-role-caps-tabs js-otg-access-change-role-caps-tabs">';
		$model = TAccess_Loader::get( 'MODEL/Access' );
        $role_data = get_role( $role );
		$role_caps = $role_data->capabilities;
        $managed_capabilities_list = array();

        /**
		 * list wordpress, toolset, wpml, woocommerce capabilities.
		 */
		$data = apply_filters( 'wpcf_access_custom_capabilities', array() );
        $caps = '';
        $out .= '<ul class="wpcf-access-capability-tabs">';
		foreach( $data as $capabilities ) {
			if ( ! isset( $capabilities['capabilities'] ) ) {
				continue;
			}
			if ( isset( $capabilities['label'] ) ) {
                $out .=  sprintf( '<li><a href="#plugin_%s">%s</a></li>', md5( $capabilities['label'] ), $capabilities['label'] ) ;
				$caps .= sprintf( '<div id="plugin_%s"><h3>%s</h3>', md5( $capabilities['label'] ), $capabilities['label'] ) ;
			}
			foreach( $capabilities['capabilities'] as $cap => $cap_info ) {
				$caps .= sprintf(
					'<p><label for="cap_%s"><input type="checkbox" name="current_role_caps[]" value="Access:cap_%s" id="cap_%s" %s>%s<br><small> %s</small></label></p>',
					$cap,
					$cap,
					$cap,
					( isset( $role_caps[ $cap ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '',
					$cap,
					$cap_info
				);
				$managed_capabilities_list[] = $cap;
			}
			if ( isset( $capabilities['label'] ) ) {
				$caps .= '</div>';
			}
		}

        $out .= '<li><a href="#plugin_' . md5( __( 'Custom capabilities' ,'wpcf-access' ) ) . '">' . __( 'Custom capabilities' ,'wpcf-access' ) . '</a></li>';
		$caps .= '<div id="plugin_'. md5( __( 'Custom capabilities' ,'wpcf-access' ) ) . '"><h3>' . __( 'Custom capabilities' ,'wpcf-access' ) . '</h3>';
		$custom_caps = get_option( 'wpcf_access_custom_caps', array() );
		$caps .= '<div class="js-wpcf-list-custom-caps">';
		if ( is_array( $custom_caps ) && count( $custom_caps ) > 0 ){
			foreach ( $custom_caps as $cap => $cap_info ){
			    $managed_capabilities_list[] = $cap;
				$checked = ( isset(  $role_caps[ $cap  ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '';
				$caps .= '<p id="wpcf-custom-cap-' . $cap . '">' .
				'<label for="cap_'  . $cap . '">'.
				'<input type="checkbox" name="current_role_caps[]" value="Access:cap_' . $cap . '" id="cap_' . $cap . '" '. $checked . '>' .
				$cap . '<br><small>' . $cap_info . '</small></label>'.
				'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_' . $cap . '">'.
				'<a href="" data-object="wpcf-custom-cap-' . $cap . '" data-remove="0" data-cap="' . $cap.  '">Delete</a><span class="ajax-loading spinner"></span>'.
				'</span>'.
				'</p>';
			}
		}
		$hidden = count( $custom_caps ) > 0 ? ' hidden' : '';
		$caps .= '<p class="js-wpcf-no-custom-caps ' . $hidden . '">' . __( 'No custom capabilities', 'wpcf-access' ) . '</p>';
		$caps .= '</div>';

		ob_start();
		?>
		<div class="wpcf-create-new-cap-div js-wpcf-create-new-cap-div">
			<p>
				<button class="button js-wpcf-access-add-custom-cap"><?php _e( 'New custom capability', 'wpcf-access' ) ?></button>
			</p>
			<div class="js-wpcf-create-new-cap-form hidden">
				<p>
					<label for="js-wpcf-new-cap-slug"><?php _e( 'Capability name', 'wpcf-access' ) ?>:</label>
					<input type="text" name="new_cap_name" id="js-wpcf-new-cap-slug">
				</p>
				<p>
					<label for="js-wpcf-new-cap-description"><?php _e( 'Capability description', 'wpcf-access' ) ?>:</label>
					<input type="text" name="new_cap_description" id="js-wpcf-new-cap-description">
				</p>
				<p class="wpcf-access-buttons-wrap wpcf-access-buttons-wrap-left">
					<button class="button js-wpcf-new-cap-cancel"><?php _e( 'Cancel', 'wpcf-access' ) ?></button>
					<button class="button button-primary js-wpcf-new-cap-add" disabled="disabled"
					data-error="<?php echo esc_attr( __( 'Only lowercase letters, numbers, _ and - allowed in capability name', 'wpcf-access' ) ) ?>">
					<?php _e( 'Add', 'wpcf-access' ) ?></button>
					<span class="ajax-loading spinner js-new-cap-spinner"></span>
				</p>
			</div>
		</div>

		<input type="hidden" value="<?php echo esc_attr( $role ) ?>" class="js-wpcf-current-edit-role">
		<?php
		$caps .= ob_get_contents();
		ob_end_clean();
		$caps .= '</div>';

		//Add Access role capability from list
        $managed_capabilities_list[] = 'wpcf_access_role';
		$other_roles_capabilities = self::get_roles_capabilities_list( $managed_capabilities_list );
        if ( count( $other_roles_capabilities ) ){
            asort( $other_roles_capabilities );
            $out .=  sprintf( '<li><a href="#plugin_%s">%s</a></li>', md5( __( 'Other capabilities', 'wpcf-access' ) ), __( 'Other capabilities', 'wpcf-access' ) ) ;
			$caps .= sprintf( '<div id="plugin_%s"><h3>%s</h3>', md5( __( 'Other capabilities', 'wpcf-access' ) ), __( 'Other capabilities', 'wpcf-access' ) ) ;
            foreach ( $other_roles_capabilities as $key => $value ){
                $cap = $value;
                $caps .= sprintf(
					'<p><label for="cap_%s"><input type="checkbox" name="current_role_caps[]" value="Access:cap_%s" id="cap_%s" %s>%s<br></label></p>',
					$cap,
					$cap,
					$cap,
					( isset( $role_caps[ $cap ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '',
					$cap
				);
            }

            $caps .= '</div>';
        }

        $out .= '</ul>';
        $out .= $caps . '</div>';

		print $out;
		die();
	}


	public static function get_roles_capabilities_list( $managed_caps ){
        global $wp_roles;
        $capabilities_list = array();
        foreach(  $wp_roles->roles as $role => $role_info ){
            $role_caps = $role_info[ 'capabilities' ];
            foreach( $role_caps as $cap => $cap_status ){
                if ( $cap_status == 1 && in_array( $cap, $capabilities_list ) === FALSE && in_array( $cap, $managed_caps ) === FALSE  ){
                    $capabilities_list[] = $cap;
                }
            }
        }
        return $capabilities_list;
    }

	/*
	 * Proccess custom role caps
	 */
	public static function wpcf_process_change_role_caps_ajax(){

        if ( ! current_user_can( 'manage_options' ) ){
             _e( 'There are security problems. You do not have permissions.', 'wpcf-access' );
             die();
        }

		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'wpcf-access-error-pages' ) ) {
            die( 'verification failed' );
        }

		$role = sanitize_text_field( $_POST['role'] );
		$caps = '';
		if ( isset( $_POST['caps'] ) ) {
			$caps = array_map( 'sanitize_text_field', $_POST['caps'] );
		}

        $model = TAccess_Loader::get( 'MODEL/Access' );

		$access_roles = $model->getAccessRoles();

        $all_capabilities = self::get_roles_capabilities_list( array() );
		$role_data = get_role( $role );

		for ( $i = 0, $caps_limit = count( $all_capabilities ); $i < $caps_limit; $i++ ) {
			if ( isset( $access_roles[ $role ]['caps'][ $all_capabilities[ $i ] ] ) && $all_capabilities[ $i ] !== 'wpcf_access_role' ) {
				unset( $access_roles[ $role ]['caps'][ $all_capabilities[ $i ] ] );
				$role_data->remove_cap( $all_capabilities[$i] );
			}
		}

		if ( ! empty( $caps ) ){
			for ($i=0, $caps_limit = count( $caps ); $i < $caps_limit; $i++ ) {
				$cap = str_replace( 'Access:cap_','', $caps[ $i ] );
				$access_roles[ $role ]['caps'][ $cap ] = true;
				$role_data->add_cap( $cap );
			}
		}
        $model->updateAccessRoles( $access_roles );
		wp_send_json_success();
	}

	/*
	 * Show popup for custom roles: caps (read only)
	 */
	public static function wpcf_access_show_role_caps_ajax(){
    
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$role = sanitize_text_field($_POST['role']);

		$out = '<form method="#">';
		$role_info = get_role($role);
		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$custom_caps = get_option('wpcf_access_custom_caps');

		foreach ($role_info->capabilities as $cap => $cap_info){
			if ( !preg_match("/level_[0-9]+/",$cap) ){
			$out .= '<p><label for="cap_'.$cap.'"><input type="checkbox" checked="checked" value="" disabled id="cap_'.$cap.'" >
			'.$cap;
			if ( isset($default_wordpress_caps[$cap]) ){
				$out .= '<br><small>'.$default_wordpress_caps[$cap];
				if ( !empty($default_wordpress_caps[$cap][1]) ){
					$out .= ' ('.$default_wordpress_caps[$cap].')';
				}
				if ( !empty($wocommerce_caps[$cap][1]) ){
					$out .= ' ('.$wocommerce_caps[$cap][1].')';
				}
				if ( !empty($wpml_caps_list[$cap][1]) ){
					$out .= ' ('.$wpml_caps_list[$cap][1].')';
				}

				$out .= '</small>';
			}
			if ( isset($custom_caps[$cap]) ){
				$out .= '<br><small>'.$custom_caps[$cap].'</small>';
			}
			$out .= '</label></p>';
			}
		}

		$out .= '</form>';
		echo $out;
		die();
	}

	/*
	 * Create new custom capability
	 */
	public static function wpcf_create_new_cap(){
        
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$custom_caps = get_option('wpcf_access_custom_caps');

		if ( !is_array($custom_caps) ){
			$custom_caps = array();
		}

		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$cap = sanitize_text_field($_POST['cap_name']);
		$description = sanitize_text_field($_POST['cap_description']);

		if ( isset($custom_caps[$cap]) || isset($default_wordpress_caps[$cap]) || isset($wocommerce_caps[$cap]) || isset($wpml_caps_list[$cap]) ){
			$output = array('error', __('This capability already exists in your site','wpcf-access'));
		}
		else{
			$custom_caps[$cap] = $description;
			update_option( 'wpcf_access_custom_caps', $custom_caps);
			$input = '<p id="wpcf-custom-cap-'.$cap.'"><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="Access:cap_'.$cap.'" id="cap_'.$cap.'" checked="checked">
				'.$cap.'<br><small>'. $description .'</small></label>'.
				'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_'.$cap.'">'.
				'<a href="" data-object="wpcf-custom-cap-'.$cap.'" data-remove="0" data-cap="'.$cap.'">Delete</a><span class="ajax-loading spinner"></span>'.
				'</span>'.
				'</p>';
			$output = array(1, $input);
		}


		echo json_encode($output);
		die();
	}

	/*
	 * Create new custom capability
	 */
	public static function wpcf_delete_cap(){
        
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }
        
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
        global $wp_roles;
		$custom_caps = get_option('wpcf_access_custom_caps');

		if ( !is_array($custom_caps) ){
			$custom_caps = array();
		}
		$output = '';
		$edit_role = sanitize_text_field($_POST['edit_role']);
		$model = TAccess_Loader::get('MODEL/Access');
		$access_roles = $model->getAccessRoles();
		$cap = sanitize_text_field($_POST['cap_name']);
		$remove = sanitize_text_field($_POST['remove']);
		$roles = array();
		if ( $remove == 0 ){
			foreach ($access_roles as $role => $role_info){
				if ( isset($role_info['caps'][$cap])  && $role != $edit_role ){print_r($role_info);
					$roles[] = ( array_key_exists( 'name', $role_info ) ? $role_info['name'] : ucwords( $role ) );
				}
			}

			if ( count( $roles ) > 0 ){
				$roles = implode(", ", $roles);
				$output = '<div class="js-wpcf-removediv js-removediv_'.$cap.'">'
						. '<p>' . __( 'The following role(s) have this capability:', 'wpcf-access' ) . '</p>' . $roles;
				$output .= '<p><button class="js-wpcf-remove-cap-cancel button" data-cap="'.$cap.'">'.__( 'Cancel', 'wpcf-access' ).'</button> '
						. '<button class="js-wpcf-remove-cap-anyway button-primary button" data-remove="1" data-object="'.sanitize_text_field($_POST['remove_div']).'" data-cap="'.$cap.'">' . __( 'Delete anyway', 'wpcf-access' ) . '</button> '
						. '<span class="ajax-loading spinner"></span>'
						. '</p></div>';
			}
			else{
				foreach ($wp_roles->roles as $role => $role_info){
					if ( isset($role_info['capabilities'][$cap]) ){
						if ( isset($access_roles[$role]['caps'][$cap]) ){
							unset($access_roles[$role]['caps'][$cap]);
						}
						$wp_roles->remove_cap( $role, $cap );
					}
				}
				$model->updateAccessRoles($access_roles);
				unset($custom_caps[$cap]);
				update_option( 'wpcf_access_custom_caps', $custom_caps);
				$output = 1;
			}
		}
		else{
			foreach ($wp_roles->roles as $role => $role_info){
				if ( isset($role_info['capabilities'][$cap]) ){
					if ( isset($access_roles[$role]['caps'][$cap]) ){
						unset($access_roles[$role]['caps'][$cap]);
					}
					$wp_roles->remove_cap( $role, $cap );
				}
			}
			$model->updateAccessRoles($access_roles);
			unset($custom_caps[$cap]);
			update_option( 'wpcf_access_custom_caps', $custom_caps);
			$output = 1;
		}
		echo $output;
		die();
	}

	public static function wpcf_access_save_section_status_ajax(){
        if ( !current_user_can('manage_options') ){
             _e('There are security problems. You do not have permissions.','wpcf-access');
             die();
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'otg_access_general_nonce')) {
            die('verification failed');
        }

        global $current_user;
		$user_id = $current_user->ID;
		$sections_array = get_user_meta( $user_id, 'wpcf_access_section_status', true );
		if ( empty($sections_array) || is_array($sections_array) === FALSE ){
		    $sections_array = array();
        }
        $target = sanitize_text_field($_POST['target']);
        $status = intval($_POST['status']);
        $sections_array[$target] = $status;
        update_user_meta( $user_id, 'wpcf_access_section_status', $sections_array);
	    die();
    }
    
    
    


}

// init on load
Access_Ajax_Helper::init();