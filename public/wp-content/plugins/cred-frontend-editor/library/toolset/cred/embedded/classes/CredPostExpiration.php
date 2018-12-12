<?php
define( 'CRED_PE_SCRIPT_URL', CRED_ASSETS_URL . '/js/' );
define( 'CRED_PE_IMAGE_URL', CRED_ASSETS_URL . '/images/' );

class CRED_PostExpiration {

	private $_post_expiration_enabled = false;
	private $_credmodel = null;
	private $_prefix = '_cred_';
	private $_form_settings = 'form_settings';
	private $_form_post_expiration = 'post_expiration';
	private $_form_notifications = 'notification';
	private $_settings_defaults = array(
		'enable'          => 0,
		'action'          => array(
			'post_status'    => '',
			'custom_actions' => array()
		),
		'expiration_time' => array(
			'expiration_date'   => 0,
			'expiration_period' => "minutes"
		)
	);
	private $_action_post_status = array();
	private $_extra_notification_codes = array();

	private $_post_expiration_slug = 'cred-post-expiration';
	private $_post_expiration_time_field = '_cred_post_expiration_time';
	private $_post_expiration_action_field = '_cred_post_expiration_action';
	private $_post_expiration_notifications_field = '_cred_post_expiration_notifications';
	private $_cron_settings_option = 'cred_post_expiration_settings';
	private $_cron_schedule_event_name = 'cred_post_expiration_event';
	private $_post_expiration_custom_actions_filter_name = 'cred_post_expiration_custom_actions';
	private $_shortcodes = array();

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	function __construct() {
		

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cred_pe_scripts' ) );
		add_action( 'cred_pe_general_settings', array( $this, 'cred_pe_general_settings' ) );
		add_filter( 'cred_pe_general_settings_save', array( $this, 'cred_pe_general_settings_save' ), 10, 2 );
		add_action( 'cred_admin_notification_notify_event_options_before', array(
			$this,
			'cred_pe_add_notification_option'
		), 10, 3 );
		add_action( 'cred_settings_action', array( $this, 'cred_settings_action' ), 10, 2 );
		add_action( 'cred_admin_save_form', array( $this, 'cred_admin_save_form' ), 10, 2 );
		add_action( 'cred_save_data', array( $this, 'cred_save_data' ), 10, 2 );
		add_action( $this->_cron_schedule_event_name, array( $this, 'cred_pe_schedule_event_action' ), 11 );
		add_action( $this->_cron_schedule_event_name, array( $this, 'cred_pe_schedule_event_notifications' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'cred_pe_add_post_meta_box' ) );
		add_action( 'save_post', array( $this, 'cred_pe_post_save' ) );
		add_filter( 'cred_ext_general_settings_options', array( $this, 'cred_pe_general_settings_options' ) );

		add_action( 'cred_ext_cred_post_form_settings', array( $this, 'cred_pe_form_setting' ), 10, 2 );

		add_filter( 'cred_admin_notification_subject_codes', array( $this, 'addExtraNotificationCodes' ), 10, 4 );
		add_filter( 'cred_admin_notification_body_codes', array( $this, 'addExtraNotificationCodes' ), 10, 4 );
		//add specific placeholders to CRED notification mail subject and body
		add_filter( 'cred_subject_notification_codes', array( $this, 'extraSubjectNotificationCodes' ), 10, 3 );
		add_filter( 'cred_body_notification_codes', array( $this, 'extraBodyNotificationCodes' ), 10, 3 );

		add_action( 'wp_ajax_cred_post_expiration_date', array( $this, 'cred_post_expiration_date' ) );
		add_action( 'wp_ajax_nopriv_cred_post_expiration_date', array( $this, 'cred_post_expiration_date' ) );
		add_filter( 'cron_schedules', array( $this, 'cred_pe_add_cron_schedules' ), 10, 1 );
		// shortcodes
		$this->_shortcodes['cred-post-expiration'] = array( $this, 'cred_pe_shortcode_cred_post_expiration' );
		add_filter( 'wpv_custom_inner_shortcodes', array( $this, 'cred_pe_shortcodes' ) );
		add_filter( 'cred_modify_pe_settings_compatibility', array(
			$this,
			"modifyPESettingsForCompatibility"
		), 10, 1 );

		foreach ( $this->_shortcodes as $tag => $function ) {
			add_shortcode( $tag, $function );
		}
	}

	function init() {
		$this->_action_post_status = array(
			'original' => __( 'Keep original status', 'wp-cred' ),
			'draft'    => __( 'Draft', 'wp-cred' ),
			'pending'  => __( 'Pending Review', 'wp-cred' ),
			'private'  => __( 'Private', 'wp-cred' ),
			'publish'  => __( 'Published', 'wp-cred' ),
			'trash'    => __( 'Trash', 'wp-cred' ),
		);
		$this->_extra_notification_codes = array(
			'%%EXPIRATION_DATE%%' => __( 'Expiration Date', 'wp-cred' ),
		);

		/* get the settings option for auto expire date feature */
		$settings_model                 = CRED_Loader::get( 'MODEL/Settings' );
		$settings                       = $settings_model->getSettings();
		$this->_post_expiration_enabled = ( isset( $settings['enable_post_expiration'] ) ? $settings['enable_post_expiration'] : false );
		$this->cred_pe_setup_schedule();
		/* get the CRED Form model */
		$this->_credmodel = CRED_Loader::get( 'MODEL/Forms' );
		/* register our script. */
		wp_register_script( 'script-cred-post-expiration', CRED_PE_SCRIPT_URL . 'cred_post_expiration.js', array( 'jquery-ui-datepicker' ), '1.0.0', true );
	}

	/**
	 * @return string
	 */
	public function getLocalizationContext() {
		return 'wp-cred';
	}

	/**
	 * @return array
	 */
	public function getActionPostStatus() {
		return $this->_action_post_status;
	}

	/**
	 * enqueue scripts and styles
	 */
	function cred_pe_scripts() {
		if ( $this->_post_expiration_enabled ) {
			$screen_ids = array();
			$settings   = $this->getCredPESettings();
			if ( isset( $settings['post_expiration_post_types'] ) ) {
				$screen_ids = $settings['post_expiration_post_types'];
			}
			$screen_ids[] = 'cred-form';
			$screen       = get_current_screen();
			if ( isset( $screen->id ) && in_array( $screen->id, $screen_ids ) ) {
				//wp_enqueue_style('style-name', get_stylesheet_uri());
				wp_enqueue_script( 'script-cred-post-expiration' );
				$calendar_image = CRED_PE_IMAGE_URL . 'calendar.gif';
				$calendar_image = apply_filters( 'wptoolset_filter_wptoolset_calendar_image', $calendar_image );
				$date_format    = self::getDateFormat();
				$js_data        = array(
					'buttonImage'   => $calendar_image,
					'buttonText'    => __( 'Select date', 'wp-cred' ),
					'dateFormatPhp' => $date_format,
					'yearMin'       => (int) self::timetodate( Toolset_Date_Utils::TIMESTAMP_LOWER_BOUNDARY, 'Y' ) + 1,
					'yearMax'       => (int) self::timetodate( Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY, 'Y' ),
					'ajaxurl'       => admin_url( 'admin-ajax.php', null )
				);
				wp_localize_script( 'script-cred-post-expiration', 'CREDExpirationScript', $js_data );
			}
		}
	}

	/**
	 * @param $timestamp
	 * @param null $format
	 *
	 * @return bool|string
	 */
	public static function timetodate( $timestamp, $format = null ) {
		if ( is_null( $format ) ) {
			$format = self::getDateFormat();
		}

		return self::_isTimestampInRange( $timestamp ) ? @adodb_date( $format, $timestamp ) : false;
	}

	public function cred_post_expiration_date() {
		$date_format = $_POST[ 'date-format' ];
		if ( $date_format == '' ) {
			$date_format = get_option( 'date_format' );
		}
		$date = $_POST[ 'date' ];
		$date = adodb_mktime( 0, 0, 0, substr( $date, 2, 2 ), substr( $date, 0, 2 ), substr( $date, 4, 4 ) );
		$date_format = str_replace( '\\\\', '\\', $date_format );
		echo json_encode( array( 'display' => adodb_date( $date_format, $date ), 'timestamp' => $date ) );
		die();
	}

	/**
	 * Perform CRED Settings save actions
     *
	 * @param $doaction
	 * @param $settings
	 */
	function cred_settings_action( $doaction, $settings ) {
		switch ( $doaction ) {
			case 'edit':
				if ( empty( $settings['enable_post_expiration'] ) ) {
					$this->deleteCredPESettings();
				} else {
					$settings = $this->getCredPESettings();
					if ( ! isset( $settings['post_expiration_cron']['schedule'] ) ) {
						$schedules = wp_get_schedules();
						foreach ( $schedules as $schedule => $schedule_definition ) {
							$settings['post_expiration_cron']['schedule'] = $schedule;
							$this->setCronSettings( $settings );
							break;
						}
					}
				}

				//New cred settings UI
				if ( $this->_post_expiration_enabled ) {
					$settings = isset( $_POST['settings'] ) ? (array) $_POST['settings'] : array();
					$settings = self::array_merge_distinct( $this->getCredPESettings(), $settings );
					$this->setCronSettings( $settings );
				} else {
					$this->deleteCredPESettings();
				}

				break;
			case 'cron':
				if ( $this->_post_expiration_enabled ) {
					$settings = isset( $_POST['settings'] ) ? (array) $_POST['settings'] : array();
					$settings = self::array_merge_distinct( $this->getCredPESettings(), $settings );
					$this->setCronSettings( $settings );
				} else {
					$this->deleteCredPESettings();
				}
				break;
		}
	}

	/**
	 * Perform extra CRED Form save actions
     *
	 * @param $post_id
	 * @param $post
	 */
	function cred_admin_save_form( $post_id, $post ) {
		if ( $this->_post_expiration_enabled ) {
			$form_expiration_settings = $this->_settings_defaults;
			if ( isset( $_POST['_cred_post_expiration'] ) ) {
				$form_expiration_settings = self::array_merge_distinct( $this->_settings_defaults, $_POST['_cred_post_expiration'] );
				$form_expiration_settings = cred_sanitize_array( $form_expiration_settings );
			}
			/* else {
			  $form_expiration_settings = $this->_settings_defaults;
			  } */
			$this->updateForm( $post_id, $form_expiration_settings );

			if ( $form_expiration_settings['enable'] ) {
				$settings = $this->getCredPESettings();
				if ( ! isset( $settings['post_expiration_post_types'] ) ) {
					$settings['post_expiration_post_types'] = array();
				}
				$form_settings = $this->getFormMeta( $post_id, $this->_form_settings );
				if ( isset( $form_settings->post['post_type'] ) ) {
					$settings['post_expiration_post_types'][] = $form_settings->post['post_type'];
					$settings['post_expiration_post_types']   = array_unique( $settings['post_expiration_post_types'] );
					$this->setCredPESettings( $settings );
				}
			}
		} else {

		}
	}

	/**
	 * Perform post expire actions when saving post
     *
	 * @param $post_id
	 * @param $form_data
	 */
	function cred_save_data( $post_id, $form_data ) {
		if ( $this->_post_expiration_enabled ) {
			$settings = $this->getFormMeta( $form_data['id'] );
			// only set expire time if post expire is enabled
			if ( isset( $settings['enable'] )
				&& $settings['enable'] ) {
				// expire time default is 0, that means no expiration
				$expire_time       = 0;
				$expiration_amount = 0;
				$expiration_period = 0;
				//Convert to integer value to make sure $expire_time is set correctly
				if ( isset( $settings['expiration_time']['expiration_date'] ) ) {
					$expiration_amount = intval( $settings['expiration_time']['expiration_date'] );
				}
				if ( isset( $settings['expiration_time']['expiration_period'] ) ) {
					$expiration_period = $settings['expiration_time']['expiration_period'];
				}
				if ( $expiration_period != null && $expiration_amount >= 0 ) {
					// calculate expiration time and get the corresponding timestamp
					$expire_time = strtotime( '+' . $expiration_amount . ' ' . $expiration_period );
				}

				update_post_meta( $post_id, $this->_post_expiration_time_field, $expire_time );
				// actions on expiration
				$settings['action']['custom_actions'] = isset( $settings['action']['custom_actions'] ) ? $settings['action']['custom_actions'] : array();
				$form                                 = get_post( $form_data['id'] );
				$form_slug                            = isset( $form->post_name ) ? $form->post_name : '';
				$settings['action']['custom_actions'] = apply_filters( $this->_post_expiration_custom_actions_filter_name . '_' . $form_slug, $settings['action']['custom_actions'], $post_id, $form_data );
				$settings['action']['custom_actions'] = apply_filters( $this->_post_expiration_custom_actions_filter_name . '_' . $form_data['id'], $settings['action']['custom_actions'], $post_id, $form_data );
				$settings['action']['custom_actions'] = apply_filters( $this->_post_expiration_custom_actions_filter_name, $settings['action']['custom_actions'], $post_id, $form_data );
				if ( ! is_array( $settings['action']['custom_actions'] ) ) {
					$settings['action']['custom_actions'] = array();
				}
				update_post_meta( $post_id, $this->_post_expiration_action_field, $settings['action'] );
				// check for notifications
				$notifications = $this->getFormMeta( $form_data['id'], $this->_form_notifications );
				if ( isset( $notifications->notifications ) ) {
					// get only 'expiration_date' notifications
					$aux_array = array();
					foreach ( $notifications->notifications as $notification ) {
						if ( 'expiration_date' == $notification['event']['type'] ) {
							$notification['form_id'] = $form_data['id'];
							$aux_array[]             = $notification;
						}
					}
					$notifications = $aux_array;

					update_post_meta( $post_id, $this->_post_expiration_notifications_field, $notifications );
				}
			}
		}
	}

	/**
	 * Insert Enable Automatic Expiration Date CRED settings option
     *
	 * @param $settings
	 */
	function cred_pe_general_settings( $settings ) {
		$this->_post_expiration_enabled = ( isset( $settings['enable_post_expiration'] ) ? $settings['enable_post_expiration'] : false );
		?>

        <label class='cred-label'>
            <input type="checkbox" autocomplete="off"
                   class='cred-checkbox-invalid js-cred-other-setting js-cred-other-setting-enable-post-expiration'
                   name="cred_enable_post_expiration"
                   value="1" <?php if ( isset( $settings['enable_post_expiration'] ) && $settings['enable_post_expiration'] ) {
				echo "checked='checked'";
			} ?> />
            <span class='cred-checkbox-replace'></span>
			<span><?php 
			_e( 'Enable the ability to set an automatic expiration date for posts created or edited with a form.', 'wp-cred' );
			echo CRED_STRING_SPACE;
			$documentation_link_args = array(
				'utm_source'	=> 'credplugin',
				'utm_campaign'	=> 'cred',
				'utm_medium'	=> 'post-expiration-settings',
				'utm_term'		=> 'Check our documentation'
			);
			$documentation_link = add_query_arg( $documentation_link_args, CRED_DOC_LINK_AUTOMATIC_POST_EXPIRATION );
			echo sprintf(
				'<a href="%1$s" title="%2$s" target="_blank">%3$s %4$s</a>.',
				esc_url( $documentation_link ),
				esc_attr( __( 'Check our documentation', 'wp-cred' ) ),
				__( 'Check our documentation', 'wp-cred' ),
				'<i class="fa fa-external-link"></i>'
			);
			?></span>
        </label>
        <div class="js-cred-other-setting-enable-post-expiration-extra" style="margin:10px 0 0 25px;<?php
		if ( ! $this->_post_expiration_enabled ) {
			echo 'display:none;';
		}
		?>">
            <div class="toolset-advanced-setting">
				<?php
				$settings          = $this->getCredPESettings();
				$settings_defaults = $this->_settings_defaults;
				echo CRED_Loader::tpl( 'pe_settings_meta_box', array(
					'cred_post_expiration' => $this,
					'settings'             => $settings,
					'settings_defaults'    => $settings_defaults,
					'field_name'           => $this->_prefix . $this->_form_post_expiration
				) );
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * @param $settings
	 * @param $posted_settings
	 *
	 * @return mixed
	 */
	public function cred_pe_general_settings_save( $settings, $posted_settings ) {

		if ( isset( $posted_settings['cred_enable_post_expiration'] ) ) {
			$settings['enable_post_expiration'] = $posted_settings['cred_enable_post_expiration'];
		} else {
			$settings['enable_post_expiration'] = 0;
		}

		$pe_settings = $this->getCredPESettings();
		if ( isset( $posted_settings['cred_post_expiration_cron_schedule'] ) ) {
			$pe_settings['post_expiration_cron']['schedule'] = $posted_settings['cred_post_expiration_cron_schedule'];
		} else {
			$pe_settings['post_expiration_cron']['schedule'] = '';
		}
		$this->setCredPESettings( $pe_settings );

		return $settings;
	}

	/**
	 * Set Enable Automatic Expire Date CRED settings option default
     *
	 * @param $defaults
	 *
	 * @return mixed
	 */
	public function cred_pe_general_settings_options( $defaults ) {
		$defaults['enable_post_expiration'] = 0;

		return $defaults;
	}

	/**
	 * @param $form
	 * @param $settings
	 *
	 * @return string|void
	 */
	public function cred_pe_form_setting( $form, $settings ) {
		if ( $this->_post_expiration_enabled ) {
			return $this->addCredPostExpireFormSettings( $form, $settings );
		}

		return '';
	}

	/**
	 * @param $form
	 * @param $settings
	 */
	public function addCredPostExpireFormSettings( $form, $settings ) {
		$form_settings = $this->getFormMeta( $form->ID );
		$form_settings = is_array( $form_settings ) ? $form_settings : array();
		$settings_defaults = $this->_settings_defaults;

		$form_settings = apply_filters( "cred_modify_pe_settings_compatibility", $form_settings );
		$form_settings['expiration_time']['expiration_period'] = toolset_getnest( $form_settings, array( 'expiration_time', 'expiration_period' ), 'days' );
		$form_settings = CRED_PostExpiration::array_merge_distinct( $settings_defaults, $form_settings );

		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$context = array(
			'cred_post_expiration' => $this,
			'settings'             => $form_settings,
			'field_name'           => $this->_prefix . $this->_form_post_expiration
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::SETTINGS_POST_EXPIRATION ),
			$context
		);
	}

	/**
	 * Get CRED PE Settings
	 */
	public function getCredPESettings() {
		return apply_filters( "cred_modify_pe_settings_compatibility", get_option( $this->_cron_settings_option, array() ) );
	}

	/**
	 * Set CRED PE Settings
     *
	 * @param $settings
	 *
	 * @return bool
	 */
	public function setCredPESettings( $settings ) {
		return update_option( $this->_cron_settings_option, $settings );
	}

	/**
	 * get CRED PE Settings
	 */
	public function deleteCredPESettings() {
		delete_option( $this->_cron_settings_option );
	}

	/**
	 * Get CRED PE Settings
     *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function modifyPESettingsForCompatibility( $settings ) {
		if ( isset( $settings['expiration_time']['weeks'] ) && $settings['expiration_time']['days'] ) {
			$hours_in_weeks                                   = ( $settings['expiration_time']['weeks'] * 7 ) * 24;
			$hours_in_days                                    = $settings['expiration_time']['days'] * 24;
			$settings['expiration_time']["expiration_date"]   = $hours_in_days + $hours_in_weeks;
			$settings["expiration_time"]["expiration_period"] = "hours";
		}

		return $settings;
	}

	/**
	 * Set CRED Settings cron
     *
	 * @param $settings
	 *
	 * @return bool
	 */
	public function setCronSettings( $settings ) {
		$settings = cred_sanitize_array( $settings );
		$result   = $this->setCredPESettings( $settings );
		$this->cred_pe_setup_schedule();

		return $result;
	}

	/**
	 * get CRED Form post_meta
	 */
	protected function getFormMeta( $form_id, $meta = '' ) {
		if ( $this->_credmodel ) {
			if ( empty( $meta ) ) {
				$meta = $this->_form_post_expiration;
			}

			return $this->_credmodel->getPostMeta( $form_id, $this->_prefix . $meta );
		}
	}

	/**
	 * set CRED Form post_meta
	 */
	protected function updateForm( $form_id, $data, $meta = '' ) {
		if ( $this->_credmodel ) {
			if ( empty( $meta ) ) {
				$meta = $this->_form_post_expiration;
			}
			$this->_credmodel->updateFormCustomField( $form_id, $meta, $data );
		}
	}

	/**
	 * Add custom schedules of cron in wordpress
     *
	 * @param $schedules
	 *
	 * @return mixed|void
	 */
	function cred_pe_add_cron_schedules( $schedules ) {
		/* default array for schedules of cron in Wordpress
		  $schedules = array(
		  'hourly'     => array( 'interval' => HOUR_IN_SECONDS,      'display' => __( 'Once Hourly' ) ),
		  'twicedaily' => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily' ) ),
		  'daily'      => array( 'interval' => DAY_IN_SECONDS,       'display' => __( 'Once Daily' ) ),
		  );
		  $schedules['fifteenmin'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => __( 'Every 15 minutes' ) );
		 */
		$schedules = apply_filters( 'cred_post_expiration_cron_schedules', $schedules );

		return $schedules;
	}

	/**
	 * Enable/Disable the schedule task for CRED post expiration
	 */
	function cred_pe_setup_schedule() {
		if ( $this->_post_expiration_enabled ) {
			$schedule = wp_get_schedule( $this->_cron_schedule_event_name );
			$settings = $this->getCredPESettings();
			if ( isset( $settings['post_expiration_cron']['schedule'] ) ) {
				if ( $schedule != $settings['post_expiration_cron']['schedule'] ) {
					wp_clear_scheduled_hook( $this->_cron_schedule_event_name );
					wp_schedule_event( time(), $settings['post_expiration_cron']['schedule'], $this->_cron_schedule_event_name );
				}
			} else {
				wp_clear_scheduled_hook( $this->_cron_schedule_event_name );
			}
		} else {
			wp_clear_scheduled_hook( $this->_cron_schedule_event_name );
		}
	}

	/**
	 * Action for the CRED post expiration scheduled task
	 *
	 * @since 1.9.3 Make sure that we only expire posts with an expiration date different than 0
	 * @since 1.9.3 While expiring posts, disable any further CRED notification to avoid false positives
	 *        when checking custom fields or post status conditions.
	 */
	function cred_pe_schedule_event_action() {
		if ( $this->_post_expiration_enabled ) {
			global $wpdb;
			$posts_expired = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m1.post_id, m2.meta_value AS action
						FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
						ON m1.post_id = m2.post_id 
						AND m1.meta_key = %s 
						AND m2.meta_key = %s
						WHERE m1.meta_value != 0 
						AND m1.meta_value < %d",
					array(
						$this->_post_expiration_time_field,
						$this->_post_expiration_action_field,
						time()
					)
				)
			);
			if ( empty( $posts_expired ) ) {
				return;
			}
			$posts_expired_ids = array();
			// Disable CRED notifications
			$cred_notification_manager = CRED_Notification_Manager_Utils::get_instance();
			$cred_notification_manager->remove_hooks();
			foreach ( $posts_expired as $post_meta ) {
				$posts_expired_ids[] = $post_meta->post_id;
				$post_meta->action   = maybe_unserialize( $post_meta->action );
				if ( isset( $post_meta->action['post_status'] ) && 'original' != $post_meta->action['post_status'] ) {
					if ( 'trash' == $post_meta->action['post_status'] ) {
						wp_trash_post( $post_meta->post_id );
					} else {
						$update_post = get_post( $post_meta->post_id, ARRAY_A );
						if ( isset( $update_post['ID'] ) ) {
							$update_post['post_status'] = $post_meta->action['post_status'];
							wp_insert_post( $update_post );
						}
					}
				}
				// run custom actions
				foreach ( $post_meta->action['custom_actions'] as $action ) {
					if ( ! empty( $action['meta_key'] ) ) {
						update_post_meta( $post_meta->post_id, $action['meta_key'], isset( $action['meta_value'] ) ? $action['meta_value'] : '' );
					}
				}
			}
			// Restore CRED notifications
			$cred_notification_manager->add_hooks();
			if ( ! empty( $posts_expired_ids ) ) {
				$posts_expired_ids = implode( ',', $posts_expired_ids );
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->postmeta
						SET meta_value = 0
						WHERE post_id IN ({$posts_expired_ids}) AND meta_key = %s",
						$this->_post_expiration_time_field
					)
				);
			}
		}
	}

	/**
	 * notifications for the CRED post expiration scheduled task
	 *
	 * @since 1.9.3 Make sure we only send expiration notifications for posts with an expiration date different than 0
	 */
	function cred_pe_schedule_event_notifications() {
		if ( $this->_post_expiration_enabled ) {
			global $wpdb;
			$posts_for_notifications = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m1.post_id, m1.meta_value AS expiration_time, m2.meta_value AS notifications
						FROM $wpdb->postmeta m1 INNER JOIN $wpdb->postmeta m2
						ON m1.post_id = m2.post_id 
						AND m1.meta_key = %s 
						AND m2.meta_key = %s
						WHERE m1.meta_value != 0
						AND m1.meta_value IS NOT NULL",
					array(
						$this->_post_expiration_time_field,
						$this->_post_expiration_notifications_field
					)
				)
			);
			$now                         = time();
			$posts_ids_for_notifications = array();
			foreach ( $posts_for_notifications as $post_meta ) {
				$post_meta->notifications = $remaining_notifications = maybe_unserialize( $post_meta->notifications );
				// check wicth notification is to be activated
				foreach ( $post_meta->notifications as $key => $notification ) {
					//Check if expiration_period index exists, for backward compatibility with versions prior to 1.9
					if ( isset( $notification['event']['expiration_period'] ) ) {
						$notification_time = $post_meta->expiration_time - ( $notification['event']['expiration_date'] * $notification['event']['expiration_period'] );
					} else {
						$notification_time = $post_meta->expiration_time - $notification['event']['expiration_date'] * DAY_IN_SECONDS;
					}

					if ( $notification_time <= $now ) {
						// notify
						$posts_ids_for_notifications[] = $post_meta->post_id;
						$form_id                       = isset( $notification['form_id'] ) ? $notification['form_id'] : null;
						// add extra plceholder codes
						add_filter( 'cred_subject_notification_codes', array( $this, 'extraSubjectNotificationCodes' ), 5, 3 );
						add_filter( 'cred_body_notification_codes', array( $this, 'extraBodyNotificationCodes' ), 5, 3 );
						// send notification now
						$notification_manager = CRED_Notification_Manager_Post::get_instance();
						$notification_manager->trigger_expiration_notifications( $post_meta->post_id, $form_id, array( $notification ) );
						// remove extra plceholder codes
						remove_filter( 'cred_subject_notification_codes', array( &$this, 'extraSubjectNotificationCodes' ), 5, 3 );
						remove_filter( 'cred_body_notification_codes', array( &$this, 'extraBodyNotificationCodes' ), 5, 3 );
						// remove notification from list
						unset( $remaining_notifications[ $key ] );
					}
				}
				// update notifications list
				if ( empty( $remaining_notifications ) ) {
					delete_post_meta( $post_meta->post_id, $this->_post_expiration_notifications_field );
				} else {
					sort( $remaining_notifications );
					update_post_meta( $post_meta->post_id, $this->_post_expiration_notifications_field, $remaining_notifications );
				}
			}
		}
	}

	/**
	 * Adds the meta box container.
     *
	 * @param $post_type
	 */
	public function cred_pe_add_post_meta_box( $post_type ) {
		if ( $this->_post_expiration_enabled ) {
			$settings = $this->getCredPESettings();
			if ( isset( $settings['post_expiration_post_types'] ) ) {
				$post_types = $settings['post_expiration_post_types'];
				if ( in_array( $post_type, $post_types ) ) {
					add_meta_box(
						'cred_post_expiration_meta_box'
						, __( 'Settings for Post Expiration Date', 'wp-cred' )
						, array( $this, 'cred_pe_render_meta_box_content' )
						, $post_type
						, 'side'
						, 'high'
					);
				}
			}
		}
	}

	/**
     * Save the meta when the post is saved.
     *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function cred_pe_post_save( $post_id ) {

		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['cred-post-expiration-nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['cred-post-expiration-nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'cred-post-expiration-date' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
		//     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		/* OK, its safe for us to save the data now. */
		if ( $this->_post_expiration_enabled && isset( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['date'] ) && ! empty( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['date'] ) ) {

			// Sanitize the user input.
			$enabled         = ( isset( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['enable'] ) && 1 == $_POST['cred_pe'][ $this->_post_expiration_time_field ]['enable'] );
			$expiration_time = sanitize_text_field( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['date'] );

			/**
			 * remove previously defined time
			 */

			$expiration_datetime = $this->get_gmt_date_by_time( $expiration_time );

			if ( ! $enabled || empty( $expiration_time ) ) {
				$expiration_time = 0;
			} else {
				$hours   = sanitize_text_field( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['hours'] );
				$minutes = sanitize_text_field( $_POST['cred_pe'][ $this->_post_expiration_time_field ]['minutes'] );
				$expiration_datetime->setTime( $hours, $minutes, 0 );
				$expiration_time = $expiration_datetime->getTimestamp();
			}

			// Update the meta field.
			// expire time default is 0, that means no expiration
			update_post_meta( $post_id, $this->_post_expiration_time_field, $expiration_time );

			$expiration_notifications = get_post_meta( $post_id, $this->_post_expiration_notifications_field );
			$updated_notifications    = array();
			foreach ( $expiration_notifications as $notifications ) {
				foreach ( $notifications as $notification ) {
					if (
						isset( $notification )
						&& isset( $notification["event"]["type"] )
						&& $notification["event"]["type"] == "expiration_date"
						&& $expiration_time > 0
					) {
						$notification["event"]["notification_timestamp"] = $expiration_time;
						$updated_notifications[] = $notification;
					}
				}
			}

			update_post_meta( $post_id, $this->_post_expiration_notifications_field, $updated_notifications );

			if (
				$expiration_time > 0
				&& self::_isTimestampInRange( $expiration_time )
			) {
				// save expiration action
				$post_status = array( 'post_status' => ( isset( $_POST['cred_pe'][ $this->_post_expiration_action_field ]['post_status'] ) ? $_POST['cred_pe'][ $this->_post_expiration_action_field ]['post_status'] : '' ) );
				$post_action = get_post_meta( $post_id, $this->_post_expiration_action_field, true );
				if ( ! is_array( $post_action ) ) {
					$post_action = array(
						'post_status'    => '',
						'custom_actions' => array()
					);
				}
				$post_action = self::array_merge_distinct( $post_action, $post_status );
				update_post_meta( $post_id, $this->_post_expiration_action_field, $post_action );
			} else {
				delete_post_meta( $post_id, $this->_post_expiration_action_field );
			}
		}
	}

	// We need to keep this for backwards compatibility
	// Note that this function will only convert dates coming on a string:
	// - in english
	// - inside the valid PHP date range
	// We are only using this when the value being checked is not a timestamp
	// And we have tried to avoid that situation from happening
	// But for old implementation, this happens for date conditions on conditional fields

	/**
	 * @param $value
	 * @param null $format
	 *
	 * @return bool|string
	 * @deprecated This doesn't seem to be used anywhere (and probably shouldn't be).
	 */
	public static function strtotime( $value, $format = null ) {
		if ( is_null( $format ) ) {
			$format = self::getDateFormat();
		}
		if ( strpos( $format, 'd/m/Y' ) !== false ) {
			// strtotime requires a dash or dot separator to determine dd/mm/yyyy format
			preg_match( '/\d{2}\/\d{2}\/\d{4}/', $value, $matches );
			if ( ! empty( $matches ) ) {
				foreach ( $matches as $match ) {
					$value = str_replace( $match, str_replace( '/', '-', $match ), $value );
				}
			}
		}
		try {
			$date = new DateTime( $value );
		} catch ( Exception $e ) {
			return false;
		}
		$timestamp = $date->format( "U" );

		return self::_isTimestampInRange( $timestamp ) ? $timestamp : false;
	}

	/**
	 * @return mixed|string|void
	 * @deprecated Use Toolset_Date_Utils::get_supported_date_format() instead.
	 */
	public static function getDateFormat() {
		$date_utils = Toolset_Date_Utils::get_instance();

		return $date_utils->get_supported_date_format();
	}

	/**
	 * @param int $timestamp
	 *
	 * @return bool
	 * @deprecated Use Toolset_Date_Utils::is_timestamp_in_range().
	 */
	public static function _isTimestampInRange( $timestamp ) {
		$date_utils = Toolset_Date_Utils::get_instance();

		return $date_utils->is_timestamp_in_range( $timestamp );
	}

	/**
     * Render Meta Box content.
     *
	 * @param WP_Post $post The post object.
	 */
	public function cred_pe_render_meta_box_content( $post ) {
		$post_expiration_time = get_post_meta( $post->ID, $this->_post_expiration_time_field, true );
		if ( empty( $post_expiration_time ) ) {
			$values = array(
				'date'    => '',
				'hours'   => 0,
				'minutes' => 0
			);
		} else {

		    $date_time = $this->get_gmt_date_by_time($post_expiration_time);

            $values['minutes'] = $date_time->format( 'i' );
			$values['hours']   = $date_time->format( 'H' );
			$values['date']    = $date_time->format( 'F d, Y' );
		}

		$post_expiration_action = get_post_meta( $post->ID, $this->_post_expiration_action_field, true );

		$post_expiration_action = toolset_ensarr($post_expiration_action);

		if ( ! isset( $post_expiration_action['post_status'] ) ) {
			$post_expiration_action['post_status'] = '';
		}

		echo CRED_Loader::tpl( 'pe_post_meta_box', array(
			'cred_post_expiration'   => $this,
			'post_expiration_time'   => $post_expiration_time,
			'post_expiration_action' => $post_expiration_action,
			'values'                 => $values,
			'post_expiration_slug'   => $this->_post_expiration_slug,
			'time_field_name'        => $this->_post_expiration_time_field,
			'action_field_name'      => $this->_post_expiration_action_field
		) );
	}

	/**
     * Add extra CRED Post Expiration notifications placeholders
     *
	 * @param $options
	 * @param $form
	 * @param $ii
	 * @param $notif
	 *
	 * @return array
	 */
	public function addExtraNotificationCodes( $options, $form, $ii, $notif ) {
		if ( $form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) {
			return $options;
		}
		$options = self::array_merge_distinct( $options, $this->_extra_notification_codes );

		return $options;
	}

	/**
     * Set specific CRED Form notifications placeholders to '' because this information is unavailable
     *
	 * @param array $codes
	 * @param int $form_id
	 * @param int|null $post_id
	 *
	 * @return mixed
     *
     * //TODO: move into new cred code and try to unify with similar function used in CRED_Form_Base::notify
	 */
	public function extraSubjectNotificationCodes( $codes, $form_id, $post_id ) {
		$extra_codes = array(
			'%%POST_PARENT_TITLE%%' => ''
		);
		foreach ( $extra_codes as $placeholder => $replace ) {
			if ( ! isset( $codes[ $placeholder ] ) ) {
				$codes[ $placeholder ] = $replace;
			}
		}
		$codes['%%EXPIRATION_DATE%%'] = '';
		if ( null !== $post_id ) {
			$post_expiration_time         = get_post_meta( $post_id, $this->_post_expiration_time_field, true );
			if ( self::_isTimestampInRange( $post_expiration_time ) ) {
				$format                       = get_option( 'date_format' );
				$codes['%%EXPIRATION_DATE%%'] = apply_filters( 'the_time', adodb_date( $format, $post_expiration_time ) );
			}
		}

		return $codes;
	}

	/**
     * Set specific CRED Form notifications placeholders to '' because this information is unavailable
     *
	 * @param array $codes
	 * @param int $form_id
	 * @param int|null $post_id
	 *
	 * @return mixed
     *
	 * //TODO: move into new cred code and try to unify with similar function used in CRED_Form_Base::notify
	 */
	public function extraBodyNotificationCodes( $codes, $form_id, $post_id ) {
		$extra_codes = array(
			'%%FORM_DATA%%' => isset( CRED_StaticClass::$out['notification_data'] ) ? CRED_StaticClass::$out['notification_data'] : '',
			'%%POST_PARENT_TITLE%%' => ( ! $post_id ) 
				? ''
				: $this->cred_pe_parent_info_by_post_id( $post_id, 'title' ),
			'%%POST_PARENT_LINK%%' => ( ! $post_id ) 
				? ''
				: $this->cred_pe_parent_info_by_post_id( $post_id, 'url' ),
			'%%CRED_NL%%' => "\r\n",
		);
		foreach ( $extra_codes as $placeholder => $replace ) {
			if ( ! isset( $codes[ $placeholder ] ) ) {
				$codes[ $placeholder ] = $replace;
			}
		}
		$codes['%%EXPIRATION_DATE%%'] = '';
		if ( null !== $post_id ) {
			$post_expiration_time = get_post_meta( $post_id, $this->_post_expiration_time_field, true );
			if ( self::_isTimestampInRange( $post_expiration_time ) ) {
				$format = get_option( 'date_format' );
				$codes['%%EXPIRATION_DATE%%'] = apply_filters( 'the_time', adodb_date( $format, $post_expiration_time ) );
			}
		}

		return $codes;
	}

	/**
     * Function used by subject/body placeholder parent data replacements
     *
	 * @param $post_id
	 * @param $get  {'url'|'title'|'id'}
	 *
	 * @return false|null|string
     *
     * @since 1.9.5
	 * //TODO: move into new cred code and try to unify with similar function used in Notification
	 */
	public function cred_pe_parent_info_by_post_id( $post_id, $get ) {
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return $this->get_migrated_pe_parent_info_by_post_id( $post_id, $get );
		} else {
			return $this->get_legacy_pe_parent_info_by_post_id( $post_id, $get );
		}
	}

	public function get_legacy_pe_parent_info_by_post_id( $post_id, $get ) {
		$post_type = get_post_type( $post_id );
		$cred_fields_types_utils = new CRED_Fields_Types_Utils();
		$parents = $cred_fields_types_utils->get_parent_fields( $post_type );

		if ( ! isset( $parents ) || empty( $parents ) ) {
			return '';
		}

		$parent_id = null;
		foreach ( $parents as $key => $parent ) {
			$parent_id = get_post_meta( $post_id, $key, true );
		}

		if ( $parent_id !== null && ! empty( $parent_id ) ) {
			switch ( $get ) {
				case 'title':
					return get_the_title( $parent_id );
				case 'url':
					return get_permalink( $parent_id );
				case 'id':
					return $parent_id;
				default:
					return '';
			}
		}

		return '';
	}

	public function get_migrated_pe_parent_info_by_post_id( $post_id, $get ) {
		do_action( 'toolset_do_m2m_full_init' );

		$post_type = get_post_type( $post_id );
		$association_query = new Toolset_Association_Query_V2();
		$associations = $association_query
			->add( $association_query->do_and( 
				$association_query->has_legacy_relationship( true ),
				$association_query->element_id_and_domain( $post_id, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() )
			) )
			->limit( 1 )
			->get_results();
		if ( empty( $associations ) ) {
			return '';
		}

		$parent_id = null;
		foreach( $associations as $legacy_association ) {
			$parent_id = $legacy_association->get_element_id( new Toolset_Relationship_Role_Parent() );
		}

		if ( $parent_id !== null ) {
			switch ( $get ) {
				case 'title':
					return get_the_title( $parent_id );
				case 'url':
					return get_permalink( $parent_id );
				case 'id':
					return $parent_id;
				default:
					return '';
			}
		}

		return '';
	}

	/**
     * Render CRED Form notification option for post expiration.
     *
	 * @param $form
	 * @param $options
	 * @param $notification
	 */
	public function cred_pe_add_notification_option( $form, $options, $notification ) {
		if ( ! $this->_post_expiration_enabled ) {
			return;
		}
		if ( $form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) {
			return;
		}
		list( $ii, $name, $type ) = $options;
		$notification = self::array_merge_distinct( array( 'event' => array( 'expiration_date' => 0 ) ), $notification );
		echo CRED_Loader::tpl( 'pe_form_notification_option', array(
			'cred_post_expiration' => $this,
			'notification'         => $notification,
			'ii'                   => $ii,
			'name'                 => $name,
			'type'                 => $type
		) );
	}

	/**
	 * our array_merge function
	 *
     */
	public static function array_merge_distinct( array $array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged [ $key ] = self::array_merge_distinct( $merged [ $key ], $value );
			} else {
				$merged [ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * cred-post-expiration-shortcode: cred-post-expiration
	 *
	 * Description: Display the expiration date/time of the current post
	 *
	 * Parameters:
	 * id => post ID, defaults to global $post->ID
	 * format => Format string for the date. Defaults to Wordpress settings option (date_format)
	 *
	 * Example usage:
	 * Expiration on [cred-post-expiration format="F jS, Y"]
	 *
	 * Link:
	 * Format parameter is the same as here: http://codex.wordpress.org/Formatting_Date_and_Time
	 *
	 */
	function cred_pe_shortcode_cred_post_expiration( $atts ) {
		extract(
			shortcode_atts( array(
				'id'     => '',
				'format' => get_option( 'date_format' )
			), $atts )
		);

		$out     = '';
		$post_id = $id;
		global $post;
		if ( empty( $post_id ) && isset( $post->ID ) ) {
			$post_id = $post->ID;
		}
		if ( ! empty( $post_id ) ) {
			$post_expiration_time = get_post_meta( $post_id, $this->_post_expiration_time_field, true );
			if ( self::_isTimestampInRange( $post_expiration_time ) ) {
				$out = apply_filters( 'the_time', adodb_date( $format, $post_expiration_time ) );
			}
		}

		return $out;
	}

	/**
	 * Filter the custom inner shortcodes array to add CRED post expiration shortcodes
	 *
	 * @param $shortcodes (array)
	 *
	 * @return $shortcodes
	 */
	function cred_pe_shortcodes( $shortcodes ) {
		foreach ( $this->_shortcodes as $tag => $function ) {
			$shortcodes[] = $tag;
		}

		return $shortcodes;
	}

	/**
     * Render datepicker like wp-types do it
     *
	 * @param $elements
	 *
	 * @return string|type
	 */
	function cred_pe_form_simple( $elements ) {
		static $form = null;
		if ( file_exists( CRED_CLASSES_PATH . '/CredPostExpiration_forms.php' ) ) {
			require_once( CRED_CLASSES_PATH . '/CredPostExpiration_forms.php' );
			if ( is_null( $form ) ) {
				$form = new CRED_PostExpiration_Form();
			}

			return $form->renderElements( $elements );
		}

		return '';
	}

	/**
     * Return the correct local DateTime time from Timestamp time
     *
	 * @param int $time
	 *
	 * @return DateTime
	 */
	private function get_gmt_date_by_time( $time ) {
		$get_gmt_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $time ), 'Y-m-d H:i:s' );

		return new DateTime( $get_gmt_date );
	}


	/**
	 * @param int $timestamp
	 *
	 * @return DateTime
     * @deprecated since 1.9.6  Seems not working fine replaced with get_gmt_date_by_time
	 */
	private function get_datetime($timestamp) {
		$stored_gtm_offset = ( get_option( 'gmt_offset' ) * 3600 );
		$timezone = timezone_name_from_abbr( '', $stored_gtm_offset, false );

		if ( ! $timezone ) {
			$timezone = $this->get_timezone_name($stored_gtm_offset);
		}

		if ( $timezone ) {
			$date_time = new DateTime( 'now', new DateTimeZone( $timezone ) ); //first argument "must" be a string
			$date_time->setTimestamp( $timestamp ); //adjust the object to correct timestamp
		} else {
			// we could not guess the timezone name, so we fail gracefully to building from the timestamp
			$date_time = new DateTime( '@' . $timestamp );
		}

		return $date_time;
    }

	/**
	 * @param string $stored_gtm_offset
	 *
	 * @return mixed
	 */
	private function get_timezone_name($stored_gtm_offset) {
		$abbr_array = timezone_abbreviations_list();

		foreach ( $abbr_array as $abbreviation ) {
			foreach ( $abbreviation as $city ) {
				if ( $city['offset'] == $stored_gtm_offset ) {
					return $city['timezone_id'];
				}
			}
		}
	}

}

global $cred_post_expiration;
$cred_post_expiration = new CRED_PostExpiration;
?>