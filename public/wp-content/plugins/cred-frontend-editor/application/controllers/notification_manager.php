<?php

/**
 * Class CRED_Notification_Manager
 *
 * is responsible of mail notification and is used by Toolset Forms to send them as described into
 * cred post/user form notification settings.
 *
 * get_attached_data/set_attached_data attach notification data to a post/user form in order to
 * send notification when is triggered *
 *
 * @since 1.9.1
 * @deprecated since 1.9.6 use CRED_Notification_Manager_Post & CRED_Notification_Manager_User
 */
class CRED_Notification_Manager {

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

	private static $instance;

	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new CRED_Notification_Manager();
			self::$instance->initialize();
		}
		return self::$instance;
	}

	public function __construct() {
	}

	public function initialize() {
		add_action('wp_loaded', array($this, 'addHooks'), 10);
	}

	public function addHooks() {

		

		add_action('save_post', array($this, 'checkPostForNotifications'), 10, 2);
		add_action('profile_update', array($this, 'checkUserForNotifications'), 10, 2);

		/**
		 * check if status is changed
		 */
		$check_to_status = array('publish', 'pending', 'draft', 'private');
		$check_from_status = array_merge($check_to_status, array('new', 'future', 'trash'));
		foreach ($check_from_status as $from) {
			foreach ($check_to_status as $to) {
				if ($from == $to) {
					continue;
				}
				$action = sprintf('%s_to_%s', $from, $to);
				add_action( $action, array($this, 'checkPostForNotifications'), 10, 2);
			}
		}

		$post_types = get_post_types(array('public' => true, 'publicly_queryable' => true, '_builtin' => true), 'names', 'or');
		foreach ($post_types as $pt) {
			add_action("updated_{$pt}_meta", array($this, 'updated_meta'), 20, 4);
		}
	}

	public function removeHooks() {
		remove_action('save_post', array($this, 'checkPostForNotifications' ), 10, 2);
		remove_action('profile_update', array($this, 'checkUserForNotifications'), 10, 2);

		$check_to_status = array('publish', 'pending', 'draft', 'private');
		$check_from_status = array_merge($check_to_status, array('new', 'future', 'trash'));
		foreach ($check_from_status as $from) {
			foreach ($check_to_status as $to) {
				if ($from == $to) {
					continue;
				}
				$action = sprintf('%s_to_%s', $from, $to);
				remove_action( $action, array($this, 'checkPostForNotifications'), 10, 2);
			}
		}

		$post_types = get_post_types(array('public' => true, 'publicly_queryable' => true, '_builtin' => true), 'names', 'or');
		foreach ($post_types as $pt) {
			remove_action("updated_{$pt}_meta", array($this, 'updated_meta'), 20, 4);
		}
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
	 * @param $form_id
	 *
	 * @return bool
	 */
	protected function is_user_form( $form_id ) {
		return ( $this->get_form_type( $form_id ) == CRED_USER_FORMS_CUSTOM_POST_NAME );
	}

	/**
	 * Returns a post or user object by generic $object_id and $is_user_form inputs
	 *
	 * @param $object_id
	 * @param $is_user_form
	 *
	 * @return array|bool|null|object|stdClass|WP_Post
	 * @since 1.9.2
	 */
	protected function get_form_object($object_id, $is_user_form) {
		$object_id = (int) $object_id;

		$object = false;
		if ( $is_user_form ) {
			$user_data = get_userdata( $object_id );
			if ( isset( $user_data )
				&& isset( $user_data->data ) ) {
				$object = $user_data->data;
			}
		} else {
			$object = get_post( $object_id );
		}
		return $object;
	}

	/**
	 * Returns notifications data form by form_id
	 * @param $form_id
	 *
	 * @return array
	 * @since 1.9.2
	 */
	protected function get_notification_data_by_form_id( $form_id ) {
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type( $is_user_form );

		return $this->get_notification_data_by_model( $form_id, $model );
	}

	/**
	 * @param $is_user_form
	 *
	 * @return null|object
	 */
	protected function get_model_by_form_type( $is_user_form ) {
		return CRED_Loader::get( ( $is_user_form ) ? 'MODEL/UserForms' : 'MODEL/Forms' );
	}

	/**
	 * @param $form_id
	 *
	 * @return null|object
	 */
	protected function get_model_by_form_id( $form_id ) {
		return $this->get_model_by_form_type( $this->is_user_form( $form_id ) );
	}

	/**
	 * Returns notification data by form_id and model
	 *
	 * @param $form_id
	 * @param $model
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
	 * Prepare attached hashed snapshot data field referred to current time form fields
	 * in order to check if something has changed in them
	 *
	 * @param $form_id
	 * @param $object_id
	 * @param array $notifications
	 *
	 * @return array|null
	 */
	protected function get_attached_data( $form_id, $object_id, $notifications = array() ) {
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type($is_user_form);

		$object = $this->get_form_object($object_id, $is_user_form);
		if ( ! $object ) {
			return null;
		}

		if ( empty( $notifications ) ) {
			$notifications = $this->get_notification_data_by_model($form_id, $model);
		}

		$attachedData = array();
		$snapshotFields = array();
		if ( ! empty( $notifications ) ) {
			foreach ( $notifications as $ii => $notification ) {
				if ( isset( $notification['event']['condition'] ) ) {
					foreach ( $notification['event']['condition'] as $jj => $condition ) {
						if ( isset( $condition['only_if_changed'] ) &&
							$condition['only_if_changed'] &&
							! in_array( $condition['field'], $snapshotFields )
						) {
							// load all fields that have a changing condition from all notifications at once
							$snapshotFields[] = $condition['field'];
						}
					}
				}
			}

			$fields = $model->get_object_fields( $object_id, $snapshotFields );
			$snapshotFieldsValuesHash = $this->fold( $this->doHash( $fields ) );
			$attachedData[ $form_id ] = array(
				'cred_form' => $form_id,
				'current' => array(
					'time' => time(),
					'post_status' => ( ! $is_user_form ) ? $object->post_status : '',
					'snapshot' => $snapshotFieldsValuesHash,
				),
			);
		}

		return $attachedData;
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
		$this->current_snapshot_field_hash = $this->get_attached_data( $form_id, $object_id, $notifications );
	}

	/**
	 * @param $form_id
	 * @param $object_id
	 * @param $attached_data
	 *
	 * @return bool
	 * @since 1.9.2
	 */
	protected function save_attached_data( $form_id, $object_id, $attached_data ) {
		if ( empty( $attached_data ) ) {
			return false;
		}

		//Removing hooks before setAttachedData in order to avoid infinite loops
		//because of update_meta called in setAttachedData
		$object_id = (int) $object_id;
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type( $is_user_form );
		$this->removeHooks();
		$is_attached_data_saved = $model->setAttachedData( $object_id, $attached_data );
		$this->addHooks();
		return $is_attached_data_saved;
	}

	/**
	 * @param $form_id
	 * @param $object_id
	 * @param array $attached_data
	 *
	 * @return bool
	 * @since 1.9.2
	 */
	protected function delete_attached_data( $form_id, $object_id, $attached_data = array() ) {
		if ( ! empty( $attached_data ) ) {
			return false;
		}

		$object_id = (int) $object_id;
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type( $is_user_form );

		return $model->removeAttachedData( $object_id );
	}

	/**
	 * @param int $object_id
	 * @param int $form_id
	 * @param array $notifications
	 */
	public function add( $object_id, $form_id, $notifications = array() ) {
		$attached_data = $this->get_attached_data( $form_id, $object_id, $notifications );
		$this->save_attached_data( $form_id, $object_id, $attached_data );
	}

	/**
	 * @param int $object_id
	 * @param int $form_id
	 */
	public function update( $object_id, $form_id ) {
		$attached_data = $this->get_attached_data( $form_id, $object_id );
		if ( ! $this->save_attached_data( $form_id, $object_id, $attached_data ) ) {
			$this->delete_attached_data( $form_id, $object_id, $attached_data );
		}
	}

	/**
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string $_meta_value
	 */
	public function updated_meta($meta_id, $object_id, $meta_key, $_meta_value) {
		switch ($meta_key) {
			case '_edit_lock':
			case '_toolset_edit_last':
				break;
			default:
				$this->checkPostForNotifications( $object_id, get_post( $object_id ) );
				break;
		}
	}

	/**
	 * @param array $notification
	 * @param array $fields
	 * @param array $snapshot
	 *
	 * @return bool
	 */
	protected function evaluateConditions($notification, $fields, $snapshot) {
		if ( ! isset( $notification['event']['condition'] )
			|| empty( $notification['event']['condition'] )
		) {
			return false;
		}

		$form_id = isset($notification['form_id']) ? $notification['form_id'] : '';

		// to check if fields have changed
		$snapshotFieldsHash = isset($snapshot['snapshot']) ? $this->unfold($snapshot['snapshot']) : array();

		$current_snapshot = array();
		if (!empty($this->current_snapshot_field_hash)) {
			foreach ( $this->current_snapshot_field_hash as $key => $value ) {
				if ( $form_id == $key
					&& isset( $value['current']['snapshot'] )
				) {
					$current_snapshot = $this->unfold( $value['current']['snapshot'] );
					break;
				}
			}
		}

		if ( isset( $notification['event']['any_all'] ) ) {
			$ALL = ( 'ALL' == $notification['event']['any_all'] );
		} else {
			$ALL = true;
		}

		$total_result = ($ALL) ? true : false;
		foreach ($notification['event']['condition'] as $jj => $condition) {
			$field = $condition['field'];
			$value = $condition['value'];
			$op = $condition['op'];
			if (isset($fields[$field])) {
				$fieldvalue = $fields[$field];
				if ( is_array( $fieldvalue )
					&& isset( $fieldvalue[0] )
				) {
					$fieldvalue = $fieldvalue[0];
				}
			}
			else {
				$fieldvalue = null;
			}

			if (isset($fieldvalue) && is_array($fieldvalue)) {
				$fieldvalue = current($fieldvalue);
				if (is_array($fieldvalue)) {
					$fieldvalue = array_filter($fieldvalue);
					$fieldvalue = reset($fieldvalue);
				}
			}
			// evaluate an individual condition here
			switch ($op) {
				case '=':
					$result = (bool) ($fieldvalue == $value);
					break;
				case '>':
					$result = (bool) ($fieldvalue > $value);
					break;
				case '>=':
					$result = (bool) ($fieldvalue >= $value);
					break;
				case '<':
					$result = (bool) ($fieldvalue < $value);
					break;
				case '<=':
					$result = (bool) ($fieldvalue <= $value);
					break;
				case '<>':
					$result = (bool) ($fieldvalue != $value);
					break;
				default:
					$result = false;
					break;
			}

			if ($condition['only_if_changed']) {
				if ( isset( $snapshotFieldsHash[ $field ] )
					&& isset( $current_snapshot[ $field ] )
				) {
					$result = $result && ( (bool) ( $snapshotFieldsHash[ $field ] !== $current_snapshot[ $field ] ) );
				}
			}

			if ( $ALL ) {
				$total_result = (bool) ( $result && $total_result );
			} else {
				$total_result = (bool) ( $result || $total_result );
			}

			// short-circuit the evaluation here to speed-up things
			if ( $ALL && ! $result ) {
				break;
			}
		}

		return $total_result;
	}

	/**
	 * @param int $object_id
	 * @param array $data
	 * @param null $attached_data
	 */
	public function triggerNotifications($object_id, $data, $attached_data = null) {
		$form_id = $data['form_id'];
		$is_user_form = $this->is_user_form( $form_id );
		$model = $this->get_model_by_form_type($is_user_form);

		if ( empty( $object_id ) ) {
			return;
		}

		if ($is_user_form) {
			$user_data = get_userdata($object_id);
			if (isset($user_data) && !empty($user_data)) {
				$object = $user_data->data;
			}

			if ( ! isset( $object )
				&& isset( $data['customer'] )
			) {
				$object = $data['customer'];
				$object->ID = $object_id;
			}
		} else {
			if (isset($data['post'])) {
				$object = $data['post'];
			} else {
				$object = get_post($object_id);
			}
		}

		if (!isset($object)) {
			return;
		}

		if ( empty( $attached_data ) ) {
			$attached_data = $model->getAttachedData( $object_id );
		}

		// trigger for this event, if set
		$this->event = 'post_modified';
		if (isset($data['event'])) {
			$this->event = $data['event'];
		}

		$notification = isset($data['notification']) ? $data['notification'] : false;
		if (
			( ! $attached_data && ! $is_user_form )
			|| ! $notification
			|| ! isset( $notification->enable )
			|| ! $notification->enable
			|| empty( $notification->notifications )
		) {
			return;
		}

        $notifications_to_send = array();
        foreach ($notification->notifications as $index => $single_notification) {
	        if ( isset( $single_notification['disabled'] )
		        && $single_notification['disabled'] == 1
	        ) {
		        continue;
	        }

	        $snapshot = isset($attached_data[$form_id]) ? $attached_data[$form_id]['current'] : array();

	        $is_correct_notification_event_type = ( $single_notification[ 'event' ][ 'type' ] == $this->event );

	        $is_payment_and_order_complete = ( $single_notification[ 'event' ][ 'type' ] == 'payment_complete'
		        && $this->event == 'order_completed' );

	        $is_order_modified = ( $is_correct_notification_event_type
		        && $this->event == 'order_modified'
		        && isset( $data[ 'data_order' ] )
		        && isset( $data[ 'data_order' ][ 'new_status' ] )
		        && $data[ 'data_order' ][ 'new_status' ] == $single_notification[ 'event' ][ 'order_status' ]
		        && $data[ 'data_order' ][ 'previous_status' ] != $data[ 'data_order' ][ 'new_status' ] );

	        $is_post_modified = ( $is_correct_notification_event_type
		        && $this->event == 'post_modified'
		        && isset( $object->post_status )
		        && $object->post_status == $single_notification[ 'event' ][ 'post_status' ]
		        && isset( $snapshot[ 'post_status' ] )
		        && $snapshot['post_status'] != $object->post_status );

	        $is_form_submit = ( $is_correct_notification_event_type
		        && $this->event == 'form_submit' );

	        $is_order_created = ( $is_correct_notification_event_type
		        && $this->event == 'order_created' );

	        if ( $is_payment_and_order_complete
		        || $is_order_modified
		        || $is_post_modified
		        || $is_form_submit
		        || $is_order_created
	        ) {
		        $notifications_to_send[] = $single_notification;
	        } else {
		        if (isset($single_notification['event'])) {
			        $condition_fields = array();
			        $notification_condition_fields = array();
			        if ( isset( $single_notification['event']['condition'] )
				        && ! empty( $single_notification['event']['condition'] )
			        ) {
				        foreach ( $single_notification['event']['condition'] as $key => $condition ) {
					        $condition_fields[] = $condition['field'];
				        }
				        $notification_condition_fields = $model->get_object_fields( $object_id, $condition_fields );
			        }

			        $send_notification = $this->evaluateConditions($single_notification, $notification_condition_fields, $snapshot);

			        if ( $send_notification ) {
				        $notifications_to_send[] = $single_notification;
			        }
		        }
	        }
        }

		if ( ! empty( $notifications_to_send ) ) {
			$this->sendNotifications( $object_id, $form_id, $notifications_to_send );
		}
    }

	/**
	 * @param int $post_id
	 * @param object $post
	 */
	public function checkPostForNotifications($post_id, $post) {
		if ( isset( $post )
			&& $post->post_type == CRED_FORMS_CUSTOM_POST_NAME ) {
			return;
		}

		$model = CRED_Loader::get('MODEL/Forms');
		$attachedData = $model->getAttachedData($post_id);
		if ( ! $attachedData ) {
			return;
		}

		$notification = false;
		foreach ($attachedData as $form_id => $data) {
			$notification = $model->getFormCustomField($form_id, 'notification');
			break;
		}

		if ($notification) {
			$this->triggerNotifications($post_id, array(
				'notification' => $notification,
				'form_id' => $form_id,
				'post' => $post
			));
		}

		// keep up-to-date with notification settings for form and post field values
		$this->update( $post_id, $form_id );
	}

	/**
	 * @param int $user_id
	 * @param object $user
	 */
	public function checkUserForNotifications($user_id, $user) {
		$model = CRED_Loader::get('MODEL/UserForms');
		$attachedData = $model->getAttachedData($user_id);
		if ( ! $attachedData ) {
			return;
		}

		$notification = false;
		foreach ($attachedData as $form_id => $data) {
			$notification = $model->getFormCustomField($form_id, 'notification');
			break;
		}

		if ( $notification ) {
			$this->triggerNotifications($user_id, array(
				'notification' => $notification,
				'form_id' => $form_id,
				'post' => $user
			));
		}
		// keep up-to-date with notification settings for form and post field values
		$this->update( $user_id, $form_id );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	private static function hash($value) {
		// use simple crc-32 for speed and space issues,
		// not concerned with hash security here
		// http://php.net/manual/en/function.crc32.php
		$hash = hash("crc32b", $value);
		//return $key.'##'.$value;
		return $hash;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function doHash($data = array()) {
		if ( empty( $data ) ) {
			return array();
		}
		$hashes = array();
		foreach ($data as $key => $value) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = serialize( $value );
			}
			$hashes[$key] = $this->hash($value);
		}
		return $hashes;
	}

	/**
	 * Creates "serialized" string of hashed fields from a array of fields
	 *
	 * @param string $hashes
	 *
	 * @return string
	 */
	protected function fold($hashes) {
		$hash = array();
		foreach ($hashes as $key => $value) {
			$hash[] = $key . '##' . $value;
		}
		return implode('|', $hash);
	}

	/**
	 * Creates array of hashed values fields from a serialized hashed string
	 *
	 * @param $hash
	 *
	 * @return array
	 */
	protected function unfold($hash) {
		if ( empty( $hash ) || '' == $hash ) {
			return array();
		}
		$hasharray = explode('|', $hash);
		$undohash = array();
		foreach ($hasharray as $hash1) {
			$tmp = explode('##', $hash1);
			$undohash[$tmp[0]] = $tmp[1];
		}
		return $undohash;
	}

	/**
	 * @return object
	 */
	protected function getCurrentUserData() {
		$current_user = wp_get_current_user();

		$user_data = new stdClass;
		$user_data->ID = isset($current_user->ID) ? $current_user->ID : 0;
		$user_data->roles = isset($current_user->roles) ? $current_user->roles : array();
		$user_data->role = isset($current_user->roles[0]) ? $current_user->roles[0] : '';
		$user_data->login = isset($current_user->data->user_login) ? $current_user->data->user_login : '';
		$user_data->display_name = isset($current_user->data->display_name) ? $current_user->data->display_name : '';

		return $user_data;
	}

	/**
	 * Translate codes in notification fields of cred form (like %%POST_ID%% to post id etc..)
	 *
	 * @param array $field
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function replacePlaceholders($field, $data) {
		return str_replace(array_keys($data), array_values($data), $field);
	}

	/**
	 * @param int $form_id
	 * @param array $notification
	 *
	 * @return array
	 */
	public function sendTestNotification($form_id, $notification) {
		// bypass if nothing
		if ( ! $notification || empty( $notification ) ) {
			return array( 'error' => __( 'No Notification given', 'wp-cred' ) );
		}

		// dummy
		$post_id = null;
		// custom action hooks here, for 3rd-party integration
		$model = $this->get_model_by_form_id($form_id);

		// get Mailer
		$mailer = CRED_Loader::get('CLASS/Mail_Handler');

		// get current user
		$user = $this->getCurrentUserData();

		// get some data for placeholders
		$form_post = get_post($form_id);
		$form_title = ($form_post) ? $form_post->post_title : '';
		$date = date('Y-m-d H:i:s', current_time('timestamp'));


		/**
		 * Extend the notification subject placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_subject = apply_filters('cred_subject_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date
		), $form_id, $post_id);

		/**
		 * Extend the notification body placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_body = apply_filters('cred_body_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%POST_LINK%%' => 'DUMMY_POST_LINK',
			'%%POST_ADMIN_LINK%%' => 'DUMMY_ADMIN_POST_LINK',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date
		), $form_id, $post_id);

		// reset mail handler
		$mailer->reset();
		$mailer->setHTML(true, false);
		$recipients = array();

		// parse Notification Fields
		if ( ! isset( $notification['to']['type'] ) ) {
			$notification['to']['type'] = array();
		}
		if ( ! is_array( $notification['to']['type'] ) ) {
			$notification['to']['type'] = (array) $notification['to']['type'];
		}

		// notification to specific recipients
		if ( in_array( 'specific_mail', $notification['to']['type'] )
			&& isset( $notification['to']['specific_mail']['address'] )
		) {
			$tmp = explode(',', $notification['to']['specific_mail']['address']);
			foreach ($tmp as $aa)
				$recipients[] = array(
					'address' => $aa,
					'to' => false,
					'name' => false,
					'lastname' => false
				);
			unset($tmp);
		}

		// add custom recipients by 3rd-party
		if ( ! $recipients
			|| empty( $recipients )
		) {
			return array( 'error' => __( 'No recipients specified', 'wp-cred' ) );
		}

		// build recipients
		foreach ($recipients as $ii => $recipient) {
			// nowhere to send, bypass
			if (!isset($recipient['address']) || !$recipient['address']) {
				unset($recipients[$ii]);
				continue;
			}

			if (false === $recipient['to']) {
				// this is already formatted
				$recipients[$ii] = $recipient['address'];
				continue;
			}

			$tmp = '';
			$tmp.=$recipient['to'] . ': ';
			$tmp2 = array();
			if ( $recipient['name'] ) {
				$tmp2[] = $recipient['name'];
			}
			if ( $recipient['lastname'] ) {
				$tmp2[] = $recipient['lastname'];
			}
			if ( ! empty( $tmp2 ) ) {
				$tmp .= implode( ' ', $tmp2 ) . ' <' . $recipient['address'] . '>';
			} else {
				$tmp .= $recipient['address'];
			}

			$recipients[$ii] = $tmp;
		}
		$mailer->addRecipients($recipients);

		// build SUBJECT
		$_subj = '';
		if ( isset( $notification['mail']['subject'] ) ) {
			$_subj = $notification['mail']['subject'];
		}

		// provide WPML localisation
		if (isset($notification['_cred_icl_string_id']['subject'])) {
			$notification_subject_string_translation_name = $this->getNotification_translation_name($notification['_cred_icl_string_id']['subject']);
			if ($notification_subject_string_translation_name) {
				$_subj = cred_translate($notification_subject_string_translation_name, $_subj, 'cred-form-' . $form_title . '-' . $form_id);
			}
		}

		// replace placeholders
		$_subj = $this->replacePlaceholders($_subj, $data_subject);

		// parse shortcodes if necessary relative to $post_id
		$_subj = do_shortcode( stripslashes( $_subj ) );

		$mailer->setSubject($_subj);

		// build BODY
		$_bod = '';
		if ( isset( $notification['mail']['body'] ) ) {
			$_bod = $notification['mail']['body'];
		}

		// provide WPML localisation
		if (isset($notification['_cred_icl_string_id']['body'])) {
			$notification_body_string_translation_name = $this->getNotification_translation_name($notification['_cred_icl_string_id']['body']);
			if ($notification_body_string_translation_name) {
				$_bod = cred_translate($notification_body_string_translation_name, $_bod, 'cred-form-' . $form_title . '-' . $form_id);
			}
		}

		// replace placeholders
		$_bod = $this->replacePlaceholders($_bod, $data_body);

		// pseudo the_content filter
		$_bod = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $_bod );
		$_bod = stripslashes($_bod);

		$mailer->setBody($_bod);

		// build FROM address / name, independantly
		$_from = array();
		if ( isset( $notification['from']['address'] ) && ! empty( $notification['from']['address'] ) ) {
			$_from['address'] = $notification['from']['address'];
		}
		if ( isset( $notification['from']['name'] ) && ! empty( $notification['from']['name'] ) ) {
			$_from['name'] = $notification['from']['name'];
		}
		if ( ! empty( $_from ) ) {
			$mailer->setFrom( $_from );
		}

		// send it
		$_send_result = $mailer->send();

		// custom action hooks here, for 3rd-party integration
		//do_action('cred_after_send_notifications_'.$form_id, $post_id);
		//do_action('cred_after_send_notifications', $post_id);

		if ( ! $_send_result ) {
			if (empty($_bod)) {
				return array('error' => __('Body content is required', 'wp-cred'));
			} else {
				return array('error' => __('Mail failed to be sent', 'wp-cred'));
			}
		}
		return array('success' => __('Mail sent succesfully', 'wp-cred'));
	}

	/**
	 * @param $post_id
	 * @param $form_id
	 * @param $notificationsToSent
	 *
	 * @return bool
	 */
	public function sendNotifications($post_id, $form_id, $notificationsToSent) {
		// custom action hooks here, for 3rd-party integration
		// get Mailer
		$mailer = CRED_Loader::get('CLASS/Mail_Handler');

		$mailer->setFormId($form_id);
		$mailer->setPostId($post_id);

		// get current user
		$user = $this->getCurrentUserData();
		$is_user_form = $this->is_user_form( $form_id );

		// get Model
		$model = $this->get_model_by_form_type($is_user_form);

		//user created/updated
		$user_data = get_userdata($post_id);
		$the_user = ($is_user_form && isset($user_data->data)) ? $user_data->data : $user;
		if ( $is_user_form
			&& isset( $the_user )
		) {
			$the_user->nickname = get_user_meta($post_id, 'nickname', true);
		}

		// get some data for placeholders
		$form_post = get_post($form_id);
		$form_title = ($form_post) ? $form_post->post_title : '';
		$link = get_permalink($post_id);
		$title = get_the_title($post_id);
		$admin_edit_link = CRED_CRED::getPostAdminEditLink($post_id);

		$date = date('Y-m-d H:i:s', current_time('timestamp'));
		$reset_pass_link = '<a href="' . wp_lostpassword_url() . '" title="Lost Password">Lost Password</a>';
		$user_pass = isset($the_user->user_pass) ? $reset_pass_link : "";
		$username = isset($the_user->user_login) ? $the_user->user_login : "";
		$nickname = isset($the_user->nickname) ? $the_user->nickname : "";
		$billing_mail = // placeholder codes, allow to add custom
		$data_subject = apply_filters('cred_subject_notification_codes', array(
			'%%USER_USERID%%' => (isset($the_user) && isset($the_user->ID)) ? $the_user->ID : '',
			'%%USER_EMAIL%%' => (isset($the_user) && isset($the_user->user_email)) ? $the_user->user_email : '',
			'%%USER_USERNAME%%' => isset(CRED_StaticClass::$_username_generated) ? CRED_StaticClass::$_username_generated : $username,
			'%%USER_PASSWORD%%' => isset(CRED_StaticClass::$_password_generated) ? CRED_StaticClass::$_password_generated : $user_pass,
			'%%RESET_PASSWORD_LINK%%' => $reset_pass_link,
			'%%USER_NICKNAME%%' => isset(CRED_StaticClass::$_nickname_generated) ? CRED_StaticClass::$_nickname_generated : $nickname,
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => $post_id,
			'%%POST_TITLE%%' => $title,
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date,
		), $form_id, $post_id);

		// placeholder codes, allow to add custom
		$data_body = apply_filters('cred_body_notification_codes', array(
			'%%USER_USERID%%' => (isset($the_user) && isset($the_user->ID)) ? $the_user->ID : '',
			'%%USER_EMAIL%%' => (isset($the_user) && isset($the_user->user_email)) ? $the_user->user_email : '',
			'%%USER_USERNAME%%' => isset(CRED_StaticClass::$_username_generated) ? CRED_StaticClass::$_username_generated : $username,
			'%%USER_PASSWORD%%' => isset(CRED_StaticClass::$_password_generated) ? CRED_StaticClass::$_password_generated : $user_pass,
			'%%RESET_PASSWORD_LINK%%' => $reset_pass_link,
			'%%USER_NICKNAME%%' => isset(CRED_StaticClass::$_nickname_generated) ? CRED_StaticClass::$_nickname_generated : $nickname,
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%POST_ID%%' => $post_id,
			'%%POST_TITLE%%' => $title,
			'%%POST_LINK%%' => $link,
			'%%POST_ADMIN_LINK%%' => $admin_edit_link,
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date,
		), $form_id, $post_id);

		$send_notification_result = true;
		foreach ($notificationsToSent as $notification_counter => $notification) {

			$notification['notification_counter'] = $notification_counter;
			$notification['form_id'] = $form_id;
			$notification['post_id'] = $post_id;

			/*
			 * sendNotification could be called from different hooks (save_posts / updated_post_meta)
			 * checking notification_queue will avoid to send duplicated notifications
			 */
			$hashed_notification_value = hash( 'md5', serialize( $notification ) );
			if (in_array($hashed_notification_value, $this->notification_sent_record)) {
				continue;
			}
			$this->notification_sent_record[] = $hashed_notification_value;

			//Checks for old notification (back compatibility)
			$notification_name = isset($notification['name']) ? $notification['name'] : '';
			$mailer->setNotificationName($notification_name);
			$mailer->setNotificationNum($notification_counter);

			// bypass if nothing
			if (
				!$notification ||
				empty($notification) ||
				!(isset($notification['to']['type']) ||
					isset($notification['to']['author']))
			) {
				continue;
			}

			// reset mail handler
			$mailer->reset();
			$mailer->setHTML(true, false);
			$recipients = array();

			if ( isset( $notification['to']['author'] )
				&& 'author' == $notification['to']['author']
			) {
				$author_id = 0;
				$author_post_id = isset($_POST['form_' . $form_id . '_referrer_post_id']) ? $_POST['form_' . $form_id . '_referrer_post_id'] : 0;
				if (0 == $author_post_id && $post_id) {
					$mypost = get_post($post_id);
					$author_id = $mypost->post_author;
				} else {
					$mypost = get_post($author_post_id);
					$author_id = $mypost->post_author;
				}

				if ($author_id) {
					$_to_type = 'to';
					$user_info = get_userdata($author_id);

					$_addr_name = (isset($user_info) && isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
					$_addr_lastname = (isset($user_info) && isset($user_info->user_lasttname) && !empty($user_info->user_lasttname)) ? $user_info->user_lastname : false;
					$_addr = $user_info->user_email;

					if (isset($_addr)) {
						$recipients[] = array(
							'to' => $_to_type,
							'address' => $_addr,
							'name' => $_addr_name,
							'lastname' => $_addr_lastname
						);
					}
				}
			}

			// parse Notification Fields
			if ( ! isset( $notification['to']['type'] ) ) {
				$notification['to']['type'] = array();
			}
			if ( ! is_array( $notification['to']['type'] ) ) {
				$notification['to']['type'] = (array) $notification['to']['type'];
			}

			// notification to a mail field (which is saved as post meta)
			if (
				in_array('mail_field', $notification['to']['type']) &&
				isset($notification['to']['mail_field']['address_field']) &&
				!empty($notification['to']['mail_field']['address_field'])
			) {
				$_to_type = 'to';
				$_addr = false;
				$_addr_name = false;
				$_addr_lastname = false;

				if ( $is_user_form ) {
					$_addr = isset( $the_user ) && isset( $the_user->user_email ) ? $the_user->user_email : "";
				} else {
					$_addr = $model->getPostMeta( $post_id, $notification['to']['mail_field']['address_field'] );
				}

				if (
					isset( $notification['to']['mail_field']['to_type'] )
					&& in_array( $notification['to']['mail_field']['to_type'], array( 'to', 'cc', 'bcc' ) )
				) {
					$_to_type = $notification['to']['mail_field']['to_type'];
				}

				if (
					isset( $notification['to']['mail_field']['name_field'] )
					&& ! empty( $notification['to']['mail_field']['name_field'] )
					&& '###none###' != $notification['to']['mail_field']['name_field']
				) {
					$_addr_name = $is_user_form ? $model->getUserMeta($post_id, $notification['to']['mail_field']['name_field']) : $model->getPostMeta($post_id, $notification['to']['mail_field']['name_field']);
				}

				if (
					isset( $notification['to']['mail_field']['lastname_field'] )
					&& ! empty( $notification['to']['mail_field']['lastname_field'] )
					&& '###none###' != $notification['to']['mail_field']['lastname_field']
				) {
					$_addr_lastname = $is_user_form ? $model->getUserMeta($post_id, $notification['to']['mail_field']['lastname_field']) : $model->getPostMeta($post_id, $notification['to']['mail_field']['lastname_field']);
				}

				// add to recipients
				$recipients[] = array(
					'to' => $_to_type,
					'address' => $_addr,
					'name' => $_addr_name,
					'lastname' => $_addr_lastname
				);
			}

			// notification to an exisiting wp user
			if (in_array('wp_user', $notification['to']['type'])) {
				$_to_type = 'to';
				$_addr = false;
				$_addr_name = false;
				$_addr_lastname = false;

				if (
					isset( $notification['to']['wp_user']['to_type'] )
					&& in_array( $notification['to']['wp_user']['to_type'], array( 'to', 'cc', 'bcc' ) )
				) {
					$_to_type = $notification['to']['wp_user']['to_type'];
				}

				$_addr = $notification['to']['wp_user']['user'];
				$user_id = email_exists($_addr);
				if ($user_id) {
					$user_info = get_userdata($user_id);
					$_addr_name = (isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
					$_addr_lastname = (isset($user_info->user_lastname) && !empty($user_info->user_lastname)) ? $user_info->user_lastname : false;

					// add to recipients
					$recipients[] = array(
						'to' => $_to_type,
						'address' => $_addr,
						'name' => $_addr_name,
						'lastname' => $_addr_lastname
					);
				}
			}

			// notification to an exisiting wp user
			if (in_array('user_id_field', $notification['to']['type'])) {
				$_to_type = 'to';
				$_addr = false;
				$_addr_name = false;
				$_addr_lastname = false;

				if (
					isset($notification['to']['user_id_field']['to_type']) &&
					in_array($notification['to']['user_id_field']['to_type'], array('to', 'cc', 'bcc'))
				) {
					$_to_type = $notification['to']['user_id_field']['to_type'];
				}

				//$user_id = $is_user_form ? @trim($model->getUserMeta($post_id, $notification['to']['user_id_field']['field_name'])) : @trim($model->getPostMeta($post_id, $notification['to']['user_id_field']['field_name']));
				$user_id = $is_user_form ? $post_id : @trim($model->getPostMeta($post_id, $notification['to']['user_id_field']['field_name']));
				if ($user_id) {
					$user_info = get_userdata($user_id);
					if ($user_info) {
						$_addr = (isset($user_info->user_email) && !empty($user_info->user_email)) ? $user_info->user_email : false;
						$_addr_name = (isset($user_info->user_firstname) && !empty($user_info->user_firstname)) ? $user_info->user_firstname : false;
						$_addr_lastname = (isset($user_info->user_lasttname) && !empty($user_info->user_lasttname)) ? $user_info->user_lastname : false;

						// add to recipients
						$recipients[] = array(
							'to' => $_to_type,
							'address' => $_addr,
							'name' => $_addr_name,
							'lastname' => $_addr_lastname
						);
					}
				}
			}

			// notification to specific recipients
			if ( in_array( 'specific_mail', $notification['to']['type'] )
				&& isset( $notification['to']['specific_mail']['address'] )
			) {
				$recipient_email_addresses = explode(',', $notification['to']['specific_mail']['address']);
				foreach ($recipient_email_addresses as $aa)
					$recipients[] = array(
						'address' => $aa,
						'to' => false,
						'name' => false,
						'lastname' => false
					);
				unset($recipient_email_addresses);
			}

			// add custom recipients by 3rd-party
			$recipients = apply_filters('cred_notification_recipients', $recipients, $notification, $form_id, $post_id);
			if ( ! $recipients
				|| empty( $recipients )
			) {
				continue;
			}

			// build recipients
			foreach ($recipients as $ii => $recipient) {
				// nowhere to send, bypass
				if ( ! isset( $recipient['address'] )
					|| ! $recipient['address']
				) {
					unset($recipients[$ii]);
					continue;
				}

				if (false === $recipient['to']) {
					// this is already formatted
					$recipients[$ii] = $recipient['address'];
					continue;
				}

				$recipient_email_addresses = '';
				$recipient_email_addresses .= $recipient['to'] . ': ';
				$recipient_array = array();
				if ( $recipient['name'] ) {
					$recipient_array[] = $recipient['name'];
				}
				if ( $recipient['lastname'] ) {
					$recipient_array[] = $recipient['lastname'];
				}
				if ( ! empty( $recipient_array ) ) {
					$recipient_email_addresses .= implode( ' ', $recipient_array ) . ' <' . $recipient['address'] . '>';
				} else {
					$recipient_email_addresses .= $recipient['address'];
				}

				$recipients[$ii] = $recipient_email_addresses;
			}

			$mailer->addRecipients($recipients);

			if ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) {
				$notification['mail']['body'] = str_replace( "[cred-container-id]", CRED_StaticClass::$_cred_container_id, $notification['mail']['body'] );
			}

			global $post;
			$oldpost = null;
			if ($post) {
				$oldpost = clone $post;
				$post = get_post($post_id);
			}

			global $current_user_id;
			if ( isset( $user_id ) ) {
				$current_user_id = $user_id;
			}
			if ( ! isset( $user_id ) && $is_user_form ) {
				$current_user_id = $post_id;
			}

			// build SUBJECT
			$_subj = '';
			if ( isset( $notification['mail']['subject'] ) ) {
				$_subj = $notification['mail']['subject'];
			}

			// build BODY
			$_bod = '';
			if ( isset( $notification['mail']['body'] ) ) {
				$_bod = $notification['mail']['body'];
			}

			$mail_subject = CRED_StaticClass::unesc_meta_data($notification['mail']['subject']);
			$mail_body = CRED_StaticClass::unesc_meta_data($notification['mail']['body']);

			$hashSubject = CRED_Helper::strHash("notification-subject-" . $form_id . "-" . $ii);
			$hashBody = CRED_Helper::strHash("notification-body-" . $form_id . "-" . $ii);

			$form = get_post($form_id);
			$prefix = $is_user_form ? 'cred-user-form-' : 'cred-form-';
			$context = $prefix . $form->post_title . '-' . $form_id;

			$_subj = cred_translate('CRED Notification Subject ' . $hashSubject, $mail_subject, $context);
			$_bod = cred_translate('CRED Notification Body ' . $hashBody, $mail_body, $context);

			// replace placeholders
			$_subj = $this->replacePlaceholders($_subj, $data_subject);

			// replace placeholders
			$_bod = $this->replacePlaceholders($_bod, $data_body);

			// parse shortcodes if necessary relative to $post_id
			$_subj = do_shortcode( stripslashes( $_subj ) );

			$mailer->setSubject($_subj);

			// pseudo the_content filter
			$_bod = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $_bod );
			$_bod = stripslashes( $_bod );

			$mailer->setBody($_bod);

			// build FROM address / name, independantly
			$_from = array();
			if ( isset( $notification['from']['address'] )
				&& ! empty( $notification['from']['address'] )
			) {
				$_from['address'] = $notification['from']['address'];
			}
			if ( isset( $notification['from']['name'] )
				&& ! empty( $notification['from']['name'] )
			) {
				$_from['name'] = $notification['from']['name'];
			}
			if ( ! empty( $_from ) ) {
				$mailer->setFrom( $_from );
			}

			// send it
			$_send_result = $mailer->send();

			if (isset($oldpost)) {
				$post = clone $oldpost;
				unset($oldpost);
			}

			if ($_send_result !== true) {
				update_option('_' . $form_id . '_last_mail_error', $_send_result);
			}

			$send_notification_result = $send_notification_result && $_send_result;
		}

		// custom action hooks here, for 3rd-party integration
		do_action('cred_after_send_notifications', $post_id);

		return $send_notification_result;
	}

	/**
	 * Retrieve string translation name of the notification based on string ID (icl string id)
	 *
	 * @param int $id
	 *
	 * @return bool|null|string
	 */
	protected function getNotification_translation_name($id) {
		if (function_exists('icl_t')) {
			global $wpdb;
			$dBtable = $wpdb->prefix . "icl_strings";
			$string_translation_name_notifications = $wpdb->get_var($wpdb->prepare("SELECT name FROM $dBtable WHERE id=%d", $id));

			if ($string_translation_name_notifications) {
				return $string_translation_name_notifications;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

}
