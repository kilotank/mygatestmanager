<?php

/**
 * Uses custom posts and fields to store form data
 */
class CRED_User_Forms_Model extends CRED_Abstract_Model {

    public function __construct() {
        parent::__construct();

        $this->post_type_name = CRED_USER_FORMS_CUSTOM_POST_NAME;
    }

	public function register_form_type() {
		$args = array(
			'labels' => array(
				'name' => __( 'User Forms', 'wp-cred' ),
				'singular_name' => __( 'User Form', 'wp-cred' ),
				'add_new' => __( 'Add New', 'wp-cred' ),
				'add_new_item' => __( 'Add New User Form', 'wp-cred' ),
				'edit_item' => __( 'Edit User Form', 'wp-cred' ),
				'new_item' => __( 'New User Form', 'wp-cred' ),
				'view_item' => __( 'View User Form', 'wp-cred' ),
				'search_items' => __( 'Search User Forms', 'wp-cred' ),
				'not_found' => __( 'No user forms found', 'wp-cred' ),
				'not_found_in_trash' => __( 'No user form found in Trash', 'wp-cred' ),
				'parent_item_colon' => '',
				'menu_name' => 'CRED User Forms',
			),
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => false,
			'rewrite' => false,
			'can_export' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => 80,
			'supports' => array( 'title', /*'author'*/ ),
		);
		register_post_type( $this->post_type_name, $args );

		add_filter( 'user_can_richedit', array( &$this, 'disable_richedit_for_cred_forms' ) );
	}

	/**
	 * @return array|bool
	 */
    public function getDefaultMessages() {
        static $messages = false;

        if (!$messages) {
            $messages = array(
                'cred_message_post_saved' => 'User ' . __('Saved', 'wp-cred'),
                'cred_message_post_not_saved_singular' => __('The user was not saved because of the following problem:', 'wp-cred'),
                'cred_message_post_not_saved_plural' => __('The user was not saved because of the following %NN problems:', 'wp-cred'),
                'cred_message_invalid_form_submission' => 'Invalid User Form Submission (nonce failure)',
                'cred_message_no_data_submitted' => 'Invalid User Form Submission (maybe a file has a size greater than allowed)',
                'cred_message_upload_failed' => 'Upload Failed',
                'cred_message_field_required' => 'This field is required',
	            'cred_message_invalid_username' => 'The username can only contain alphanumeric characters, dashes or underscores',
	            'cred_message_passwords_do_not_match' => 'Passwords do not match',
                'cred_message_enter_valid_date' => 'Please enter a valid date',
                'cred_message_values_do_not_match' => 'Field values do not match',
                'cred_message_enter_valid_email' => 'Please enter a valid email address',
                'cred_message_enter_valid_colorpicker' => 'Please use a valid hexadecimal value',
                'cred_message_enter_valid_number' => 'Please enter numeric data',
                'cred_message_enter_valid_url' => 'Please enter a valid URL address',
	            'cred_message_email_already_exists' => 'Sorry, that email address is already used!',
	            'cred_message_username_already_exists' => 'Sorry, that username already exists!',
	            'cred_message_invalid_edit_user_role' => 'This form can not edit users with a role of %%EDITED_USER_ROLE%%',
                'cred_message_enter_valid_captcha' => 'Wrong CAPTCHA',
                'cred_message_missing_captcha' => 'Missing CAPTCHA',
                'cred_message_show_captcha' => 'Show CAPTCHA',
                'cred_message_edit_skype_button' => 'Edit Skype Button',
                'cred_message_not_valid_image' => 'Not Valid Image',
                'cred_message_file_type_not_allowed' => 'File type not allowed',
                'cred_message_image_width_larger' => 'Image width larger than %dpx',
                'cred_message_image_height_larger' => 'Image height larger than %dpx',
                'cred_message_show_popular' => 'Show Popular',
                'cred_message_hide_popular' => 'Hide Popular',
                'cred_message_add_taxonomy' => 'Add',
                'cred_message_remove_taxonomy' => 'Remove',
                'cred_message_add_new_taxonomy' => 'Add New',
            );
        }

        return $messages;
    }

	/**
	 * @return array|bool
	 */
    public function getDefaultMessageDescriptions() {
        static $desc = false;

        if (!$desc) {
	        $desc = array(
		        'cred_message_post_saved' => __( 'User saved Message', 'wp-cred' ),
		        'cred_message_post_not_saved_singular' => __( 'User not saved message (one problem)', 'wp-cred' ),
		        'cred_message_post_not_saved_plural' => __( 'User not saved message (several problems)', 'wp-cred' ),
		        'cred_message_invalid_form_submission' => __( 'Invalid submission message', 'wp-cred' ),
		        'cred_message_no_data_submitted' => __( 'Invalid Form Submission (maybe a file has a size greater than allowed)', 'wp-cred' ),
		        'cred_message_upload_failed' => __( 'Upload failed message', 'wp-cred' ),
		        'cred_message_field_required' => __( 'Required field message', 'wp-cred' ),
		        'cred_message_invalid_username' => __( 'Invalid username message', 'wp-cred' ),
		        'cred_message_passwords_do_not_match' => __('Passwords do not match', 'wp-cred'),
		        'cred_message_enter_valid_date' => __( 'Invalid date message', 'wp-cred' ),
		        'cred_message_values_do_not_match' => __( 'Invalid hidden field value message', 'wp-cred' ),
		        'cred_message_enter_valid_email' => __( 'Invalid email message', 'wp-cred' ),
		        'cred_message_enter_valid_colorpicker' => __( 'Invalid color picker message', 'wp-cred' ),
		        'cred_message_enter_valid_number' => __( 'Invalid numeric field message', 'wp-cred' ),
		        'cred_message_enter_valid_url' => __( 'Invalid URL message', 'wp-cred' ),
		        'cred_message_email_already_exists' => __( 'Email already exists message', 'wp-cred' ),
		        'cred_message_username_already_exists' => __( 'Username already exists message', 'wp-cred' ),
		        'cred_message_invalid_edit_user_role' => __( 'Invalid Editing User Role', 'wp-cred' ),
		        'cred_message_enter_valid_captcha' => __( 'Invalid captcha message', 'wp-cred' ),
		        'cred_message_missing_captcha' => __( 'Missing captcha message', 'wp-cred' ),
		        'cred_message_show_captcha' => __( 'Show captcha button', 'wp-cred' ),
		        'cred_message_edit_skype_button' => __( 'Edit skype button', 'wp-cred' ),
		        'cred_message_not_valid_image' => __( 'Invalid image message', 'wp-cred' ),
		        'cred_message_file_type_not_allowed' => __( 'Invalid file type message', 'wp-cred' ),
		        'cred_message_image_width_larger' => __( 'Invalid image width message', 'wp-cred' ),
		        'cred_message_image_height_larger' => __( 'Invalid image height message', 'wp-cred' ),
		        'cred_message_show_popular' => __( 'Taxonomy show popular message', 'wp-cred' ),
		        'cred_message_hide_popular' => __( 'Taxonomy hide popular message', 'wp-cred' ),
		        'cred_message_add_taxonomy' => __( 'Add taxonomy term', 'wp-cred' ),
		        'cred_message_remove_taxonomy' => __( 'Remove taxonomy term', 'wp-cred' ),
		        'cred_message_add_new_taxonomy' => __( 'Add new taxonomy message', 'wp-cred' ),
	        );
        }

        return $desc;
    }

	/**
	 * @param string $src
	 * @param string $limit
	 *
	 * @return array
	 */
    public function getUsers($src = "", $limit = "") {
        $args = array(
            'orderby' => 'nicename'
        );
        if (!empty($src)) {
	        $text = cred_wrap_esc_sql( cred_wrap_esc_like( $src ) );
            $args['search'] = '*'.$text.'*';
        }
        if (!empty($limit)) {
            $args['number'] = $limit;
        }
        return get_users($args);
    }

	/**
	 * @param array $fields
	 *
	 * @return array|mixed|object
	 */
    public function changeFormat($fields) {
        // change format here
	    if ( isset( $fields['form_settings'] ) ) {
		    $form_settings = $fields['form_settings'];
		    if ( ! isset( $form_settings->form ) ) {
			    if ( isset( $form_settings->message ) ) {
				    if ( is_string( $form_settings->message ) ) {
					    $_message = $form_settings->message;
				    } else {
					    $_message = '';
				    }
			    } else {
				    $_message = '';
			    }

			    $setts = new stdClass;
			    $setts->form = array(
				    'type' => isset( $form_settings->form_type ) ? $form_settings->form_type : '',
				    'action' => isset( $form_settings->form_action ) ? $form_settings->form_action : '',
				    'action_page' => isset( $form_settings->form_action_page ) ? $form_settings->form_action_page : '',
				    'action_message' => $_message,
				    'user_role' => isset( $form_settings->user_role ) ? $form_settings->user_role : '',
				    'redirect_delay' => isset( $form_settings->redirect_delay ) ? $form_settings->redirect_delay : 0,
				    'hide_comments' => isset( $form_settings->hide_comments ) ? $form_settings->hide_comments : 0,
				    'theme' => isset( $form_settings->cred_theme_css ) ? $form_settings->cred_theme_css : 'minimal',
				    'has_media_button' => isset( $form_settings->has_media_button ) ? $form_settings->has_media_button : 0,
				    'has_toolset_buttons' => isset( $form_settings->has_toolset_buttons ) ? $form_settings->has_toolset_buttons : 0,
				    'autogenerate_username_scaffold' => isset( $form_settings->autogenerate_username_scaffold ) ? $form_settings->autogenerate_username_scaffold : 0,
				    'autogenerate_nickname_scaffold' => isset( $form_settings->autogenerate_nickname_scaffold ) ? $form_settings->autogenerate_nickname_scaffold : 0,
				    'autogenerate_password_scaffold' => isset( $form_settings->autogenerate_password_scaffold ) ? $form_settings->autogenerate_password_scaffold : 0,
				    'include_wpml_scaffold' => isset( $form_settings->include_wpml_scaffold ) ? $form_settings->include_wpml_scaffold : 0,
				    'include_captcha_scaffold' => isset( $form_settings->include_captcha_scaffold ) ? $form_settings->include_captcha_scaffold : 0,
			    );
			    $setts->post = array(
				    'post_type' => isset( $form_settings->post_type ) ? $form_settings->post_type : '',
				    'post_status' => isset( $form_settings->post_status ) ? $form_settings->post_status : '',
			    );
			    unset( $fields['form_settings'] );
			    $fields['form_settings'] = $setts;
		    }
	    }

        if (isset($fields['extra'])) {
            // reformat messages
            if (isset($fields['extra']->messages)) {
                foreach ($fields['extra']->messages as $mid => $msg) {
                    if (is_array($msg) && isset($msg['msg'])) {
                        $fields['extra']->messages[$mid] = $msg['msg'];
                    }
                }
            }
        }

        if (isset($fields['notification'])) {
            $nt = (object) $fields['notification'];
            $notts = new stdClass;
            $notts->enable = isset($nt->enable) ? $nt->enable : 0;
            $notts->notifications = isset($nt->notifications) ? $nt->notifications : array();
            foreach ($notts->notifications as $ii => $n) {
                if (isset($n['mail_to_type'])) {
                    $_type = isset($n['mail_to_type']) ? $n['mail_to_type'] : '';
                    $notts->notifications[$ii] = array(
                        'event' => array(
                            'type' => 'form_submit',
                            'post_status' => '',
                            'condition' => array(),
                            'any_all' => ''
                        ),
                        'to' => array(
                            'type' => array(
                                $_type
                            ),
                            'wp_user' => array(
                                'to_type' => 'to',
                                'user' => isset($n['mail_to_user']) ? $n['mail_to_user'] : ''
                            ),
                            'mail_field' => array(
                                'to_type' => 'to',
                                'address_field' => isset($n['mail_to_field']) ? $n['mail_to_field'] : '',
                                'name_field' => '',
                                'lastname_field' => ''
                            ),
                            'user_id_field' => array(
                                'to_type' => 'to',
                                'field_name' => isset($n['mail_to_user_id_field']) ? $n['mail_to_user_id_field'] : ''
                            ),
                            'specific_mail' => array(
                                'address' => isset($n['mail_to_specific']) ? $n['mail_to_specific'] : '',
                            )
                        ),
                        'from' => array(
                            'address' => isset($n['from_addr']) ? $n['from_addr'] : '',
                            'name' => isset($n['from_name']) ? $n['from_name'] : ''
                        ),
                        'mail' => array(
                            'subject' => isset($n['subject']) ? $n['subject'] : '',
                            'body' => isset($n['body']) ? $n['body'] : ''
                        )
                    );
                }

                // apply some defaults
                $notts->notifications[$ii] = $this->merge(array(
                    'event' => array(
                        'type' => 'form_submit',
                        'post_status' => 'publish',
                        'condition' => array(
                        ),
                        'any_all' => 'ALL'
                    ),
                    'to' => array(
                        'type' => array(),
                        'wp_user' => array(
                            'to_type' => 'to',
                            'user' => ''
                        ),
                        'mail_field' => array(
                            'to_type' => 'to',
                            'address_field' => '',
                            'name_field' => '',
                            'lastname_field' => ''
                        ),
                        'user_id_field' => array(
                            'to_type' => 'to',
                            'field_name' => ''
                        ),
                        'specific_mail' => array(
                            'address' => ''
                        )
                    ),
                    'from' => array(
                        'address' => '',
                        'name' => ''
                    ),
                    'mail' => array(
                        'subject' => '',
                        'body' => ''
                    )
                        ), $notts->notifications[$ii]);
            }
            unset($fields['notification']);
            $fields['notification'] = $notts;
        }

        // provide some defaults
        $fields = $this->merge(array(
            'form_settings' => (object) array(
                'post' => array(
                    'post_type' => '',
                    'post_status' => ''
                ),
                'form' => array(
                    'type' => '',
                    'action' => '',
                    'action_page' => '',
                    'action_message' => '',
                    'user_role' => '',
                    'redirect_delay' => 0,
                    'hide_comments' => 0,
                    'theme' => 'minimal',
                    'has_media_button' => 0,
                    'has_toolset_buttons' => 0,
                    'autogenerate_username_scaffold' => 1,
                    'autogenerate_nickname_scaffold' => 1,
                    'autogenerate_password_scaffold' => 1,
                    'include_wpml_scaffold' => 0,
                    'include_captcha_scaffold' => 0
                )
            ),
            'extra' => (object) array(
                'css' => '',
                'js' => '',
                'messages' => $this->getDefaultMessages()
            ),
            'notification' => (object) array(
                'enable' => 0,
                'notifications' => array()
            )
                ), $fields);

        return $fields;
    }

//=================== GENERAL (CUSTOM) POST HANDLING METHODS ====================================================

	/**
	 * Function that retrieves user_meta
	 *
	 * @param $user_id
	 * @param $meta
	 * @param $single
	 *
	 * @return mixed
	 */
	public function getUserMeta( $user_id, $meta, $single = true ) {
		return get_user_meta( $user_id, $meta, $single );
	}

	/**
	 * Generic method used to get fields related to the user_id context
	 *
	 * @param string|array $object_field
	 * @param array $include_fields_only    array used in getUserFields to get the only fields wanted
	 *
	 * @return array
	 */
	public function get_object_fields( $object_field, $include_fields_only = null ) {
		$user_id = $this->try_get_user_id_by_cred_commerce_object_field( $object_field );

		return $this->getUserFields( $user_id, $include_fields_only );
	}

	/**
	 * If user is draft one and comes from cred commerce it is array that contains user_id other then other cred commerce info
	 *
	 * @param string|array $object_field
	 *
	 * @return int|string
	 *
	 * @since 1.9.3
	 */
	protected function try_get_user_id_by_cred_commerce_object_field( $object_field ) {
		return ( is_array( $object_field ) ) ? $object_field['user_id'] : $object_field;
	}

	/**
	 * @param $user_id
	 * @param array|null $include_only_fields  array of the only wanted fields
	 *
	 * @return array    array of fields
	 */
	public function getUserFields( $user_id, $include_only_fields = null ) {
		$fields = CRED_Loader::get( 'MODEL/UserFields' )->getCustomFields();
		if ( isset( CRED_StaticClass::$out['generic_fields'] )
			&& ! empty( CRED_StaticClass::$out['generic_fields'] ) ) {
			$fields = array_merge( CRED_StaticClass::$out['generic_fields'], $fields );
		}

		foreach ( $fields as $field_slug => $field ) {
			if ( is_array( $field ) ) {
				foreach ( $field as $index => $value ) {
					$fields[ $field_slug ][ $index ] = maybe_unserialize( maybe_unserialize( $value ) );
				}
			} else {
				$fields[ $field_slug ] = maybe_unserialize( $field );
			}
		}

		$intersected = array();
		if ( isset( $include_only_fields )
			&& ! empty( $include_only_fields ) ) {
			foreach ( $fields as $field_slug => $field ) {
				if ( isset( $field['plugin_type_prefix'] ) ) {
					$field_slug = $field['plugin_type_prefix'] . $field_slug;
				}
				if ( in_array( $field_slug, $include_only_fields ) ) {
					$intersected[ $field_slug ] = $field;
				}
			}
		}
		unset( $fields );

		$user = new WP_User( $user_id );
		$values = (array) $user->data;
		$values['first_name'] = get_user_meta( $user_id, 'first_name', true );
		$values['last_name'] = get_user_meta( $user_id, 'last_name', true );
		$values['nickname'] = get_user_meta( $user_id, 'nickname', true );

		$new_array = array();
		if ( ! empty( $intersected ) ) {
			foreach ( $intersected as $field_slug => $field ) {
				if ( isset( $values[ $field_slug ] ) ) {
					$new_array[ $field_slug ] = $values[ $field_slug ];
				} else {
					$values[ $field_slug ] = get_user_meta( $user_id, $field_slug, true );
					if ( isset( $values[ $field_slug ] ) ) {
						$new_array[ $field_slug ] = $values[ $field_slug ];
					}
				}
			}
		}
		unset( $fields );
		unset( $user );

		return $new_array;
	}

	/**
	 * @param int $user_id
	 * @param string $data
	 *
	 * @return bool|int
	 */
    public function setAttachedData($user_id, $data) {
        return update_user_meta(intval($user_id), '__cred_user_notification_data', $data); // serialize
    }

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
    public function removeAttachedData($user_id) {
        return delete_user_meta(intval($user_id), '__cred_user_notification_data');
    }

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
    public function getAttachedData($user_id) {
        return get_user_meta(intval($user_id), '__cred_user_notification_data', true); // unserialize
    }

	/**
	 * @param int $user_id
	 * @param bool $reassign_user_id
	 *
	 * @return bool
	 */
    public function deleteUser($user_id, $reassign_user_id = null) {
        $result = wp_delete_user($user_id, $reassign_user_id);
        return ($result !== false);
    }

	/**
	 * @param array $userdata
	 * @param array $usermeta
	 * @param array $fieldsInfo
	 * @param array|null $removed_fields
	 *
	 * @return array
	 * @deprecated since 2.0 moved to CRED_User_Premium_Feature::add_temporary_user
	 */
    public function addTemporaryUser($userdata, $usermeta, $fieldsInfo, $removed_fields = null) {
	    if ( CRED_StaticClass::$_password_generated != null ) {
		    $usermeta[ md5( '_password_generated' ) ] = CRED_StaticClass::$_password_generated;
	    }

	    $temp = array();

        $_cred_user_orders = get_option("_cred_user_orders", "");
	    if ( ! isset( $_cred_user_orders ) || empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = array();
	    }

	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
	    }

	    if ( ! isset( $removed_fields ) ) {
		    $removed_fields = array();
	    }

        $count = "draft_" . count($_cred_user_orders);
        $_cred_user_orders[$count] = array('userdata' => $userdata,
            'usermeta' => $usermeta,
            'fieldsInfo' => $fieldsInfo,
            'removed_fields' => $removed_fields);

	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
	    }

        update_option("_cred_user_orders", $_cred_user_orders);
        unset($temp);

        return array('is_commerce' => true, 'user_id' => $count);
    }

	/**
	 * @param int $num
	 * @param int|null $order_id
	 *
	 * @return bool|int|WP_Error
	 * @deprecated since 2.0 moved to CRED_User_Premium_Feature::publish_temporary_user
	 */
    public function publishTemporaryUser($num, $order_id = null) {
        $_cred_user_orders = get_option("_cred_user_orders", "");

	    if ( ! isset( $_cred_user_orders ) || empty( $_cred_user_orders ) ) {
		    return false;
	    }

	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
	    }

	    if ( ! isset( $_cred_user_orders[ $num ] ) ) {
		    return false;
	    }

        $data = $_cred_user_orders[$num];

        //avoid to delete temporary user because of possible refund
	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
	    }

	    update_option("_cred_user_orders", $_cred_user_orders);

        if (isset($data['usermeta'][md5('_password_generated')])) {
            CRED_StaticClass::$_password_generated = $data['usermeta'][md5('_password_generated')];
            unset($data['usermeta'][md5('_password_generated')]);
        }

        $new_user_id = $this->addUser($data['userdata'], $data['usermeta'], $data['fieldsInfo'], $data['removed_fields']);

        if (isset($order_id)) {
	        $order_id = (int) $order_id;
	        $sql = sprintf( 'SELECT * FROM %s WHERE post_id = %d', $this->wpdb->postmeta, $order_id );
            $metas = $this->wpdb->get_results($sql);
            foreach ($metas as $meta) {
                $mkey = substr($meta->meta_key, 1, strlen($meta->meta_key));
                update_user_meta($new_user_id, $mkey, $meta->meta_value);
            }

            //update draft_N with the real user
            update_post_meta($order_id, '_cred_post_id', $new_user_id);
        }

        return $new_user_id;
    }

	/**
	 * @param int $num
	 *
	 * @return bool
	 * @deprecated since 2.0 moved to CRED_User_Premium_Feature::delete_draft_temporary_user
	 */
    public function deleteTemporaryUser($num) {
        $_cred_user_orders = get_option("_cred_user_orders", "");
	    if ( ! isset( $_cred_user_orders )
		    || empty( $_cred_user_orders ) ) {
		    return false;
	    }

	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
	    }

	    if ( ! isset( $_cred_user_orders[ $num ] ) ) {
		    return false;
	    }

	    unset( $_cred_user_orders[ $num ] );

	    if ( ! empty( $_cred_user_orders ) ) {
		    $_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
	    }

        update_option("_cred_user_orders", $_cred_user_orders);
        return true;
    }

	/**
	 * @param array $userdata
	 * @param array $usermeta
	 * @param array $fieldsInfo
	 * @param array|null $removed_fields
	 *
	 * @return int|WP_Error
	 */
	public function addUser( $userdata, $usermeta, $fieldsInfo, $removed_fields = null ) {
		$user_id = $this->createUser( $userdata );
		$this->addUserInfo( $user_id, $usermeta, $fieldsInfo, $removed_fields );
		return $user_id;
	}


	/**
	 * Creates an user
	 *
	 * @param array $userdata User data.
	 * @return int|WP_Error
	 * @since 2.0.1
	 */
	public function createUser( $userdata ) {
		if ( ! isset( $userdata['user_role'] )
			|| empty( $userdata['user_role'] ) ) {
			global $wp_roles;
			$_roles = array_reverse( $wp_roles->roles );
			foreach ( $_roles as $k => $v ) {
				$userdata['user_role'] = array( $k );
				break;
			}
		}

		$user_role = is_array( $userdata['user_role'] ) ? $userdata['user_role'] : json_decode( $userdata['user_role'], true );
		$user_role = $user_role[0];

		unset( $userdata['user_role'] );
		unset( $userdata['ID'] );

		$userdata['role'] = $user_role;
		$user_id = wp_insert_user( $userdata );

		return $user_id;
	}

	/**
	 * Adds user info
	 *
	 * @param int|WP_Error $user_id User ID.
	 * @param array $usermeta
	 * @param array $fieldsInfo
	 * @param array|null $removed_fields
	 */
	public function addUserInfo( $user_id, $usermeta, $fieldsInfo, $removed_fields = null ) {
		$allowed_tags = $allowed_protocols = array();
		$this->setAllowed( $allowed_tags, $allowed_protocols );
		if ( ! is_wp_error( $user_id ) ) {

			if ( isset( $removed_fields )
				&& is_array( $removed_fields ) ) {
				// remove the fields that need to be removed
				foreach ( $removed_fields as $meta_key ) {
					delete_user_meta( $user_id, $meta_key );
				}
			}
			$usermeta = $this->esc_data( $usermeta );
			foreach ( $usermeta as $meta_key => $meta_value ) {
				delete_user_meta( $user_id, $meta_key );
				if ( is_array( $meta_value )
					&& ! $fieldsInfo[ $meta_key ]['save_single'] ) {
					foreach ( $meta_value as $meta_value_single ) {
						$meta_value_single = str_replace( '\\\\"', '"', $meta_value_single );
						$meta_value_single = wp_kses( $meta_value_single, $allowed_tags, $allowed_protocols );
						$meta_value_single = str_replace( "&amp;", "&", $meta_value_single );
						add_user_meta( $user_id, $meta_key, $meta_value_single, false /* $unique */ );
					}
				} else {
					if ( is_array( $meta_value ) ) {
						foreach ( $meta_value as &$meta_val ) {
							$meta_val = str_replace( '\\\\"', '"', $meta_val );
							$meta_val = wp_kses( $meta_val, $allowed_tags, $allowed_protocols );
							$meta_val = str_replace( "&amp;", "&", $meta_val );
						}
					} else {
						$meta_value = str_replace( '\\\\"', '"', $meta_value );
						$meta_value = wp_kses( $meta_value, $allowed_tags, $allowed_protocols );
						//avoid & to be replaced with &amp;
						$meta_value = str_replace( "&amp;", "&", $meta_value );
					}
					add_user_meta( $user_id, $meta_key, $meta_value, false /* $unique */ );
				}
			}
		}
	}

	/**
	 * @param array $userdata
	 *
	 * @return int|WP_Error
	 * @since 2.0.1 splitted in two methods
	 */
    public function updateUser( $userdata ) {
        //CHECK Userdata
        $user_role = $userdata['user_role'];
        unset($userdata['user_role']);

        $user_id = wp_update_user($userdata);

        return $user_id;
    }

    /**
     * Updates user info
     *
     * @param array $usermeta
     * @param array $fieldsInfo
     * @param array $removed_fields
     * @since 2.0.1
    */
    public function updateUserInfo( $user_id,  $usermeta, $fieldsInfo, $removed_fields = null ) {
        $allowed_tags = $allowed_protocols = array();
        $this->setAllowed($allowed_tags, $allowed_protocols);
        if (!is_wp_error($user_id)) {
            if (isset($removed_fields)
	            && is_array($removed_fields)) {
                // remove the fields that need to be removed
                foreach ($removed_fields as $meta_key) {
                    delete_user_meta($user_id, $meta_key);
                }
            }
            $usermeta = $this->esc_data($usermeta);
            foreach ($usermeta as $meta_key => $meta_value) {
                delete_user_meta($user_id, $meta_key);
                if (is_array($meta_value)
	                && !$fieldsInfo[$meta_key]['save_single']) {
                    foreach ($meta_value as $meta_value_single) {
                        $meta_value_single = str_replace('\\\\"', '"', $meta_value_single);
                        $meta_value_single = wp_kses($meta_value_single, $allowed_tags, $allowed_protocols);
                        $meta_value_single = str_replace("&amp;", "&", $meta_value_single);
                        add_user_meta($user_id, $meta_key, $meta_value_single, false /* $unique */);
                    }
                } else {
                    if (is_array($meta_value)) {
                        foreach ($meta_value as &$meta_val) {
                            $meta_val = str_replace('\\\\"', '"', $meta_val);
                            $meta_val = wp_kses($meta_val, $allowed_tags, $allowed_protocols);
                            $meta_val = str_replace("&amp;", "&", $meta_val);
                        }
                    } else {
                        $meta_value = str_replace('\\\\"', '"', $meta_value);
                        $meta_value = wp_kses($meta_value, $allowed_tags, $allowed_protocols);
                        //avoid & to be replaced with &amp;
                        $meta_value = str_replace("&amp;", "&", $meta_value);
                    }
                    add_user_meta($user_id, $meta_key, $meta_value, false /* $unique */);
                }
            }
        }
    }

	/**
	 * @param array $allowed_tags
	 * @param array $allowed_protocols
	 */
    private function setAllowed(&$allowed_tags, &$allowed_protocols) {
        $__allowed_tags = wp_kses_allowed_html('post');
        $__allowed_protocols = array('http', 'https', 'mailto');
        $settings_model = CRED_Loader::get('MODEL/Settings');
        $settings = $settings_model->getSettings();
        $allowed_tags = isset($settings['allowed_tags']) ? $settings['allowed_tags'] : $__allowed_tags;
        foreach ($__allowed_tags as $key => $value) {
            if (!isset($allowed_tags[$key])) {
                unset($__allowed_tags[$key]);
            }
        }
        $allowed_tags = $__allowed_tags;
        $allowed_protocols = $__allowed_protocols;
    }

}
