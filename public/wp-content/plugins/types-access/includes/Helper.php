<?php

/*
*   Access Helper
*
*/


final class Access_Helper
{
    static $roles;
	static $import_messages;
    static $preview_posts = array();
    static $types_caps;
    static $tax_caps;
    static $types_caps_predefined;



    public static function init() {
        
        self::$import_messages = null;

        self::$types_caps = self::wpcf_access_types_caps_array();
	    self::$types_caps_predefined = self::wpcf_access_types_caps_predefined_array();
	    self::$tax_caps = self::wpcf_access_tax_caps_array();

		// Loading actions
        add_action( 'plugins_loaded',	array( __CLASS__, 'wpcf_access_early_plugins_loaded'), 0 );
		// This happens at plugins_loaded:1
        add_action( 'wpml_loaded',		array( __CLASS__, 'wpcf_access_wpml_loaded' ) );
        add_action( 'plugins_loaded',	array( __CLASS__, 'register_post_types_and_taxonomies'), 1 );
        add_action( 'plugins_loaded',	array( __CLASS__, 'wpcf_access_late_plugins_loaded'), 2 );


        add_action( 'init',				array( __CLASS__, 'wpcf_access_early_init'), 1 );

		if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts',			array( __CLASS__, 'wpcf_access_select_group_metabox_files' ) );
            add_action( 'admin_head',						array( __CLASS__, 'wpcf_access_select_group_metabox' ) );
            add_action( 'admin_init',						array( __CLASS__, 'wpcf_access_check_add_media_permissions' ) );
            add_filter( 'icl_get_extra_debug_info',			array( __CLASS__, 'add_access_extra_debug_information' ) );
            add_action( 'wp_loaded',						array( __CLASS__, 'wpcf_access_import_on_form_submit' ) );
            add_action( 'admin_notices',					array( __CLASS__, 'wpcf_access_import_notices_messages' ) );
			add_action( 'wp_ajax_access_import_export',		array(__CLASS__, 'import_export_hook'));
			add_action( 'admin_notices',			        array( __CLASS__, 'toolset_access_admin_notice' ) );
			add_action( 'admin_head',                       array( __CLASS__, 'admin_add_help' ) );
		} else {
            add_filter( 'pre_get_posts',					array( __CLASS__, 'wpcf_access_show_post_preview' ) );
            add_filter( 'request',							array( __CLASS__, 'wpcf_access_set_feed_permissions' ) );
            add_filter( 'wp_get_nav_menu_items',            array( __CLASS__, 'wpcf_access_set_menu_permissions' ), null, 3 );
            add_action( 'init',            array( __CLASS__, 'preview_custom_error' ) );
            /**
             * Running preview_custom_error twice to fix an issue on multi-site when WP replace updated $current_user to original
             */
            add_action( 'wp_head',            array( __CLASS__, 'preview_custom_error' ) );
			add_filter( 'get_previous_post_where',              array( __CLASS__, 'filter_post_link_query' ), null, 5 );
			add_filter( 'get_next_post_where',                  array( __CLASS__, 'filter_post_link_query' ), null, 5 );

			add_filter( 'toolset_access_check_if_post_type_managed', array( __CLASS__, 'check_if_post_type_managed' ), null, 2 );
			add_filter( 'toolset_access_get_allowed_post_groups', array( __CLASS__, 'get_allowed_post_groups' ), null, 2 );


		}
	    add_filter( 'wp_link_query',            array( __CLASS__, 'filter_wp_link_query' ), null, 2 );


        add_shortcode( 'toolset_access',			array( __CLASS__, 'wpcf_access_create_shortcode_toolset_access' ) );
        add_filter( 'wpv_custom_inner_shortcodes',	array( __CLASS__, 'wpv_access_string_in_custom_inner_shortcodes' ) );

		add_action( 'otg_access_action_access_roles_updated', array( __CLASS__, 'otg_access_reload_access_roles' ) );

    }

	/**
	 * Remove deprecated settings from existing array
	 */
	public static function toolset_access_update_settings() {
		$access_settings = get_option('toolset-access-options');

		$custom_errors_keys = array(
			'_custom_read_errors',
			'_custom_read_errors_value',
			'_archive_custom_read_errors',
			'_archive_custom_read_errors_value'
		);


		foreach ( $access_settings as $group => $types ) {
			foreach ( $types as $access_type => $settings ) {
				//empty custom errors
				if ( in_array( $access_type, $custom_errors_keys ) ) {
					foreach( $settings as $custom_error_cpt => $custom_array_array ) {
						foreach ( $custom_array_array['permissions']['read'] as $role => $value ) {
							if ( empty( $value ) ) {
								unset( $access_settings->{$group}[ $access_type ][ $custom_error_cpt ]['permissions']['read'][ $role ] );
							}
						}
						if ( empty( $custom_array_array['permissions']['read'] ) ){
							unset( $access_settings->{$group}[ $access_type ][ $custom_error_cpt ] );
						}
					}
				}
				//Remove predefined permissions array
				if ( array_key_exists( '__permissions', $settings ) ) {
					unset( $access_settings->{$group}[ $access_type ]['__permissions'] );
				}

				if ( $group === 'third_party' ) {
					foreach ( $settings as $area => $area_settings ) {
						if ( array_key_exists( '__permissions', $area_settings ) ) {
							unset( $access_settings->{$group}[ $access_type ][ $area ]['__permissions'] );
						}

					}
				}
			}
		}
		update_option( 'toolset-access-options', $access_settings );
	}

	/**
     * Filter wp_link_query result to exclude posts where a user has no read permissions
	 * @param array $result
	 *
	 * @return array
	 */
    public static function filter_wp_link_query( $result ) {

	    foreach ( $result as $result_item_key => $result_item ) {
		    if ( ! apply_filters( 'toolset_access_api_get_post_permissions', false, $result_item['ID'], 'read' ) ) {
			    // no read permission
			    unset( $result[ $result_item_key ] );
		    };
	    }

	    return $result;
    }

	/**
     * Add Post Group to next/prev link query
     *
	 * @param $where
	 * @param $in_same_term
	 * @param $excluded_terms
	 * @param $taxonomy
	 * @param $post
	 *
	 * @return string
	 */
	public static function filter_post_link_query( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		global $wpdb;

		if ( ! apply_filters( 'toolset_access_check_if_post_type_managed', true, $post->post_type )
                && ! apply_filters( 'toolset_access_check_if_post_type_managed', true, 'post' ) ) {
		    return $where;
        }
		$is_post_type_allowed = apply_filters( 'toolset_access_api_get_post_type_permissions', false, $post->post_type, 'read' );

		$where_groups = '';
        if ( ! $is_post_type_allowed ) {
	        $post_groups = apply_filters( 'toolset_access_get_allowed_post_groups', array(), true );
            $where = ", {$wpdb->postmeta} as postmeta " . $where;
            for ( $i = 0; $i < count( $post_groups ); $i++ ) {
	            $where_groups .= " postmeta.meta_value = '{$post_groups[ $i ]}' OR ";
            }
	        if ( ! empty( $where_groups ) ) {
		        $where_groups = substr( $where_groups, 0, - 3 );
		        $where .= " AND postmeta.meta_key = '_wpcf_access_group' AND ( {$where_groups} ) ".
		                   " AND p.ID = postmeta.post_id";
	        }
        } else {
            $post_groups = apply_filters( 'toolset_access_get_allowed_post_groups', array(), false );

	        for ( $i = 0; $i < count( $post_groups ); $i++ ) {
		        $where_groups .= "'{$post_groups[ $i ]}', ";
	        }
	        if ( ! empty( $where_groups ) ) {
		        $where_groups = substr( $where_groups, 0, - 2 );
		        $where = " LEFT JOIN $wpdb->postmeta postmeta ON p.ID = postmeta.post_id ".
		                 "AND postmeta.meta_key = '_wpcf_access_group' " . $where;
		        $where .= " AND ( postmeta.meta_value IS NULL OR ( postmeta.meta_value IS NOT NULL AND postmeta.meta_value ".
		                  "NOT IN ({$where_groups}) ) ) ";

	        }
        }

		return $where;
	}

	/**
     * Get list of Post groups where user can/can't read posts
     *
	 * @param $user_can_read
	 *
	 * @return array
	 */
	public static function get_allowed_post_groups( $groups, $user_can_read ) {
		global $wpcf_access;

		$types = $wpcf_access->settings->types;
		$current_role = self::wpcf_get_current_logged_user_role();
		foreach ( $types as $group_slug => $group_data ) {
			if ( strpos( $group_slug, 'wpcf-custom-group-' ) === 0 ) {
                $read = $group_data['permissions']['read']['roles'];
				if ( in_array( $current_role, $read ) && $user_can_read ){
				    $groups[] = $group_slug;
				} elseif ( ! in_array( $current_role, $read ) && ! $user_can_read ) {
					$groups[] = $group_slug;
                }
			}
        }
        return $groups;
	}

	/**
     * Check if post type or 'post' managed by Access
	 * @param $post
	 *
	 * @return bool
	 */
	public static function check_if_post_type_managed ( $status, $post_type ) {
		global $wpcf_access;

		$types = $wpcf_access->settings->types;

		if ( isset( $types[ $post_type ] ) && $types[ $post_type ]['mode'] == 'permissions' ) {
		    return true;
        }

        return false;
    }


    /**
     *
     */
    public static function preview_custom_error( ) {
        if ( isset( $_GET['toolset_access_preview'] ) && $_GET['toolset_access_preview'] == 1 ){
            global $current_user, $wp_roles, $wpcf_access;
            if ( ! current_user_can('manage_options') ) {
                return;
            }

            if ( ! isset( $_GET['role'] ) || ! isset( $_GET['error_type'] ) || ! isset( $_GET['id'] )
                 || ! isset( $_GET['access_preview_post_type'] ) || ! isset( $_GET['access_preview'] ) ) {
                return;
            }
            $role = sanitize_text_field( $_GET['role'] );
            $error_type = sanitize_text_field( $_GET['error_type'] );
            $error_id = sanitize_text_field( $_GET['id'] );
            $post_type = sanitize_text_field( $_GET['access_preview_post_type'] );
            $access_preview = sanitize_text_field( $_GET['access_preview'] );
            $update_user = false;


            if ( ! array_key_exists( $role, $wp_roles->roles ) && $role != 'guest' ){
                return;
            }

            if ( $access_preview == 'single' ) {
                if ( ( $error_type == 'error_layouts' || $error_type == 'error_ct' ) && empty( $error_id ) ) {
                    return;
                }

                $wpcf_access->wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );
                if ( isset( $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'] ) ){
                    $role_index = array_search( $role, $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'] );
                    unset( $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'][ $role_index ] );
                }
                $wpcf_access->settings->types[ $post_type ]['permissions']['read']['users'] = array();
                $wpcf_access->settings->types['_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ]       = $error_type;
                $wpcf_access->settings->types['_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ] = $error_id;
                $update_user = true;
            }

            if ( $access_preview == 'archive' ) {
                if ( ( $error_type == 'error_layouts' || $error_type == 'error_ct'  || $error_type == 'error_php' ) && empty( $error_id ) ) {
                    return;
                }

                $wpcf_access->settings->types['_archive_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ]       = $error_type;
                $wpcf_access->settings->types['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ] = $error_id;
                $update_user = true;
            }

            if ( $update_user ) {
                if ( $role != 'guest' ) {
                    $current_user->roles[0] = $role;
                    $current_user->caps     = $wp_roles->roles[ $role ]['capabilities'];
                    $current_user->allcaps  = $wp_roles->roles[ $role ]['capabilities'];
                } else {
                    $current_user->roles[0] = '';
                    $current_user->caps     = array( 'read' );
                    $current_user->allcaps  = array( 'read' );
                }

            }
        }

    }


    public static function admin_add_help(){
        $screen = get_current_screen();

		if ( is_null( $screen ) || $screen->base != 'toolset_page_types_access' ) {
			return;
		}

        $help = '<p>'.__("<strong>Post Types</strong>",'wpcf-access').'<br>
        '. __('Control who can do different actions for each post type.', 'wpcf-access') .'
		<a href="https://toolset.com/documentation/user-guides/setting-access-control/" title="'. __('Access Control for Standard and Custom Content Types', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>Taxonomies</strong>",'wpcf-access').'<br>
        '. __('Control who can do different actions for each taxonomy.', 'wpcf-access') .'
		<a href="https://toolset.com/documentation/user-guides/setting-access-control/" title="'. __('Access Control for Standard and Custom Taxonomies ', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>Posts Groups</strong>",'wpcf-access').'<br>
        '. __('Control the read access to individual posts (pages, posts and custom post types). Create ‘post groups’,  which will hold all the items that will have the same read permission. Each group of posts can have as many items as you want.', 'wpcf-access') .'
		<a href="https://toolset.com/documentation/user-guides/limiting-read-access-specific-content/" title="'. __('Limiting read access to specific content', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>WPML Groups</strong>",'wpcf-access').'<br>
        '. __('Control access and editing privileges for users for different languages.', 'wpcf-access') .'
		<a href="https://wpml.org/documentation/translating-your-contents/how-to-use-access-plugin-to-create-editors-for-specific-language/" title="'. __('How to Use Access Plugin to Create Editors for Specific Language', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>Types Fields</strong>",'wpcf-access').'<br>
        '. __('Control who can view and edit custom fields.', 'wpcf-access') .'
			<a href="https://toolset.com/documentation/user-guides/access-control-for-user-fields/" title="'. __('Access Control for Fields', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>Toolset Forms</strong>",'wpcf-access').'<br>
        '. __('Control who has access to different Toolset forms.', 'wpcf-access') .'
			<a href="https://toolset.com/documentation/user-guides/access-control-for-cred-forms/" title="' . esc_attr( __('Access Control for Toolset Forms', 'wpcf-access') ) . '" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$help .= '<p>'.__("<strong>Custom Roles</strong>",'wpcf-access').'<br>
        '. __('Set up custom user roles and control their privileges.', 'wpcf-access') .'
		<a href="https://toolset.com/documentation/user-guides/managing-wordpress-admin-capabilities-access/" title="'. __('Managing WordPress Admin Capabilities with Access', 'wpcf-access') .'" target="_blank"><i class="fa fa-question-circle"></i></a></p>';

		$screen->add_help_tab(
					array(
						'id'		=> 'access-help',
						'title'		=> __('Access Control', 'wpcf-access'),
						'content'	=> $help,
					)
				);
    }

	/*
	 * @since 2.2
	 * toolset_access_admin_notice
	 * Show admin notice that access settings was converted to role based system
	*/
	public static function toolset_access_admin_notice() {
	    global $current_user, $pagenow, $wpdb;
	    $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
	    if ( $pagenow == 'plugins.php' || ( $pagenow == 'admin.php' && $page == 'types_access' )) {
            $user_id = $current_user->ID;
            if ( get_user_meta( $user_id, 'toolset_access_conversion_ignore_notice' ) ) {
                 $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key='toolset_access_conversion_ignore_notice'" );
            }
        }
    }

    /**
     * @since 2.2
     * @param array $access_types
     * @param array $access_tax
     * @param array $access_third_party
     * @return stdClass
     * convert Access settings to new format
     */
    public static function toolset_access_convert_options_format( $access_types = array(), $access_tax = array(), $access_third_party = array() ){

        $access_settings = new stdClass;


        $access_settings->types = array();
        $access_settings->tax = array();
        $access_settings->third_party = array();

        //Parse post types
        foreach( $access_types as $group => $group_permissions){
            $group_permissions = self::toolset_access_format_permissions_array( $group_permissions );
            $access_settings->types[$group] = $group_permissions;
        }

        //Parse taxonomies
        foreach( $access_tax as $group => $group_permissions){
            $group_permissions = self::toolset_access_format_permissions_array( $group_permissions );
            $access_settings->tax[$group] = $group_permissions;
        }

        //Parse third party
        foreach( $access_third_party as $group => $group_permissions){
            foreach( $group_permissions as $sub_group => $sub_group_permissions){
                $sub_group_permissions = self::toolset_access_format_permissions_array( $sub_group_permissions );
                $access_settings->third_party[$group][$sub_group] = $sub_group_permissions;
            }

        }

        return $access_settings;
    }

    /**
     * @since 2.2
     * Add missed capabilities to old Access roles
     */
    public static function toolset_access_fix_old_access_roles(){
        if( !class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }
        $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
        $level_map = self::wpcf_access_role_to_level_map();
        foreach( $ordered_roles as $role => $role_data ){
            if ( isset($role_data['capabilities']['wpcf_access_role']) ){
                $caps = array();
                $role_data = get_role( $role );
				if ( empty( $role_data ) ) {
				    continue;
                }
                $role_level = str_replace('level_', '', $level_map[$role]);
                if ( $role_level == 10 ){
                   $caps = array('edit_posts','edit_others_posts','edit_published_posts','edit_pages','publish_pages','publish_posts','edit_others_pages','edit_published_pages','delete_pages',
                       'delete_others_pages','delete_published_pages','delete_others_posts','delete_published_posts');
                }
                if ( $role_level > 4 ){
                    $caps[] = 'edit_posts';
                }
                $caps[] = 'read';

                for ($i=0;$i<count($caps);$i++){
                    $role_data->add_cap( $caps[$i], 1 );
                }
            }
        }
    }

    /**
     * @since 2.2
     * Return  array of roles with same or highest level
     * @param $role
     * @return array
     */
    public static function toolset_access_get_roles_by_minimal_role( $role ){

        if( !class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }
        $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
        $level_map = self::wpcf_access_role_to_level_map();
        $output_roles = array();
        if ( $role == 'guest' && !isset($level_map[$role]) ){
            $role_level = 'level_0';
            $output_roles[] = 'guest';
        }else{
            if ( isset($level_map[$role]) ){
                $role_level = $level_map[$role];
            }else{
                $role_level = 0;
            }
        }

        foreach( $ordered_roles as $role => $role_data ){
            if ( isset($role_data['capabilities'][$role_level]) ){
                $output_roles[] = $role;
            }
        }
        return $output_roles;
    }

    /**
     * @param array $group_permissions
     * @return array
     * @since 2.2
     * format minimal role to list of roles
     */
    public static function toolset_access_format_permissions_array( $group_permissions = array()){

        if( !class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }
        $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
        $level_map = self::wpcf_access_role_to_level_map();
        if ( isset($group_permissions['permissions']) ){
            foreach ( $group_permissions['permissions'] as $permission_name => $permissions ){
               $minimal_role = $permissions['role'];
               if ( $minimal_role != 'guest' ){
                    $minimal_role_level = $level_map[$minimal_role];
                    $minimal_level = str_replace( 'level_', '', $minimal_role_level );
               }else{
                    $minimal_role_level = 'level_0';
                    $minimal_level = 0;
               }
               $permissions['roles'] = array();
               foreach ( $ordered_roles as $role_name => $role_array ){
                    if ( $minimal_role == 'guest' ){
                        $permissions['roles'][] = $role_name;
                    }else{
                        if ( $role_name == 'guest' ){
                            continue;
                        }
                        if ( isset($role_array['capabilities'][$minimal_role_level]) ){
                            $permissions['roles'][] = $role_name;
                        }
                        //Set Roles with no level to level 0 permissions
                        if ( $minimal_level == 0 && !isset($role_array['capabilities'][$minimal_role_level]) ){
                            $permissions['roles'][] = $role_name;
                        }
                    }
               }
               if ( $minimal_role == 'guest' ){
                    $permissions['roles'][] = 'guest';
               }
               $group_permissions['permissions'][$permission_name] = $permissions;
            }
        }
        return $group_permissions;
    }

    /**
     *
     */
	public static function wpcf_access_early_plugins_loaded() {
		// Initialize the global $wpcf_access and all its most needed data
		global $wpcf_access;
		$wpcf_access						= new stdClass();
		// WPML related properties
		$wpcf_access->wpml_installed		= false;
        $wpcf_access->wpml_installed_groups	= false;
		$wpcf_access->active_languages		= array();
		$wpcf_access->current_language		= null;
		$wpcf_access->language_permissions	= array();
		// API
		add_filter( 'otg_access_filter_is_wpml_installed', array( __CLASS__, 'otg_access_is_wpml_installed' ) );
	}

    /**
     * @param $status
     * @return mixed
     * Return true when WPML plugin active and configured
     */
	public static function otg_access_is_wpml_installed( $status ) {
		global $wpcf_access;
		$status = $wpcf_access->wpml_installed_groups;
		return $status;
	}

    /**
     *
     */
	public static function wpcf_access_wpml_loaded() {
		global $wpcf_access;
		$wpcf_access->wpml_installed		= apply_filters( 'wpml_setting', false, 'setup_complete' );
        $wpcf_access->wpml_installed_groups	= false;
		$wpcf_access->active_languages		= array();
		$wpcf_access->current_language		= apply_filters( 'wpml_current_language', null );
		if ( $wpcf_access->wpml_installed ) {
            if ( wpml_version_is( '3.3', '>=' ) ) {
                $wpcf_access->active_languages		= apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
                foreach( $wpcf_access->active_languages as $lang => $lang_array ) {
	                $keys_to_preserve = array( 'code', 'english_name', 'native_name', 'active' );
	                $wpcf_access->active_languages[ $lang ] = array_intersect_key( $lang_array, array_fill_keys( $keys_to_preserve, null ) );
                }
                $wpcf_access->wpml_installed_groups	= true;
            } else {
                $wpcf_access->wpml_installed		= false;
            }
        }
	}

    /**
     * Add custom capabilities to custom post types and taxonomies
     */
    public static function register_post_types_and_taxonomies() {

        add_action('registered_post_type',	array(__CLASS__,  'wpcf_access_registered_post_type_hook'), 10, 2);
        add_action('registered_taxonomy',	array(__CLASS__,  'wpcf_access_registered_taxonomy_hook'), 10, 3);
    }

	/*
	 *
	 */
	public static function wpcf_access_late_plugins_loaded() {

		global $wpcf_access;

		$model = TAccess_Loader::get('MODEL/Access');

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
		}

        // Access works standalone now
        define( 'WPCF_PLUS', true );
        if ( ! defined( 'WPCF_ACCESS_DEBUG' ) ) {
            define( 'WPCF_ACCESS_DEBUG', false );
		}

        // TODO Not used yet
        // Take a snapshot (to restore on deactivation???)
        /*$snapshot = get_option('wpcf_access_snapshot', array());
        if (empty($snapshot)) {
            $snapshot = get_option('wp_user_roles', array());
            update_option('wpcf_access_snapshot', $snapshot);
        }*/


        $wpcf_access->settings = $model->getAccessSettings();

        $wpcf_access->third_party			= array();
        $wpcf_access->third_party_post		= array();
        $wpcf_access->third_party_caps		= array();
        // Rules
        $wpcf_access->rules					= new stdClass();
        $wpcf_access->rules->types			= array();
        $wpcf_access->rules->taxonomies		= array();
        // Other
        $wpcf_access->errors				= array();
        $wpcf_access->shared_taxonomies		= array();
        $wpcf_access->upload_files			= array();
        $wpcf_access->debug					= array();
        $wpcf_access->debug_hooks_with_args	= array();
        $wpcf_access->debug_all_hooks		= array();

        $wpcf_access = apply_filters('types_access', $wpcf_access);

        // get_user_locale() was introduced in WordPress 4.7
        $locale = ( function_exists('get_user_locale') ? get_user_locale() : get_locale() );
        TAccess_Loader::loadLocale( 'wpcf-access', 'access-' . $locale . '.mo' );

        // Load admin code
        if ( is_admin() ) {
            /*
            * Post functions.
            */
            TAccess_Loader::load('CLASS/Post');
            //TAccess_Loader::load('CLASS/Admin');
        }
        /*
         * Disable Access for administrator
         */
        add_action( 'init', array( __CLASS__, 'wpcf_access_init' ), 9 );
        add_action( 'init', array( __CLASS__, 'wpcf_access_late_init' ), 9999 );
        add_action( 'init', array( __CLASS__, 'wpcf_access_get_taxonomies_shared_init' ), 19 );

        if ( $wpcf_access->wpml_installed ){
            self::wpcf_load_wpml_groups_caps();
        }
        TAccess_Loader::load('CLASS/Ajax');

		/*
         * Hooks to collect and map settings.
         */
        add_filter('wpcf_type', array(__CLASS__, 'wpcf_access_init_types_rules'), 10, 2);
        add_action('wpcf_type_registered', array(__CLASS__,  'wpcf_access_collect_types_rules'));
        add_filter('wpcf_taxonomy_data', array(__CLASS__,  'wpcf_access_init_tax_rules'), 10, 3);
        // WATCHOUT:  this hook callback does not exist
        //add_action('wpcf_taxonomy_registered', array(__CLASS__, 'wpcf_access_collect_tax_rules'));


	   // @toto whouser WTF; we set 3 arguments and we pass only one!!!! Smells!!!
        add_filter('types_access_check', array(__CLASS__,  'wpcf_access_filter_rules'), 15, 3);
        //TAccess_Loader::load('CLASS/Collect');

        /*
         * Check functions.
         *
         * 'user_has_cap' is main WP filter we use to filter capability check.
         * All changes are done on-the-fly and per call. No caching.
         *
         * WP accepts $allcaps array of capabilities returned.
         * It?s actually property of $WP_User->allcaps.
         *
         * TODO we should use other way to assign capabilities
         * This is runing on each current_user_can() call and it might happen docens of times per pagenow
         * At least we have added caching per cap
         *
         */


         add_filter( 'user_has_cap', array(__CLASS__,  'wpcf_access_user_has_cap_filter'), 0, 4 );


        /*
         * Exceptions.
         */
        add_filter('types_access_check', array(__CLASS__,  'wpcf_access_exceptions_check'), 10, 3);
        //TAccess_Loader::load('CLASS/Exceptions');

        if ( isset($_GET['page']) && $_GET['page'] == 'types_access' ){
            add_action('admin_footer', array(__CLASS__,  'wpcf_access_dependencies_render_js'));
        }


        add_filter('types_access_dependencies', array(__CLASS__,  'wpcf_access_dependencies_filter'));

		/**
		 * Filter to check permission for post types
		 * @param $has_permission | required
		 * @param $post_type(slug)(string) | required
		 * @param $option_name (publish, edit_own, edit_any, delete_own, delete_any, read) | optional | default: read
		 * @param $user | optional, default: $current_user
		 * @param $language (code)| optional, default: default language, example: en
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_post_type_permissions', array(__CLASS__, 'toolset_access_api_get_post_type_permissions_process'), 10, 5 );

		/**
		 * Filter to check permission for taxonomies
		 * @param $has_permission | required
		 * @param $taxonomy(slug) | required
		 * @param $option_name (assign_terms, delete_terms, edit_terms, manage_terms) | optional | default: manage_terms
		 * @param $user(object) | optional, default: $current_user
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_taxonomy_permissions', array(__CLASS__, 'toolset_access_api_get_taxonomy_permissions_process'), 10, 4 );

		/**
		 * Filter to check permission for specific post
		 * @param $has_permission | required
		 * @param $post_id(string) | $post(object) | required
		 * @param $option_name (read, edit) | optional | default: read
		 * @param $user | optional, default: $current_user
		 * @param $language (code)| optional, default: default language, example: en
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_post_permissions', array(__CLASS__, 'toolset_access_api_get_post_permissions_process'), 10, 5 );

        TAccess_Loader::load('CLASS/Upload');
        TAccess_Loader::load('CLASS/Debug');

        do_action('wpcf_access_plugins_loaded');
	}

    /**
     *
     */
	public static function wpcf_access_early_init() {
        // Force roles initialization
        // WP is lazy and it does not initialize $wp_roles if user is not logged in.
        global $wpcf_access;

        $role = self::wpcf_get_current_logged_user_role();
        if ( $wpcf_access->wpml_installed ) {
            if ( wpml_version_is( '3.3', '>=' ) ) {
                if ( $role != 'administrator' ) {
                    add_filter('wpml_active_languages_access',	array(__CLASS__,'otg_access_check_language_edit_permissions'), 10, 2);
                    add_filter('wpml_override_is_translator',	array(__CLASS__,'otg_access_filter_wpml_override_is_translator'), 10, 3);
                    add_filter('wpml_link_to_translation',		array(__CLASS__,'otg_access_filter_wpml_link'), 11, 4);
                    add_filter('wpml_icon_to_translation',		array(__CLASS__,'otg_access_filter_wpml_icon'), 9, 4);
                    add_filter('wpml_text_to_translation',		array(__CLASS__,'otg_access_filter_wpml_text'), 9, 4);
                } else{
                    $wpcf_access->wpml_installed = false;
                }
            } else {
                $wpcf_access->wpml_installed = false;
            }
        }

		// Setup roles
        self::$roles = self::wpcf_get_editable_roles();
    }

    /**
     * @param boolean  $has_permission
     * @param string $post
     * @param string $option_name
     * @param string $user
     * @param string $language
     * @return bool
     */
    public static function toolset_access_api_get_post_permissions_process( $has_permission = false, $post = '', $option_name = 'read', $user = '', $language = '' ){
        if ( empty($post) ){
            return $has_permission;
        }

        if ( !is_object($post) ){
            $post = get_post($post);
        }

        if ( !isset($post->ID) ){
            return $has_permission;
        }

        if ( in_array($option_name, array('edit', 'read')) === FALSE ){
            return $has_permission;
        }



        $converted_caps = array( 'edit_own' => 'edit_posts', 'edit_any' => 'edit_others_posts', 'read' => 'read' );

        if ( empty($user) ){
            global $current_user;
            $user = $current_user;
        }

        if ( !is_object($user) ){
            $user = get_user_by( 'ID', $user );
        }

        if ( !is_object($user) ){
            return $has_permission;
        }
        if ( $option_name == 'edit' ){
            if ( $post->post_author == $user->ID ){
                $option_name = 'edit_own';
            }else{
                $option_name = 'edit_any';
            }
        }

        $role = self::wpcf_get_current_logged_user_role( $user );
        $post_type = $post->post_type;
        if ( $role == 'administrator' ){
            return true;
        }

        global $wpcf_access, $wp_roles;

        //If Access settings not set yet use capabilities from role
        if( !isset($wpcf_access->settings->types) ){
            if ( $role == 'guest'  ){
                if ( $option_name == 'read' ){
                    return true;
                }else{
                    return false;
                }
            }
            $role_caps = $wp_roles->roles;
            if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
                return true;
            }else{
                return false;
            }
        }

	    $types_settings = $wpcf_access->settings->types;

	    if ( $option_name == 'read' ){
		    $group = get_post_meta($post->ID, '_wpcf_access_group', true);
		    if ( !empty($group) ){
			    if ( isset($types_settings[$group]) ){
				    if (
					    in_array($role, $types_settings[ $group ]['permissions'][ $option_name ]['roles']) !== FALSE ||
					    self::check_permissions_for_specific_user( $types_settings[ $group ]['permissions'][ $option_name ] )
				    ){
					    return true;
				    }else{
					    return false;
				    }
			    }
		    }
	    }

        $is_wpml_installed = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
        // Check WPML permission
        if ( $is_wpml_installed ){
            $language_settings = $wpcf_access->language_permissions;
            if ( empty($language) ){
                $language = apply_filters( 'wpml_setting', '', 'default_language' );
            }

            if ( isset($language_settings[$post_type][$language][$option_name]) ){
                if (
                    in_array( $role, $language_settings[ $post_type ][ $language ][ $option_name ]['roles'] ) !== FALSE ||
                    self::check_permissions_for_specific_user( $language_settings[ $post_type ][ $language ][ $option_name ] )
                ){
                    return true;
                }else{
                    return false;
                }
            }
        }

        //If settings set and post type managed by Access
        if ( isset($types_settings[$post_type]) && $types_settings[$post_type]['mode'] == 'permissions' && isset($types_settings[$post_type]['permissions'][$option_name]['roles']) ){
            if (
                in_array( $role, $types_settings[ $post_type ]['permissions'][ $option_name ]['roles'] ) !== FALSE ||
                self::check_permissions_for_specific_user( $types_settings[ $post_type ]['permissions'][ $option_name ] )
            ){
                return true;
            }else{
                return false;
            }
        }
        //If settings set and post not type managed by Access
        elseif ( (isset($types_settings[$post_type]) && $types_settings[$post_type]['mode'] != 'permissions' && isset($types_settings['post']) && $types_settings[$post_type]['mode'] == 'permissions') ||
            ( !isset($types_settings[$post_type]) && isset($types_settings['post']) && $types_settings['post']['mode'] == 'permissions' )  ){
            if (
                isset( $types_settings['post']['permissions'][ $option_name ]['roles'] )  &&
                (
                    in_array( $role, $types_settings['post']['permissions'][ $option_name ]['roles'] ) !== FALSE ||
                    self::check_permissions_for_specific_user( $types_settings['post']['permissions'][ $option_name ] )
                )
            ){
                return true;
            }else{
                return false;
            }
        }
        //Use role capabilities
        else{
            if ( $role == 'guest'  ){
                if ( $option_name == 'read' ){
                    return true;
                }else{
                    return false;
                }
            }
            $role_caps = $wp_roles->roles;
            if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * @param boolean  $has_permission
     * @param string $taxonomy
     * @param string $option_name
     * @param string $user
     * @return bool
     */
    public static function toolset_access_api_get_taxonomy_permissions_process( $has_permission = false, $taxonomy = '', $option_name = 'manage_terms', $user = '' ){

        if ( empty($taxonomy) ){
            return $has_permission;
        }

        if ( in_array($option_name, array('assign_terms', 'delete_terms', 'edit_terms', 'manage_terms')) === FALSE ){
            return $has_permission;
        }

        if ( empty($user) ){
            global $current_user;
            $user = $current_user;
        }

        if ( !is_object($user) ){
            $user = get_user_by( 'ID', $user );
        }

        if ( !is_object($user) ){
            return $has_permission;
        }

        $converted_caps = array('assign_terms' => 'edit_posts', 'delete_terms' => 'manage_categories', 'edit_terms' => 'manage_categories', 'manage_terms' => 'manage_categories' );
        $role = self::wpcf_get_current_logged_user_role( $user );

        if ( $role == 'administrator' ){
            return true;
        }

        global $wpcf_access, $wp_roles;
        $tax_settings = $wpcf_access->settings->tax;

        if( !isset($wpcf_access->settings->tax) ){
            if ( $role == 'guest'  ){
                return false;
            }
            $role_caps = $wp_roles->roles;
            if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
                return true;
            }else{
                return false;
            }
        }

        if ( isset($tax_settings[$taxonomy]) && $tax_settings[$taxonomy]['mode'] == 'permissions' ){
            if (
                in_array($role, $tax_settings[$taxonomy]['permissions'][ $option_name ]['roles']) !== FALSE ||
                self::check_permissions_for_specific_user( $tax_settings[$taxonomy]['permissions'][ $option_name ] )
            ){
                return true;
            }else{
                return false;
            }
        }
        //If settings set and tax not type managed by Access
        elseif ( (isset($tax_settings[$taxonomy]) && $tax_settings[$taxonomy]['mode'] != 'permissions' && isset($tax_settings['category']) && $tax_settings[$taxonomy]['mode'] == 'permissions') ||
            ( !isset($tax_settings[$taxonomy]) && isset($tax_settings['category']) && $tax_settings[$taxonomy]['mode'] == 'permissions' )  ){
            if (
                in_array($role, $tax_settings['category']['permissions'][ $option_name ]['roles']) !== FALSE ||
                self::check_permissions_for_specific_user( $tax_settings['category']['permissions'][ $option_name ] )
            ){
                return true;
            }else{
                return false;
            }
        }

        //Use role capabilities
        else{
            if ( $role == 'guest'  ){
                return false;
            }
            global $wp_roles;
            $role_caps = $wp_roles->roles;
            if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
                return true;
            }else{
                return false;
            }
        }



    }

    /**
     * @param boolean $has_permission
     * @param string $post_type
     * @param string $option_name
     * @param string $user
     * @param string $language
     * @return bool
     */
    public static function toolset_access_api_get_post_type_permissions_process( $has_permission = false, $post_type = '', $option_name = 'read', $user = '', $language = '' ){

        if ( empty($post_type) ){
            return $has_permission;
        }
        $model = TAccess_Loader::get('MODEL/Access');
        $_post_types = $model->getPostTypes();

        if ( !isset($_post_types[$post_type]) || $_post_types[$post_type]->public != 1 ){
            return $has_permission;
        }
        if ( in_array($option_name, array('publish', 'edit_own', 'edit_any', 'delete_own', 'delete_any', 'read')) === FALSE ){
            return $has_permission;
        }

        $converted_caps = array('publish' => 'publish_posts', 'edit_own' => 'edit_posts', 'edit_any' => 'edit_others_posts',
            'delete_own' => 'delete_posts', 'delete_any' => 'delete_others_posts', 'read' => 'read' );

        if ( empty($user) ){
            global $current_user;
            $user = $current_user;
        }

        if ( !is_object($user) ){
            $user = get_user_by( 'ID', $user );
        }

        if ( !is_object($user) ){
            return $has_permission;
        }

        $role = self::wpcf_get_current_logged_user_role( $user );

        if ( $role == 'administrator' ){
            return true;
        }

        global $wpcf_access, $wp_roles;

        //If Access settings not set yet use capabilities from role
        if( !isset($wpcf_access->settings->types) ){
            if ( $role == 'guest'  ){
                if ( $option_name == 'read' ){
                    return true;
                }else{
                    return false;
                }
            }
            $role_caps = $wp_roles->roles;
            if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
                return true;
            }else{
                return false;
            }
        }

        if ( $wpcf_access->wpml_installed ){
            $language_settings = $wpcf_access->language_permissions;
            if ( empty($language) ){
                $language = apply_filters( 'wpml_setting', '', 'default_language' );
            }

            if ( isset($language_settings[$post_type][$language][$option_name]) ){
                if (
                    in_array( $role, $language_settings[ $post_type ][ $language ][ $option_name ]['roles']) !== FALSE ||
                    self::check_permissions_for_specific_user( $language_settings[ $post_type ][ $language ][ $option_name ] )
                ){
                    return true;
                }else{
                    return false;
                }
            }
        }


        $types_settings = $wpcf_access->settings->types;
        //If settings set and post type managed by Access
        if ( isset($types_settings[$post_type]) && $types_settings[$post_type]['mode'] == 'permissions' && isset($types_settings[$post_type]['permissions'][$option_name]['roles']) ){
            if (
                in_array( $role, $types_settings[ $post_type ]['permissions'][ $option_name ]['roles']) !== FALSE ||
                self::check_permissions_for_specific_user( $types_settings[ $post_type ]['permissions'][ $option_name ] )
            ){
                return true;
            }else{
                return false;
            }
        }
        //If settings set and post not type managed by Access
        elseif ( (isset($types_settings[$post_type]) && $types_settings[$post_type]['mode'] != 'permissions' && isset($types_settings['post']) && $types_settings[$post_type]['mode'] == 'permissions') ||
            ( !isset($types_settings[$post_type]) && isset($types_settings['post']) && $types_settings['post']['mode'] == 'permissions' )  ){
            if ( isset( $types_settings['post']['permissions'][ $option_name ]['roles'] ) &&
                 (
                     in_array( $role, $types_settings['post']['permissions'][ $option_name ]['roles']) !== FALSE ||
                     self::check_permissions_for_specific_user( $types_settings['post']['permissions'][ $option_name ] )
                 )
            ){
                return true;
            }else{
                return false;
            }
        }
        //Use role capabilities
        if ( $role == 'guest'  ){
            if ( $option_name == 'read' ){
                return true;
            }else{
                return false;
            }
        }
        $role_caps = $wp_roles->roles;
        if ( isset( $role_caps[$role]['capabilities'][$converted_caps[$option_name]] ) ){
            return true;
        }else{
            return false;
        }

    }

    /**
     * Check if current user in specific users list
     * @param $setting
     *
     * @return bool
     */
    private static function check_permissions_for_specific_user( $setting ) {
        global $current_user;
        if( isset( $setting['users'] ) && is_array( $setting['users'] )) {
            if ( in_array( $current_user->ID, $setting['users'] ) !== FALSE ) {
                return true;
            }
        }
        return false;
    }



	/**
	* ----------------------------
	* WPML related methods
	* ----------------------------
	*/

    /*
        Replace Translation management permissions with Access settings
    */
    public static function otg_access_filter_wpml_override_is_translator( $is_translator, $user_id, $args ){
        return true;
    }

    /**
     * @param $link
     * @param $post_id
     * @param $lang
     * @param $trid
     * @return string
     */
    public static function otg_access_filter_wpml_link( $link, $post_id, $lang, $trid ){
        $status = self::otg_access_wpml_check_access_by_post_id( $post_id, $lang);
        if (
			! $status['edit_any']
			&& ! $status['edit_own']
		) {
            $link = '#no_privileges';
        }else{
            $link =  remove_query_arg( 'return_url', $link );
        }
        return $link;
    }

    /**
     * @param $icon
     * @param $post_id
     * @param $lang
     * @param $trid
     * @return string
     * Replace existing translate post icon enabled/disabled
     */
    public static function otg_access_filter_wpml_icon( $icon, $post_id, $lang, $trid ){
        $status = self::otg_access_wpml_check_access_by_post_id( $post_id, $lang);
        if (
			! $status['edit_any']
			&& ! $status['edit_own']
		) {
            if ( $icon == 'add_translation.png' ){
                $icon = 'add_translation_disabled.png';
            }else{
                $icon = 'edit_translation_disabled.png';
            }
        }
        return $icon;
    }

    /**
     * @param $text
     * @param $post_id
     * @param $lang
     * @param $trid
     * @return mixed
     */
    public static function otg_access_filter_wpml_text( $text, $post_id, $lang, $trid ){
        $status = self::otg_access_wpml_check_access_by_post_id( $post_id, $lang);
        if ( !$status['edit_any'] && !$status['edit_own'] ){
            $text = __('You do not have permissions', 'wpcf-access');
        }
        return $text;
    }

    /*
     *
     */
    public static function otg_access_check_language_read_permissions( $languages ) {
        global $wpcf_access, $current_user, $typenow;
        if ( current_user_can('manage_options') ){
             return $languages;
        }
        $post_id = $post_id = self::wpcf_access_get_current_page();

        if ( empty($post_type) && !empty($typenow) ){
            $post_type = $typenow;
        }

        if ( empty($post_type) && !empty($post_id) ){
            $post_type = get_post_field( 'post_type', $post_id );
        }

        if ( empty($post_type) && isset($_GET['post_type']) ){
            $post_type = $_GET['post_type'];
        }

        if ( empty($post_type) ){
            $post_type = 'post';
        }


        if ( !isset($wpcf_access->settings->types[$post_type])){
            return $languages;
        }

        $access_settings = $wpcf_access->language_permissions;
        if ( isset($access_settings[$post_type]) && !empty($access_settings[$post_type]) ){
            $languages_permissions = $access_settings[$post_type];
            foreach( $languages_permissions as $language => $language_permissions ){
                $status = self::otg_access_wpml_check_access_by_post_id( '', $language, $post_type, array('read'=>true));
                if ( !$status['read'] ){
                    unset($languages[$language]);
                }
            }
        }
        return $languages;
    }

    /*
    * Check WPML permissions by post id
    */
    public static function otg_access_wpml_check_access_by_post_id( $post_id, $lang, $post_type = '', $caps_to_check = array('edit_any' => true, 'edit_own' => true), $user = '' ){
        global $wpcf_access, $current_user, $typenow;
        /*if ( current_user_can('manage_options') ){
             return $caps_to_check;
        }*/

        $user_id = $current_user->ID;

        if ( empty($post_id) && isset($_GET['post']) ){
            $post_id = $_GET['post'];
        }

        if ( empty($post_type) && !empty($typenow) ){
            $post_type = $typenow;
        }

        if ( empty($post_type) && !empty($post_id) ){
            $post_type = get_post_field( 'post_type', $post_id );
        }

        if ( empty($post_type) && isset($_GET['post_type']) ){
            $post_type = $_GET['post_type'];
        }

        if ( empty($post_type) ){
            $post_type = 'post';
        }

        if ( empty($user) ){
            $user = $current_user;
        }

        $output = $caps_to_check;

        $access_settings = $wpcf_access->language_permissions;
        $role = self::wpcf_get_current_logged_user_role();

        if ( isset($access_settings[$post_type][$lang]) && !empty($access_settings[$post_type][$lang]) ){
            $language_permissions = $access_settings[$post_type][$lang];

            if ( !empty($post_id) ){
                $post_author = get_post_field( 'post_author', $post_id );
            }
            else{
                $post_author = $user_id;
            }

            foreach($caps_to_check as $cap => $status ){
                if ( !isset($language_permissions[$cap])){
                    continue;
                }

                ${$cap} = $language_permissions[$cap]['roles'];
                if ( isset($language_permissions[$cap]['users']) ){
                    ${$cap . '_users'} = $language_permissions[$cap]['users'];
                }
                $output[$cap] = false;
                $_cap = str_replace('_any','_own',$cap);
                if ( !is_array(${$cap}) ){
                    ${$cap} = array(${$cap});
                }
                if ( strpos( $cap, 'own' ) == '' && ( in_array( $role, ${$cap} ) !== FALSE || ( isset( ${$cap . '_users'} ) && in_array( $user_id, ${$cap . '_users'} ) ) ) ){
                    $output[ $cap ] = true;
                    $output[ 'temp_' . $_cap ] = true;
                }

                if ( strpos( $cap, 'own' ) > 0 && $user_id == $post_author &&
                    ( in_array( $role, ${$cap} ) !== FALSE || ( isset( ${$cap . '_users'} ) && in_array( $user_id, ${$cap . '_users'} ) ) ) ){
                    $output[$cap] = true;
                }
                if ( strpos($cap, 'own') > 0 && isset($output['temp_'.$_cap]) && $output['temp_'.$_cap] ){
                    $output[$cap] = true;
                    unset($output['temp_'.$_cap]);
                }
                if ( !$output[$cap] ){
                    $output[$cap] = self::otg_access_check_translation_by_post_id( $post_id, $post_type, $user_id, $lang );
                }
            }
        } else{
            foreach( $output as $cap => $status ) {
                $output[ $cap ] = false;
            }
        }

        return $output;

    }

    /**
     * @param $post_id
     * @param $post_type
     * @param $user_id
     * @param $lang
     * @return bool
     */
    public static function otg_access_check_translation_by_post_id( $post_id, $post_type, $user_id, $requested_language ) {
        global $wpcf_access;
        if ( !has_action('wpml_tm_loaded') || !did_action('wpml_tm_loaded') ){
            return false;
        }
        $translation_batches = self::wpcf_access_get_translation_batches( $user_id );
        if ( empty($translation_batches) ){
            return false;
        }
        $wpml_active_languagess = $wpcf_access->active_languages;

        foreach ( $translation_batches as $batch_id => $batch ) {
            for ( $i=0, $count = count( $batch ); $i < $count; $i++ ) {
                if ( $batch[ $i ]['status'] == 'Complete' || $batch[ $i ]['status'] == 'Translation complete' || ! isset( $batch[ $i ]['original_post_type'] ) ){
                    continue;
                }
                $batch_post_type = substr( $batch[ $i ]['original_post_type'], 5 );
                $language_from = $batch[ $i ]['from_language'];
                $language_to = $batch[ $i ]['to_language'];
                $language_ask = $wpml_active_languagess[ $requested_language ];
                $language_ask = ( isset( $language_ask['translated_name'] ) ? $language_ask['translated_name'] : $language_ask['english_name'] );
                if ( !empty($post_id) ){
                   if ( $batch_post_type != $post_type ){
                        return false;
                    }
                    $original_id = $batch[ $i ]['original_doc_id'];

                    $access_cache_user_has_cap_key = md5( 'access::post_language_' . $post_id );
                    $cached_caps = Access_Cacher::get( $access_cache_user_has_cap_key, 'access_cache_post_languages' );
                    if ( false === $cached_caps ) {
                        $post_language = apply_filters( 'wpml_post_language_details', '', $post_id );
                        Access_Cacher::set( $access_cache_user_has_cap_key, $post_language, 'access_cache_post_languages' );
                    }else{
                        $post_language = $cached_caps;
                    }

                    $post_language = ( isset($post_language['translated_name'])?$post_language['translated_name']:$post_language['native_name'] );

                    if ( $original_id == $post_id ){
                        if ( $post_language == $language_from && $language_ask == $language_to ){
                        return true;
                        }
                    }
                }
                else{
                    if ( $batch_post_type == $post_type && ( $language_from == $language_ask || $requested_language == $batch[ $i ]['language_code'] ) ){
                        return true;
                    }
                }
            }

		}
		return false;
    }

    /**
     * @param $post_type_name
     * @return int|string
     * Get post type name by plural name
     */
    public static function get_post_type_singular_by_plural( $post_type_name ) {
        $model = TAccess_Loader::get( 'MODEL/Access' );
        $_post_types = $model->getPostTypes();
        foreach ( $_post_types as $post_type => $post_type_data ) {
            if (
                isset( $post_type_data->__accessNewCaps ) && $post_type_data->__accessNewCaps->edit_posts == 'edit_' . $post_type_name
            ) {
                return $post_type;
            }
        }
        return '';
    }

    /**
     * Load WPML groups permissions if exists
     */
    public static function wpcf_load_wpml_groups_caps() {
        global $wpcf_access;
        $wpcf_access->language_permissions = array();
        $settings_access = $wpcf_access->settings->types;
        // Load language permissions from groups
        if ( is_array($settings_access) && !empty($settings_access) ){
            foreach ( $settings_access as $group_slug => $group_data) {
                if ( strpos($group_slug, 'wpcf-wpml-group-') !== 0 ) {
                    continue;
                }
                if ( !apply_filters('wpml_is_translated_post_type', null, $group_data['post_type']) ){
                    continue;
                }
                if ( isset($group_data['languages']) && is_array($group_data['languages']) && !empty($group_data['languages']) ){
                    foreach( $group_data['languages'] as $lang => $lang_data ){
                        $wpcf_access->language_permissions[$group_data['post_type']][$lang] = $group_data['permissions'];
                        $wpcf_access->language_permissions[$group_data['post_type']][$lang]['group'] = $group_slug;
                    }
                }
            }
        }
        self::wpcf_load_wpml_languages_permissions();
    }

    /**
     * Load missed WPML permissions
     */
    public static function wpcf_load_wpml_languages_permissions() {
        global $wpcf_access;

        $model = TAccess_Loader::get('MODEL/Access');
        $settings_access = $wpcf_access->settings->types;
        $_post_types = $model->getPostTypesNames();
        //Load language permissions from post_type, if group for language not exists
        $wpml_active_languages = $wpcf_access->active_languages;
        foreach ( $_post_types as $post_type ) {
            foreach ( $wpml_active_languages as $language => $language_data ) {
                if (
					! isset( $wpcf_access->language_permissions[ $post_type ][ $language ] )
					&& isset( $settings_access[ $post_type ]['permissions'] )
					&& $settings_access[ $post_type ]['mode'] != 'not_managed'
				) {
                    $wpcf_access->language_permissions[ $post_type ][ $language ] = $settings_access[ $post_type ]['permissions'];
                }
                else if (
                    isset( $settings_access[ $post_type ]['permissions'] )
                    && $settings_access[ $post_type ]['mode'] == 'not_managed'
                    && isset( $settings_access['post']['permissions'] )
                    && $settings_access['post']['mode'] != 'not_managed'
				) {
                    $wpcf_access->language_permissions[ $post_type ][ $language ] = $settings_access['post']['permissions'];
                }
            }
        }
    }


    /**
     * @param $languages
     * @param $args
     * @return mixed
     */
    public static function otg_access_check_language_edit_permissions($languages, $args){
        global $wpcf_access, $current_user, $_post_types, $typenow, $post;
        if ( !isset($args['action']) ){
            return $languages;
        }
        if ( current_user_can('manage_options') ){
             return $languages;
        }
        $wpml_default_language = apply_filters( 'wpml_setting', '', 'default_language' );
        $action = $args['action'];
        $post_id =  isset($args['post_id']) ? $args['post_id'] : '';
        $post_type =  isset($args['post_type']) ? $args['post_type'] : '';

        if ( empty($post_type) && !empty($typenow) ){
            $post_type = $typenow;
        }

        if ( empty($post_id) && isset($_GET['post']) ){
            $post_id = $_GET['post'];
        }

        if ( empty($post_type) && !empty($post_id) ){
            $post_type = get_post_field( 'post_type', $post_id );
        }

        if ( empty($post_type) && isset($_GET['post_type']) ){
            $post_type = $_GET['post_type'];
        }

        //Get post type on front-end
        if ( !is_admin() ){
            if ( isset( $post->post_type ) ){
                $post_type = $post->post_type;
            }
        }

        if ( empty($post_type) ){
            $post_type = 'post';
        }
        if ( !isset($wpcf_access->settings->types[$post_type])){
           $post_type = self::get_post_type_singular_by_plural( $post_type );
        }

        if ( !isset($wpcf_access->settings->types[$post_type])){
            return $languages;
        }

        $access_settings = $wpcf_access->language_permissions;

        if ( $action == 'read' ){
            $access_settings = $wpcf_access->language_permissions;
            if ( isset($access_settings[$post_type]) && !empty($access_settings[$post_type]) ){
                $languages_permissions = $access_settings[ $post_type ];
                foreach( $languages_permissions as $language => $language_permissions ){
                    $status = self::otg_access_wpml_check_access_by_post_id( '', $language, $post_type, array('read'=>true));
                    if ( !$status['read'] ){
                        unset($languages[$language]);
                    }
                }
            }
        }else{
            if ( isset($access_settings[$post_type]) && !empty($access_settings[$post_type]) ){
                $languages_permissions = $access_settings[ $post_type ];
                foreach( $languages_permissions as $language => $language_permissions ){
                    if ( isset($args['main']) && $args['main'] &&  $language == $wpml_default_language){
                        $additional_lang[$language] = $languages[$language];
                    }
                    $status = self::otg_access_wpml_check_access_by_post_id( '', $language, $post_type );
                    if ( !$status['edit_any'] && !$status['edit_own']  ){//&& $language != $wpml_default_language
                        unset($languages[$language]);

                    }
                }
            }
        }
        return $languages;
    }



    /**
     * @param $custom_inner_shortcodes
     * @return array
     * Add toolset_access shortcode to Views:Third-party shortcode arguments
     */
    public static function wpv_access_string_in_custom_inner_shortcodes($custom_inner_shortcodes) {
        $custom_inner_shortcodes[] = 'toolset_access';
        return $custom_inner_shortcodes;
    }

	/*
	 * Check if user have media permission
	*/
	public static function wpcf_access_check_if_user_can_do_media( $post_type = 'attachment', $action = 'read' ){
		global $current_user, $wpcf_access;
        $settings_access = $wpcf_access->settings->types;

		if ( !isset($settings_access[$post_type]) ){
			return true;
		}
		if ( $settings_access[$post_type]['mode'] == 'not_managed' ){
			return true;
		}

		$role = self::wpcf_get_current_logged_user_role();

		if ( $role == 'administrator' ){
			return true;
		}

		if ( in_array($role, $settings_access[$post_type]['permissions'][$action]['roles']) !== FALSE ){
			return true;
		}else{
			return false;
		}

	}

	/*
	 * Disable media upload
	 */
	public static function wpcf_access_check_add_media_permissions(  ){
		global $current_user;
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return true;
		}

		$user_can_edit_own = self::wpcf_access_check_if_user_can_do_media( 'attachment', 'edit_own' );
		$user_can_read = self::wpcf_access_check_if_user_can_do_media( 'attachment', 'read' );
		if ( !$user_can_edit_own ){
			remove_submenu_page( 'upload.php', 'media-new.php' );
			add_action('wp_handle_upload_prefilter',array(__CLASS__,'wpcf_access_disable_media_upload'),1);
		}
		if ( !$user_can_read ){
            global $menu;
            if ( isset($menu) && is_array($menu) ){
                remove_menu_page( 'upload.php' );
            }
			remove_action( 'media_buttons', 'media_buttons' );
		}

		if ( $_SERVER['SCRIPT_NAME'] == '/wp-admin/upload.php' ) {
			if ( !$user_can_read ){
				wp_redirect( get_admin_url() ); exit;
			}
        }
		if ( $_SERVER['SCRIPT_NAME'] == '/wp-admin/media-new.php' ) {
			if ( !$user_can_edit_own ){
				wp_redirect( get_admin_url() . 'upload.php' ); exit;
			}
        }
	}

    /**
     * @param $query
     * @return mixed
     */
	public static function wpcf_access_show_only_user_media( $query ){
		if ( $query->query['post_type'] === 'attachment' ){
			global $user_ID;
	        $query->set('author',  $user_ID);
		}
        return $query;
	}

    /**
     * @param $file
     * @return mixed
     */
	public static function wpcf_access_disable_media_upload( $file ){
		$file['error'] = __('You have no access to upload files', 'wpcf-access');
	  	return $file;
	}

	/*
	 * Show public preview
	 */
	public static function wpcf_access_show_post_preview($query ){
		if ($query->is_main_query() && $query->is_preview() && $query->is_singular() ){
            /**
             * filter_posts_results - save queried $post if user has preview_any permission
             * WP by default erase $post if post is draft and user has no capability edit_posts
             * filter_the_posts retrun $post
             *
             * posts_results use priority 9 to run the filter before Views
             */
            add_filter( 'posts_results', array( __CLASS__, 'filter_posts_results' ), 9, 2 );
            add_filter( 'the_posts',     array( __CLASS__, 'filter_the_posts' ), 10, 2 );
        }
		return $query;
	}

    /**
     * Check if current user can preview a post and save post object to $preview_posts
     * @param array $posts
     * @param WP_Query $query
     * @global $preview_posts
     *
     * @return array|void
     * @since 2.4
     */
    public static function filter_posts_results( array $posts, WP_Query $query ) {
        remove_filter( 'pre_get_posts', array( __CLASS__, 'wpcf_access_show_post_preview' ) );
        remove_filter( 'posts_results', array( __CLASS__, 'filter_posts_results' ), 10, 2 );

        if ( empty( $posts ) ){
            return array();
        }

        $post_id = $posts[0]->ID;

        $model = TAccess_Loader::get('MODEL/Access');
        $settings_access = $model->getAccessTypes();
        $post_type = get_post_type( $post_id );
        $role = self::wpcf_get_current_logged_user_role();

        if ( isset( $settings_access[ $post_type ] ) && $settings_access[ $post_type ]['mode'] == 'permissions' ){
            if ( isset( $settings_access[ $post_type ]['permissions']['read_private']['roles'] ) ){
                if ( in_array( $role, $settings_access[ $post_type ]['permissions']['read_private']['roles']) !== FALSE ){
                    self::$preview_posts = $posts;
                }else{
                    remove_filter( 'the_posts', array( __CLASS__, 'filter_the_posts' ) );
                }
            }
        }

        return $posts;
    }

    /**
     * @param $posts
     * @param WP_Query $query
     *
     * @return array
     */
    public static function filter_the_posts( $posts, WP_Query $query ) {
        if ( ! empty( self::$preview_posts )  ) {
            $posts = self::$preview_posts;
	        self::$preview_posts = array();
        }
        return $posts;
    }

	/**
     * Filter menu items and exclude posts where current user has no read permissions
	 * @param $items
	 * @param $menu
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function wpcf_access_set_menu_permissions( $items, $menu, $args ){
        foreach ( $items as $key => $item ) {
            if ( ! isset( $item->type ) || !isset( $item->type ) || $item->type != 'post_type' || ( $item->type == 'post_type' && $item->object == 'nav_menu_item' ) ){
                continue;
            }
            $has_read_permission = true;
            $has_read_permission = apply_filters( 'toolset_access_api_get_post_permissions', $has_read_permission, $item->object_id, 'read' );
            if ( ! $has_read_permission ){
                unset( $items[$key] );
            }
        }
        return $items;
    }

    /*
	 * Add group permissions to feeds
	 */
	public static function wpcf_access_set_feed_permissions( $query ){
		if ( isset($query['feed']) ){

            global $current_user, $wpcf_access;
            $role = self::wpcf_get_current_logged_user_role();
            if ( $role == 'administrator' ){
                return $query;
            }

            $settings_access = $wpcf_access->settings->types;
            $exclude_ids = array();
            foreach ( $settings_access as $group_slug => $group_data) {
                if ( strpos($group_slug, 'wpcf-custom-group-') === 0 ) {
                    if ( isset( $settings_access[ $group_slug ]['permissions']['read']['users'] )
                         && in_array( $current_user->ID, $settings_access[ $post_type ]['permissions']['read']['users'] ) ){
                        continue;
                    }
                    if ( in_array($role, $settings_access[$group_slug]['permissions']['read']['roles']) !== FALSE ){
                        $exclude_posts = get_posts( array( 'meta_key' => '_wpcf_access_group', 'meta_value'=>$group_slug, 'post_type' => get_post_types() ) );
                        $temp_posts = wp_list_pluck($exclude_posts,'ID');
                        $exclude_ids = array_merge($exclude_ids,$temp_posts);
                    }
                }
            }
            $query['post__not_in'] = $exclude_ids ;
        }
        return $query;
	}

	/*
	 * Access shortcode: toolset_access
	 *
	 * Description: Set access to part of content in posts/pages/content templates/views
	 *
	 * Parameters:
	 * 'role' => List of roles separated by comma
	 * 'operator' => 'allow|deny'
	 * allow - show content for only listed roles
	 * deny - deny content for listed roles, all other roles will see this content
	 * 'raw' => "false|true", default false
	 *
	 * Note: Roles can be uppercase/lowercase
	 * Note: Shortcodes can be used inside toolset_access
	 *
	 * Example: [toolset_access role="Administrator,guest" operator="allow"]Content here[/toolset_access]
	 *
	*/
	public static function wpcf_access_create_shortcode_toolset_access( $atts, $content ){
		 extract(
			shortcode_atts(
				array(
			      'role'		=> '',
			      'operator'	=> 'allow',
			      'raw'			=> 'false'
				),
				$atts
			)
		);

		 if ( empty($content) ){
		 	return;
		 }

		 if ( empty($role) ){
		 	return;
		 }

		global $wp_roles;
		$received_roles = explode(',', $role );
        $received_roles_normal = explode(',', strtolower($role) );
		$roles = $wp_roles->roles;
		$recived_roles_fixed = array();
		foreach ($roles as $levels => $roles_data)
        {
        	if ( in_array($roles_data['name'], $received_roles) || in_array($roles_data['name'], $received_roles_normal) ){
        		$recived_roles_fixed[] = $levels;
			}
			if ( in_array($levels, $received_roles) ){
        		$recived_roles_fixed[] = $levels;
			}
		}
		if ( in_array('Guest', $received_roles) || in_array('guest', $received_roles_normal) ){
        		$recived_roles_fixed[] = 'guest';
		}
		$current_role = self::wpcf_get_current_logged_user_role();

		if ( in_array($current_role, $recived_roles_fixed) ){
			if ( $operator == 'allow' ){
				return self::wpcf_access_do_shortcode_content( $content, $raw );
		 	}
		}else{
			if ( $operator == 'deny' ){
				return self::wpcf_access_do_shortcode_content( $content, $raw );
		 	}
		}

	}

	/*
	 * Add filters to shortcode content
	 *
	*/
	public static function wpcf_access_do_shortcode_content( $content, $raw )
    {
    	if ( function_exists( 'WPV_wpcf_record_post_relationship_belongs' ) ) {
			$content = WPV_wpcf_record_post_relationship_belongs( $content );
		}


		if ( class_exists( 'WPV_template' ) ) {
			global $WPV_templates;
			$content = $WPV_templates->the_content($content);
		}

		if ( isset( $GLOBALS['wp_embed'] ) ) {
			global $wp_embed;
			$content = $wp_embed->run_shortcode($content);
			$content = $wp_embed->autoembed($content);
		}

    	if ( function_exists( 'wpv_resolve_internal_shortcodes' ) ) {
			$content = wpv_resolve_internal_shortcodes($content);
		}
		if ( function_exists( 'wpv_resolve_wpv_if_shortcodes' ) ) {
			$content = wpv_resolve_wpv_if_shortcodes($content);
		}


		$content = convert_smilies($content);
		//Enable wpautop if raw = false
		if ( $raw == 'false' ){
			$content = wpautop($content);
		}

		$content = shortcode_unautop($content);
		$content = prepend_attachment($content);


		$content = do_shortcode($content);
		$content = capital_P_dangit($content);
    	return $content;
	}

	/**
	 * Check whether the Access metabox should be included in the current post type edit page.
	 *
	 * @param string $post_type
	 * @return boolean
	 * @since 2.6
	 */
	public static function is_metabox_excluded_by_post_type( $post_type ) {
		if ( 'attachment' === $post_type ) {
			return true;
		}

		$toolset_post_type_exclude = new Toolset_Post_Type_Exclude_List();
		$toolset_post_type_exclude_list = $toolset_post_type_exclude->get();

		return in_array( $post_type, $toolset_post_type_exclude_list, true );
	}

	public static function wpcf_access_select_group_metabox_files() {
		global $post, $pagenow;

		if (
			$pagenow === 'post.php'
			&& isset( $post->post_type )
			&& ! Access_Helper::is_metabox_excluded_by_post_type( $post->post_type )
		) {
            TAccess_Loader::loadAsset( 'SCRIPT/wpcf-access-post', 'wpcf-access' );
            TAccess_Loader::loadAsset( 'STYLE/wpcf-access-post', 'wpcf-access' );
			wp_enqueue_style( 'toolset-notifications-css' );
		}
    }

    /**
     *
     */
    public static function wpcf_access_select_group_metabox( )
    {
    	global $post, $wp_version, $pagenow;
        if (
			isset( $post )
			&& is_object( $post )
			&& $post->ID != ''
			&& ! Access_Helper::is_metabox_excluded_by_post_type( $post->post_type )
		) {
			if ( current_user_can('manage_options') || current_user_can('access_change_post_group') || current_user_can('access_create_new_group') ){
                if ( $pagenow == 'post.php' ){
                    add_meta_box('access_group', __('Post group', 'wpcf-access'), array(__CLASS__,'meta_box'), $post->post_type, 'side', 'high');
                }
            }
		}
	}

    /**
     * @param $post
     * Post types metabox for select group
     */
	public static function meta_box( $post ){
		$message = __( 'No Post Group selected.', 'wpcf-access' );
        $model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
        if ( isset($settings_access[$post->post_type]['mode']) && $settings_access[$post->post_type]['mode'] != 'not_managed' ){
            if (isset($_GET['post'])) {
                $group = get_post_meta($_GET['post'], '_wpcf_access_group', true);


                if ( isset($settings_access[$group]) && !empty($settings_access[$group]) ){
                    $message = sprintf(
                        __( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), $settings_access[$group]['title'] ).'
                        </p>';
                        if ( current_user_can('manage_options') ){
                            $message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'.
                            sprintf(__( 'Edit %s group privileges', 'wpcf-access' ), $settings_access[$group]['title']).'</a></p>';
                        }
                }
            }
            $out = '<div class="js-wpcf-access-post-group">'.$message.'</div>';
            if ( current_user_can('manage_options') ){
                $out .= '<input type="hidden" value="1" id="access-show-edit-link" />';
            }
            $out .= '<input type="button" value="'.__( 'Change Post Group', 'wpcf-access' ).'" data-id="'.$post->ID.'" class="js-wpcf-access-assign-post-to-group button">';
            $out .= wp_nonce_field('wpcf-access-error-pages', 'wpcf-access-error-pages', true, false);
        }
        else{
             $out = '<p>' . __( 'This content type is not currently managed by the Access plugin. To be able to add it to Post Group, first go to the Access admin and allow Access to control it.', 'wpcf-access' ).'
					</p>';
        }
		print $out;
	}

	public static function wpcf_access_check_if_user_can( $role, $level, $user = '' ){
    	global $wp_roles;
		$cur_level = 0;

		$ordered_roles = Access_Helper::wpcf_access_order_roles_by_level($wp_roles->roles);
		foreach ($ordered_roles as $levels => $roles_data){
            if (empty($roles_data))
                continue;

			foreach ($roles_data as $role_slug => $role_options)
        	{
        		if($role_slug == $role){
					$cur_level = $levels;
				}
        	}
        }
        if ( $level>=$cur_level){
            return true;
        }else{
            return false;
        }
	}

    /**
     * Init function.
     */
    public static function wpcf_access_init()
    {
        // Add debug info
        if (WPCF_ACCESS_DEBUG) {
            TAccess_Loader::loadAsset('STYLE/types-debug', 'types-debug', false);
            wp_enqueue_style('types-debug');
            wp_enqueue_script('jquery');
            add_action('admin_footer', array('Access_Debug', 'wpcf_access_debug'));
            add_action('wp_footer', array('Access_Debug', 'wpcf_access_debug'));
        }

        // Filter WP default capabilities for current user on 'init' hook
        //
        // We need to remove some capabilities added because of user role.
        // Example: editor has upload_files but may be restricted
        // because of Access settings.
        self::wpcf_access_user_filter_caps();

		add_action( 'admin_enqueue_scripts', 							array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_filter( 'toolset_filter_register_menu_pages',				array( __CLASS__, 'register_access_pages_in_menu'), 20 );
		add_filter( 'toolset_filter_register_export_import_section',	array( __CLASS__, 'register_export_import_section'), 50 );
		add_action( 'toolset_action_admin_init_in_toolset_page', 		array( __CLASS__, 'load_assets_in_shared_pages' ) );

        do_action('wpcf_access_init');
    }


    /**
     * Post init function.
     */
    public static function wpcf_access_late_init()
    {
        global $wpcf_access;
        // Register all 3rd party hooks now
        //
        // All 3rd party hooks should be registered all the time.
        // Otherwise they won't be called.
        self::wpcf_access_hooks_collect();
        if ( $wpcf_access->wpml_installed ){
            self::wpcf_load_wpml_languages_permissions();
        }

	    /**
	     * TODO: change this when WordPress will fix capabilities for child menu elements
         * This code fix the issue with CPT capability when CPT added as child to other (parent) CPT menu
         * and current user has no edit permissions for parent CPT
         * The issue is: Wordpress use parent edit capability for all elements in parent menu.
	     */
        global $wp_post_types;
        foreach( $wp_post_types as $post_type => $post_type_data ){
            if ( isset( $post_type_data->show_in_menu_page ) && strpos( $post_type_data->show_in_menu_page, 'edit.php?post_type=') !== FALSE  ){
                $parent_post_type = trim( str_replace( 'edit.php?post_type=', '', $post_type_data->show_in_menu_page ) );
                if ( isset( $wp_post_types[ $parent_post_type ] ) ){
                   $user_can_edit_parent = apply_filters( 'toolset_access_api_get_post_type_permissions', false, $parent_post_type, 'edit_own' );
                   if ( ! $user_can_edit_parent ){
                       $post_type_data->show_in_menu_page = true;
                       $post_type_data->show_in_menu = true;
                   }
                }

            }
        }


        do_action('wpcf_access_late_init');

    }

    /**
     * Sets shared taxonomies check.
     *
     * @global type $wpcf_access
     * @staticvar null $cache
     * @return null
     */
    public static function wpcf_access_get_taxonomies_shared_init()
    {
        self::wpcf_access_get_taxonomies_shared();
    }

    /**
     * Disable 'Add new' button for current language if user has no edit_own permission
     * @param string $post_type
     * @param array $access_settings
     * @param object $user
     *
     * @since 2.2
     */
    public static function wpcf_access_disable_add_new_button( $post_type, $access_settings, $user ){
        global $wpcf_access;
        //If current user can edit at least one language
        $is_current_user_can_edit = false;
        $_post_types = get_post_types( array(), 'objects' );
        foreach( $access_settings[ $post_type ] as $lang => $lang_data ){
            $status = apply_filters( 'toolset_access_api_get_post_type_permissions', false, $post_type, 'edit_own', $user, $lang  );
            if ( !$status  && $wpcf_access->current_language == $lang ){
                if ( isset( $_post_types[ $post_type ] ) ) {
                    self::disable_add_new_button_for_post_type( $post_type, $_post_types[ $post_type ] );
                }
            }
            if ( $status ){
                $is_current_user_can_edit = true;
            }
        }
        //Disable button for all languages
        if ( ! $is_current_user_can_edit && $wpcf_access->current_language == 'all' && isset( $_post_types[ $post_type ] ) ){
            self::disable_add_new_button_for_post_type( $post_type, $_post_types[ $post_type ] );
        }
    }

    /**
     * Proccess disable add new button
     * @param string $post_type_slug
     * @param object $post_type_object
     */
    public static function disable_add_new_button_for_post_type( $post_type_slug, $post_type_object ) {
        global $wpcf_access;
        $cap = "create_" . $post_type_slug;
        $post_type_object->cap->create_posts = $cap;
        map_meta_cap( $cap, 0 );
    }

    /**
     * @param string $user
     * @return array|bool|string
     * Check if current user have active translation jobs
     */
    public static function wpcf_access_get_translation_batches( $user = '' ){
         global $wpdb, $current_user;
         if ( empty($user) ){
            $user = $current_user->ID;
         }
         $translation_batches = Access_Cacher::get( 'wpcf_access_translation_batches_'.$user );

         if ( false !== $translation_batches ) {
             return $translation_batches;
         }

         $translation_jobs_collection = new WPML_Translation_Jobs_Collection( $wpdb, array( 'limit_no' => 1000, 'translator_id' => $user, 'status__not' => 10 ) );

         $translation_result = $translation_jobs_collection->get_paginated_batches( 0, 1000 );
         $translation_count = $translation_jobs_collection->get_count();
         if ( $translation_count == 0 ){
            Access_Cacher::set( 'wpcf_access_translation_batches_'.$user, '' );
            return '';
         }
         $temp_batches = array();
         foreach ( $translation_result[ 'batches' ] as $batch_id => $batch ) {
            $temp_batches[$batch_id] = $batch->get_jobs_as_array();
         }

         Access_Cacher::set( 'wpcf_access_translation_batches_'.$user, $temp_batches );
         return $temp_batches;
    }

    /**
     * @param $allcaps
     * @param $post_type
     * @param null $user
     * @return mixed
     */
    public static function wpcf_access_check_translation_jobs_exists( $allcaps, $post_type, $user = null ){
        global $wp_post_types, $current_user;

        if ( empty($user) ){
            $user = $current_user;
        }

        $translation_batches = self::wpcf_access_get_translation_batches( $user->ID );
        if ( empty($translation_batches) ){
            return $allcaps;
        }

        foreach ( $translation_batches as $batch_id => $batch ) {
            $temp_batch = $batch;
            for ( $i=0, $count = count($temp_batch); $i<$count; $i++ ){
                if ( $temp_batch[$i]['status'] == 'Complete' || $temp_batch[$i]['status'] == 'Translation complete' || !isset($temp_batch[$i]['original_post_type']) ){
                    continue;
                }
                $test_post_type = substr($temp_batch[$i]['original_post_type'],5);
                if ( $test_post_type == $post_type ){
                    $post_type = strtolower($wp_post_types[$post_type]->labels->name);
					$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_' . $post_type, true, $user );
					$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_published_' . $post_type, true, $user );
                    return $allcaps;
                }
            }

		}
        return $allcaps;

    }

    /**
     * @param $allcaps
     * @param $cap
     * @param $args
     * @param $action
     * @param null $user
     * @return mixed
     */
    public static function wpcf_check_wpml_post_type_permissions( $allcaps, $cap, $args, $action, $user = null ) {

		if ( is_null( $user ) ) {
			return $allcaps;
		}

        if ( !isset($user->ID) || $user->ID == 0 ){
            return $allcaps;
        }

        if ( !isset($cap[0]) ){
            return $allcaps;
        }

        global $wpcf_access;

        $post_type_plural = $post_type = str_replace( array('edit_others_','edit_published_','delete_others_','delete_published_','edit_','delete_','publish_'), '', $cap[0] );

        if ( !isset( $wpcf_access->post_types_info[$post_type_plural]) ){
            $model = TAccess_Loader::get('MODEL/Access');
            $_post_types = $model->getPostTypesNames();
            unset($model);
            $post_type_object = null;

            if ( in_array( $post_type_plural, $_post_types ) ) {
                $post_type_object = get_post_type_object( $post_type_plural );
	            $post_type_cap = sanitize_title_with_dashes( strtolower( $post_type_object->labels->name ) );
            } else {
                $post_type_cap = $post_type_plural;
                $post_type = self::get_post_type_singular_by_plural( $post_type_cap );
            }
            if ( empty($post_type_cap) || empty($post_type) ){
                return $allcaps;
            }
            $wpcf_access->post_types_info[$post_type_plural] = array($post_type_cap, $post_type);
        }else{
            $post_type_cap = $wpcf_access->post_types_info[$post_type_plural][0];
            $post_type = $wpcf_access->post_types_info[$post_type_plural][1];
        }

        $role = self::wpcf_get_current_logged_user_role( $user );


        if (
                ! isset( $wpcf_access->settings->types[$post_type] ) ||
                (
                    isset( $wpcf_access->settings->types[ $post_type ]['mode'] ) &&
                    'not_managed' == $wpcf_access->settings->types[ $post_type ]['mode']
                )
        ){
            return $allcaps;
        }

        $access_settings = $wpcf_access->language_permissions;

        if ( !isset( $wpcf_access->post_types_info[$post_type_plural][2] ) ){
            $is_translatable = apply_filters('wpml_is_translated_post_type', null, $post_type);
            $wpcf_access->post_types_info[$post_type_plural][2] = $is_translatable;
        }else{
            $is_translatable = $wpcf_access->post_types_info[$post_type_plural][2];
        }
        if ( !$is_translatable ){
            return $allcaps;
        }

        if ( !isset($args[2]) || empty($args[2])){
            $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_' . $post_type_cap, false, $user );
            $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_published_' . $post_type_cap, false, $user );
            $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_others_' . $post_type_cap, false, $user );

            $set_all_caps = false;

            if ( isset($access_settings[$post_type]) ){
                self:: wpcf_access_disable_add_new_button( $post_type, $access_settings, $user );
                foreach( $access_settings[$post_type] as $lang => $lang_data ){

                    if ( $wpcf_access->current_language !== $lang && isset( $_POST['originalaction'] ) && $_POST['originalaction'] == 'editpost' ) {
                        continue;
                    }
                    $status = self::otg_access_wpml_check_access_by_post_id( '', $lang, $post_type,
                        array( 'edit_any' => true, 'edit_own' => true, 'publish' => true, 'delete_any' => true, 'delete_own' => true ), $user );

                    //Resolve publish option
                    if ( $status['publish'] && $wpcf_access->current_language == $lang  ){
                        $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'publish_' . $post_type_cap, true, $user );
                    }elseif (  !$status['publish'] && $wpcf_access->current_language == $lang ){
                        $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'publish_' . $post_type_cap, false, $user );
                    }


                    if ( !isset($languages_set) ){
                        if (  !isset($allcaps['edit_' . $post_type_cap])   ){
                            if ( $status['edit_any'] ){
                                $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_' . $post_type_cap, true, $user );
                                $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_published_' . $post_type_cap, true, $user );
                                $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_others_' . $post_type_cap, true, $user );
                                $languages_set = true;
                            }elseif ( !$status['edit_any'] && $status['edit_own'] ){
                                $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_' . $post_type_cap, true, $user );
                                $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, 'edit_published_' . $post_type_cap, true, $user );
                                $languages_set = true;
                            }else{
                                if ( has_action('wpml_tm_loaded') && did_action('wpml_tm_loaded') ){
                                    // @todo check whouser
                                    $allcaps = self::wpcf_access_check_translation_jobs_exists( $allcaps, $post_type, $user );
                                }
                            }
                        }
                    }
                }




            }
        }else{
            global $post;

            $access_cache_user_has_cap_key = md5( 'access::post_language_' . $args[2] );
            $cached_caps = Access_Cacher::get( $access_cache_user_has_cap_key, 'access_cache_post_languages' );
            if ( false === $cached_caps ) {
                $post_language = apply_filters( 'wpml_post_language_details', '', $args[2] );
                Access_Cacher::set( $access_cache_user_has_cap_key, $post_language, 'access_cache_post_languages' );
            }else{
                $post_language = $cached_caps;
            }

            $additional_key = 'edit_all_' . $action;
            if ( isset( $post->post_author ) && $post->post_author == $user->data->ID ) {
                $additional_key = 'edit_own' . $post->ID . $action;
            }
            if ( !is_object( $post_language ) && isset( $post_language['language_code'] ) ) {
                $additional_key .= $post_language['language_code'];
            }
            $access_cache_posttype_languages_caps_key_single = md5( 'access::postype_language_cap__single_' . $post_type . $additional_key );
            $cached_post_type_caps = Access_Cacher::get( $access_cache_posttype_languages_caps_key_single, 'access_cache_posttype_languages_caps_single' );

            if ( false !== $cached_post_type_caps ){
                foreach( $cached_post_type_caps as $cap => $status ){
                    $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $cap, $status, $user );
                }
                return $allcaps;
            }

            if ( !is_object($post_language) && empty($post_language['language_code']) && isset($_POST['icl_post_language']) ){
                $post_language['language_code'] = $_POST['icl_post_language'];
                if ( isset($_POST['post_type']) ){
                    $post_type = $_POST['post_type'];
                    $model = TAccess_Loader::get('MODEL/Access');
                    $_post_types = $model->getPostTypesNames();
                    unset($model);
                    if ( in_array( $post_type, $_post_types ) ) {
                        $post_type_object = get_post_type_object( $post_type );
	                    $post_type_cap = sanitize_title_with_dashes( strtolower( $post_type_object->labels->name ) );
                    } else {
                        $post_type_cap = $post_type;
                        $post_type = self::get_post_type_singular_by_plural( $post_type_cap );
                    }
                }
            }


            if ( !is_object($post_language) && isset($access_settings[$post_type][$post_language['language_code']]) ){
                $current_wpml_settings = $access_settings[$post_type][$post_language['language_code']];
                $publish_roles = $publish_users = array();
                $def = array( 'edit_own', 'delete_own', 'edit_any', 'delete_any', 'publish' );
                for ($i=0;$i<count($def);$i++){
                    if ( isset( $current_wpml_settings[ $def[ $i ] ]['roles'] ) ) {
	                    ${$def[ $i ] . '_roles'} = $current_wpml_settings[ $def[ $i ] ]['roles'];
                    }else{
	                    ${$def[ $i ] . '_roles'} = array();
                    }

                    if ( isset($current_wpml_settings[$def[$i]]['users']) ){
                        ${$def[$i] . '_users'} = $current_wpml_settings[$def[$i]]['users'];
                    }
                }
                $cached_post_type_caps = array();
                // Delete/Edit any
                if ( !is_array(${$action . '_any_roles'}) ){
                    ${$action . '_any_roles'} = array(${$action . '_any_roles'});
                }
                if ( ( isset(${$action . '_any_roles'}) && in_array( $role, ${$action . '_any_roles'} ) !== FALSE )
                    || ( isset(${$action . '_any_users'}) && in_array($user->data->ID, ${$action . '_any_users'}) !== FALSE  ) ){
                    if ( $action == 'edit' ){
                        $caps_to_update = array( 'edit' => true, 'edit_others' => true, 'edit_private' => true, 'edit_published' => false );
                        if ( self::toolset_access_check_if_user_or_role_can_publish( $role, $user, $publish_roles, $publish_users ) ){
                            $caps_to_update['edit_published'] = true;
                        }
                    }else{
                        $caps_to_update = array( 'delete' => true, 'delete_published' => true, 'delete_private' => true, 'delete_others' => true );
                    }

                    $allcaps = self::toolset_access_bulk_allcaps_update( $caps_to_update, $post_type_cap, $user, $allcaps );
                    $cached_post_type_caps = self::toolset_access_bulk_cached_caps_update( $caps_to_update, $post_type_cap, $cached_post_type_caps );
                    Access_Cacher::set( $access_cache_posttype_languages_caps_key_single, $cached_post_type_caps, 'access_cache_posttype_languages_caps_single' );
                    return $allcaps;
                }else{
                    if ( $action == 'edit' ){
                        $caps_to_update = array( 'edit' => false, 'edit_published' => false, 'edit_others' => false );
                    }else{
                        $caps_to_update = array( 'delete' => false, 'delete_published' => false, 'delete_others' => false );
                    }
                    $allcaps = self::toolset_access_bulk_allcaps_update( $caps_to_update, $post_type_cap, $user, $allcaps );
                    $cached_post_type_caps = self::toolset_access_bulk_cached_caps_update( $caps_to_update, $post_type_cap, $cached_post_type_caps );
                    Access_Cacher::set( $access_cache_posttype_languages_caps_key_single, $cached_post_type_caps, 'access_cache_posttype_languages_caps_single' );
                }

                //Edit/delete own

                if ( ( isset(${$action . '_own_roles'}) && in_array( $role, ${$action . '_own_roles'} ) !== FALSE )
                    || ( isset(${$action . '_own_users'}) && in_array($user->data->ID, ${$action . '_own_users'})) ){
                    if ( $action == 'edit' ){
                        $caps_to_update = array( 'edit' => true, 'edit_others' => false, 'edit_published' => false );
                        if ( self::toolset_access_check_if_user_or_role_can_publish( $role, $user, $publish_roles, $publish_users ) ){
                            $caps_to_update['edit_published'] = true;
                        }
                    }else{
                        $caps_to_update = array( 'delete' => true, 'delete_published' => false, 'delete_others' => false );
                        if ( self::toolset_access_check_if_user_or_role_can_publish( $role, $user, $publish_roles, $publish_users ) ){
                            $caps_to_update['delete_published'] = true;
                        }
                    }

                    $allcaps = self::toolset_access_bulk_allcaps_update( $caps_to_update, $post_type_cap, $user, $allcaps );
                    $cached_post_type_caps = self::toolset_access_bulk_cached_caps_update( $caps_to_update, $post_type_cap, $cached_post_type_caps );
                    Access_Cacher::set( $access_cache_posttype_languages_caps_key_single, $cached_post_type_caps, 'access_cache_posttype_languages_caps_single' );
                    return $allcaps;
                }else{
                    if ( $action == 'edit' ){
                        $caps_to_update = array( 'edit' => false, 'edit_published' => false, 'edit_others' => false );
                    }else{
                        $caps_to_update = array( 'delete' => false, 'delete_published' => false, 'delete_others' => false );
                    }
                    $allcaps = self::toolset_access_bulk_allcaps_update( $caps_to_update, $post_type_cap, $user, $allcaps );
                    $cached_post_type_caps = self::toolset_access_bulk_cached_caps_update( $caps_to_update, $post_type_cap, $cached_post_type_caps );
                }
                Access_Cacher::set( $access_cache_posttype_languages_caps_key_single, $cached_post_type_caps, 'access_cache_posttype_languages_caps_single' );
            }
        }
        return $allcaps;
    }

	/**
     * Check if requested user can publish CPT
	 * @param $role
	 * @param $user
	 * @param $publish_roles
	 * @param $publish_users
	 *
	 * @return bool
	 */
    public static function toolset_access_check_if_user_or_role_can_publish ( $role, $user, $publish_roles, $publish_users ) {
        if ( in_array( $role, $publish_roles ) !== FALSE || in_array( $user->data->ID, $publish_users ) !== FALSE ){
            return true;
        }
        return false;
    }

	/**
	 * @param array $caps
	 * @param array $cached_post_type_caps
	 *
	 * @return array
	 */
    public static function toolset_access_bulk_cached_caps_update ( $caps, $post_type, $cached_post_type_caps = array() ) {
        foreach ( $caps as $cap => $bool ){
            $cached_post_type_caps[ $cap . '_' . $post_type ] = $bool;
        }
        return $cached_post_type_caps;
    }

	/**
	 * @param $caps
	 * @param $post_type
	 * @param $user
	 * @param array $allcaps
	 *
	 * @return array|mixed
	 */
    public static function toolset_access_bulk_allcaps_update ( $caps, $post_type, $user, $allcaps= array() ) {
        foreach ( $caps as $cap => $bool ){
            $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $cap . '_' . $post_type, $bool, $user );
        }
        return $allcaps;
    }

    /**
     * Check if capability requested in has_cap related to Access
     * @return true or false
     * @since 2.2
     */
    public static function wpcf_access_is_managed_capability( $cap ){

        // we use long statement, with strpos because this is the fastest way to check if $arg contain part of text
        if ( strpos( $cap, 'edit_' ) !== false || strpos( $cap, 'wpcf_' ) !== false || strpos( $cap, 'manage_' ) !== false
             || strpos( $cap, '_cred' ) !== false || strpos( $cap, 'delete_' ) !== false || strpos( $cap, 'publish_' ) !== false
             || strpos( $cap, 'view_own_in_profile_' ) !== false || strpos( $cap, 'modify_own_' ) !== false || strpos( $cap, 'view_fields_in' ) !== false
             || strpos( $cap, 'modify_fields_in_' ) !== false || strpos( $cap, 'assign_' ) !== false || strpos( $cap, 'read_private' ) !== false ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 'has_cap' filter.
     *
     * Returns all the modified capabilities. Cached per capability
     * NOTE cached per cap checked
     * NOTE maybe it sets them in just the first pass and we do not need one per different cap check
     *
     * @global type $current_user
     * @global type $wpcf_access->rules->types
     * @param array $allcaps All the capabilities of the user
     * @param array $cap     [0] Required capability
     * @param array $args    [0] Requested capability
     *                       [1] User ID
     *                       [2] Associated object ID
	 * @param object $user   The user ti check capabilities against, added in WP 3.7.0
     * @return array
     */
    public static function wpcf_access_user_has_cap_filter( $allcaps, $caps, $args, $user )
    {
        global $wpcf_access;
        $role = self::wpcf_get_current_logged_user_role( $user );
        $admin_exclude_caps = array( 'delete_users' => 1, 'manage_options' => 1, 'edit_theme_options' => 1, 'manage_links' => 1, 'edit_plugins' => 1,
            'ddl_edit_layout' => 1, 'delete_users' => 1, 'edit_themes' => 1, 'manage_network' => 1, 'manage_sites' => 1 );

        if ( self::wpcf_access_is_managed_capability( $args[0] ) && ! isset( $admin_exclude_caps[ $args[0] ] ) ){

            if ( $role == 'administrator' ) {
                if ( isset( $caps[0] ) ) {
                    foreach ( $caps as $val => $cap ) {
                        $allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $cap, true, $user );
                    }
                }
                return $allcaps;
            }

            if ( $args[0] !== 'edit_comments'  ) {
                $res = '';
                if ( strpos($args[0], 'edit_') !== false && strpos($args[0], 'edit_') == 0 ){
                    $res = 'edit';
                }
                if ( empty($res) && strpos($args[0], 'delete_') !== false && strpos($args[0], 'delete_') == 0 ){
                    $res = 'delete';
                }
                if ( empty($res) && strpos($args[0], 'publish_') !== false && strpos($args[0], 'publish_') == 0 ){
                    $res = 'publish';
                }
                if ( !empty($res) ){
                    $access_cache_user_has_cap = 'access_cache_user_has_cap';
                    $access_cache_user_has_cap_key = md5( 'access::edit_caps' . implode('-', $caps) .'_'. serialize($args) . '#' . $user->ID );
                    $cached_caps = Access_Cacher::get( $access_cache_user_has_cap_key, $access_cache_user_has_cap );
                    if ( false === $cached_caps ) {
                        $allcaps = self::wpcf_access_check( $allcaps, $caps, $args, true, $user );

                        if ( $wpcf_access->wpml_installed ) {
                            $allcaps = self::wpcf_check_wpml_post_type_permissions( $allcaps, $caps, $args, $res, $user );
                        }
                        Access_Cacher::set( $access_cache_user_has_cap_key, $allcaps, $access_cache_user_has_cap );
                    }else{
                        $allcaps = $cached_caps;
                    }
                    return $allcaps;
                }
            }

            $access_cache_user_has_cap = 'access_cache_user_has_cap';
            $arg3 = '';
            if ( isset( $args[2] ) ){
                $arg3 = '_'.$args[2];
            }
            $access_cache_user_has_cap_key = md5( 'access::user' . $args[1]  . 'cap' . $args[0] . $arg3 . '#' . $user->ID );
            $cached_caps = Access_Cacher::get( $access_cache_user_has_cap_key, $access_cache_user_has_cap );

            if ( false === $cached_caps ) {
                $allcaps = self::wpcf_access_check( $allcaps, $caps, $args, true, $user );
                Access_Cacher::set( $access_cache_user_has_cap_key, $allcaps, $access_cache_user_has_cap );
            }else{
                $allcaps = $cached_caps;
            }
       }
       return $allcaps;
    }

    /**
     * Main check function.
     *
     * @global type $wpcf_access
     * @global type $post
     * @global type $pagenow
     * @param array $allcaps All the capabilities of the user
     * @param array $cap     [0] Required capability
     * @param array $args    [0] Requested capability
     *                       [1] User ID
     *                       [2] Associated object ID
     * @param bool   $parse true|false to return $allcaps or boolean
	 * @param object $user   The user to check the capability against
     * @return array|boolean
     */
    public static function wpcf_access_check( $allcaps, $caps, $args, $parse = true, $user = null ) {
		if ( is_null( $user ) ) {
			return $allcaps;
		}

		global $wpcf_access;
        // this is number but users stored are strings
        $_user_id = $user->ID;
        $role = self::wpcf_get_current_logged_user_role( $user );
        // Debug if some args[0] is array
        if ( WPCF_ACCESS_DEBUG ) {
            if (
				empty( $args[0] )
				|| ! is_string( $args[0] )
			) {
                $wpcf_access->errors['cap_args'][] = array(
                    'file' => __FILE__ . ' #' . __LINE__,
                    'args' => func_get_args(),
                    'debug_backtrace' => debug_backtrace(),
                );
            }
        }
        if (
			empty( $args[0] )
			|| ! is_string( $args[0] )
		) {
            return $allcaps;
        }

        // Main capability queried
        $capability_requested = $capability_original = $args[0];

        // Other capabilities required to be true
        $caps_clone = $caps;

        // All user capabilities
        $allcaps_clone = $allcaps;

        $allow = null;
        $parse_args = array(
            'caps' => $caps_clone,
            'allcaps' => $allcaps_clone,
            'data' => array(), // default settings
            'args' => func_get_args(),
            'role' => false,
            'users' => false
        );

        // Allow check to be altered
		// @todo whouser check on callbacks
        $parse_args['requested_user'] = $user;
        list( $capability_requested, $parse_args ) = apply_filters( 'types_access_check', array( $capability_requested, $parse_args, $args ) );

        // TODO Monitor this
        // I saw mixup of $key => $cap and $cap => $true filteres by collect.php
        // Also we're adding sets of capabilities to 'caps'
    //    foreach ($parse_args['caps'] as $k => $v) {
    //        if (is_string($k)) {
    //            $parse_args['caps'][] = $k;
    //            unset($parse_args['caps'][$k]);
    //        }
    //    }
        // Debug
        if ($capability_original != $capability_requested)
        {
            $wpcf_access->converted[$capability_original][$capability_requested] = 1;
        }

        $parse_args['cap'] = $capability_requested;

        // Allow rules to be altered
        $wpcf_access->rules = apply_filters('types_access_rules',
                $wpcf_access->rules, $parse_args);

		// @todo whouser check in UploadHelper.php
        $override = apply_filters( 'types_access_check_override', null, $parse_args );
        if (!is_null($override))
        {
            return $override;
        }

        if (!empty($wpcf_access->rules->types[$capability_requested]))
        {
            $types = $wpcf_access->rules->types[$capability_requested];

            $types_roles = !empty($types['roles']) ? $types['roles'] : array();
            $types_roles = ( is_array($types_roles) ? $types_roles : array($types_roles));
            $types_users = !empty($types['users']) ? $types['users'] : array();
            $parse_args['roles'] = $types_roles;
            $parse_args['users'] = $types_users;


            // Set data
            $parse_args['data'] = self::wpcf_access_types_caps();
            $parse_args['data'] = isset($parse_args['data'][$capability_requested]) ? $parse_args['data'][$capability_requested] : array();
            $allow = false;
            if( is_array( $types_users ) && in_array( $_user_id, $types_users) !== FALSE ){
                $allow = true;
            }elseif( in_array( $role, $types_roles) !== FALSE  ){
                $allow = true;
            }

            $return  = $parse ?  self::wpcf_access_parse_caps( (bool) $allow, $parse_args, $user ) : (bool) $allow;

            return $return;
        }

        // Check taxonomies ($wpcf_access->rules->taxonomies)
        // See if main requested capability ($capability_requested)
        // is in collected taxonomies rules and process it.

        if (!empty($wpcf_access->rules->taxonomies[$capability_requested]))
        {

            $tax = $wpcf_access->rules->taxonomies[$capability_requested];
            if ( isset( $tax['follow'] ) && $tax['follow'] == 1 ) {
                if ( isset( $wpcf_access->rules->taxonomies['manage_category'] ) && strpos( $capability_requested, 'manage_' ) !== FALSE  ){
                    $tax = $wpcf_access->rules->taxonomies['manage_category'];
                }
                 if ( isset( $wpcf_access->rules->taxonomies['edit_category'] ) && strpos( $capability_requested, 'edit_' ) !== FALSE  ){
                    $tax = $wpcf_access->rules->taxonomies['edit_category'];
                }
                 if ( isset( $wpcf_access->rules->taxonomies['delete_category'] ) && strpos( $capability_requested, 'delete_' ) !== FALSE  ){
                    $tax = $wpcf_access->rules->taxonomies['delete_category'];
                }
                if ( isset( $wpcf_access->rules->taxonomies['assign_category']  ) && strpos( $capability_requested, 'assign_' ) !== FALSE ){
                    $tax = $wpcf_access->rules->taxonomies['assign_category'];
                }
            }
            $tax_role = !empty($tax['roles']) ? $tax['roles'] : array();
            $tax_users = !empty($tax['users']) ? $tax['users'] : array();
            $parse_args['roles'] = $tax_role;
            $parse_args['users'] = $tax_users;

            // Check taxonomies 'follow'
            if (!isset($tax['taxonomy']))
            {
                $wpcf_access->errors['no_taxonomy_recorded'] = $tax;
            }
            $shared = self::wpcf_access_is_taxonomy_shared($tax['taxonomy']);

            // have hardcoded the 'follow' capabilities,
            // so management is same as no follow mode
            $follow = false;

            // Set data
            $parse_args['data'] = self::wpcf_access_tax_caps();
            $parse_args['data'] = isset($parse_args['data'][$capability_requested]) ? $parse_args['data'][$capability_requested] : array();

            // Check if taxonomy use 'Same as parent' settings ('follow').
            if (!$follow)
            {
                $allow = false;
                if( in_array( $_user_id, $tax_users) !== FALSE ){
                    $allow = true;
                }elseif( in_array( $role, $tax_role) !== FALSE  ){
                    $allow = true;
                }
                return $parse ? self::wpcf_access_parse_caps( (bool) $allow, $parse_args, $user ) : (bool) $allow;
            }
        }


        // Check 3rd party saved settings (option 'wpcf-access-3rd-party')
        // After that check on-the-fly registered capabilities to use default data
        // This is already collected with wpcf_access_hooks_collect

        if (!empty($wpcf_access->third_party_caps[$capability_requested]))
        {
            // check only requested cap not all
            $data=$wpcf_access->third_party_caps[$capability_requested];
            //foreach ($wpcf_access->third_party_caps as $cap => $data) {
            $wpcf_access->third_party_debug[$capability_requested] = 1;

            // Set saved role if available
            $data['roles'] = array();
            if (isset($data['saved_data']['roles'])){
                $data['roles'] = $data['saved_data']['roles'];
            }elseif ( isset( $data['saved_data']['role']) ){
                $data['roles'] = self::toolset_access_get_roles_by_role($data['saved_data']['role']);
            }
            // Set saved users if available
            $data['users'] = isset($data['saved_data']['users']) ? $data['saved_data']['users'] : array();

            $parse_args['roles'] = $data['roles'];
            $parse_args['users'] = $data['users'];


            $parse_args['data'] = array();
            $allow = false;
            if( in_array( $_user_id, $parse_args['users']) !== FALSE ){
                $allow = true;
            }elseif( in_array( $role, $data['roles']) !== FALSE  ){
                $allow = true;
            }
            return $parse ? self::wpcf_access_parse_caps( (bool) $allow, $parse_args, $user ) : (bool) $allow;

        }
        $wpcf_access->debug_all_hooks[$capability_requested][] = $parse_args;
        return is_null($allow) ? $allcaps : self::wpcf_access_parse_caps( (bool) $allow, $parse_args, $user );
    }

    /**
     * Convert minimal role from Types/Cred to minimal caps and return list of roles
     * @param $role
     * @return array
     */
    public static function toolset_access_get_roles_by_role( $role, $cap = '' ){
        if( !class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }
        $key = 'toolset_access_roles_list_'.md5($role.$cap);
        $roles_list = Access_Cacher::get( $key );
        if ( false === $roles_list  ) {
            $ordered_roles = Access_Admin_Edit::toolset_access_order_wp_roles();
            $required_cap = $cap;
            if ( empty($cap) ){
                $required_cap = 'edit_posts';
                if ( $role == 'guest' || $role == 'subscriber' ){
                    $required_cap = 'read';
                }
                elseif ( $role == 'administrator' ){
                    $required_cap = 'delete_users';
                }
            }

            $roles_list = array();
            foreach( $ordered_roles as $role => $role_data ){
                if ( isset($role_data['capabilities'][$required_cap]) ){
                    $roles_list[] = $role;
                }
            }
            Access_Cacher::set( $key , $roles_list );
        }
        return $roles_list;
    }

    /**
     * Parses caps.
     *
     * @param type $allow
     * @param type $cap
     * @param type $caps
     * @param type $allcaps
     */
    public static function wpcf_access_parse_caps( $allow, $args, $user = null ) {
        // Set vars
        $args_clone = $args;
        $cap = $args['cap'];
        $caps = $args['caps'];
        $allcaps = $args['allcaps'];
        $data = $args['data'];
        $args = $args['args'];

		if ( is_null( $user ) ) {
			return $allcaps;
		}

        if ($allow)
        {
            // If true - force all caps to true

			$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $cap, true, $user );
            foreach ($caps as $c)
            {
                // TODO - this is temporary solution for comments
                if ($cap == 'edit_comment'
                        && (strpos($c, 'edit_others_') === 0
                        || strpos($c, 'edit_published_') === 0)) {
					$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $c, true, $user );
                }
                // TODO Monitor this - tricky, WP requires that all required caps
                // to be true in order to allow cap.
                if (!empty($data['fallback']))
                {
                    foreach ($data['fallback'] as $fallback)
                    {
						$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $fallback, true, $user );
                    }
                }
                else
                {
					$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $c, true, $user );
                }
            }
        }
        else
        {
            // If false unset caps in allcaps
			$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $cap, false, $user );

            // TODO Monitor this
            // Do we want to unset allcaps?
            foreach ($caps as $c)
            {
				$allcaps = Access_Helper::otg_access_add_or_remove_cap( $allcaps, $c, false, $user );
            }
        }

        if (WPCF_ACCESS_DEBUG)
        {
            global $wpcf_access;
            $debug_caps = array();
            foreach ($caps as $cap)
            {
                $debug_caps[$cap] = isset($allcaps[$cap]) ? $allcaps[$cap] : 0;
            }
            $wpcf_access->debug[$cap][] = array(
                'parse_args' => $args_clone,
                'dcaps' => $debug_caps,
            );
        }
        return $allcaps;
    }

    /**
     * Adds capabilities on WPCF types before registration hook.
     *
     * Access insists on using map_meta_cap true. It sets all post types to use
     * mapped capabilities.
     *
     * Examples:
     * 'edit_posts => 'edit_types'
     * 'edit_others_posts => 'edit_others_views'
     * 'edit_published_posts => 'edit_published_cred'
     *
     * This prevents using shared capabilities across post types
     * and so matching wrong settings.
     *
     * If in debug mode, debug output will show if any capabilities are overlapping.
     *
     * @param type $data
     * @param type $post_type
     * @return boolean
     */
    public static function wpcf_access_init_types_rules($data, $post_type)
    {
        $isTypesActive = self::wpcf_access_is_wpcf_active();
        if (!$isTypesActive)    return $data;

        $model = TAccess_Loader::get('MODEL/Access');

        $types = array();
        $types = $model->getAccessTypes();
        // Check if managed
        if (isset($types[$post_type]['mode']))
        {
            if ($types[$post_type]['mode'] === 'not_managed')
                return $data;

            if ( $types[ $post_type ]['mode'] === 'follow' ) {
                global $wpcf_access;
                unset( $wpcf_access->settings->types[ $post_type ]['permissions'] );
                return $data;
            }
            // Set capability type (singular and plural names needed)
            if (!self::wpcf_is_object_valid('type', $data))
            {
                if ( !isset($types[$post_type]['mode']) || $types[$post_type]['mode'] != 'not_managed' ){
                    $types[$post_type]['mode'] = 'not_managed';
                    $model->updateAccessTypes($types);
                }
                return $data;
            }

            $data['capability_type'] = array(
                sanitize_title($data['labels']['singular_name']),
                sanitize_title($data['labels']['name'])
            );

            // Flag WP to use meta mapping
            $data['map_meta_cap'] = true;
        }
        return $data;
    }

    /**
     * Adds capabilities on WPCF taxonomies before registration hook.
     *
     * Same as for post types. Create own capabilities for each taxonomy.
     *
     * @global type $wpcf_access->rules->taxonomies
     * @param type $data
     * @param type $taxonomy
     * @param type $object_types
     * @return type
     */
    public static function wpcf_access_init_tax_rules($data, $taxonomy, $object_types)
    {
        global $wpcf_access;

        $isTypesActive = self::wpcf_access_is_wpcf_active();
        if (!$isTypesActive)    return $data;

        $model = TAccess_Loader::get('MODEL/Access');

        $taxs = $model->getAccessTaxonomies();

        // Check if managed
        if (empty($taxs[$taxonomy]['mode']))
            return $data;

        $settings = $taxs[$taxonomy]; //$data['_wpcf_access_capabilities'];
        $mode = isset($settings['mode']) ? $settings['mode'] : 'not_managed';
        if ($mode == 'not_managed')
            return $data;

        // Match only predefined capabilities
        $caps = self::wpcf_access_tax_caps();
        foreach ($caps as $cap_slug => $cap_data)
        {

            // Create capability slug
            $new_cap_slug = str_replace('_terms',
                    '_' . sanitize_title($data['labels']['name']), $cap_slug);
            $data['capabilities'][$cap_slug] = $new_cap_slug;
            // Set mode
            $wpcf_access->rules->taxonomies[$new_cap_slug]['follow'] = $mode == 'follow';

            // If mode is not 'folow' and settings are determined
            if (/*$mode != 'follow' &&*/ isset($settings['permissions'][$cap_slug]))
            {
                $wpcf_access->rules->taxonomies[$new_cap_slug]['roles'] = $settings['permissions'][$cap_slug]['roles'];
                $wpcf_access->rules->taxonomies[$new_cap_slug]['users'] = isset($settings['permissions'][$cap_slug]['users']) ? $settings['permissions'][$cap_slug]['users'] : array();
            }

            // Add to rules
            $wpcf_access->rules->taxonomies[$new_cap_slug]['taxonomy'] = $taxonomy;
        }
        return $data;
    }

    /**
     * Sets rules for WPCF types after registration hook.
     *
     * @global type $wpcf_access_types_rules
     * @param type $data
     */
    public static function wpcf_access_collect_types_rules($data)
    {
        global $wpcf_access;

        //taccess_log($data);

        $model = TAccess_Loader::get('MODEL/Access');
        $type = $data->slug;
        $types = $model->getAccessTypes();

        if (!isset($types[$type]))
            return false;

        $settings = $types[$type]; // $data->_wpcf_access_capabilities;
        if ($settings['mode'] == 'not_managed' || empty($settings['permissions']))
            return false;

        $caps = self::wpcf_access_types_caps();
        $mapped = array();

        // Map predefined to existing capabilities
        foreach ($caps as $cap_slug => $cap_spec)
        {
            if (isset($settings['permissions'][$cap_spec['predefined']])) {
                $mapped[$cap_slug] = $settings['permissions'][$cap_spec['predefined']];
            } else {
                $mapped[$cap_slug] = $cap_spec['predefined'];
            }
        }

        // Set rule settings for post type by pre-defined caps
        foreach ($data->cap as $cap_slug => $cap_spec)
        {
            if (isset($mapped[$cap_slug])) {
                if (isset($mapped[$cap_slug]['roles'])) {
                    $wpcf_access->rules->types[$cap_spec]['roles'] = $mapped[$cap_slug]['roles'];
                } else {
                    $wpcf_access->rules->types[$cap_spec]['roles'] = self::toolset_access_get_roles_by_role('administrator');
                }
                $wpcf_access->rules->types[$cap_spec]['users'] = isset($mapped[$cap_slug]['users']) ? $mapped[$cap_slug]['users'] : array();
                $wpcf_access->rules->types[$cap_spec]['types'][$data->slug] = 1;
            }
        }
    }

    /**
     * Maps rules and settings for post types registered outside of Types.
     *
     * @param type $post_type
     * @param type $args
     */
    public static function wpcf_access_registered_post_type_hook($post_type, $args)
    {
        global $wpcf_access, $wp_post_types;
        static $_builtin_types=null;

        $model = TAccess_Loader::get('MODEL/Access');

        //List of AJAX actions where Access will apply read permissions
        $toolset_access_allowed_ajax_actions = array( 'wpv_get_archive_query_results' );
        $toolset_access_allowed_ajax_actions = apply_filters( 'toolset_access_allowed_ajax_actions', $toolset_access_allowed_ajax_actions );
        $settings_access = $wpcf_access->settings->types;
        if ( isset( $wp_post_types[ $post_type ] ) && $wp_post_types[ $post_type ]->public == 1 )
        {
            // Force map meta caps, if not builtin
            if (in_array($post_type, array('post', 'page')))
            {
                switch ($post_type)
                {
                    case 'page':
                        $_sing='page';
                        $_plur='pages';
                        break;
                    case 'post':
                    default:
                        $_sing='post';
                        $_plur='posts';
                        break;
                }
            }
            else
            {
                // else use singular/plural names
                $_sing=sanitize_title($wp_post_types[$post_type]->labels->singular_name);
                $_plur=sanitize_title($wp_post_types[$post_type]->labels->name);
				if ( $_sing == $_plur ){
					$_plur = $_plur.'_s';
				}
            }
            $capability_type=array( $_sing, $_plur );

            // set singular / plural caps based on names or default for builtins
            $tmp=unserialize(serialize($wp_post_types[$post_type]));
            $tmp->capability_type = $capability_type;
            $tmp->map_meta_cap = true;
            $tmp->capabilities = array();
            $tmp->cap = get_post_type_capabilities($tmp);


            // provide access pointers
            $wp_post_types[$post_type]->__accessIsCapValid=!self::wpcf_check_cap_conflict(array_values((array)$tmp->cap));
            $wp_post_types[$post_type]->__accessIsNameValid=self::wpcf_is_object_valid('type', self::wpcf_object_to_array($tmp));
            $wp_post_types[$post_type]->__accessNewCaps=$tmp->cap;

            $is_post_managed = ( isset( $settings_access['post'] ) && $settings_access['post']['mode'] == 'permissions' );

            if ( isset( $settings_access[ $post_type ] ) || isset( $settings_access[ 'post' ] ) )
            {
                $data = isset( $settings_access[ $post_type ] ) ? $settings_access[$post_type] : $settings_access[ 'post' ];

                // Mark that will inherit post settings
                // TODO New types to be added
                if (
                    !in_array($post_type, array('post', 'page', 'attachment', 'media'))
                    && (empty($wp_post_types[$post_type]->capability_type)
                    || $wp_post_types[$post_type]->capability_type == 'post')
                )
                {
                    $wp_post_types[$post_type]->_wpcf_access_inherits_post_cap = 1;
                }

                if (
                    $data['mode'] == 'not_managed' ||
                    !$wp_post_types[$post_type]->__accessIsCapValid ||
                    !$wp_post_types[$post_type]->__accessIsNameValid
                )
                {
                    if (
                        ! $is_post_managed ||
                        ! $wp_post_types[$post_type]->public ||
                        in_array( $post_type, array( 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' ) )

                    ){
                        if ( ! isset( $settings_access[$post_type]['mode'] ) ){
                            $settings_access[$post_type]['mode'] = 'not_managed';
                            $model->updateAccessTypes( $settings_access );
                        }

                    }
                    return false;
                }
                $caps   = self::wpcf_access_types_caps();
                if ( $data['mode'] !== 'follow' ) {

                    $mapped = array();
                    // Map predefined
                    foreach ( $caps as $cap_slug => $cap_spec ) {
                        if ( isset( $data['permissions'][ $cap_spec['predefined'] ] ) ) {
                            $mapped[ $cap_slug ] = $data['permissions'][ $cap_spec['predefined'] ];
                        } else {
                            $mapped[ $cap_slug ] = $cap_spec['predefined'];
                        }
                    }

                    // set singular / plural caps based on names or default for builtins
                    $wp_post_types[ $post_type ]->capability_type = $capability_type;
                    $wp_post_types[ $post_type ]->map_meta_cap    = true;
                    $wp_post_types[ $post_type ]->capabilities    = array();
                    $wp_post_types[ $post_type ]->cap             = get_post_type_capabilities( $wp_post_types[ $post_type ] );
                    //$wp_post_types[$post_type]=$tmp;
                    unset( $wp_post_types[ $post_type ]->capabilities );

                    // Set rule settings for post type by pre-defined caps
                    foreach ( $args->cap as $cap_slug => $cap_spec ) {
                        if ( isset( $mapped[ $cap_slug ] ) ) {
                            if ( isset( $mapped[ $cap_slug ]['roles'] ) ) {
                                $wpcf_access->rules->types[ $cap_spec ]['roles'] = $mapped[ $cap_slug ]['roles'];
                            } else {
                                $wpcf_access->rules->types[ $cap_spec ]['roles'] = self::toolset_access_get_roles_by_role( 'administrator' );
                            }

                            $wpcf_access->rules->types[ $cap_spec ]['users']                            = isset( $mapped[ $cap_slug ]['users'] ) ? $mapped[ $cap_slug ]['users'] : array();
                            $wpcf_access->rules->types[ $cap_spec ]['types'][$post_type/*$args->name*/] = 1;
                        }
                    }

                    //taccess_log(array($post_type, $args->cap, $mapped, $wpcf_access->rules->types));

                    // TODO create_posts set manually for now
                    // Monitor WP changes
                    if ( ! isset( $wpcf_access->rules->types['create_posts'] ) && isset( $wpcf_access->rules->types['edit_posts'] ) ) {
                        $wpcf_access->rules->types['create_posts'] = $wpcf_access->rules->types['edit_posts'];
                    }
                    /*if (!isset($wpcf_access->rules->types['create_pages']) && isset($wpcf_access->rules->types['edit_pages'])) {
						$wpcf_access->rules->types['create_pages'] = $wpcf_access->rules->types['edit_pages'];
					}*/
                    if ( ! isset( $wpcf_access->rules->types['create_post'] ) && isset( $wpcf_access->rules->types['edit_post'] ) ) {
                        $wpcf_access->rules->types['create_post'] = $wpcf_access->rules->types['edit_post'];
                    }
                    /*if (!isset($wpcf_access->rules->types['create_page']) && isset($wpcf_access->rules->types['edit_page'])) {
						$wpcf_access->rules->types['create_page'] = $wpcf_access->rules->types['edit_page'];
					}*/
                }

                // Set min reading role
                $needs_min_reading_role = ( $data['mode'] != 'not_managed' || ( $post_type != 'post' && $data['mode'] == 'follow' && $is_post_managed  ) );
                //Apply read permissions for selected AJAX requests
                $is_allowed_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $toolset_access_allowed_ajax_actions ) );

                // Check frontend read
                if ( $needs_min_reading_role && !is_admin() ||$is_allowed_ajax ) {
                    if ( $is_post_managed && 'post' !== $post_type ) {
	                    $data['permissions'] = toolset_getnest( $settings_access, array( $post_type, 'permissions' ), $settings_access['post']['permissions'] );
                    }
                    if ( isset( $data['permissions']['read']['roles'] ) ) {
                        $wpcf_access->custom_read[] = array(
                            $data['permissions']['read']['roles'],
                            $post_type
                        );
                        add_action( 'init', array( __CLASS__, 'set_frontend_read_permissions_action' ), 999 );
                    } else {
                        // Missed setting? Debug that!
                        $wpcf_access->errors['missing_settings']['read'][] = array(
                            'caps' => $caps,
                            'data' => $data,
                        );
                    }
                }
            }
        }
    }

    /**
     * Set read permissions
     */
    public static function set_frontend_read_permissions_action(  ){
        global $wpcf_access;
        if ( !isset($wpcf_access->read_permissions_set) ){
            for ( $i = 0; $i < count( $wpcf_access->custom_read ); $i++ ) {
                self::set_frontend_read_permissions( $wpcf_access->custom_read[$i][0],
                                            $wpcf_access->custom_read[$i][1] );
            }
            $wpcf_access->read_permissions_set = true;
        }//exit;
    }

    /**
     * Maps rules and settings for taxonomies registered outside of Types.
     *
     * @param type $post_type
     * @param type $args
     */
    public static function wpcf_access_registered_taxonomy_hook($taxonomy, $object_type, $args)
    {
        global $wp_taxonomies, $wpcf_access;

        $model = TAccess_Loader::get('MODEL/Access');

        $settings_access = $model->getAccessTaxonomies();

        // do basic access tests
        if (isset($wp_taxonomies[$taxonomy]))
        {
            $caps = self::wpcf_access_tax_caps();

            // Map pre-defined capabilities
            $new_caps=array();
            $valid=true;
            foreach ($caps as $cap_slug => $cap_data)
            {
                // Create cap slug
                $new_cap_slug = str_replace('_terms',
                        '_' . sanitize_title($wp_taxonomies[$taxonomy]->name), $cap_slug);

                if (!empty($args['_builtin']) || (isset($wp_taxonomies[$taxonomy]->cap->$cap_slug)
                    && $wp_taxonomies[$taxonomy]->cap->$cap_slug == $cap_data['default'])
                )
                {
                    $new_caps[$cap_slug] = $new_cap_slug;
                }
                else if (isset($wp_taxonomies[$taxonomy]->cap->$cap_slug)  &&
                        isset($wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug])
                )
                {
                    $new_caps[$cap_slug] = $wp_taxonomies[$taxonomy]->cap->$cap_slug;
                }
            }

            // provide access pointers
            $wp_taxonomies[$taxonomy]->__accessIsCapValid=!self::wpcf_check_cap_conflict(array_values($new_caps));
            $wp_taxonomies[$taxonomy]->__accessIsNameValid=self::wpcf_is_object_valid('taxonomy', self::wpcf_object_to_array($wp_taxonomies[$taxonomy]));
            $wp_taxonomies[$taxonomy]->__accessNewCaps=$new_caps;

            taccess_log(array($taxonomy, $wp_taxonomies[$taxonomy]));

            if (isset($settings_access[$taxonomy]))
            {
                $data = $settings_access[$taxonomy];
                $mode = isset($data['mode']) ? $data['mode'] : 'not_managed';
                $data['mode'] = $mode;

                if (
                    $mode == 'not_managed' ||
                    !$wp_taxonomies[$taxonomy]->__accessIsCapValid ||
                    !$wp_taxonomies[$taxonomy]->__accessIsNameValid
                )
                {
                    if ( !isset($settings_access[$taxonomy]['mode']) ){
                        $settings_access[$taxonomy]['mode']='not_managed';
                        $model->updateAccessTaxonomies($settings_access);
                    }
                    return false;
                }
                if ( $taxonomy == 'post_tag' ){
                    //print_r($new_caps);exit;
                }
                foreach ($new_caps as $cap_slug=>$new_cap_slug)
                {
                    // Alter if tax is built-in or other has default capability settings
                    if (!empty($args['_builtin']) || (isset($wp_taxonomies[$taxonomy]->cap->$cap_slug)
                        && $wp_taxonomies[$taxonomy]->cap->$cap_slug == $caps[$cap_slug]['default'])
                    )
                    {
                        $wp_taxonomies[$taxonomy]->cap->$cap_slug = $new_cap_slug;
                        $wpcf_access->rules->taxonomies[$new_cap_slug]['follow'] = $mode == 'follow';
                        if (/*$mode != 'follow' &&*/ isset($data['permissions'][$cap_slug]))
                        {
                            $wpcf_access->rules->taxonomies[$new_cap_slug]['roles'] = $data['permissions'][$cap_slug]['roles'];
                            $wpcf_access->rules->taxonomies[$new_cap_slug]['users'] = isset($data['permissions'][$cap_slug]['users']) ? $data['permissions'][$cap_slug]['users'] : array();
                        }

                        // Otherwise just map capabilities
                    }
                    else if (isset($wp_taxonomies[$taxonomy]->cap->$cap_slug)  &&
                            isset($wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug])
                    )
                    {
                        $wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug]['follow'] = $mode == 'follow';
                        if (/*$mode != 'follow' &&*/ isset($data['permissions'][$cap_slug]))
                        {
                            $wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug]['roles'] = $data['permissions'][$cap_slug]['roles'];
                            $wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug]['users'] = isset($data['permissions'][$cap_slug]['users']) ? $data['permissions'][$cap_slug]['users'] : array();
                        }
                    }
                    $wpcf_access->rules->taxonomies[$wp_taxonomies[$taxonomy]->cap->$cap_slug]['taxonomy'] = $taxonomy;
                }
            }
        }
    }

    /**
     * Filters rules according to sets permitted.
     *
     * Settings are defined in /includes/dependencies.php
     * Each capability is in relationship with some other and can't be used solely
     * without other.
     *
     * @global type $current_user
     * @global type $wpcf_access
     * @staticvar null $cache
     * @return null
     */
    public static function wpcf_access_filter_rules( )
    {
        global $current_user, $wpcf_access;

        static $cache = null;
        $args = func_get_args();
        $_user_id = $current_user->ID;
        if ( isset($args[0][1]['requested_user']->ID) && $args[0][1]['requested_user']->ID != $_user_id ){
            $_user_id = $args[0][1]['requested_user']->ID;
        }

		$key_var1 = serialize($args[0][0]);
		$key_var2 = serialize($args[0][1]);
		$key_var3 = serialize($args[0][2]);
		$ckey = 'access_'.$key_var1.'_'.$key_var2.'_'.$key_var3;
		$result = Access_Cacher::get( $ckey );
		$cap = $args[0][0];
        $parse_args = $args[0][1];
        $args = $args[0][2];
		if ( false !== $result ) {
			return $result;
		}

		$found = self::wpcf_access_search_cap($cap);
        if ($found) {
            $wpcf_access->debug_fallbacks_found[$cap] = $found;
        } else {
            $wpcf_access->debug_fallbacks_missed[$cap] = 1;
			Access_Cacher::set( $ckey, array($cap, $parse_args, $args) );
            return array($cap, $parse_args, $args);
        }

        $set = self::wpcf_access_user_get_caps_by_type($_user_id,
                $found['_context']);

        if (empty($set)) {
            $wpcf_access->debug_missing_context[$found['_context']][$cap]['user'] = $current_user->ID;
			Access_Cacher::set( $ckey, array($cap, $parse_args, $args) );
            return array($cap, $parse_args, $args);
        }

        // Set allowed caps accordin to sets allowed
        // /includes/dependencies.php will hook on 'access_dependencied' filter
        // and map capabilities in two arrays depending on main capability.
        //
        // Example:
        // 'edit_own' disabled will have:
        // 'disallowed_caps' => ('edit_any', 'delete_any', 'publish')
        //
        // 'edit_own' enabled will have:
        // 'allowed_caps' => ('read')

        $allowed_caps = $disallowed_caps = array();

        // Apply dependencies filter
        list($allowed_caps, $disallowed_caps) = apply_filters('types_access_dependencies',
                array($allowed_caps, $disallowed_caps, $set));

        $filtered = array();

        // TODO Monitor this
        foreach ($disallowed_caps as $disallowed_cap)
        {
            if (in_array($disallowed_cap, $parse_args['caps']))
            {
                // Just messup checked caps
                $filtered['caps'] = array();
                $parse_args = array_merge($parse_args, $filtered);
                $wpcf_access->debug_caps_disallowed[$found['_context']][$cap][] = $disallowed_cap;
				Access_Cacher::set( $ckey, array($cap, $parse_args) );
                return array($cap, $parse_args);
            }
        }

        // TODO Monitor this
        foreach ($allowed_caps as $allowed_cap)
        {
            $parse_args['caps'][] = $allowed_cap;
            $filtered['allcaps'][$allowed_cap] = true;
            $wpcf_access->debug_caps_allowed[$found['_context']][$cap][] = $allowed_cap;
        }

        $parse_args = array_merge($parse_args, $filtered);
        Access_Cacher::set( $ckey, array($cap, $parse_args) );
		return array($cap, $parse_args);
    }

    /**
     * Defines dependencies.
     *
     * @return array
     */
    public static function wpcf_access_dependencies()
    {
        $deps = array(
            // post types
            'edit_own' => array(
                'true_allow' => array('read'),
                'false_disallow' => array('edit_any', 'publish')
            ),
            'edit_any' => array(
                'true_allow' => array('read', 'edit_own'),
            ),
            'publish' => array(
                'true_allow' => array('read', 'edit_own', 'delete_own'),
            ),
            'delete_own' => array(
                'true_allow' => array('read'),
                'false_disallow' => array('delete_any', 'publish'),
            ),
            'delete_any' => array(
                'true_allow' => array('read', 'delete_own'),
            ),
            'read' => array(
                'false_disallow' => array('edit_own', 'delete_own', 'edit_any',
                    'delete_any', 'publish', 'read_private'),
            ),
            'read_private' => array(
                'true_allow' => array('read'),
            ),
            // taxonomies
            'edit_terms' => array(
                'true_allow' => array('manage_terms'),
                'false_disallow' => array('manage_terms','delete_terms')
            ),
            'delete_terms' => array(
                'true_allow' => array('manage_terms', 'edit_terms')
            ),
            'manage_terms' => array(
                'true_allow' => array('edit_terms', 'delete_terms'),
                'false_disallow' => array('edit_terms','delete_terms')
            ),
            'assign_terms' => array(),
        );
        return $deps;
    }

    /**
     * Renders JS
     */
    public static function wpcf_access_dependencies_render_js()
    {
        $deps = self::wpcf_access_dependencies();
        $output = '';
        $output .= "\n\n<script type=\"text/javascript\">\n/*<![CDATA[*/\n";
        $active = array();
        $inactive = array();
        $active_message = array();
        $inactive_message = array();

        $output .= 'var wpcf_access_dep_active_messages_pattern_singular = "'
                . __("Since you enabled '%cap', '%dcaps' has also been enabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_active_messages_pattern_plural = "'
                . __("Since you enabled '%cap', '%dcaps' have also been enabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_inactive_messages_pattern_singular = "'
                . __("Since you disabled '%cap', '%dcaps' has also been disabled.",
                        'wpcf-access')
                . '";' . "\n";
        $output .= 'var wpcf_access_dep_inactive_messages_pattern_plural = "'
                . __("Since you disabled '%cap', '%dcaps' have also been disabled.",
                        'wpcf-access')
                . '";' . "\n";
        /*$output .= 'var wpcf_access_edit_comments_inactive = "'
                . __("Since you disabled '%dcaps' user/role will not be able to edit comments also.",
                        'wpcf-access')
                . '";' . "\n";*/

        foreach ($deps as $dep => $data)
        {
            $dep_data = self::wpcf_access_get_cap_predefined_settings($dep);
            $output .= 'var wpcf_access_dep_' . $dep . '_title = "'
                    . $dep_data['title']
                    . '";' . "\n";
            foreach ($data as $dep_active => $dep_set)
            {
                if (strpos($dep_active, 'true_') === 0)
                {
                    $active[$dep][] = '\'' . implode('\', \'', $dep_set) . '\'';
                    foreach ($dep_set as $cap)
                    {
                        $_cap = self::wpcf_access_get_cap_predefined_settings($cap);
                        $active_message[$dep][] = $_cap['title'];
                    }
                }
                else
                {
                    $inactive[$dep][] = '\'' . implode('\', \'', $dep_set) . '\'';
                    foreach ($dep_set as $cap)
                    {
                        $_cap = self::wpcf_access_get_cap_predefined_settings($cap);
                        $inactive_message[$dep][] = $_cap['title'];
                    }
                }
            }
        }

        foreach ($active as $dep => $array)
        {
            $output .= 'var wpcf_access_dep_true_' . $dep . ' = ['
                    . implode(',', $array) . '];' . "\n";
            $output .= 'var wpcf_access_dep_true_' . $dep . '_message = [\''
                    . implode('\',\'', $active_message[$dep]) . '\'];' . "\n";
        }

        foreach ($inactive as $dep => $array)
        {
            $output .= 'var wpcf_access_dep_false_' . $dep . ' = ['
                    . implode(',', $array) . '];' . "\n";
            $output .= 'var wpcf_access_dep_false_' . $dep . '_message = [\''
                    . implode('\',\'', $inactive_message[$dep]) . '\'];' . "\n";
        }

        $output .= "/*]]>*/\n</script>\n\n";
        echo $output;
    }

    /**
     * Returns specific cap dependencies.
     *
     * @param type $cap
     * @param type $true
     * @return type
     */
    public static function wpcf_access_dependencies_get($cap, $true = true)
    {
        $deps = self::wpcf_access_dependencies();
        $_deps = array();
        if (isset($deps[$cap]))
        {
            foreach ($deps[$cap] as $dep_active => $data)
            {
                if ($true && strpos($dep_active, 'true_') === 0) {
                    $_deps[substr($dep_active, 5)] = $data;
                } else {
                    $_deps[substr($dep_active, 6)] = $data;
                }
            }
        }
        return $_deps;
    }

    /**
     * Filters dependencies.
     *
     * @param type $args
     */
    public static function wpcf_access_dependencies_filter($args)
    {
        $allow = $args[0];
        $disallow = $args[1];
        $set = $args[2];
		$cache_key = 'wpcf_access_dependencies_filter_'.serialize($args[0]).'_'.serialize($args[1]).'_'.serialize($args[2]);
		$result = Access_Cacher::get( $cache_key );
		if ( false !== $result ) {
			return $result;
		}
        foreach ($set as $data)
        {
            $context = $data['context'] == 'taxonomies' ? 'taxonomy' : 'post_type';
            $name = $data['parent'];
            $caps = $data['caps'];

            // Check dependencies and map them to WP readable
            foreach ($caps as $_cap => $true)
            {
                $true = (bool) $true;

                // Get dependencies settings by cap
                $deps = self::wpcf_access_dependencies_get($_cap, $true);

                // Map to WP rules
                if (!empty($deps['allow']))
                {
                    foreach ($deps['allow'] as $__cap)
                    {
                        $caps_readable = self::wpcf_access_predefined_to_wp_caps($context,
                                $name, $__cap);
                        $allow = $caps_readable + $allow;
                    }
                }
                if (!empty($deps['disallow']))
                {
                    foreach ($deps['disallow'] as $__cap)
                    {
                        $caps_readable = self::wpcf_access_predefined_to_wp_caps($context,
                                $name, $__cap);
                        $disallow = $caps_readable + $disallow;
                    }
                }
            }
        }
		Access_Cacher::set( $cache_key, array($allow, $disallow) );
        return array($allow, $disallow);
    }

    /**
     * Filters cap.
     *
     * @param type $capability_requested
     * @return string
     */
    public static function wpcf_access_exceptions_check()
    {
        $args = func_get_args();
        $capability_requested = $args[0][0];
        $parse_args = $args[0][1];
        if ( $args[0][1]['requested_user']->ID != $args[0][2] ){
            $args[0][2][1] = $args[0][1]['requested_user']->ID;
        }
        $args = $args[0][2];
        $user_id = $args[1];

        $found = self::wpcf_access_search_cap($capability_requested);
        // Allow filtering
        list($capability_requested, $parse_args, $args) = apply_filters('wpcf_access_exceptions',
                array($capability_requested, $parse_args, $args, $found));

        switch ($capability_requested)
        {
            case 'edit_comment':
                $post_type='posts';
                foreach ($parse_args['caps'] as $kk=>$cc)
                {
                    if (0===strpos($cc, 'edit_published_'))
                    {
                        $post_type=str_replace('edit_published_', '', $cc);
                        break;
                    }
                    elseif (0===strpos($cc, 'edit_others_'))
                    {
                        $post_type=str_replace('edit_others_', '', $cc);
                        break;
                    }
                }
                if ( !user_can($user_id, "edit_others_{$post_type}")) {
                    $comment_id = $args[2];
                    $comment = get_comment($comment_id);
                    if ( !empty($comment->comment_post_ID) ) {
                        $post = get_post( $comment->comment_post_ID );
                        if (!empty($post->ID) && $post->post_author != $user_id ) {
                            return array('cannot_edit_comment', array('caps' => array()), $args);
                        }
                    }
                }

                $capability_requested = 'edit_'.$post_type;
                $parse_args['caps'] = array('edit_published_'.$post_type, 'edit_others_'.$post_type, 'edit_comment');
                break;

            case 'moderate_comments':
                $post_type='posts';
                foreach ($parse_args['caps'] as $kk=>$cc)
                {
                    if (0===strpos($cc, 'edit_published_'))
                    {
                        $post_type=str_replace('edit_published_', '', $cc);
                        break;
                    }
                    elseif (0===strpos($cc, 'edit_others_'))
                    {
                        $post_type=str_replace('edit_others_', '', $cc);
                        break;
                    }
                }
                $capability_requested = 'edit_others_'.$post_type;
                $parse_args['caps'] = array('edit_published_'.$post_type, 'edit_others_'.$post_type, 'edit_comment', 'moderate_comments');
                break;

    //        case 'delete_post':
    //        case 'edit_post':
            default:
                // TODO Watchout for more!
                if (isset($args[1]) && isset($args[2]))
                {
                    $user = get_userdata(intval($args[1]));
                    $post_id = intval($args[2]);
                    $post = get_post($post_id);

                    if (!empty($user->ID) && !empty($post))
                    {
                        $parse_args_clone = $parse_args;
                        $args_clone = $args;
                        // check post id is valid, avoid capabilities warning
                        if (intval($post->ID)>0)
                        {
                            $map = map_meta_cap($capability_requested, $user->ID,
                                    $post->ID);
                            if (is_array($map) && !empty($map[0]))
                            {
                                foreach ($map as $cap)
                                {
                                    $args_clone = array($cap);
                                    $result = self::wpcf_access_check($parse_args_clone['allcaps'],
                                            $map, $args_clone, false);
                                    if (!$result)
                                        $parse_args['caps'] = array();
                                }
                            }
                        }
                        // Not sure why we didn't use this mapping before
                        $capability_requested = self::wpcf_access_map_cap($capability_requested,
                                $post_id, $user_id );
                    }

                    if (WPCF_ACCESS_DEBUG)
                    {
                        global $wpcf_access;
                        $wpcf_access->debug_hooks_with_args[$capability_requested][] = array(
                            'args' => $args,
                        );
                    }
                }
                break;
        }
        return array($capability_requested, $parse_args, $args);
    }

    /**
     * Register caps general settings.
     *
     * @global type $wpcf_access
     * @param type $args
     * @return boolean
     */
    public static function wpcf_access_register_caps($args)
    {
        global $wpcf_access;
        foreach (array('area', 'group') as $check) {
            if (empty($args[$check])) {
                return false;
            }
        }
        if (in_array($args['area'], array('types', 'tax'))) {
            return false;
        }
        extract($args);
        if (!isset($caps)) {
            $caps = array($cap_id => $args);
        }
        foreach ($caps as $cap) {
            foreach (array('cap_id', 'title', 'default_role') as $check) {
                if (empty($cap[$check])) {
                    continue;
                }
            }
            extract($cap);
            $wpcf_access->third_party[$area][$group]['permissions'][$cap_id] = array(
                'cap_id' => $cap_id,
                'title' => $title,
                'roles' => self::toolset_access_get_roles_by_role($default_role),
                'saved_data' => isset($wpcf_access->settings->third_party[$area][$group]['permissions'][$cap_id]) ? $wpcf_access->settings->third_party[$area][$group]['permissions'][$cap_id] : array('roles' => self::toolset_access_get_roles_by_role($default_role)),
            );
            return $wpcf_access->third_party[$area][$group]['permissions'][$cap_id];
        }
        return false;
    }

    /**
     * Returns specific post access settings.
     *
     * @global type $post
     * @param type $post_id
     * @param type $area
     * @param type $group
     * @param type $cap_id
     * @return type
     */
    public static function wpcf_access_get_post_access($post_id = null, $area = null,
            $group = null, $cap_id = null)
    {
        if (is_null($post_id))
        {
            global $post;
            if (empty($post->ID))
            {
                return array();
            }
            $post_id = $post->ID;
        }
        $model = TAccess_Loader::get('MODEL/Access');
        $meta = $model->getAccessMeta($post_id); //get_post_custom($post_id, 'wpcf-access', true);
        if (empty($meta))
        {
            return array();
        }
        if (!empty($area) && empty($group))
        {
            return !empty($meta[$area]) ? $meta[$area] : array();
        }
        if (!empty($area) && !empty($group) && empty($cap_id))
        {
            return !empty($meta[$area][$group]) ? $meta[$area][$group] : array();
        }
        if (!empty($area) && !empty($group) && !empty($cap_id))
        {
            return !empty($meta[$area][$group]['permissions'][$cap_id]) ? $meta[$area][$group]['permissions'][$cap_id] : array();
        }
        return array();
    }

    /**
     * Register caps per post.
     *
     * @global type $wpcf_access
     * @param type $args
     * @return boolean
     */
    public static function wpcf_access_register_caps_post($args)
    {
        global $wpcf_access, $post;
        foreach (array('area', 'group') as $check)
        {
            if (empty($args[$check]))
                return false;
        }
        if (in_array($args['area'], array('types', 'tax')))
            return false;

        extract($args);
        if (!isset($caps))
            $caps = $args;

        foreach ($caps as $cap)
        {
            foreach (array('cap_id', 'title', 'default_role') as $check)
            {
                if (empty($cap[$check]))
                    continue;
            }
            extract($cap);
            $saved_data = self::wpcf_access_get_post_access($post->ID, $area, $group,
                    $cap_id);
            $wpcf_access->third_party_post[$post->ID][$area][$group]['permissions'][$cap_id] = array(
                'cap_id' => $cap_id,
                'title' => $title,
                'roles' => self::toolset_access_get_roles_by_role($default_role),
                'saved_data' => !empty($saved_data) ? $saved_data : array('roles' => self::toolset_access_get_roles_by_role($default_role)),
            );
        }
    }

    /**
     * Collect all 3rd party hooks.
     *
     * @global type $wpcf_access
     * @return type
     */
    public static function wpcf_access_hooks_collect()
    {
        global $wpcf_access;
        $r = array();

		$extra_tabs	= apply_filters( 'types-access-tab', array() );
		// Native Third Party areas
        $a			= apply_filters( 'types-access-area', array() );
		//Third Party areas coming from custom tabs
		foreach ( $extra_tabs as $tab_slug => $tab_name ) {
			$a = apply_filters( 'types-access-area-for-' . $tab_slug, $a );
		}

        if ( ! is_array( $a ) ) {
			$a = array();
		}

        foreach ($a as $area)
        {
            if (!isset($r[$area['id']]))
                $r[$area['id']]=array();

            $g = apply_filters('types-access-group', array(), $area['id']);
            if (!is_array($g)) $g=array();
            foreach ($g as $group)
            {
                if (!isset($r[$area['id']][$group['id']]))
                    $r[$area['id']][$group['id']]=array();

                $c = apply_filters('types-access-cap', array(), $area['id'],
                        $group['id']);
                if (!is_array($c)) $c=array();

                foreach ($c as $cap)
                {
                    $r[$area['id']][$group['id']][$cap['cap_id']] = $cap;
                    $cap['area'] = $area['id'];
                    $cap['group'] = $group['id'];
                    $cap_reg_data = self::wpcf_access_register_caps($cap);
                    $wpcf_access->third_party_caps[$cap['cap_id']] = $cap_reg_data;
                }
            }
        }
        return $r;
    }

    /**
     * @return bool|string
     */
	public static function wpcf_access_get_current_page( ) {
		// Avoid breaking CLI
		if (
			! isset( $_SERVER['HTTP_HOST'] )
			|| ! isset( $_SERVER['REQUEST_URI'] )
		) {
			return '';
		}

		$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
		$url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $post_types = get_post_types( '', 'names' );
        $stored_post_types = Access_Cacher::get( 'wpcf-access-current-post-types' );
        if ( false === $stored_post_types ) {
            Access_Cacher::set( 'wpcf-access-current-post-types', $post_types );
            $check_post_id = true;
        }else{
            if ( $post_types == $stored_post_types ){
                $check_post_id = false;
            }else{
                Access_Cacher::set( 'wpcf-access-current-post-types', $post_types );
                $check_post_id = true;
            }
        }

 		$post_id = Access_Cacher::get( 'wpcf-access-current-post-id' );
		if ( false === $post_id || $check_post_id ) {
		    global $sitepress;
		    if ( is_object( $sitepress ) ){
                remove_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
                $post_id = url_to_postid( $url );
                add_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
                if ( empty( $post_id ) ){
                    $post_id = url_to_postid( $url );
                }
            }else{
                $post_id = url_to_postid( $url );
            }

			if ( !isset($post_id)  || empty($post_id) || $post_id == 0 ){
				if ( count($_GET) == 1 && get_option('permalink_structure') == ''){
					foreach ( $_GET as $key => $val ) {
                        $val = self::wpcf_esc_like($val);
                        $key = self::wpcf_esc_like($key);
						if ( post_type_exists($key) ){
						    global $wpdb;
							$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = '%s' and post_type='%s'", $val, $key));
						}
					}
				}
			}

            if ( empty($post_id) ){
                $homepage = get_option( 'page_on_front' );
                if ( get_home_url().'/' == $url && $homepage != '' ){
                    $post_id = $homepage;
                }
            }

			if ( !isset($post_id) || empty($post_id) ){
				$post_id = '';
			}else{
                Access_Cacher::set( 'wpcf-access-current-post-id', $post_id );
            }

			$post_id = Access_Cacher::get( 'wpcf-access-current-post-id' );

		}
		return $post_id;
	}

    /**
     * Hides post type on frontend.
     *
     * Checks if user is logged and if has required level to read posts.
     * This was determined only by role.
     *
     * In theory this is only run when registerin a post type so it changes its frontend settings
     * But this is being used too for frontend single pages by checking the group and possible error settings
     *
     * TODO This frontend settings per post should be done on another hook and on a singular / archive basis
     *
     * @todo Check if checking by user_id is needed
     *
     * @global type $wpcf_access
     * @global type $wp_post_types
     * @global type $current_user
     * @param type $role
     * @param type $post_type
     */
    public static function set_frontend_read_permissions($roles, $post_type)
    {
        global $wpcf_access, $wp_post_types;
		$current_user = wp_get_current_user();

        $role = self::wpcf_get_current_logged_user_role();

        if ( $role == 'administrator' ){
            return;
        }

        //Hide post type by default
        $hide = true;

		$settings_access = $wpcf_access->settings->types;

        if ( ( ! isset( $settings_access[ $post_type ] ) || $settings_access[ $post_type ]['mode'] == 'follow' ) && isset( $settings_access[ 'post' ] ) ) {
            $settings_access[$post_type]['permissions'] = $settings_access[ 'post' ]['permissions'];
        }

        if ( !isset($settings_access[$post_type]) &&  'permissions' != $settings_access[$post_type]['mode'] ){
            return;
        }

        $users = array();
        if ( isset($settings_access[$post_type]['permissions']['read']['users']) ){
            $users = $settings_access[$post_type]['permissions']['read']['users'];
        }

        if (  $wpcf_access->wpml_installed ){
            $wpml_settings = $wpcf_access->language_permissions;
            $current_post_language = apply_filters( 'wpml_current_language', NULL);
            //Specific user
            if ( isset($wpml_settings[$post_type][$current_post_language]['read']['roles']) ){
                $roles = $wpml_settings[$post_type][$current_post_language]['read']['roles'];
            }
            if ( isset($wpml_settings[$post_type][$current_post_language]['read']['users']) ){
                $users = $wpml_settings[$post_type][$current_post_language]['read']['users'];
            }
        }

        if ( in_array($role, $roles ) !== FALSE ){
            $hide = false;
        }

        // If user added as specific user
        if ( !empty($current_user->ID) && in_array($current_user->ID, $users ) !== FALSE ){
            $hide = false;
        }


		$post_id = '';
        $custom_error_info = self::wpcf_access_check_custom_error( $post_type, $role );

        if ( isset( $custom_error_info[0] ) && $custom_error_info[0] == 1 ) {
            if ( $custom_error_info[1] == 'unhide' ) {
                $hide = false;
            }
            if ( $custom_error_info[1] == 'hide' ) {
                $hide = true;
            }
            if ( $custom_error_info[2] ) {
                add_filter( 'comments_open', array(
                    'Access_Helper',
                    'wpcf_access_disable_comments'
                ), 1);
            }
            $post_id = self::wpcf_access_get_current_page();
        }

        // Set post type properties to hide on frontend
        if ($hide && isset($wp_post_types[$post_type]))
        {
            if ( isset($wpcf_access->settings->types['post']) && $wpcf_access->settings->types['post']['mode'] != 'not_managed' ){
                $wp_post_types[$post_type]->public = false;
            }else{
                $wp_post_types[$post_type]->public = true;
            }
			$wp_post_types[$post_type]->show_in_nav_menus = false;
            $wp_post_types[$post_type]->exclude_from_search = true;
            $wpcf_access->debug_hidden_post_types[] = $post_type;
			$wpcf_access->hide_built_in[] = $post_type;
          	if ( $post_type !== 'attachment' ){
                $custom_archive_error_info = Access_Cacher::get( 'wpcf-access-archive-permissions-' . $post_type );
                if ( false === $custom_archive_error_info ) {
                    $custom_archive_error = self::wpcf_access_check_archive_for_errors( $post_type );
                    Access_Cacher::set( 'wpcf-access-archive-permissions-' . $post_type, $custom_archive_error_info );
                }

                if( class_exists('WPDD_Layouts') && apply_filters( 'ddl-is_integrated_theme', false ) ) {
                    $wp_post_types[ $post_type ]->public = true;
                }

                if ( is_array( $custom_archive_error ) && empty( $post_id ) ) {
                    list( $action, $source, $item_id ) = $custom_archive_error;
                    if ( $action == 'unhide' ) {
                        $wpcf_access->hide_built_in = array_diff( $wpcf_access->hide_built_in, array( $post_type ) );

                        if ( $source == 'view' ) {
                            if ( function_exists('wpv_force_wordpress_archive') ) {
                                add_filter( 'wpv_filter_force_wordpress_archive', array(
                                    __CLASS__,
                                    'wpcf_access_replace_archive_view'
                                ) );
                            }
                        }
                        if ( $source == 'layout' && ! empty( $item_id ) ) {
                            add_filter( 'ddl-is_ddlayout_assigned', array(
                                __CLASS__,
                                'wpcf_access_load_layout_archive_is_assigned'
                            ) );
                            add_action( 'wp_head', array(
                                __CLASS__,
                                'wpv_access_error_template_archive_layout'
                            ) );
                        }
                        if ( $source == 'php' ) {
                            add_action( 'template_redirect', array(
                                __CLASS__,
                                'wpcf_access_replace_archive_php_template'
                            ) );
                        }
                    }
                }
			}



			//This hiden because we need to show that post types exists but nothinf found.
			//TODO GEN, check this
			//$wp_post_types[$post_type]->publicly_queryable = false;


            // Trigger change for posts and pages
            // Built-in post types can only be excluded from search
            // using following filters: 'posts_where', 'get_pages', 'the_comments'
                // If debug mode - record call


                // Register filters
                add_filter('posts_where', array('Access_Helper', 'wpcf_access_filter_posts'));
                add_filter('get_pages', array('Access_Helper', 'wpcf_access_exclude_pages'));
                add_filter('the_comments', array('Access_Helper', 'wpcf_access_filter_comments'));

            //}
        }
        else if ($wp_post_types[$post_type])
        {
            $wp_post_types[$post_type]->public = true;
            $wpcf_access->debug_visible_post_types[] = $post_type;
        }
    }

    /**
     * Load php file on Archive pages
     */
	public static function wpcf_access_replace_archive_php_template(){
		global  $wp_query;

		$post_type_object = $wp_query->get_queried_object();
		if ($post_type_object) {
			$post_type = $post_type_object->name;
			$error = Access_Cacher::get( 'wpcf_archive_error_value_'.$post_type );
			if ( false !== $error ) {
				$template = $error;
				if ( file_exists($template) ){
					include( $template );
					Access_Cacher::delete('wpcf_archive_error_value_'.$post_type);
					exit;
				}
			}
		}
	}

	/*
	 * Override existing WPA for post type
	 */
	public static function wpcf_access_replace_archive_view($view){
		global  $wp_query;

		$post_type_object = $wp_query->get_queried_object();

		if ($post_type_object) {
			$post_type = $post_type_object->name;
			$error = Access_Cacher::get( 'wpcf_archive_error_value_'.$post_type );
			if ( false !== $error ) {
				$view = $error;
				Access_Cacher::delete('wpcf_archive_error_value_'.$post_type);
			}
		}

		return $view;
	}

	/*
	 * check if archive have custom errors
	 */
	public static function wpcf_access_check_archive_for_errors($post_type){
        global $wpcf_access;
		$role = self::wpcf_get_current_logged_user_role();
		if ( $role == 'administrator' ){
			return;
		}

		$settings_access = $wpcf_access->settings->types;

		if ( isset( $settings_access['_archive_custom_read_errors'][ $post_type ]['permissions']['read'] ) &&
		       isset( $settings_access['_archive_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ] ) ) {

			$error_type = $settings_access['_archive_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ];

			if ( $error_type == 'error_ct' ) {
				if ( isset( $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'] ) ) {
					$error_value = $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ];
					Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );

					return array( 'unhide', 'view', $error_value );
				} else {
					return;
				}
			}
			if ( $error_type == 'error_layouts' ) {
				if ( isset( $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'] ) ) {
					$error_value = $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ];
					Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );

					return array( 'unhide', 'layout', $error_value );
				} else {
					return;
				}
			}
			if ( $error_type == 'error_php' ) {
				if ( isset( $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'] ) ) {
					$error_value = $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ];
					Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );

					return array( 'unhide', 'php', $error_value );
				} else {
					return;
				}
			}
		}
        if ( isset( $settings_access['_archive_custom_read_errors'][ $post_type ]['permissions']['read']['everyone'] ) ) {
			if ( !empty($settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read']['everyone']) ){
				$error_type = $settings_access['_archive_custom_read_errors'][$post_type]['permissions']['read']['everyone'];
				if ( $error_type == 'error_ct' ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];

						Access_Cacher::set( 'wpcf_archive_error_value_'.$post_type, $error_value );
						return array('unhide','view',$error_value);
					}
					else{
						return;
					}
				}
				if ( $error_type == 'error_ct' || $error_type == 'error_layouts' ){
					if ( isset( $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read'] ) ){
						$error_value = $settings_access['_archive_custom_read_errors_value'][ $post_type ]['permissions']['read']['everyone'];

						Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );
						return array( 'unhide', 'layout', $error_value );
					}
					else{
						return;
					}
				}
				if ( $error_type == 'error_php' ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']) ){
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
						Access_Cacher::set( 'wpcf_archive_error_value_'.$post_type, $error_value );
						return array('unhide','php',$error_value);
					}
					else{
						return;
					}
				}
			}
		}
	}


	/*
	 * Replace archive output when no read permissions
	 */
	public static function wpcf_access_replace_archive_output($query){
		global  $wp_query, $wpcf_access;

		if ( is_post_type_archive() ) {


			$post_type_object = $wp_query->get_queried_object();

	        // See if we have a settings for this post type
	        if ($post_type_object) {
				$role = self::wpcf_get_current_logged_user_role();

				if ( $role == 'administrator' ){
					return;
				}

                $settings_access = $wpcf_access->settings->types;

				if ( isset($settings_access['_archive_custom_read_errors'][$post_type_object->name]) ){
					if ( isset($settings_access['_archive_custom_read_errors_value'][$post_type_object->name]['permissions']['read']) ){
						$error_type = $settings_access['_archive_custom_read_errors'][$post_type_object->name]['permissions']['read'][$role];
						$error_value = $settings_access['_archive_custom_read_errors_value'][$post_type_object->name]['permissions']['read'][$role];
					}else{
						return;
					}
				}else{
					return;
				}
			}
		}
	}


	/*
	 * Disable comments on page where custom error - Content template
	 */
	public static function wpcf_access_disable_comments(){
		return false;
	}

	/*
	 * Get current user role
	 */
	public static function wpcf_get_current_logged_user_role( $user = '' ){
		global $current_user;
		$role = '';
		if ( is_user_logged_in() || !empty( $user ) ){
		    $check_user = $current_user;
		    if ( !empty( $user ) ){
		        $check_user = $user;
            }
			if ( is_array($check_user->roles) ){
				$role_temp = $check_user->roles;
				$role = array_shift($role_temp);
			}else{
				$role = $check_user->roles;
			}
		}
		if ($role == ''){
			$role = 'guest';
		}
		return $role;
	}

	/*
	 * Get current user role
	 */
	public static function wpcf_get_current_logged_user_level( $user ){
		if ( isset($user->allcaps) ){
			$caps = $user->allcaps;
			for ($i=10;$i>=0;$i--){
				if ( isset($caps['level_'.$i] ) ){
					return $i;
				}
			}
			return 0;
		}else{
			return 0;
		}
	}

    /**
     * @param $role
     * @param $user_level
     * @return int|mixed|string
     */
	public static function wpcf_convert_user_role( $role, $user_level ){

		if ($role == 'guest'){
			return $role;
		}

		$managed_roles = array();
    	$roles = Access_Helper::wpcf_get_editable_roles();
		$default_roles = Access_Helper::wpcf_get_default_roles();
		foreach ($roles as $role => $details)
    	{

    		for ($i=10;$i>=0;$i--){
    			if ( isset( $details['capabilities']['level_'.$i]) ){
    				if ( !isset( $managed_roles[$i] ) ){
    					$managed_roles[$i] = $role;
						$i=-1;
					}
				}
			}
		}

		if ( isset($managed_roles[$user_level]) ){
			return $managed_roles[$user_level];
		}else{
			return 'guest';
		}
	}

    /**
     * @param $post_id
     * @return array|void
     * Set error on page when custom error
     */
	public static function wpcf_access_get_custom_error( $post_id ){
		global $wp_query,$current_user, $wpcf_access;
		$role = self::wpcf_get_current_logged_user_role();

		if ( $role == 'administrator' ){
			return;
		}

		$settings_access = $wpcf_access->settings->types;
		$post_type = get_post_type($post_id);
		$post_status = get_post_status($post_id);

		$template_id = $show = '';
		$group = get_post_meta( $post_id, '_wpcf_access_group', true);
		$go = true;
		$read = false;


		if ( isset( $settings_access[ $post_type ]) && $settings_access[$post_type]['mode'] == 'permissions' ){
            $check_cap = $settings_access[ $post_type ]['permissions'];
        }else{
            $check_cap = isset( $settings_access['post']['permissions'] ) ? $settings_access['post']['permissions'] : null;
            $post_type = 'post';
        }

        if ( !isset($check_cap['read']['roles']) || !isset($settings_access[$post_type]) || $settings_access[$post_type]['mode'] === 'not_managed' ){
				return array($show, '', true);
		}

        //Read permissions by Language
        if (  $wpcf_access->wpml_installed ){
            $wpml_settings = $wpcf_access->language_permissions;
            $current_post_language = apply_filters( 'wpml_current_language', NULL);
            $wpml_default_language = apply_filters( 'wpml_setting', '', 'default_language' );
            if ( isset($wpml_settings[$post_type][$current_post_language]) ){
                $check_cap = $wpml_settings[$post_type][$current_post_language];
                if ( isset($check_cap['group']) ){
                    $group = $check_cap['group'];
                }
            }
        }

		//If group assigned to this post
		if ( isset($group) && !empty($group) && isset($settings_access[$group]) && $post_status == 'publish' ){
			$show = '';
			$read = false;
			if ( isset($current_user->ID) ){
				if ( isset($settings_access[$group]['permissions']['read']['users']) && in_array($current_user->ID, $settings_access[$group]['permissions']['read']['users']) !== FALSE ){
					return array($show, '', true);
				}
			}
			if ( in_array($role, $settings_access[$group]['permissions']['read']['roles']) !== FALSE ){
                return array($show, '', true);
            }else{
                $read = false;
            }


			//Check if current post and role have specific error.
			if ( isset($settings_access['_custom_read_errors'][$group]['permissions']['read'][$role]) && $go ){
				$error_type = $settings_access['_custom_read_errors'][ $group ]['permissions']['read'][ $role ];
				if ( $error_type == 'error_404'){
					$show = $error_type;
					$go = false;

				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role];
					$go = false;
					$read = true;
				}
				if ( $error_type == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read'][$role];
					$go = false;
				}
			}

			//Check if current group have specific error
			if ( isset($settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone']) && $go ){
				$error_type = $settings_access['_custom_read_errors'][ $group ]['permissions']['read']['everyone'];
				if ( $error_type == 'error_404'){
					$show = $settings_access['_custom_read_errors'][$group]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( $error_type == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$group]['permissions']['read']['everyone'];
					$go = false;
				}
			}

			return array($show, $template_id, $read);

		}

        // Check post type permissions
        if ( ( is_array( $check_cap['read']['roles'] ) && in_array( $role, $check_cap['read']['roles'] ) !== FALSE ) ||
             ( array_key_exists( 'users', $check_cap['read'] ) && is_array( $check_cap['read']['users'] ) && in_array( $current_user->ID, $check_cap['read']['users'] ) !== FALSE ) ){
            return array( $show, '', true );
        }


		if ( $go ){

			//Check if current post and role have specific error.
			if ( isset($settings_access['_custom_read_errors'][$post_type]['permissions']['read'][$role]) && $go ){

				$error_type = $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ];
				if ( $error_type == 'error_404'){
					$show = $error_type;
					$go = false;
					$read = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					$go = false;
					$read = true;
				}
				if (  $error_type == 'error_php' && isset( $settings_access['_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ] ) ){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read'][$role];
					$go = false;
				}
			}

			//Check if current group have specific error
			if ( isset($settings_access['_custom_read_errors'][$post_type]['permissions']['read']['everyone']) && $go ){
			    $error_type = $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read']['everyone'];
				if ( $error_type == 'error_404'){
					$show = $error_type;
					$go = false;
					$read = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
					$go = false;
				}
				if ( $error_type == 'error_php' &&
					isset($settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'])){
					$show = $error_type;
					$template_id = $settings_access['_custom_read_errors_value'][$post_type]['permissions']['read']['everyone'];
					$go = false;
				}
			}

		}

		return array($show, $template_id, $read);
	}


    /**
     * @param $post_type
     * @param $role
     * @return array
     * Check for custom error
     */
	public static function wpcf_access_check_custom_error($post_type, $role){
		global $wpdb,$current_user;

		$post_id = self::wpcf_access_get_current_page();
		if ( !isset($post_id) || empty($post_id) ){
			return array(0,'');
		}

		$return = 0;
		$do = '';
		$template = Access_Cacher::get( 'wpcf-access-post-permissions-'.$post_id );
		if ( false === $template ) {
			$template = self::wpcf_access_get_custom_error($post_id);
			Access_Cacher::set( 'wpcf-access-post-permissions-'.$post_id, $template );
		}

		$custom_error = toolset_getarr( $template, 0, '' );
		$custom_error_value = toolset_getarr( $template, 1, '' );

		if ( 'error_ct' === $custom_error ) {
			self::disable_the_content_hooks();
		}

		$disable_comments = false;
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_ct' ) {
			$do = 'unhide';
			$return = 1;
			$disable_comments = true;
			add_filter('wpv_filter_force_template', array(__CLASS__, 'wpv_access_error_content_template'), 20, 3);
		}
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_php' && !$template[2] ) {
			$do = 'unhide';
			$return = 1;
			add_action( 'template_redirect', array(__CLASS__, 'wpv_access_error_php_template'), $custom_error_value );
		}
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_layouts' ) {
			$do = 'unhide';
			$return = 1;
			add_action( 'wp', array(__CLASS__, 'wpv_access_error_template_layout' ) );
		}
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_404' && !$template[2] ) {
			$do = 'hide';
			add_action( 'pre_get_posts', array(__CLASS__, 'wpcf_exclude_selected_post_from_single'), 0 );
			$return = 1;
		}
		if ( $template[2] ) {
			$do = 'unhide';
			$return = 1;
		}
		if ( ! $template[2] &&  empty( $custom_error ) ) {
			$do = 'hide';
			$return = 1;
		}


		return array($return, $do, $disable_comments);
	}

	/**
	 * Remove the_content filters from Elementor when render Content Template custom error
	 */
	public static function disable_the_content_hooks(){
	    global $wp_filter;
	    $filters = $wp_filter['the_content'];
		foreach( $filters as $priority => $filters_array ) {
            foreach( $filters_array as $filter_index => $filter ) {
	            if ( isset( $filter['function'][1] ) && is_object( $filter['function'][0] )
                     && 'Elementor\Frontend' == get_class($filter['function'][0]) && 'apply_builder_in_content' === $filter['function'][1] ) {
		            remove_filter( 'the_content', array( $filter['function'][0], $filter['function'][1] ), $priority );
	            }
            }
		}
    }

	/*
	 * Exclude current post from list of queries
	 */
	public static function wpcf_exclude_selected_post_from_single( $query ){
        if ( !is_admin() && $query->is_main_query() ) {
            $post_id = self::wpcf_access_get_current_page();
            if ( !isset($post_id) || empty($post_id)){
                return;
            }
            $not_in =  $query->get('post__not_in');
            $not_in[] = $post_id;
            $query->set('post__not_in', $not_in);
        }
	}

	/*
	 * Load PHP Template error
	 */
	public static function wpv_access_error_php_template( $template ){
		global $post;

		if ( !isset($post) || empty($post)){
			return;
		}
		$post_id = $post->ID;
		$template = self::wpcf_access_get_custom_error($post_id);
		$templates = wp_get_theme()->get_page_templates();
		if ( !empty($templates) ){
             $file = '';
			 foreach ( $templates as $template_name => $template_filename ) {
				 	if ( $template_filename == $template[1] ){
				 		$file = $template_name;
					}
			 }
             if ( !empty($file) && file_exists(get_template_directory() . '/'. $file) ){
                include( get_template_directory() . '/'. $file );
             }
             elseif(  !empty($file) && file_exists(get_stylesheet_directory() . '/'. $file) ){
                include( get_stylesheet_directory() . '/'. $file );
             }
             else{
                echo '<h1>' . __('Can\'t find php template', 'wpcf-access') . '</h1>';
             }
			 exit;
		}
		else{
			return;
		}

	}

    /**
     * @param $content
     * @return string|void
     */
	public static function wpv_access_error_template_layout( $content ){
		global $post;
        remove_action( 'wp', array( __CLASS__, 'wpv_access_error_template_layout' ) );

        if( ! class_exists('WPDD_Layouts') ){
            return '';
        }
		if ( ! isset( $post ) || empty( $post ) ){
			return;
		}

		if( ! class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }


        $is_layout_template = has_current_post_ddlayout_template();
		$post_id = $post->ID;
		$template_info = self::wpcf_access_get_custom_error( $post_id );
		$template = Access_Admin_Edit::toolset_access_get_layout_name( $template_info[1] );
		Access_Cacher::set( 'wpcf_single_post_error_value', $template_info[1] );
		if ( empty( $template ) ){
            return '';
        }

		add_filter( 'get_layout_id_for_render', array( __CLASS__, 'wpcf_access_load_layout' ) );
		add_filter( 'ddl-get_layout_id_by_slug', array( __CLASS__, 'wpcf_access_load_layout' ) );

		do_action( 'toolset_theme_settings_force_settings_refresh', $post->ID  );
		add_filter( 'force_get_settings_for_layout_or_ct_passed_from_url', '__return_true');

		if ( ! $is_layout_template ){
            /**
             * ddl_apply_the_content_filter_in_cells, ddl_apply_the_content_filter_in_post_content_cell disable the_content filter for
             * visual editor and post content cells when loading custom error assigned to layout
             */
            add_filter( 'ddl_apply_the_content_filter_in_cells', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11, 1 );
            add_filter( 'ddl_apply_the_content_filter_in_post_content_cell', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11, 1 );
            add_filter( 'the_content', array( __CLASS__, 'wpv_access_error_template_layout_the_content' ) );
        }

	}

    /**
     * @param $bool
     * Used for ddl_apply_the_content_filter_in_cells, ddl_apply_the_content_filter_in_post_content_cell to disable the_content filter for visual edit and post content cells
     * @return bool | false
     */
    public static function disable_the_content_filter_for_layouts_cells( $bool ) {
        return false;
    }

	public static function wpv_access_error_template_layout_the_content( $content ){
        remove_filter( 'the_content', array( __CLASS__, 'wpv_access_error_template_layout_the_content' ) );
        remove_filter( 'ddl_apply_the_content_filter_in_cells', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11 );
        remove_filter( 'ddl_apply_the_content_filter_in_post_content_cell', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11 );
        $error = Access_Cacher::get('wpcf_single_post_error_value');
        $_GET['layout_id'] = $error;
        add_filter( 'get_layout_id_for_render', array( __CLASS__, 'wpcf_access_load_layout' ) );
        $layout_content = get_the_ddlayout( $error, array( 'initialize_loop' => false ) );
        add_filter( 'ddl_apply_the_content_filter_in_cells', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11, 1 );
        add_filter( 'ddl_apply_the_content_filter_in_post_content_cell', array( __CLASS__, 'disable_the_content_filter_for_layouts_cells' ), 11, 1 );
		add_filter( 'the_content', array( __CLASS__, 'wpv_access_error_template_layout_the_content' ) );
        return $layout_content;
	}

    /**
     * @param $id
     *
     * @return string
     */
    public static function wpv_access_error_template_archive_layout( $id ){
        remove_action( 'wp_head', array( __CLASS__, 'wpv_access_error_template_archive_layout' ) );

        if( ! class_exists('WPDD_Layouts') ){
            return '';
        }
        if ( self::is_layout_custom_error() ){
            add_filter( 'get_layout_id_for_render', array( __CLASS__, 'wpcf_access_load_layout_archive') );
        }else{
            return $id;
        }

    }

    public static function wpcf_access_load_layout(){
        $output = Access_Cacher::get('wpcf_single_post_error_value');
        return $output;
    }

    /**
     * Return custom error layout id
     * if layouts doens't exists, return id of assigned layout
     * @return mixed
     */
    public static function wpcf_access_load_layout_archive(){
        remove_filter( 'get_layout_id_for_render', array( __CLASS__, 'wpcf_access_load_layout_archive') );
        if ( self::is_layout_custom_error()  ) {
            global $wp_query;
            $post_type_object = $wp_query->get_queried_object();
            $post_type = $post_type_object->name;
            $output = Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );
            return $output;
        }
        $id = WPDD_Layouts_RenderManager::getInstance()->get_layout_id_for_render( null, array() );
        return $id;
    }

    /**
     * @return bool
     */
    public static function is_layout_custom_error (){
        global $wp_query;
        $post_type_object = $wp_query->get_queried_object();

        if ( $post_type_object && get_class( $post_type_object ) == 'WP_Post_Type'  ) {
            $post_type = $post_type_object->name;
            $cached_layout_id = Access_Cacher::get( 'wpcf_archive_error_value_' . $post_type );
            if ( $cached_layout_id > 0 ) {
                return true;
            }
        }
        return false;
    }

    public static function wpcf_access_load_layout_archive_is_assigned(){
        return true;
    }



	/*
	 * Load Content template error
	 */
	public static function wpv_access_error_content_template( $template_selected, $post_id, $kind = '' ){
		$template = self::wpcf_access_get_custom_error($post_id);
		if ( isset($template[0]) && !empty($template[0])){
			return $template[1];
		}else{
			return;
		}

	}

    /**
     * Filters posts.
     *
     * @global type $wpcf_access
     * @global type $wpdb
     * @param type $args
     * @return type
     */
    public static function wpcf_access_filter_posts($args)
    {
        global $wpcf_access, $wpdb;
        if (!empty($wpcf_access->hide_built_in)) {
            foreach ($wpcf_access->hide_built_in as $post_type) {
                $args .= " AND $wpdb->posts.post_type <> '$post_type'";
            }
        }
        return $args;
    }

    /**
     * Excludes pages if necessary.
     *
     * @global type $wpcf_access
     * @param type $pages
     * @return type
     */
    public static function wpcf_access_exclude_pages($pages)
    {
        global $wpcf_access;
        if (!empty($wpcf_access->hide_built_in)) {
            if (in_array('page', $wpcf_access->hide_built_in)) {
                return array();
            }
        }
        return $pages;
    }

    /**
     * Filters comments.
     *
     * @global type $wpcf_access
     * @param type $comments
     * @return type
     */
    public static function wpcf_access_filter_comments($comments)
    {
        global $wpcf_access;
        if (!empty($wpcf_access->hide_built_in)) {
            foreach ($comments as $key => $comment) {
                // TODO Monitor this: only posts comment missing post_type?
                // Set 'post' as default
                if (!isset($comment->post_type)) {
                    $wpcf_access->errors['filter_comments_no_post_type'][] = $comment;
                    $comment->post_type = get_post_type($comment->comment_post_ID);
                }
                if (in_array($comment->post_type, $wpcf_access->hide_built_in)) {
                    unset($comments[$key]);
                }
            }
        }
        return $comments;
    }

    /**
     * Filters default WP capabilities for user.
     *
     * WP adds default capabilities depending on built-in role
     * that sometimes by-pass user_can() check.
     *
     * @todo Check if upload_files should be suspended from 3.5
     * @global type $current_user
     * @global type $wpcf_access
     */
    public static function wpcf_access_user_filter_caps()
    {
        $role = self::wpcf_get_current_logged_user_role();
        if (!empty($current_user->allcaps)) {
            foreach ($current_user->allcaps as $cap => $true) {
                $cap_found = self::wpcf_access_search_cap($cap);
                if (!empty($cap_found)) {
                    $allow = false;
                    if( in_array( $current_user->ID, $cap_found['users'] ) !== FALSE ){
                        $allow = true;
                    }elseif( in_array( $role,  $cap_found['roles'] ) !== FALSE  ){
                        $allow = true;
                    }

                    if (!$allow) {
                        unset($current_user->allcaps[$cap]);
                    }
                }
            }
        }
    }

    /**
     * Determines post type.
     *
     * @global type $post
     * @global type $pagenow
     * @return string
     */
    public static function wpcf_access_determine_post_type()
    {
        global $post;
        $post_type = false;
        $post_id = self::wpcf_access_determine_post_id();
        if (!empty($post) || !empty($post_id)) {
            if (get_post($post_id)) {
                return get_post_type($post_id);
            }
            $post_type = get_post_type($post);
        } /*else if (isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
        } else if (isset($_POST['post_type'])) {
            $post_type = $_POST['post_type'];
        }*/
        else if (isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            $post_type = get_post_type($post_id);
        }
        else if (isset($_GET['post_id'])) {
            $post_id = intval($_GET['post_id']);
            $post_type = get_post_type($post_id);
        } else if (isset($_POST['post_id'])) {
            $post_id = intval($_POST['post_id']);
            $post_type = get_post_type($post_id);
        } else if (isset($_POST['post'])) {
            $post_id = intval($_POST['post']);
            $post_type = get_post_type($post_id);
        } else if (isset($_SERVER['HTTP_REFERER'])) {
            $split = explode('?', $_SERVER['HTTP_REFERER']);
            if (isset($split[1])) {
                parse_str($split[1], $vars);
                if (isset($vars['post_type'])) {
                    $post_type = $vars['post_type'];
                } else if (isset($vars['post'])) {
                    $post_type = get_post_type($vars['post']);
                } else if (strpos($split[1], 'post-new.php') !== false) {
                    $post_type = 'post';
                }
            } else if (strpos($_SERVER['HTTP_REFERER'], 'post-new.php') !== false
                    || strpos($_SERVER['HTTP_REFERER'], 'edit-tags.php') !== false
                    || strpos($_SERVER['HTTP_REFERER'], 'edit.php') !== false) {
                $post_type = 'post';
            }
        }
        return $post_type;
    }

    /**
     * Determines post ID.
     *
     * @global type $post
     * @global type $pagenow
     * @return string bbbb
     */
    public static function wpcf_access_determine_post_id()
    {
        global $post;
        if (!empty($post)) {
            return $post->ID;
        } else if (isset($_GET['post'])) {
            return intval($_GET['post']);
        } else if (isset($_POST['post'])) {
            return intval($_POST['post']);
        } else if (isset($_GET['post_id'])) {
            return intval($_GET['post_id']);
        } else if (isset($_POST['post_id'])) {
            return intval($_POST['post_id']);
        } else if (defined('DOING_AJAX') && isset($_SERVER['HTTP_REFERER'])) {
            $split = explode('?', $_SERVER['HTTP_REFERER']);
            if (isset($split[1])) {
                parse_str($split[1], $vars);
                if (isset($vars['post'])) {
                    return intval($vars['post']);
                } else if (isset($vars['post_id'])) {
                    return intval($vars['post_id']);
                }
            }
        }
        return false;
    }

    /**
     * Gets attachment parent post type.
     *
     * @return boolean
     */
    public static function wpcf_access_attachment_parent_type()
    {
        if (isset($_POST['attachment_id'])) {
            $post_id = intval($_POST['attachment_id']);
        } else if (isset($_GET['attachment_id'])) {
            $post_id = intval($_GET['attachment_id']);
        } else {
            return false;
        }
        $post = get_post($post_id);
        if (!empty($post->post_parent)) {
            $post_parent = get_post($post->post_parent);
            if (!empty($post_parent->post_type)) {
                return $post_parent->post_type;
            }
        }
        return false;
    }

    /**
     * Maps predefinied capabilities to specific post_type or taxonomy capability.
     *
     * Example in case of Page post type:
     * edit_post => edit_page
     *
     * @param type $context
     * @param type $name
     * @param type $cap
     * @return type
     */
    public static function wpcf_access_predefined_to_wp_caps($context = 'post_type',
            $name = 'post', $cap = 'read') {

        // Get WP type object data
        $data = $context == 'taxonomy' ? get_taxonomy($name) : get_post_type_object($name);
        if (empty($data)) {
            return array();
        }

        // Get defined capabilities
        $caps = $context == 'taxonomy' ? self::wpcf_access_tax_caps() : self::wpcf_access_types_caps();

        // Set mapped WP capabilities
        $caps_mapped = array();
        foreach ($caps as $_cap => $_data) {
            if ($_data['predefined'] == $cap) {
                if (!empty($data->cap->{$_cap})) {
                    $caps_mapped[$data->cap->{$_cap}] = $data->cap->{$_cap};
                }
            }
        }
        return array_keys($caps_mapped);
    }

    /**
     * Check Media post type.
     *
     * @global type $wp_version
     * @return type
     */
    public static function wpcf_access_is_media_registered()
    {
        global $wp_version;
        // WP 3.5
        return version_compare($wp_version, '3.4.3', '>');
    }

    /**
     * Maps capability according to current user and post_id.
     *
     * @param type $parse_args
     * @param type $post_id
     * @return type
     */
    public static function wpcf_access_map_cap($cap, $post_id, $user_id = null)
    {
        $current_user = wp_get_current_user();
        // do check for 0 post id
        $_user_id = $current_user->ID;
        if ( $_user_id != $user_id ){
            $_user_id = $user_id;
        }
        if (intval($post_id)>0)
        {
            $map = map_meta_cap($cap, $_user_id, $post_id);
            if (is_array($map) && !empty($map[0])) {
                return $map[0];
            }
        }
        return $cap;
    }

    /**
     * Returns cap settings declared in embedded.php
     *
     * @param type $cap
     * @return type
     */
    public static function wpcf_access_get_cap_settings($cap)
    {
        $caps_types = self::wpcf_access_types_caps();
        if (isset($caps_types[$cap]))
            return $caps_types[$cap];

        $caps_tax = self::wpcf_access_tax_caps();
        if (isset($caps_tax[$cap]))
            return $caps_tax[$cap];

        return array(
            'title' => $cap,
            'roles' => self::toolset_access_get_roles_by_role('administrator'),
            'predefined' => 'edit_any',
        );
    }

    /**
     * Returns cap settings declared in embedded.php
     *
     * @param type $cap
     * @return type
     */
    public static function wpcf_access_get_cap_predefined_settings($cap)
    {
        $predefined = self::wpcf_access_types_caps_predefined();
        if (isset($predefined[$cap]))
            return $predefined[$cap];
        // If not found, try other caps
        return self::wpcf_access_get_cap_settings($cap);
    }

    /**
     * @return array|null
     */
    public static function wpcf_access_get_taxonomies_shared(/*$tax=false*/)
    {
        global $wpcf_access;
        static $cache = null;
        static $failed = array();

        if (is_null($cache))
        {
            $found = array();
            $model = TAccess_Loader::get('MODEL/Access');
            $taxonomies = $model->getTaxonomies(null);
            foreach ($taxonomies as $slug => $data)
            {
                if (count($data->object_type) > 1) {
                    $found[$slug] = $data->object_type;
                }
            }
            $cache = $wpcf_access->shared_taxonomies = $found;
        }
        return $cache;
    }

    /**
     * Checks if taxonomy is shared.
     *
     * @param type $taxonomy
     * @return type
     */
    public static function wpcf_access_is_taxonomy_shared($taxonomy)
    {
        $shared = self::wpcf_access_get_taxonomies_shared(/*$taxonomy*/);
        return !empty($shared[$taxonomy]) ? $shared[$taxonomy] : false;
    }

    /**
     * Sets taxonomy mode.
     *
     * @param type $taxonomy
     * @param type $mode
     * @return type
     */
    public static function wpcf_access_get_taxonomy_mode($taxonomy, $mode = 'follow')
    {
        // default to 'not_managed' if shared to have uniform handling of imported caps
        return self::wpcf_access_is_taxonomy_shared($taxonomy) ? /*'permissions'*/'not_managed' : $mode;
    }

    /**
     * Adds or removes caps for roles down to level.
     *
     * @param type $role
     * @param type $cap
     * @param type $allow
     * @param type $distinct
     */
    public static function wpcf_access_assign_cap_by_level($role, $cap)
    {
		$access_roles = self::wpcf_get_editable_roles();
        $ordered_roles = self::wpcf_access_order_roles_by_level($access_roles);
        $flag = $found = false;
        foreach ($ordered_roles as $level => $roles)
        {
            foreach ($roles as $role_name => $role_data)
            {
                $role_set = get_role($role_name);
                if (!$flag)
                    $role_set->add_cap($cap);
                else
                    $role_set->remove_cap($cap);
                if ($role == $role_name)
                    $found = true;
            }
            if ($found)
                $flag = true;
        }
    }

    /**
     * Sorts default capabilities by predefined key.
     *
     * @return type
     */
    public static function wpcf_access_sort_default_types_caps_by_predefined()
    {
        $default_caps = self::wpcf_access_types_caps();
        $caps = array();
        foreach ($default_caps as $cap => $cap_data)
            $caps[$cap_data['predefined']][] = $cap;
        return $caps;
    }

    /**
     * @param bool $overwrite
     * @return null
     */
    public static function wpcf_access_get_areas($overwrite=false)
    {
        static $areas=null;

        if (is_null($areas) || $overwrite)
        {
            $areas = apply_filters('types-access-show-ui-area', array());
        }
        return $areas;
    }

    /**
     *
     */
	public static function admin_enqueue_scripts() {
		global $pagenow;
			if (
				$pagenow == 'admin.php'
				&& isset( $_GET['page'] )
				&& ($_GET['page'] == 'types_access')
			) {
				Access_Helper::wpcf_access_admin_menu_load();
			}
	}

    /**
     * @param $pages
     * @return array
     */
	public static function register_access_pages_in_menu( $pages ) {
		$pages[] = array(
			'slug'			=> 'types_access',
			'menu_title'	=> __('Access Control', 'wpcf-access'),
			'page_title'	=> __('Access Control', 'wpcf-access'),
			'callback'		=> array( 'Access_Helper', 'wpcf_access_admin_menu_page' )
		);
		return $pages;
	}

    /**
     *
     */
	public static function export_settings_template() {
        include TACCESS_TEMPLATES_PATH . '/export-settings.tpl.php';
    }

    /**
     *
     */
	public static function import_settings_template() {
        include TACCESS_TEMPLATES_PATH . '/import-settings.tpl.php';
    }

    /**
     * @param $sections
     * @return mixed
     */
	public static function register_export_import_section( $sections ) {
		$sections['access'] = array(
			'slug'		=> 'access',
			'title'		=> __( 'Access', 'wpcf-access' ),
			'icon'		=> '<i class="icon-access-logo ont-icon-16"></i>',
			'items'		=> array(
							'export'	=> array(
											'title'		=> __( 'Export Access Settings','wpcf-access' ),
											'callback'	=> array( 'Access_Helper', 'export_settings_template' ),
										),
							'import'	=> array(
											'title'		=> __( 'Import Access Settings','wpcf-access' ),
											'callback'	=> array( 'Access_Helper', 'import_settings_template' ),
										),
						),
		);
		return $sections;
	}

    /**
     * @param $current_page
     */
	public static function load_assets_in_shared_pages( $current_page ) {
		switch ( $current_page ) {
			case 'toolset-export-import':
				// @todo review whether those assets are needed at all
				Access_Helper::wpcf_access_admin_import_export_load();
				break;
		}
	}

    /**
     * Adds help on admin pages.
     *
     * @param type $contextual_help
     * @param type $screen_id
     * @param type $screen
     * @return type
     */
    public static function wpcf_access_admin_plugin_help( $hook, $page='' )
    {
        global $wp_version;
        $call = false;
        $contextual_help = '';
        //$contextual_help = wpcf_access_admin_help( $call, $contextual_help );
        // WP 3.3 changes
        if ( version_compare( $wp_version, '3.2.1', '>' ) )
        {
            //set_current_screen( $hook );
            $screen = get_current_screen();
            if ( !is_null( $screen ) && $screen->id==$hook)
            {
                $args = array(
                    'title' => __( 'Access', 'wpcf-access' ),
                    'id' => 'wpcf-access',
                    'content' => $contextual_help,
                    'callback' => false,
                );
                $screen->add_help_tab( $args );
                $args = array(
                    'title' => __( 'Access', 'wpcf-access' ),
                    'id' => 'wpcf-access',
                    'content' => $contextual_help,
                    'callback' => false,
                );
                $screen->add_help_tab( $args );
            }
        }
        else
        {
            add_contextual_help( $hook, $contextual_help );
        }
    }

    /**
     * Menu page load hook.
     */
    public static function wpcf_access_admin_menu_load()
    {
        TAccess_Loader::loadAsset('STYLE/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('SCRIPT/wpcf-access-dev', 'wpcf-access');
		TAccess_Loader::loadAsset('STYLE/wpcf-access-dialogs-css', 'wpcf-access-dialogs-css');
		TAccess_Loader::loadAsset('STYLE/notifications', 'notifications');

        $select2_version = '4.0.3';
       if ( !wp_script_is('toolset_select2', 'registered') ) {
            wp_register_script(
                'toolset_select2',
                TACCESS_PLUGIN_URL . '/toolset/toolset-common/res/lib/select2/select2.js',
                array( 'jquery' ),
                $select2_version
            ); wp_deregister_script('toolset-select2-compatibility');
       }

        if ( !wp_style_is('toolset-select2-css', 'registered') ) {
            wp_register_style(
                'toolset-select2-css',
                TACCESS_PLUGIN_URL . '/toolset/toolset-common/res/lib/select2/select2.css',
                array(),
                $select2_version
            );
        }
	    wp_deregister_style( 'toolset-select2-overrides-css' );
        wp_enqueue_script('toolset_select2');
        wp_enqueue_style('toolset-select2-css');

        add_thickbox();
    }

    /**
     *
     */
    public static function wpcf_access_admin_import_export_load()
    {
    	TAccess_Loader::loadAsset('SCRIPT/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('SCRIPT/wpcf-access-utils-dev', 'wpcf-access-utils');
		TAccess_Loader::loadAsset('STYLE/wpcf-access-dialogs-css', 'wpcf-access-dialogs-css');
		TAccess_Loader::loadAsset('STYLE/notifications', 'notifications');
		add_thickbox();
    }

    /**
     * Menu page render hook.
     */
    public static function wpcf_access_admin_menu_page()
    {
        if( !class_exists('Access_Admin_Edit') ){
            TAccess_Loader::load('CLASS/Admin_Edit');
        }
        echo "\r\n" . '<div class="wrap">
        <div id="icon-wpcf-access" class="icon32"><br /></div>
        <h1>' . __('Access Control', 'wpcf-access') . '</h1>' . "\r\n";
        Access_Admin_Edit::wpcf_access_admin_edit_access();
        echo "\r\n" . '</div>' . "\r\n";
    }

    /**
     *
     */
	public static function wpcf_access_import_on_form_submit() {
		if (
			current_user_can('manage_options')
			&& isset( $_FILES['access-import-file'] )
			&& isset( $_POST['access-import'] )
			&& isset( $_POST['access-import-form'] )
			&& wp_verify_nonce( $_POST['access-import-form'], 'access-import-form' )
		) {
			// @todo move this to wp_loaded and check current_user_can FGS!
            TAccess_Loader::load( 'CLASS/XML_Processor' );
            $options = array();
            if ( isset( $_POST['access-overwrite-existing-settings'] ) ) {
                $options['access-overwrite-existing-settings'] = 1;
            }
            if ( isset( $_POST['access-remove-not-included-settings'] ) ) {
                $options['access-remove-not-included-settings'] = 1;
            }
            self::$import_messages = Access_XML_Processor::importFromXML( $_FILES['access-import-file'], $options );
        }
	}

    /**
     *
     */
	public static function wpcf_access_import_notices_messages() {
		$import_messages = self::$import_messages;
		$display_messages = array();
		if ( ! is_null( $import_messages ) ) {
			if ( is_wp_error( $import_messages ) ) {
				$display_messages = array(
					'type'		=> 'error',
					'message'	=> '<p>' . $import_messages->get_error_message( $import_messages->get_error_code() ) . '</p>'
				);
			} elseif ( is_array( $import_messages ) ) {
				$display_messages = array(
					'type'		=> 'updated',
					'message'	=> '<h3>' . __( 'Access import summary :','wpcf-access' ) . '</h3>'
									. '<ul>'
									. '<li>' . __( 'Settings Imported :','wpcf-access' ) . $import_messages['new'] . '</li>'
									. '<li>' . __( 'Settings Overwritten :','wpcf-access' ) . $import_messages['updated'] . '</li>'
									. '<li>' . __( 'Settings Deleted :','wpcf-access' ) . $import_messages['deleted'] . '</li>'
									. '</ul>'
				);
			}
			if ( ! empty( $display_messages ) ) {
				?>
				<div class="message <?php echo $display_messages['type']; ?>"><p><?php echo $display_messages['message']; ?></p></div>
				<?php
			}
		}
	}

    /**
     * @param $action
     */
    public static function import_export_hook($action)
    {
        if (isset($_POST['access-export']) && wp_verify_nonce($_POST['access-export-form'], 'access-export-form'))
        {
            TAccess_Loader::load('CLASS/XML_Processor');
            Access_XML_Processor::exportToXML('all');
        }
    }

    /**
     * @return bool
     */
    public static function wpcf_access_is_wpcf_active()
    {
        if (defined('WPCF_VERSION') || defined('WPCF_RUNNING_EMBEDDED'))
            return true;
        return false;
    }

    /**
     * Parses submitted data.
     *
     * @param type $data
     * @return type
     */
    public static function wpcf_access_parse_permissions( $data, $caps, $custom = false, $saved_data = array() )
    {
        $permissions = array();
        // TODO Monitor this (fails sometimes as 3.5)
        if (empty($data['permissions']))
            return $permissions;

        foreach ($data['permissions'] as $cap => $data_cap)
        {
            $cap = sanitize_text_field($cap);
            $users = isset($saved_data['permissions'][$cap]['users']) ? $saved_data['permissions'][$cap]['users'] : array();
            // Check if submitted
            if (isset($data['permissions'][$cap]))
            {
                $permissions[$cap] = $data['permissions'][$cap];
            }
            else
            {
                $permissions[$cap] = $data_cap;
            }

            if (!isset($permissions[$cap]['roles']) || empty($permissions[$cap]['roles']))
            {
                $permissions[$cap] = array_merge($permissions[$cap], array('roles' => self::toolset_access_get_roles_by_role('administrator') ) );
            }

            // Make sure only pre-defined are used on ours, third-party rules
            // can have anything they want.
            if (!$custom && !isset($caps[$cap]))
            {
                unset($permissions[$cap]);
                continue;
            }

            // Add users
            if (!empty($users))
            {
                $permissions[$cap]['users'] = $users;
            }
        }
        return $permissions;
    }

	/**
     * Return array of types predefined caps
	 * @return array
	 */
	public static function wpcf_access_types_caps_predefined() {
        return self::$types_caps_predefined;
    }

    /**
     * Defines predefined capabilities.
     *
     * @return array
     */
    public static function wpcf_access_types_caps_predefined_array()
    {
        $modes = array(
            // posts
            'read' => array(
                'title' => __('Read', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'read'),
                'predefined' => 'read',
            ),
            'read_private' => array(
                'title' => __('Preview any', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'manage_options'),
                'predefined' => 'read_private',
            ),
            'edit_own' => array(
                'title' => __('Edit own', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            'delete_own' => array(
                'title' => __('Delete own', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_posts'),
                'predefined' => 'delete_own',
            ),
            'edit_any' => array(
                'title' => __('Edit any', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_others_posts'),
                'predefined' => 'edit_any',
            ),
            'delete_any' => array(
                'title' => __('Delete any', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_others_posts'),
                'predefined' => 'delete_any',
            ),
            'publish' => array(
                'title' => __('Publish', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'publish_posts'),
                'predefined' => 'publish',
            )
        );
        return $modes;
    }

    /**
     * @param $caps
     * @return bool
     */
    public static function wpcf_check_cap_conflict($caps)
    {
        $wp_default_caps=array(
            'activate_plugins',
            'add_users',
            'create_users',
            'delete_plugins',
            'delete_themes',
            'delete_users',
            'edit_dashboard',
            'edit_files',
            'edit_plugins',
            'edit_theme_options',
            'edit_themes',
            'edit_users',
            'export',
            'import',
            'install_plugins',
            'install_themes',
            'list_users',
            'manage_options',
            'promote_users',
            'remove_users',
            'switch_themes',
            'unfiltered_html',
            //'unfiltered_upload',
            'update_core',
            'update_plugins',
            'update_themes',
            //'upload_files'
        );

        $cap_conflict=array_intersect($wp_default_caps, (array)$caps);

        if (!empty($cap_conflict))
            return true;
        return false;
    }

    /**
     * @return array
     */
    public static function wpcf_get_types_caps_default()
    {
        return array(
            // posts
            'read' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'read')
            ),
            'edit_own' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'delete_own' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'edit_any' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'delete_any' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'publish' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ));
    }

    /**
     * @return array
     */
    public static function wpcf_get_taxs_caps_default()
    {
        return array(
            'manage_terms' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'edit_terms' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'delete_terms' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
            'assign_terms' => array(
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts')
            ),
        );
    }

	/**
     * Return types caps array
	 * @return array
	 */
	public static function wpcf_access_types_caps()	{
        return self::$types_caps;
	}

    /**
     * Defines capabilities.
     *
     * @return type
     */
    public static function wpcf_access_types_caps_array()
    {
        $caps = array(
            //
            // READ
            //
            'read_post' => array(
                'title' => __('Read post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'read'),
                'predefined' => 'read',
            ),
            'read_private_posts' => array(
                'title' => __('Read private posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            //
            // EDIT OWN
            //
            'create_post' => array(
                'title' => __('Create post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            'create_posts' => array(
                'title' => __('Create post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            'edit_post' => array(
                'title' => __('Edit post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            'edit_posts' => array(
                'title' => __('Edit post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',
            ),
            'edit_comment' => array(
                'title' => __('Moderate comments', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_own',//'edit_own_comments',
                'fallback' => array('edit_published_posts', 'edit_others_posts'),
            ),
            //
            // DELETE OWN
            //
            'delete_post' => array(
                'title' => __('Delete post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_posts'),
                'predefined' => 'delete_own',
            ),
            'delete_posts' => array(
                'title' => __('Delete post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_posts'),
                'predefined' => 'delete_own',
            ),
            'delete_private_posts' => array(
                'title' => __('Delete private posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_private_posts'),
                'predefined' => 'delete_own',
            ),
            //
            // EDIT ANY
            //
            'edit_others_posts' => array(
                'title' => __('Edit others posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_others_posts'),
                'predefined' => 'edit_any',
                'fallback' => array( 'moderate_comments' ),
            ),
            'edit_published_posts' => array(
                'title' => __('Edit published posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_published_posts'),
                'predefined' => 'publish'
            ),
            'edit_private_posts' => array(
                'title' => __('Edit private posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_private_posts'),
                'predefined' => 'edit_any',
            ),
            'moderate_comments' => array(
                'title' => __('Moderate comments', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit_any_comments',
                'fallback' => array('edit_others_posts', 'moderate_comments'),
            ),
            //
            // DELETE ANY
            //
            'delete_others_posts' => array(
                'title' => __('Delete others posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_others_posts'),
                'predefined' => 'delete_any',
            ),
            'delete_published_posts' => array(
                'title' => __('Delete published posts', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'delete_published_posts'),
                'predefined' => 'publish',
            ),
            //
            // PUBLISH
            //
            'publish_posts' => array(
                'title' => __('Publish post', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'publish_posts'),
                'predefined' => 'publish',
            ),
        );
        return apply_filters('wpcf_access_types_caps', $caps);
    }

    /**
     * @return array
     */
    public static function wpcf_get_default_roles()
    {
        return array('administrator', 'editor', 'author', 'contributor', 'subscriber');
    }

    /**
     * @param $tax
     * @param $taxdata
     * @param $post_caps
     * @return array
     */
    public static function wpcf_types_to_tax_caps($tax, $taxdata, $post_caps)
    {
        $tax_caps_map = self::wpcf_access_tax_caps();
        $tax_caps = array();

        $tax_map_cap = isset($taxdata['cap']) ? $taxdata['cap'] : array();

        if (!isset($post_caps['permissions']))
            return $tax_caps;

        foreach ($tax_caps_map as $tcap => $mdata)
        {
        	$match_var = array_keys($mdata['match']);
            $match = array_shift($match_var);
            $replace = $mdata['match'][$match];
            $tax_cap = $tcap ; //isset($tax_map_cap[$tcap]) ? $tax_map_cap[$tcap] : $match.$tax_plural;
            foreach ($post_caps['permissions'] as $cap=>$data)
            {
                if (0===strpos($cap, $replace['match_access']))
                {
                    // copy roles and users from post type caps to associated tax caps
                    // follow , ;)
                    $tax_caps[$tax_cap]=$data;
                    break;
                }
            }
            // use a default here
            if (!isset($tax_caps[$tax_cap]))
            {
                $tax_caps[$tax_cap]=array('roles'=> self::toolset_access_get_roles_by_role('administrator') );
            }
        }
        return $tax_caps;
    }

	/**
     * Return arry of Tax caps
	 * @return array
	 */
	public static function wpcf_access_tax_caps() {
        return self::$tax_caps;
    }

    /**
     * Defines capabilities.
     *
     * @return type
     */
    public static function wpcf_access_tax_caps_array()
    {
        $caps = array(
            'manage_terms' => array(
                'title' => __('Manage terms', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'manage_categories'),
                'predefined' => 'manage',
                'match' => array(
                    'manage_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'edit_terms' => array(
                'title' => __('Edit terms', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'manage_categories'),
                'predefined' => 'edit',
                'match' => array(
                    'edit_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'delete_terms' => array(
                'title' => __('Delete terms', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'manage_categories'),
                'predefined' => 'edit',
                'match' => array(
                    'delete_' => array(
                        'match_access' => 'edit_any',
                        'match' => 'edit_others_',
                        'default' => 'manage_categories',
                    ),
                ),
                'default' => 'manage_categories',
            ),
            'assign_terms' => array(
                'title' => __('Assign terms', 'wpcf-access'),
                'roles' => self::toolset_access_get_roles_by_role('', 'edit_posts'),
                'predefined' => 'edit',
                'match' => array(
                    'assign_' => array(
                        'match_access' => 'edit_',
                        'match' => 'edit_',
                        'default' => 'edit_posts',
                    ),
                ),
                'default' => 'edit_posts',
            ),
        );
        return apply_filters('wpcf_access_tax_caps', $caps);
    }

    /**
     * Maps role to level.
     *
     * Returns an array of roles => levels
     * As this is used a lot of times, we added caching
     *
     * @return array $map
     */
    public static function wpcf_access_role_to_level_map()
    {
        $access_cache_map_group = 'access_cache_map_group';
		$access_cache_map_key = md5( 'access::role_to_level_map' );
		$map = Access_Cacher::get( $access_cache_map_key, $access_cache_map_group );
		if ( false === $map ) {

			$default_roles=self::wpcf_get_default_roles();

			$map = array(
				'administrator' => 'level_10',
				'editor' => 'level_7',
				'author' => 'level_2',
				'contributor' => 'level_1',
				'subscriber' => 'level_0',
			);
			require_once ABSPATH . '/wp-admin/includes/user.php';
			$roles = self::wpcf_get_editable_roles();
			foreach ($roles as $role => $data)
			{
				$role_data = get_role($role);
				if (!empty($role_data))
				{
					for ($index = 10; $index > -1; $index--)
					{
						if (isset($data['capabilities']['level_' . $index]))
						{
							$map[$role] = 'level_' . $index;
							break;
						}
					}
					// try to deduce the required level
					if (!isset($map[$role]))
					{
						foreach ($default_roles as $r)
						{
							if ($role_data->has_cap($r))
							{
								$map[$role] = $map[$r];
								break;
							}
						}
					}
					// finally use a default here, level_0, subscriber
					if (!isset($map[$role]))
						$map[$role] = 'level_0';
				}
			}
			Access_Cacher::set( $access_cache_map_key, $map, $access_cache_map_group );
		}
        return $map;
    }

    /**
     * Maps role to level.
     *
     * @param type $role
     * @return type
     */
    public static function wpcf_access_role_to_level($role)
    {
        $map = self::wpcf_access_role_to_level_map();
        return isset($map[$role]) ? $map[$role] : false;
    }

    /**
     * Checks if role is ranked higher.
     * @deprecated
     * @param type $role
     * @param type $compare
     * @return boolean
     */
    public static function wpcf_access_is_role_ranked_higher($role, $compare)
    {
        if ($role == $compare)
            return true;
        $level_role = self::wpcf_access_role_to_level($role);
        $level_compare = self::wpcf_access_role_to_level($compare);
        return self::wpcf_access_is_level_ranked_higher($level_role, $level_compare);
    }

    /**
     * Checks if level is ranked higher.
     *
     * @param type $level
     * @param type $compare
     * @return boolean
     */
    public static function wpcf_access_is_level_ranked_higher($level, $compare)
    {
        if ($level == $compare) {
            return true;
        }
        $level = strpos($level, 'level_') === 0 ? substr($level, 6) : $level;
        $compare = strpos($compare, 'level_') === 0 ? substr($compare, 6) : $compare;
        return intval($level) > intval($compare);
    }

    /**
     * Orders roles by level.
     *
     * @param type $roles
     * @return type
     */
    public static function wpcf_access_order_roles_by_level($roles)
    {
        $ordered_roles = array();
        for ($index = 10; $index > -1; $index--) {
            foreach ($roles as $role => $data) {
                if (isset($data['capabilities']['level_' . $index])) {
                    $ordered_roles[$index][$role] = $data;
                    unset($roles[$role]);
                }
            }
        }
        $ordered_roles['not_set'] = !empty($roles) ? $roles : array();
        return $ordered_roles;
    }

    /**
     * Gets all caps by level.
     *
     * Loops over all collected rules and sees each one matches current user.
     *
     * @global type $wpcf_access
     * @param type $level
     * @param type $context
     * @return type
     */
    public static function wpcf_access_user_get_caps_by_type($user_id, $context = 'types')
    {
        global $wpcf_access;
        static $cache = array();
        if (isset($cache[$user_id][$context])) {
            return $cache[$user_id][$context];
        }
        $role = self::wpcf_get_current_logged_user_role();
        if ( empty($role) || empty($wpcf_access->settings->{$context})) {
            return array();
        }
        $caps = array();
        foreach ($wpcf_access->settings->{$context} as $type => $data) {
            if (empty($data['mode']) || 'not_managed'==$data['mode']) continue;
            if (!empty($data['permissions']) && is_array($data['permissions'])) {
                foreach ($data['permissions'] as $_cap => $_data) {
                    if ( isset($_data['roles']) ) {
                        if ( !is_array($_data['roles']) ){
                            $_data['roles'] = array($_data['roles']);
                        }
                        $can = false;
                        if ( in_array( $role, $_data['roles']) !== FALSE ){
                            $can = true;
                        }
                        $cap_data['context'] = $context;
                        $cap_data['parent'] = $type;
                        $cap_data['caps'][$_cap] = (bool) $can;
                        $caps[$type] = $cap_data;
                    }
                }
            }
        }
        $cache[$user_id][$context] = $caps;
        return $caps;
    }

    /**
     * Determines highest ranked role and it's level.
     *
     * @param type $user_id
     * @param type $rank
     * @return type
     */
    public static function wpcf_access_rank_user($user_id, $rank = 'high')
    {
        global $wpcf_access;
        static $cache = array();
        $user = get_userdata($user_id);
        if (empty($user)) {
            $wpcf_access->user_rank['not_found'][$user_id] = array('guest', false);
            return array('guest', false);
        }
        if (!empty($cache[$user_id])) {
            return $cache[$user_id];
        }
		$roles = self::wpcf_get_editable_roles();
        $levels = self::wpcf_access_order_roles_by_level($roles);
        $role = false;
        $level = false;
        foreach ($levels as $_levels => $_level) {
            $current_level = $_levels;
            foreach ($_level as $_role => $_role_data) {
                if (in_array($_role, $user->roles)) {
                    $role = $_role;
                    $level = $current_level;
                    if ($rank != 'low') {
                        $cache[$user_id] = array($role, $level);
                        $wpcf_access->user_rank[$user_id] = $cache[$user_id];
                        return $cache[$user_id];
                    }
                }
            }
        }
        if (!$role || !$level) {
            return array('guest', false);
        }
        $cache[$user_id] = array($role, $level);

        $wpcf_access->user_rank[$user_id] = $cache[$user_id];

        return array($role, $level);
    }

    /**
     * Search for cap in collected rules.
     *
     * @global type $wpcf_access
     * @param type $cap
     * @return type
     */
    public static function wpcf_access_search_cap($cap)
    {
        global $wpcf_access;
        $settings = array();
        if (isset($wpcf_access->rules->types[$cap])) {
            $settings = $wpcf_access->rules->types[$cap];
            $settings['_context'] = 'types';
        } else if (isset($wpcf_access->rules->tax[$cap])) {
            $settings = $wpcf_access->rules->tax[$cap];
            $settings['_context'] = 'tax';
        }
        return empty($settings) ? false : $settings;
    }

	public static function otg_access_reload_access_roles() {
		self::$roles = null;
		self::$roles = self::wpcf_get_editable_roles();
	}

    /**
     * Track fetching editable roles.
     *
     * Sometimes WP includes get_editable_role func too late.
     * Especially if user is not logged.
     *
     * @global type $wpcf_access
     * @return type
     */
    public static function wpcf_get_editable_roles()
    {
        if ( !is_null( self::$roles ) ) {
            return self::$roles;
        }
        global $wpcf_access;
        if (!function_exists('get_editable_roles')) {
            if ( !function_exists('wp_roles') ){
                include_once ABSPATH . '/wp-admin/includes/user.php';
            }else{
                $wp_roles = wp_roles();
                return $wp_roles->roles;
            }

        }
        if (!function_exists('get_editable_roles')) {
            $wpcf_access->errors['get_editable_roles-missing_func'] = debug_backtrace();
            return array();
        }
        return get_editable_roles();
    }

    /*
    *   Auxilliary function
    */
    public static function wpcf_object_to_array($data, $depth = 1)
    {
        if ( $depth > 4 ) {
            return array();
        }

        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::wpcf_object_to_array($value, $depth + 1);
            }
            return $result;
        }
        return $data;
    }

    /**
     * Check if object (post type or taxonomy) is valid to be managed by Access
     *
     * @return bool
     */
    public static function wpcf_is_object_valid( $type, $data )
    {
        global $wpcf_access;

        if (!in_array($type,array('type','taxonomy')))
            return false;

        $data=self::wpcf_object_to_array($data);

        // valid for builtin types/taxes as they have predefined caps regardless of locale and labels
        if (isset($data['_builtin']) && $data['_builtin'])
            return true;

        $whitelist=array(
            'type'=>array('Media'),
            'taxonomy'=>array()
            );

        // no label, bypass
        if (!isset($data) || empty($data) || !isset($data['labels'])) {
            return false;
        } else {
            // same plural and singular names, bypass, else problems (NOTE the actual label to test is menu_name, which by default is equal to (plural) name)
            $singular=$data['labels']['singular_name'];
            $plural=(isset($data['labels']['menu_name'])&&$data['labels']['name']!=$data['labels']['menu_name'])?$data['labels']['menu_name']:$data['labels']['name'];

            if ($plural==$singular && !in_array($data['labels']['name'],$whitelist[$type])) {
               // return false;
            }
        }
        return true;
    }

    /**
     * Get extra debug information.
     *
     * Get extra debug information for debug page.
     *
     * @param array debug information table
     *
     * @return array debug information table
     */
    public static function add_access_extra_debug_information($extra_debug)
    {
        global $wpcf_access;
        $clone = clone $wpcf_access;
        $extra_debug['access'] = array();
        foreach( array('rules', 'debug', 'settings', 'errors') as $key ) {
            $extra_debug['access'][$key] = (array)$clone->$key;
        }
        unset($clone);
        return $extra_debug;
    }

    /**
     * @param $text
     * @return mixed
     */
    public static function wpcf_esc_like( $text ) {
        global $wpdb;
        if ( method_exists( $wpdb, 'esc_like' ) ) {
             return $wpdb->esc_like( $text );
        } else {
             return like_escape( esc_sql( $text ) );
        }
    }

    /**
     * @return mixed
     */
	public static function otg_access_get_post_types_with_custom_groups() {

		global $wpdb;
		$values_to_prepare = array();
		$values_to_prepare[] = '_wpcf_access_group';
		$values_to_prepare[] = 'publish';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT posts.post_type
				FROM {$wpdb->posts} AS posts
				LEFT JOIN {$wpdb->postmeta} AS meta
				ON (
					posts.ID = meta.post_id
					AND meta.meta_key = %s
				)
				WHERE posts.post_status = %s
				AND meta.meta_value IS NOT NULL",
				$values_to_prepare
			)
		);

		return $results;
	}

    /**
     * @param $allcaps
     * @param $cap
     * @param bool $add
     * @param null $user
     * @return mixed
     */
	public static function otg_access_add_or_remove_cap( $allcaps, $cap, $add = true, $user = null ) {
		global $current_user;
		if ( is_null( $user ) ) {
			//return $allcaps;
		}
		if ( $add ) {
			$allcaps[ $cap ] = 1;
			if ( isset($user->allcaps) ){
			    $user->allcaps[ $cap ] = true;
            }
			if ( isset($user->ID) && $user->ID == $current_user->ID ){
                $current_user->allcaps[ $cap ] = true;
            }

		} else {
			unset( $allcaps[ $cap ] );
            if ( isset($user->allcaps[ $cap ]) ){
			    unset( $user->allcaps[ $cap ] );
            }
			if ( isset($user->ID) && $user->ID == $current_user->ID ){
                unset( $current_user->allcaps[ $cap ] );
            }
		}
		return $allcaps;
	}

    /*
     * @since 2.2
     * Add new access role to access settings
     * $role - role slug
     * $copy_of_role - copy permissions from that role
     * If $copy_of_role is empty, create permissions depend of role capabilities
     */
	public static function toolset_access_add_role_to_settings( $role, $copy_of_role = '' ){
	    global $wpcf_access;
	    if ( empty($role) ){
	        return;
        }

        if ( !empty($copy_of_role) ){

            $new_settings = $wpcf_access->settings;

            foreach( $new_settings->types as $group => $group_permissions){
                $group_permissions = self::toolset_access_permissions_array_add_role( $group_permissions, $role, $copy_of_role );
                $new_settings->types[$group] = $group_permissions;
            }

            //Parse taxonomies
            foreach( $new_settings->tax as $group => $group_permissions){
                $group_permissions = self::toolset_access_permissions_array_add_role( $group_permissions, $role, $copy_of_role );
                $new_settings->tax[$group] = $group_permissions;
            }

            //Parse third party
            foreach( $new_settings->third_party as $group => $group_permissions){
                foreach( $group_permissions as $sub_group => $sub_group_permissions){
                    $sub_group_permissions = self::toolset_access_permissions_array_add_role( $sub_group_permissions, $role, $copy_of_role );
                    $new_settings->third_party[$group][$sub_group] = $sub_group_permissions;
                }

            }

            $wpcf_access->settings = $new_settings;
            $model = TAccess_Loader::get('MODEL/Access');
            $model->updateAccessSettings($new_settings);

        }else{
            /* TODO Gen: every page load check if new roles were added
             If yes, scan capabilites and add to access settings
             -manage_categories = Add/edit taxonomies
             -edit_posts - add posts and cpt (_pages)
             -delete_posts (_pages)
             -edit_others_posts (_pages)
             -delete_others_posts (_pages)
             - publish_posts, publish_pages
             WPML, posts groups, Types groups, Cred forms will set to subscriber before will be changed by admin
            */
        }


    }

    /*
     * @since 2.2
     * Add $role to permissions and default permissions array
     */
    public static function toolset_access_permissions_array_add_role( $group_permissions, $role, $copy_of_role ){

        if ( isset($group_permissions['permissions']) ){
            foreach ( $group_permissions['permissions'] as $permission_name => $permissions ){
                if( isset( $permissions['roles'] ) && is_array( $permissions['roles'] ) && in_array( $copy_of_role, $permissions['roles'] ) !== FALSE ){
                    $permissions['roles'][] = $role;
                }
                $group_permissions['permissions'][$permission_name] = $permissions;
            }

        }
        return $group_permissions;
    }

}
