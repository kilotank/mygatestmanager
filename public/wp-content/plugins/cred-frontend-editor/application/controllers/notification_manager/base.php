<?php

/**
 * Class CRED_Notification_Manager
 *
 * is responsible of mail notification and is used by CRED to send them as described into
 * cred post/user form notification settings.
 *
 * get_attached_data/set_attached_data attach notification data to a post/user form in order to
 * send notification when is triggered *
 *
 * @since 1.9.6
 */
abstract class CRED_Notification_Manager_Base {

	protected $event = false;
	protected $current_snapshot_field_hash;
	protected $current_form_types = array();

	/**
	 * Array used to store notification already sent in order to avoid double sending
	 *
	 * @var array
	 * @since 1.9.2
	 */
	protected $notification_sent_record = array();

	public function __construct() {
	}

	/**
	 * @param int $form_id
	 *
	 * @return false|string
	 */
	protected function get_form_type( $form_id ) {
		if ( ! isset( $this->current_form_types[ $form_id ] ) ) {
			$this->current_form_types[ $form_id ] = get_post_type( $form_id );
		}

		return $this->current_form_types[ $form_id ];
	}

	/**
	 * @param int $form_id
	 *
	 * @return bool
	 * @deprecated since 1.9.6
	 */
	protected function is_user_form( $form_id ) {
		return ( $this->get_form_type( $form_id ) == CRED_USER_FORMS_CUSTOM_POST_NAME );
	}

	/**
	 * Returns a post or user object by generic $object_id and $is_user_form inputs
	 *
	 * @param int $object_id
	 *
	 * @return WP_Post|bool
	 * @since 1.9.6
	 */
	abstract protected function get_form_object( $object_id );

	/**
	 * @return CRED_Forms_Model|CRED_User_Forms_Model
	 * @since 1.9.6
	 */
	abstract protected function get_current_model();

	/**
	 * Returns notifications data form by form_id
	 *
	 * @param int $form_id
	 *
	 * @return array
	 * @since 1.9.2
	 * @deprecated since 1.9.6
	 */
	protected function get_notification_data_by_form_id( $form_id ) {
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type( $is_user_form );

		return $this->get_notification_data_by_model( $form_id, $model );
	}

	/**
	 * @param bool $is_user_form
	 *
	 * @return CRED_Forms_Model|CRED_User_Forms_Model
	 * @deprecated since 1.9.6
	 */
	protected function get_model_by_form_type( $is_user_form ) {
		return CRED_Loader::get( ( $is_user_form ) ? 'MODEL/UserForms' : 'MODEL/Forms' );
	}

	/**
	 * @param int $form_id
	 *
	 * @return CRED_Forms_Model|CRED_User_Forms_Model
	 * @deprecated since 1.9.6
	 */
	protected function get_model_by_form_id( $form_id ) {
		return $this->get_model_by_form_type( $this->is_user_form( $form_id ) );
	}

	/**
	 * Returns notification data by form_id and model
	 *
	 * @param int $form_id
	 * @param CRED_Forms_Model|CRED_User_Forms_Model $model
	 *
	 * @return array
	 * @since 1.9.2
	 */
	protected function get_notification_data_by_model( $form_id, $model ) {
		$notifications = array();
		$notificationData = $model->getFormCustomField( $form_id, 'notification' );
		if (
			isset( $notificationData->enable )
			&& $notificationData->enable
			&& isset( $notificationData->notifications )
		) {
			$notifications = $notificationData->notifications;
		}

		return $notifications;
	}


	/**
	 * Put current hashed attached snapshot data fields in static temporary variable
	 * in order to use if only_if_changed option is set as well
	 *
	 * @param int $object_id
	 * @param int $form_id
	 * @param array $notifications
	 */
	public function set_current_attached_data( $form_id, $object_id, $notifications = array() ) {
		if ( ! $this->current_snapshot_field_hash ) {
			$this->current_snapshot_field_hash = $this->get_attached_data( $form_id, $object_id, $notifications );
		}
	}


	/**
	 * @param int $form_id
	 * @param int $object_id
	 * @param array $notifications
	 *
	 * @return mixed
	 */
	abstract protected function get_attached_data( $form_id, $object_id, $notifications = array() );

	/**
	 * @param int $object_id
	 * @param array $attached_data
	 *
	 * @return mixed
	 */
	abstract protected function save_attached_data( $object_id, $attached_data );

	/**
	 * @param int $object_id
	 * @param array $attached_data
	 *
	 * @return mixed
	 */
	abstract protected function delete_attached_data( $object_id, $attached_data = array() );

	/**
	 * @param int $object_id
	 * @param int $form_id
	 * @param array $notifications
	 */
	public function add( $object_id, $form_id, $notifications = array() ) {
		$attached_data = $this->get_attached_data( $form_id, $object_id, $notifications );
		$this->save_attached_data( $object_id, $attached_data );
	}

	/**
	 * @param int $object_id
	 * @param int $form_id
	 */
	public function update( $object_id, $form_id ) {
		$attached_data = $this->get_attached_data( $form_id, $object_id );
		if ( ! $this->save_attached_data( $object_id, $attached_data ) ) {
			$this->delete_attached_data( $object_id, $attached_data );
		}
	}

	/**
	 * @param array $notification
	 * @param array $fields
	 * @param array $snapshot
	 *
	 * @return bool
	 */
	protected function evaluate_conditions( $notification, $fields, $snapshot ) {
		if ( ! isset( $notification[ 'event' ][ 'condition' ] )
			|| empty( $notification[ 'event' ][ 'condition' ] )
		) {
			return false;
		}

		$form_id = isset( $notification[ 'form_id' ] ) ? $notification[ 'form_id' ] : '';

		// to check if fields have changed
		$snapshot_fields_hash = isset( $snapshot[ 'snapshot' ] ) ? $this->unfold( $snapshot[ 'snapshot' ] ) : array();

		$current_snapshot = array();
		if ( ! empty( $this->current_snapshot_field_hash ) ) {
			foreach ( $this->current_snapshot_field_hash as $key => $value ) {
				if ( $form_id == $key
					&& isset( $value[ 'current' ][ 'snapshot' ] )
				) {
					$current_snapshot = $this->unfold( $value[ 'current' ][ 'snapshot' ] );
					break;
				}
			}
		}
		if ( isset( $notification[ 'event' ][ 'any_all' ] ) ) {
			$any_all_event = ( 'ALL' == $notification[ 'event' ][ 'any_all' ] );
		} else {
			$any_all_event = true;
		}

		$total_result = ( $any_all_event ) ? true : false;
		foreach ( $notification[ 'event' ][ 'condition' ] as $index => $condition ) {
			$field = $condition[ 'field' ];
			$value = $condition[ 'value' ];
			$op = $condition[ 'op' ];
			if ( isset( $fields[ $field ] ) ) {
				$field_value = $fields[ $field ];
				if ( is_array( $field_value )
					&& isset( $field_value[ 0 ] )
				) {
					$field_value = $field_value[ 0 ];
				}
			} else {
				$field_value = null;
			}

			if ( isset( $field_value ) && is_array( $field_value ) ) {
				$field_value = current( $field_value );
				if ( is_array( $field_value ) ) {
					$field_value = array_filter( $field_value );
					$field_value = reset( $field_value );
				}
			}

			// evaluate an individual condition here
			$result = $this->get_comparation_field_result( $op, $field_value, $value );
			if ( $condition[ 'only_if_changed' ] ) {
				if ( isset( $snapshot_fields_hash[ $field ] )
					&& isset( $current_snapshot[ $field ] )
				) {
					$result = $result && ( (bool) ( $snapshot_fields_hash[ $field ] !== $current_snapshot[ $field ] ) );
				}
			}

			if ( $any_all_event ) {
				$total_result = (bool) ( $result && $total_result );
			} else {
				$total_result = (bool) ( $result || $total_result );
			}

			// short-circuit the evaluation here to speed-up things
			if ( $any_all_event && ! $result ) {
				break;
			}
		}
		return $total_result;
	}

	/**
	 * @param int $object_id
	 * @param array $data
	 * @param array|null $attached_data
	 */
	abstract public function trigger_notifications( $object_id, $data, $attached_data = null );

	/**
	 * @param int $object_id
	 * @param object $post
	 */
	abstract public function check_for_notifications( $object_id, $post );


	/**
	 * @param string $value
	 *
	 * @return string
	 */
	protected static function hash( $value ) {
		// use simple crc-32 for speed and space issues,
		// not concerned with hash security here
		// http://php.net/manual/en/function.crc32.php
		$hash = hash( "crc32b", $value );

		//return $key.'##'.$value;
		return $hash;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function do_hash( $data = array() ) {
		if ( empty( $data ) ) {
			return array();
		}
		$hashes = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = serialize( $value );
			}
			$hashes[ $key ] = $this->hash( $value );
		}

		return $hashes;
	}

	/**
	 * Creates "serialized" string of hashed fields from a array of fields
	 *
	 * @param array $hashes
	 *
	 * @return string
	 */
	protected function fold( $hashes ) {
		$hash = array();
		foreach ( $hashes as $key => $value ) {
			$hash[] = $key . '##' . $value;
		}

		return implode( '|', $hash );
	}

	/**
	 * Creates array of hashed values fields from a serialized hashed string
	 *
	 * @param string $hash
	 *
	 * @return array
	 */
	protected function unfold( $hash ) {
		if ( empty( $hash ) || '' == $hash ) {
			return array();
		}
		$hasharray = explode( '|', $hash );
		$undohash = array();
		foreach ( $hasharray as $hash1 ) {
			$tmp = explode( '##', $hash1 );
			$undohash[ $tmp[ 0 ] ] = $tmp[ 1 ];
		}

		return $undohash;
	}

	/**
	 * Uniforming user data for sending notificaiton
	 *
	 * @return WP_User|object
	 */
	protected function get_current_user_data() {
		$current_user = wp_get_current_user();

		$user_data = new stdClass;
		$user_data->ID = isset( $current_user->ID ) ? $current_user->ID : 0;
		$user_data->roles = isset( $current_user->roles ) ? $current_user->roles : array();
		$user_data->role = isset( $current_user->roles[ 0 ] ) ? $current_user->roles[ 0 ] : '';
		$user_data->login = isset( $current_user->data->user_login ) ? $current_user->data->user_login : '';
		$user_data->display_name = isset( $current_user->data->display_name ) ? $current_user->data->display_name : '';
		$user_data->user_email = isset( $current_user->data->user_email ) ? $current_user->data->user_email : '';
		$user_data->user_pass = isset( $current_user->data->user_pass ) ? $current_user->data->user_pass : '';
		$user_data->user_login = isset( $current_user->data->user_login ) ? $current_user->data->user_login : '';
		$user_data->nickname = isset( $current_user->data->nickname ) ? $current_user->data->nickname : '';

		return $user_data;
	}

	/**
	 * Translate codes in notification fields of cred form (like %%POST_ID%% to post id etc..)
	 *
	 * @param string $field
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function replace_placeholders( $field, $data ) {
		return str_replace( array_keys( $data ), array_values( $data ), $field );
	}

	/**
	 * @param int $form_id
	 * @param array $notification
	 *
	 * @return array
	 * @deprecated Maybe, seems not used anywhere?
	 */
	public function send_test_notification( $form_id, $notification ) {
		// bypass if nothing
		if ( ! $notification || empty( $notification ) ) {
			return array( 'error' => __( 'No Notification given', 'wp-cred' ) );
		}

		// dummy
		$post_id = null;
		// custom action hooks here, for 3rd-party integration
		$model = $this->get_current_model();

		// get Mailer
		$mailer = CRED_Loader::get( 'CLASS/Mail_Handler' );

		// get current user
		$user = $this->get_current_user_data();

		// get some data for placeholders
		$form_post = get_post( $form_id );
		$form_title = ( $form_post ) ? $form_post->post_title : '';
		$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

		/**
		 * Extend the notification subject placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_subject = apply_filters( 'cred_subject_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date,
		), $form_id, $post_id );

		/**
		 * Extend the notification body placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_body = apply_filters( 'cred_body_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%POST_LINK%%' => 'DUMMY_POST_LINK',
			'%%POST_ADMIN_LINK%%' => 'DUMMY_ADMIN_POST_LINK',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date,
		), $form_id, $post_id );

		// reset mail handler
		$mailer->reset();
		$mailer->setHTML( true, false );
		$recipients = array();

		// parse Notification Fields
		if ( ! isset( $notification[ 'to' ][ 'type' ] ) ) {
			$notification[ 'to' ][ 'type' ] = array();
		}
		if ( ! is_array( $notification[ 'to' ][ 'type' ] ) ) {
			$notification[ 'to' ][ 'type' ] = (array) $notification[ 'to' ][ 'type' ];
		}

		// notification to specific recipients
		if ( in_array( 'specific_mail', $notification[ 'to' ][ 'type' ] )
			&& isset( $notification[ 'to' ][ 'specific_mail' ][ 'address' ] )
		) {
			$exploded_addresses = explode( ',', $notification[ 'to' ][ 'specific_mail' ][ 'address' ] );
			foreach ( $exploded_addresses as $address ) {
				$recipients[] = array(
					'address' => $address,
					'to' => false,
					'name' => false,
					'lastname' => false,
				);
			}
			unset( $exploded_addresses );
		}

		// add custom recipients by 3rd-party
		if ( ! $recipients
			|| empty( $recipients )
		) {
			return array( 'error' => __( 'No recipients specified', 'wp-cred' ) );
		}

		// build recipients
		foreach ( $recipients as $key => $recipient ) {
			// nowhere to send, bypass
			if ( ! isset( $recipient[ 'address' ] ) || ! $recipient[ 'address' ] ) {
				unset( $recipients[ $key ] );
				continue;
			}

			if ( false === $recipient[ 'to' ] ) {
				// this is already formatted
				$recipients[ $key ] = $recipient[ 'address' ];
				continue;
			}

			$exploded_addresses = '';
			$exploded_addresses .= $recipient[ 'to' ] . ': ';
			$recipient_store = array();
			if ( $recipient[ 'name' ] ) {
				$recipient_store[] = $recipient[ 'name' ];
			}
			if ( $recipient[ 'lastname' ] ) {
				$recipient_store[] = $recipient[ 'lastname' ];
			}
			if ( ! empty( $recipient_store ) ) {
				$exploded_addresses .= implode( ' ', $recipient_store ) . ' <' . $recipient[ 'address' ] . '>';
			} else {
				$exploded_addresses .= $recipient[ 'address' ];
			}

			$recipients[ $key ] = $exploded_addresses;
		}
		$mailer->addRecipients( $recipients );

		// build SUBJECT
		$subject = '';
		if ( isset( $notification[ 'mail' ][ 'subject' ] ) ) {
			$subject = $notification[ 'mail' ][ 'subject' ];
		}

		// provide WPML localisation
		if ( isset( $notification[ '_cred_icl_string_id' ][ 'subject' ] ) ) {
			$notification_subject_string_translation_name = $this->getNotification_translation_name( $notification[ '_cred_icl_string_id' ][ 'subject' ] );
			if ( $notification_subject_string_translation_name ) {
				$subject = cred_translate( $notification_subject_string_translation_name, $subject, 'cred-form-' . $form_title . '-' . $form_id );
			}
		}

		// replace placeholders
		$subject = $this->replace_placeholders( $subject, $data_subject );

		// parse shortcodes if necessary
		$subject = do_shortcode( $subject );

		$mailer->setSubject( $subject );

		// build BODY
		$body = '';
		if ( isset( $notification[ 'mail' ][ 'body' ] ) ) {
			$body = $notification[ 'mail' ][ 'body' ];
		}

		// provide WPML localisation
		if ( isset( $notification[ '_cred_icl_string_id' ][ 'body' ] ) ) {
			$notification_body_string_translation_name = $this->getNotification_translation_name( $notification[ '_cred_icl_string_id' ][ 'body' ] );
			if ( $notification_body_string_translation_name ) {
				$body = cred_translate( $notification_body_string_translation_name, $body, 'cred-form-' . $form_title . '-' . $form_id );
			}
		}

		// replace placeholders
		$body = $this->replace_placeholders( $body, $data_body );

		// pseudo the_content filter
		$body = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $body );
		$body = stripslashes( $body );

		$mailer->setBody( $body );

		// build FROM address / name, independantly
		$from = array();
		if ( isset( $notification[ 'from' ][ 'address' ] ) && ! empty( $notification[ 'from' ][ 'address' ] ) ) {
			$from[ 'address' ] = $notification[ 'from' ][ 'address' ];
		}
		if ( isset( $notification[ 'from' ][ 'name' ] ) && ! empty( $notification[ 'from' ][ 'name' ] ) ) {
			$from[ 'name' ] = $notification[ 'from' ][ 'name' ];
		}
		if ( ! empty( $from ) ) {
			$mailer->setFrom( $from );
		}

		// send it
		$send_result = $mailer->send();

		if ( ! $send_result ) {
			if ( empty( $body ) ) {
				return array( 'error' => __( 'Body content is required', 'wp-cred' ) );
			} else {
				return array( 'error' => __( 'Mail failed to be sent', 'wp-cred' ) );
			}
		}

		return array( 'success' => __( 'Mail sent successfully', 'wp-cred' ) );
	}

	/**
	 * @param int $object_id
	 * @param int $form_id
	 * @param array $notificationsToSent
	 *
	 * @return bool
	 */
	abstract public function send_notifications( $object_id, $form_id, $notificationsToSent );


	/**
	 * Enqueue notifications for later sending.
	 *
	 * @param int $object_id Form or User ID.
	 * @param int $form_id Form ID.
	 * @param array $notifications Notifications to send
	 */
	public function enqueue_notifications( $object_id, $form_id, $notifications ) {
		$queue_manager = CRED_Notification_Manager_Queue::get_instance();
		foreach ( $notifications as $notification ) {
			$queue_manager->enqueue( $object_id, $form_id, $notification['event']['type'], get_class( $this ), $notification );
		}
	}

	/**
	 * Retrieve string translation name of the notification based on string ID (icl string id)
	 *
	 * @param int $id
	 *
	 * @return bool|null|string
	 */
	protected function getNotification_translation_name( $id ) {
		if ( function_exists( 'icl_t' ) ) {
			global $wpdb;
			$dBtable = $wpdb->prefix . "icl_strings";
			$string_translation_name_notifications = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $dBtable WHERE id=%d", $id ) );

			if ( $string_translation_name_notifications ) {
				return $string_translation_name_notifications;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 * @deprecated 1.9.6
	 */
	protected function evaluate( $params ) {
		$form_id = $params[ 'form_id' ];

		$snapshot = isset( $params[ 'snapshot' ] ) ? $params[ 'snapshot' ] : array();
		$fields = isset( $params[ 'fields' ] ) ? $params[ 'fields' ] : array();
		$notification = isset( $params[ 'notification' ] ) ? $params[ 'notification' ] : array();
		$notification[ 'form_id' ] = $form_id;
		$post = isset( $params[ 'post' ] ) ? $params[ 'post' ] : array();

		$notification_type = apply_filters( 'cred_notification_event_type', $notification[ 'event' ][ 'type' ], $notification, $form_id, $post->ID );
		switch ( $notification_type ) {
			case 'form_submit':
				if ( $this->event
					&& 'form_submit' == $this->event
				) {
					return $this->evaluate_conditions( $notification, $fields, $snapshot );
				}
				break;
			case 'post_modified':
				if ( $post->post_status == $notification[ 'event' ][ 'post_status' ] && $post->post_status != $snapshot[ 'post_status' ] ) {
					return $this->evaluate_conditions( $notification, $fields, $snapshot );
				}
				break;
			case 'meta_modified':
				return $this->evaluate_conditions( $notification, $fields, $snapshot );
			// custom event
			default:
				if ( apply_filters( 'cred_custom_notification_event_type_condition', ( $this->event && $this->event == $notification[ 'event' ][ 'type' ] ), $notification, $form_id, $post->ID ) ) {
					return $this->evaluate_conditions( $notification, $fields, $snapshot );
				}
				break;
		}

		return false;
	}

	/**
	 * @param int $post_id
	 * @param string $title
	 * @param string $form_title
	 * @param string $date
	 * @param string $link
	 * @param string $admin_edit_link
	 *
	 * @return array
	 */
	protected function get_placeholders_post_array( $post_id, $title, $form_title, $date, $link = '', $admin_edit_link = '' ) {
		return array(
			'%%DATE_TIME%%' => $date,
			'%%POST_ID%%' => $post_id,
			'%%POST_TITLE%%' => $title,
			'%%FORM_NAME%%' => $form_title,
			'%%POST_LINK%%' => $link,
			'%%POST_ADMIN_LINK%%' => $admin_edit_link,
		);
	}

	/**
	 * @param WP_User $user
	 *
	 * @return array
	 */
	protected function get_placeholders_user_array( $user ) {
		$reset_pass_link = '<a href="' . wp_lostpassword_url() . '" title="' . __( 'Lost Password', 'wp-cred' ) . '">' . __( 'Lost Password', 'wp-cred' ) . '</a>';

		$user_id = '';
		$user_login = '';
		$user_display_name = '';
		$user_pass = '';
		$user_name = '';
		$nickname = '';
		$user_email = '';
		if ( isset( $user ) ) {
			$user_id = $user->ID;
			$user_login = $user->user_login;
			$user_display_name = $user->display_name;
			$user_email = $user->user_email;
			$user_pass = $user->user_pass;
			$user_pass = isset( CRED_StaticClass::$_password_generated ) ? CRED_StaticClass::$_password_generated : $user_pass;
			$user_name = $user->user_login;
			$user_name = isset( CRED_StaticClass::$_username_generated ) ? CRED_StaticClass::$_username_generated : $user_name;
			$nickname = $user->nickname;
			$nickname = isset( CRED_StaticClass::$_nickname_generated ) ? CRED_StaticClass::$_nickname_generated : $nickname;
		}

		return array(
			'%%USER_USERID%%' => $user_id,
			'%%USER_EMAIL%%' => $user_email,
			'%%USER_USERNAME%%' => $user_name,
			'%%USER_PASSWORD%%' => $user_pass,
			'%%RESET_PASSWORD_LINK%%' => $reset_pass_link,
			'%%USER_NICKNAME%%' => $nickname,
			'%%USER_LOGIN_NAME%%' => $user_login,
			'%%USER_DISPLAY_NAME%%' => $user_display_name,
		);
	}

	/**
	 * @param int $post_id
	 * @param int $form_id
	 * @param array $placeholders
	 *
	 * @return array
	 */
	protected function get_data_subject_applying_filters( $post_id, $form_id, $placeholders ) {
		$data_subject = apply_filters( 'cred_subject_notification_codes', $placeholders, $form_id, $post_id );

		return $data_subject;
	}

	/**
	 * @param int $post_id
	 * @param int $form_id
	 * @param array $placeholders
	 *
	 * @return array
	 */
	protected function get_data_body_applying_filters( $post_id, $form_id, $placeholders ) {
		$data_body = apply_filters( 'cred_body_notification_codes', $placeholders, $form_id, $post_id );

		return $data_body;
	}

	/**
	 * @param int $object_id
	 * @param int $form_id
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	abstract protected function try_add_author_to_recipients( $object_id, $form_id, $notification, &$recipients );

	/**
	 * @param int $object_id
	 * @param array $notification
	 * @param array $recipients
	 * @param WP_User|null $the_user
	 *
	 * @return bool
	 */
	abstract protected function try_add_mail_field_to_recipients( $object_id, $notification, &$recipients, $the_user = null );

	/**
	 * @param int $object_id
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	abstract protected function try_add_user_id_field_to_recipients( $object_id, $notification, &$recipients );

	/**
	 * Try to add to recipients notification wp_user
	 *
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	protected function try_add_wp_user_to_recipients( $notification, &$recipients ) {
		// notification to an exisiting wp user
		if ( in_array( 'wp_user', $notification[ 'to' ][ 'type' ] ) ) {
			$_to_type = 'to';
			$_addr = false;
			$_addr_name = false;
			$_addr_lastname = false;

			if (
				isset( $notification[ 'to' ][ 'wp_user' ][ 'to_type' ] )
				&& in_array( $notification[ 'to' ][ 'wp_user' ][ 'to_type' ], array( 'to', 'cc', 'bcc' ) )
			) {
				$_to_type = $notification[ 'to' ][ 'wp_user' ][ 'to_type' ];
			}

			$_addr = $notification[ 'to' ][ 'wp_user' ][ 'user' ];
			$user_id_to_sent_to = email_exists( $_addr );
			if ( $user_id_to_sent_to ) {
				$user_info = get_userdata( $user_id_to_sent_to );
				$_addr_name = ( isset( $user_info->user_firstname ) && ! empty( $user_info->user_firstname ) ) ? $user_info->user_firstname : false;
				$_addr_lastname = ( isset( $user_info->user_lastname ) && ! empty( $user_info->user_lastname ) ) ? $user_info->user_lastname : false;

				// add to recipients
				$recipients[] = array(
					'to' => $_to_type,
					'address' => $_addr,
					'name' => $_addr_name,
					'lastname' => $_addr_lastname,
				);

				return true;
			}
		}

		return false;
	}

	/**
	 * Try to add to recipients notification specific mail
	 *
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	protected function try_add_specific_mail_to_recipients( $notification, &$recipients ) {
		// notification to specific recipients
		if ( in_array( 'specific_mail', $notification[ 'to' ][ 'type' ] )
			&& isset( $notification[ 'to' ][ 'specific_mail' ][ 'address' ] )
		) {
			$recipient_email_addresses = explode( ',', $notification[ 'to' ][ 'specific_mail' ][ 'address' ] );
			if ( ! empty( $recipient_email_addresses ) ) {
				foreach ( $recipient_email_addresses as $aa ) {
					$recipients[] = array(
						'address' => $aa,
						'to' => false,
						'name' => false,
						'lastname' => false,
					);
				}

				return true;
			}
			unset( $recipient_email_addresses );
		}

		return false;
	}

	/**
	 * Build recipients
	 *
	 * @param array $recipients
	 */
	protected function build_recipients( &$recipients ) {
		// build recipients
		foreach ( $recipients as $index => $recipient ) {
			// nowhere to send, bypass
			if ( ! isset( $recipient[ 'address' ] )
				|| ! $recipient[ 'address' ]
			) {
				unset( $recipients[ $index ] );
				continue;
			}

			if ( false === $recipient[ 'to' ] ) {
				// this is already formatted
				$recipients[ $index ] = $recipient[ 'address' ];
				continue;
			}

			$recipient_email_addresses = '';
			$recipient_email_addresses .= $recipient[ 'to' ] . ': ';
			$recipient_array = array();
			if ( $recipient[ 'name' ] ) {
				$recipient_array[] = $recipient[ 'name' ];
			}
			if ( $recipient[ 'lastname' ] ) {
				$recipient_array[] = $recipient[ 'lastname' ];
			}
			if ( ! empty( $recipient_array ) ) {
				$recipient_email_addresses .= implode( ' ', $recipient_array ) . ' <' . $recipient[ 'address' ] . '>';
			} else {
				$recipient_email_addresses .= $recipient[ 'address' ];
			}

			$recipients[ $index ] = $recipient_email_addresses;
		}
	}

	/**
	 * @param array $notification
	 *
	 * @return array
	 */
	protected function get_mail_form_by_notification( $notification ) {
		// build FROM address / name, independantly
		$_from = array();
		if ( isset( $notification[ 'from' ][ 'address' ] )
			&& ! empty( $notification[ 'from' ][ 'address' ] )
		) {
			$_from[ 'address' ] = $notification[ 'from' ][ 'address' ];
		}
		if ( isset( $notification[ 'from' ][ 'name' ] )
			&& ! empty( $notification[ 'from' ][ 'name' ] )
		) {
			$_from[ 'name' ] = $notification[ 'from' ][ 'name' ];
		}

		return $_from;
	}

	/**
	 * @param $op
	 * @param $field_value
	 * @param $value
	 *
	 * @return bool
	 */
	protected function get_comparation_field_result( $op, $field_value, $value ) {
		switch ( $op ) {
			case '=':
				$result = (bool) ( $field_value == $value );
				break;
			case '>':
				$result = (bool) ( $field_value > $value );
				break;
			case '>=':
				$result = (bool) ( $field_value >= $value );
				break;
			case '<':
				$result = (bool) ( $field_value < $value );
				break;
			case '<=':
				$result = (bool) ( $field_value <= $value );
				break;
			case '<>':
				$result = (bool) ( $field_value != $value );
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}


	/**
	 * Saves current snapshot before any evaluation, needed for WP backend editing
	 *
	 * @param int $object_id Post or User ID
	 * @since 2.0.1
	 */
	public function save_pre_snapshot( $object_id ) {
		$model = $this->get_current_model();
		$attached_data = $model->getAttachedData( $object_id );
		if ( ! $attached_data ) {
			return;
		}
		reset( $attached_data );
		$form_id = key( $attached_data );
		$this->set_current_attached_data( $form_id, $object_id, $model->getFormCustomField( $form_id, 'notification' )->notifications );
	}
}
