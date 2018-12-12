<?php

/**
 * Asset management for CRED.
 *
 * All script or style handles should be defined here as constants.
 *
 * All CRED assets should be placed in public/ subdirectories.
 *
 * Note: Not extending the Toolset_Assets_Manager deliberately because it has specific function related to Toolset
 * Common and does some things globally. We may use it here in the future, though.
 *
 * @since 1.9
 */
class CRED_Asset_Manager {

	const CRED_FORM_SETTINGS_BOX = 'cred_form_settings_box';
	const CRED_ACTION_AJAX_UPLOAD = 'cred_frontend_ajax_upload_submit';
	const CRED_AJAX_FILE_UPLOADER = 'cred-frontend-ajax-file-uploader';
	const CREDFILE = 'wpt-field-credfile';

	const SCRIPT_CODEMIRROR_SHORTCODES_MODE = 'cred_codemirror_shortcodes';

	const SCRIPT_EDITOR_PROTOTYPE = 'cred_editor_prototype';
	const STYLE_EDITOR = 'cred_editor_shared_styles';
	const STYLE_EDITOR_REL_PATH = '/public/css/editor.css';

	private static $instance;

	/**
	 * @var Toolset_Assets_Manager
	 */
	private $toolset_assets_manager = null;


	private function __construct( \Toolset_Assets_Manager $di_toolset_assets_manager = null ) {
		$this->toolset_assets_manager = ( null === $di_toolset_assets_manager )
			? Toolset_Assets_Manager::get_instance()
			: $di_toolset_assets_manager;

		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_cred_button_assets' ), 1 );

		add_action( 'toolset_forms_enqueue_frontend_form_assets', array( $this, 'enqueue_frontend_assets_on_demand' ) );
	}
	
	public function register_assets() {
		$this->register_cred_scripts();
		$this->register_cred_styles();
	}

	/**
	 * Get full asset URL.
	 *
	 * @param string $relative_path Path relative to the asset directory without the initial slash.
	 * @return string Full URL
	 * @since 1.9
	 */
	public function get_asset_url( $relative_path ) {
		return sprintf( '%s/public/%s', untrailingslashit( CRED_ABSURL ), $relative_path );
	}

	/**
	 * Get full admin assets URL.
	 *
	 * @param string $relative_path Path relative to the asset directory without the initial slash.
	 * @return string Full URL
	 * @since 1.9
	 */
	public function get_admin_assets_url( $relative_path ) {
		return sprintf( '%s/library/toolset/cred/embedded/assets/%s', untrailingslashit( CRED_ABSURL ), $relative_path );
	}

	/**
	 * Registers all CRED scripts
	 *
	 * @since 1.9
	 */
	public function register_cred_scripts() {
		//template post form-settings-meta-box
		wp_register_script(self::CRED_FORM_SETTINGS_BOX, $this->get_admin_assets_url('js/cred_form_settings_box.js'), array('jquery','cred_gui','toolset_select2'), CRED_FE_VERSION);

		wp_register_script( 'cred_console_polyfill', $this->get_admin_assets_url( 'common/js/console_polyfill.js' ), array(), CRED_FE_VERSION );
		wp_register_script( 'cred_extra', $this->get_admin_assets_url( 'common/js/extra.js' ), array( 'jquery', 'jquery-effects-scale', 'toolset-event-manager' ), CRED_FE_VERSION );
		wp_register_script( 'cred_utils', $this->get_admin_assets_url( 'common/js/utils.js' ), array( 'jquery', 'cred_extra' ), CRED_FE_VERSION );
		wp_register_script( 'cred_gui', $this->get_admin_assets_url( 'common/js/gui.js' ), array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-dialog', 'wp-pointer' ), CRED_FE_VERSION );
		wp_register_script( 'cred_mvc', $this->get_admin_assets_url( 'common/js/mvc.js' ), array( 'jquery', 'icl_editor-script' ), CRED_FE_VERSION );
		wp_register_script( self::SCRIPT_CODEMIRROR_SHORTCODES_MODE, $this->get_admin_assets_url( 'third-party/codemirror_shortcodes.js' ), array( 'jquery', Toolset_Assets_Manager::SCRIPT_CODEMIRROR ), CRED_FE_VERSION );
		wp_register_script( 'cred_cred_dev', $this->get_admin_assets_url( 'js/cred.js' ), array( 'jquery', 'underscore', 'cred_console_polyfill', 'toolset-meta-html-codemirror-xml-script', 'toolset-codemirror-script', 'toolset-meta-html-codemirror-css-script', self::SCRIPT_CODEMIRROR_SHORTCODES_MODE, 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc', 'jquery-ui-sortable', 'toolset-utils', 'toolset-event-manager' ), CRED_FE_VERSION );
		wp_register_script( 'cred_cred_nocodemirror_dev', $this->get_admin_assets_url( 'js/cred.js' ), array( 'jquery', 'underscore', 'cred_console_polyfill', 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc', 'jquery-ui-sortable', 'toolset-utils', 'toolset-event-manager' ), CRED_FE_VERSION );
		wp_register_script( 'cred_cred_post_dev', $this->get_admin_assets_url( 'js/post.js' ), array( 'jquery', 'cred_console_polyfill', 'cred_extra', 'cred_utils', 'cred_gui', 'toolset-event-manager', 'cred_cred_dev', 'cred_settings' ), CRED_FE_VERSION );
		wp_register_script( 'cred_cred_nocodemirror', $this->get_admin_assets_url( 'js/cred.js' ), array( 'jquery', 'underscore', 'jquery-ui-dialog', 'wp-pointer', 'jquery-effects-scale', 'cred_extra', 'cred_utils', 'cred_gui', 'cred_mvc', 'jquery-ui-sortable', 'toolset-utils', 'toolset-event-manager' ), CRED_FE_VERSION );
		wp_register_script( 'cred_wizard_dev', $this->get_admin_assets_url( 'js/wizard.js' ), array( 'cred_cred_dev' ), CRED_FE_VERSION );
		wp_register_script( 'cred_settings', $this->get_admin_assets_url( 'js/settings.js' ), array( 'jquery', 'underscore', 'jquery-ui-dialog', 'jquery-ui-tabs', 'toolset-settings' ), CRED_FE_VERSION );

		wp_localize_script( 'cred_settings', 'cred_settings', array(
			'is_m2m_enabled' => apply_filters( 'toolset_is_m2m_enabled', false ),
			'is_wizard_enabled' => $this->get_wizard_setting(),
			'rfg_post_types' => $this->get_rfg_post_types(),
			'_current_page' => CRED_Helper::getCurrentPostType(),
			'_cred_wpnonce' => wp_create_nonce( '_cred_wpnonce' ),
			'autogenerate_username_scaffold' => isset( CRED_Helper::$current_form_fields ) ? CRED_Helper::$current_form_fields['form_settings']->form['autogenerate_username_scaffold'] : 0,
			'autogenerate_nickname_scaffold' => isset( CRED_Helper::$current_form_fields ) ? CRED_Helper::$current_form_fields['form_settings']->form['autogenerate_nickname_scaffold'] : 0,
			'autogenerate_password_scaffold' => isset( CRED_Helper::$current_form_fields ) ? CRED_Helper::$current_form_fields['form_settings']->form['autogenerate_password_scaffold'] : 0,
			// settings
			'assets' => CRED_ASSETS_URL,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'editurl' => admin_url( 'post.php' ),
			'form_controller_url' => '/Forms/updateFormField',
			'wizard_url' => '/Settings/disableWizard',
			'homeurl' => home_url( '/' ),
			'settingsurl' => CRED_CRED::$settingsPage,
			// help
			'help' => CRED_CRED::$help,
			'locale' => $this->get_locale_array(),
			'wizard_instructions_post_content' => $this->get_wizard_instructions_template( 'post' ),
			'wizard_instructions_user_content' => $this->get_wizard_instructions_template( 'user' ),
		) );

		wp_register_script(
			self::SCRIPT_EDITOR_PROTOTYPE,
			CRED_ABSURL . '/public/js/editor.prototype.js',
			array(
				'jquery',
				'underscore',
				'quicktags',
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_SHORTCODE,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_CSS,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_HTMLMIXED,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_JS,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_OVERLAY,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_HINT,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_HINT_CSS,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_PANEL,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_SEARCH,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_SEARCH_CURSOR,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_XML,
				Toolset_Assets_Manager::SCRIPT_ICL_EDITOR,
				Toolset_Assets_Manager::SCRIPT_ICL_MEDIA_MANAGER,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Toolset_Assets_Manager::SCRIPT_UTILS,
				Toolset_Assets_Manager::SCRIPT_SELECT2,
				CRED_Asset_Manager::SCRIPT_CODEMIRROR_SHORTCODES_MODE
			),
			CRED_FE_VERSION
		);
		
		wp_register_script(
			'cred-frontend-js',
			$this->get_asset_url( 'js/frontend.js' ),
			array( 'jquery', 'knockout', 'underscore', Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER ),
			CRED_FE_VERSION,
			true
		);
		wp_localize_script( 'cred-frontend-js', 'cred_frontend_settings', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		));

		wp_register_script(
			'cred-select2-frontend-js',
			$this->get_asset_url( 'js/select2_frontend.js' ),
			array( 'jquery', Toolset_Assets_Manager::SCRIPT_SELECT2 ),
			CRED_FE_VERSION,
			true
		);

		wp_localize_script( 'cred-select2-frontend-js', 'cred_select2_frontend_settings',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'select2_fields_list' => array(),
				'cred_lang' => apply_filters( 'wpml_current_language', '' )
			)
		);
	}
	
	private function get_rfg_post_types() {
		$rfg_post_types = array();

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return $rfg_post_types;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$rfg_post_types = get_post_types( array( Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ), 'names' );

		return $rfg_post_types;
	}

	/**
	 * @return bool|string
	 */
	private function get_wizard_setting() {
		$sm = CRED_Loader::get( 'MODEL/Settings' );
		$settings = $sm->getSettings();

		return ( isset( $settings[ 'wizard' ] ) ) ? $settings[ 'wizard' ] : false;
	}

	/**
	 * Get Wizard Summary Template
	 *
	 * @return string
	 * @throws Exception
	 */
	private function get_wizard_instructions_template( $type_form ) {
		$template_file_path = CRED_ABSPATH . "/library/toolset/cred/embedded/views/templates/wizard-instructions-{$type_form}.php";
		ob_start();
		if ( file_exists( $template_file_path ) ) {
			include( $template_file_path );
			$out = ob_get_clean();

			return esc_html( $out );
		} else {
			return __( "Wizard template file {$template_file_path} not found", 'wp-cred' );
		}
	}

	/**
	 * @return array
	 *
	 * @since 1.9.1
	 */
	private function get_locale_array() {
		return array(
			'default_select_text' => __( '--- not set ---', 'wp-cred' ),
			'OK' => __( 'OK', 'wp-cred' ),
			'Yes' => __( 'Yes', 'wp-cred' ),
			'No' => __( 'No', 'wp-cred' ),
			'syntax_button_title' => __( 'Syntax', 'wp-cred' ),
			'text_button_title' => __( 'Text', 'wp-cred' ),
			'title_explain_text' => __( 'Set the title for this new form.', 'wp-cred' ),
			'next_text' => __( 'Continue >', 'wp-cred' ),
			'prev_text' => __( '< Previous', 'wp-cred' ),
			'finish_text' => __( 'Finish', 'wp-cred' ),
			'quit_wizard_text' => __( 'Exit Wizard', 'wp-cred' ),
			'quit_wizard_confirm_text' => __( 'Do you want to disable the Wizard for this form only, or disable the Wizard for all future forms as well? <br /><br /><span style="font-style:italic">(You can re-enable the Wizard at the Forms tab of the Toolset Settings page if you change your mind)</span>', 'wp-cred' ),
			'quit_wizard_all_forms' => __( 'All forms', 'wp-cred' ),
			'quit_wizard_this_form' => __( 'This form', 'wp-cred' ),
			'cancel_text' => __( 'Cancel', 'wp-cred' ),
			'form_type_missing' => __( 'You must select the Form Type for the form', 'wp-cred' ),
			'post_type_missing' => __( 'You must select a Post Type for the form', 'wp-cred' ),
			'post_status_missing' => __( 'You must select a Post Status for the form', 'wp-cred' ),
			'post_action_missing' => __( 'You must select a Form Action for the form', 'wp-cred' ),
			'ok_text' => __( 'OK', 'wp-cred' ),
			'step_1_title' => __( 'Instructions', 'wp-cred' ),
			'step_2_title' => __( 'Title', 'wp-cred' ),
			'step_3_title' => __( 'Settings', 'wp-cred' ),
			'step_4_title' => __( 'Build Form', 'wp-cred' ),
			'step_5_title' => __( 'E-mail Notifications', 'wp-cred' ),
			'submit_but' => __( 'Update', 'wp-cred' ),
			'form_content' => __( 'Form Content', 'wp-cred' ),
			'form_fields' => __( 'Form Fields', 'wp-cred' ),
			'post_fields' => __( 'Standard Post Fields', 'wp-cred' ),
			//Added
			'user_fields' => __( 'Standard User Fields', 'wp-cred' ),
			'custom_fields' => __( 'Custom Fields', 'wp-cred' ),
			'taxonomy_fields' => __( 'Taxonomies', 'wp-cred' ),
			'parent_fields' => __( 'Parents', 'wp-cred' ),
			'hierarchical_parent_fields' => __( 'Parents (hierarchical)', 'wp-cred' ),
			'relationship_fields' => __( 'Relationship', 'wp-cred' ),
			'extra_fields' => __( 'Extra Fields', 'wp-cred' ),
			'form_types_not_set' => __( 'Form Type or Post Type is not set!' ),
			'set_form_title' => __( 'Please set the form Title', 'wp-cred' ),
			'create_new_content_form' => __( '(Create a new-post form first)', 'wp-cred' ),
			'create_edit_content_form' => __( '(Create an edit-post form first)', 'wp-cred' ),
			'create_new_content_user_form' => __( '(Create a new-user form first)', 'wp-cred' ),
			'create_edit_content_user_form' => __( '(Create an edit-user form first)', 'wp-cred' ),
			'show_advanced_options' => __( 'Show advanced options', 'wp-cred' ),
			'hide_advanced_options' => __( 'Hide advanced options', 'wp-cred' ),
			'select_form' => __( 'Please select a form first', 'wp-cred' ),
			'select_post' => __( 'Please select a post first', 'wp-cred' ),
			'insert_post_id' => __( 'Please insert a valid post ID', 'wp-cred' ),
			'insert_shortcode' => __( 'Click to insert the specified shortcode', 'wp-cred' ),
			'select_shortcode' => __( 'Please select a shortcode first', 'wp-cred' ),
			'post_types_dont_match' => __( 'This post type is incompatible with the selected form', 'wp-cred' ),
			'post_status_must_be_public' => __( 'In order to display the post, post status must be set to Publish', 'wp-cred' ),
			'refresh_done' => __( 'Refresh Complete', 'wp-cred' ),
			'enable_popup_for_preview' => __( 'You have to enable popup windows in order for Preview to work!', 'wp-cred' ),
			'show_syntax_highlight' => __( 'Enable Syntax Highlight', 'wp-cred' ),
			'hide_syntax_highlight' => __( 'Revert to default editor', 'wp-cred' ),
			'syntax_highlight_on' => __( 'Syntax Highlight On', 'wp-cred' ),
			'syntax_highlight_off' => __( 'Syntax Highlight Off', 'wp-cred' ),
			'invalid_title' => __( 'Title should contain only letters, numbers and underscores/dashes', 'wp-cred' ),
			'invalid_notification_sender_email' => __( 'Notifications sender E-mail must be a valid E-mail address', 'wp-cred' ),
			'form_user_not_set' => __( 'Form User Fields not set!' ),
			'logged_in_user_shortcodes_warning' => __( 'Both `User Login Name` and `User Display Name` codes, work only on notifications triggered by a form submission.', 'wp-cred' ),
			'form_created_using_wizard' => __( 'Form has been succesfully saved with ID ', 'wp-cred' ),
		);
	}

	/**
	 * Registers all CRED styles
	 *
	 * @since 1.9
	 */
	public function register_cred_styles() {
		wp_register_style( 'cred_template_style', $this->get_admin_assets_url( 'css/gfields.css' ), array( 'wp-admin', 'colors-fresh', 'font-awesome', 'cred_cred_style_nocodemirror_dev' ), CRED_FE_VERSION );
		wp_register_style( 'cred_cred_style_dev', $this->get_admin_assets_url( 'css/cred.css' ), array( 'font-awesome', 'toolset-meta-html-codemirror-css-hint-css', 'toolset-meta-html-codemirror-css', 'wp-jquery-ui-dialog', 'wp-pointer' ), CRED_FE_VERSION );
		wp_register_style( 'cred_cred_style_nocodemirror_dev', $this->get_admin_assets_url( 'css/cred.css' ), array( 'font-awesome', 'wp-jquery-ui-dialog', 'wp-pointer' ), CRED_FE_VERSION );
		
		wp_register_style( 'cred_wizard_general_style', $this->get_admin_assets_url( 'css/wizard-general.css' ), array(
			'cred_cred_style_dev',
		), CRED_FE_VERSION );

		$this->toolset_assets_manager->register_style(
			self::STYLE_EDITOR,
			CRED_ABSURL . self::STYLE_EDITOR_REL_PATH,
			array(),
			CRED_FE_VERSION
		);
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return is progress bar disabled for file upload fields by the filter 'cred_file_upload_disable_progress_bar'
	 *
	 * @return bool
	 */
	public function is_progress_bar_disabled() {
		/**
		 * cred_file_upload_disable_progress_bar
		 *
		 * Allows to override the decision whether the file upload & progress bar should be displayed
		 * we have this new feature since version 1.3.6.3 so current filter by default should be set to false
		 *
		 * @param bool $disable true to disable, false to enable.
		 *
		 * @since 1.3.6.3
		 */
		return (bool) apply_filters( 'cred_file_upload_disable_progress_bar', false );
	}

	/**
	 * Enqueue all assets needed for the frontend file upload field.
	 *
	 * @since 1.9.3
	 */
	public function enqueue_file_upload_assets() {
		wp_register_script( self::CREDFILE, $this->get_asset_url( 'js/credfile.js' ), array(
			'wptoolset-forms',
			'wptoolset-form-jquery-validation',
		), WPTOOLSET_FORMS_VERSION, true );
		wp_enqueue_script( self::CREDFILE );

		if ( $this->is_progress_bar_disabled() ) {
			// Nothing else is needed
			return;
		}

		$base_url = $this->get_asset_url( 'js/jquery_upload' );

		wp_enqueue_style( 'progress_bar-style', "$base_url/progress_bar.css" );

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_script( 'load-image-all-script', "$base_url/load-image.all.min.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-iframe-transport-script', "$base_url/jquery.iframe-transport.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-script', "$base_url/jquery.fileupload.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-process-script', "$base_url/jquery.fileupload-process.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-image-script', "$base_url/jquery.fileupload-image.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-audio-script', "$base_url/jquery.fileupload-audio.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-video-script', "$base_url/jquery.fileupload-video.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-validate-script', "$base_url/jquery.fileupload-validate.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-ui-script', "$base_url/jquery.fileupload-ui.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-fileupload-jquery-ui-script', "$base_url/jquery.fileupload-jquery-ui.js", array( 'jquery' ), '', true );

		$max_upload_size = wp_max_upload_size();

		wp_enqueue_script( self::CRED_AJAX_FILE_UPLOADER, "$base_url/file_upload.js", array( 'jquery' ) );
		wp_localize_script( self::CRED_AJAX_FILE_UPLOADER, 'settings',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action' => self::CRED_ACTION_AJAX_UPLOAD,
				'media_settings' => Toolset_Utils::get_image_sizes( 'thumbnail' ),
				'delete_confirm_text' => __( 'Are you sure to delete this file ?', 'wp-cred' ),
				'delete_alert_text' => __( 'Generic Error in deleting file', 'wp-cred' ),
				'delete_text' => __( 'delete', 'wp-cred' ),
				'failed_upload_too_large_file_alert_text' => __( 'The file you uploaded is too large. You can upload files up to ', 'wp-cred' ),
				'failed_upload_generic_alert_text' => __( 'There was an error uploading your file.', 'wp-cred' ),
				'max_upload_size' => $max_upload_size,
				'human_readable_max_upload_size' => size_format( $max_upload_size, 2 ),
				'nonce' => wp_create_nonce( 'ajax_nonce' ),
			)
		);
	}

	/**
	 * Adds all assets needed for the frontend form display/submit.
	 * 
	 * Note that we only add styles here: scripts are loaded on demand, in the footer.
	 *
	 * @since 1.9
	 */
	public function enqueue_required_frontend_assets() {
		//Enqueue front-end styles
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'toolset-select2-css' );
	}

	/**
	 * Enqueue the assets needed for frontend forms, on demand.
	 *
	 * @since 2.1.1
	 */
	public function enqueue_frontend_assets_on_demand() {
		//Enqueue front-end scripts
		wp_enqueue_script( 'cred-frontend-js' );
		wp_enqueue_script( 'cred-select2-frontend-js' );
	}

	/**
	 * Adds all assets needed for the old legacy CRED button.
	 * We still need it because third parties use this directly and because it shares methods with the cred.js script.
	 *
	 * @since 1.9
	 * @deprecated 1.9.3
	 */
	public function enqueue_cred_button_assets() {
		wp_enqueue_script( 'cred_settings' );
		wp_enqueue_script( 'cred_cred_post_dev' );
		wp_enqueue_style( 'cred_cred_style_dev' );
	}

	/**
	 * Hooks the frontend assets queueing to correct action.
	 *
	 * @since 1.9
	 */
	public function enqueue_frontend_assets() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_required_frontend_assets' ) );
	}

	/**
	 * load frontend assets on init
	 *
	 * @since 1.9.3
	 */
	public static function load_frontend_assets() {
	}

	/**
	 * unload frontend assets if no form rendered on page
	 *
	 * @since 1.9.3
	 */
	public static function unload_frontend_assets() {
		//Print custom js/css on front-end
		$custom_js_cache = wp_cache_get( 'cred_custom_js_cache' );
		//maybe we have multiple cred form array with exactly the same js so we need to clean array duplicated values
		if ( ! empty( $custom_js_cache )
			&& is_array( $custom_js_cache ) ) {
			$custom_js_cache = array_unique( $custom_js_cache, SORT_REGULAR );
			wp_cache_delete( 'cred_custom_js_cache' );
			foreach ( $custom_js_cache as $custom_js ) {
				echo "\n<script type='text/javascript' class='custom-js'>\n";
				echo html_entity_decode( $custom_js, ENT_QUOTES ) . "\n";
				echo "</script>\n";
			}
		}

		$custom_css_cache = wp_cache_get( 'cred_custom_css_cache' );
		//maybe we have multiple cred form array with exactly the same css so we need to clean array duplicated values
		if ( ! empty( $custom_css_cache )
			&& is_array( $custom_css_cache ) ) {
			$custom_css_cache = array_unique( $custom_css_cache, SORT_REGULAR );
			wp_cache_delete( 'cred_custom_css_cache' );
			foreach ( $custom_css_cache as $custom_css ) {
				echo "\n<style type='text/css'>\n";
				echo html_entity_decode( $custom_css, ENT_QUOTES ) . "\n";
				echo "</style>\n";
			}
		}
	}
}
