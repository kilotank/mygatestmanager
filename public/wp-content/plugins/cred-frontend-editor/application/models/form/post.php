<?php

/**
 * Class CRED_Form_Post
 */
class CRED_Form_Post extends CRED_Form_Base {

	/**
	 * CRED_Form_Post constructor.
	 *
	 * @param $form_id
	 * @param bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 */
	public function __construct( $form_id, $post_id = false, $form_count = 0, $preview = false ) {
		parent::__construct( $form_id, $post_id, $form_count, $preview );
		CRED_StaticClass::$_current_prefix = "cred-form-";
		$this->maybe_post_to_edit_is_the_global_one();
	}

	/**
	 * Function that sets for current edit post form $this->_post_id to global $post->ID
	 * in order to handle the cred edit form shortcode without post attribute like [cred_form form="edit-post"]
	 * In this case we need to change the current post to edit to the global one.
	 */
	function maybe_post_to_edit_is_the_global_one() {
		$form_fields = $this->_formData->getFields();
		$form_type = isset( $form_fields['form_settings']->form['type'] ) ? $form_fields['form_settings']->form['type'] : 'new';
		if ( 'edit' == $form_type
			&& ! $this->get_post_id()
		) {
			global $post;
			if ( $post ) {
				$this->set_post_id( $post->ID );
			}
		}
	}

	/**
	 * @param int|bool $post_id
	 * @param int $form_id
	 * @param object $form
	 * @param object $_fields
	 * @param object $thisform
	 * @param CRED_Helper $formHelper
	 * @param bool $is_ajax
	 *
	 * @return bool
	 */
	public function check_redirection( $post_id, $form_id, $form, $_fields, $thisform, $formHelper, $is_ajax ) {
		if ( ! ( is_int( $post_id ) && $post_id > 0 ) ) {
			return false;
		}

		// save results for later messages if PRG
		// do custom action here
		// user can redirect, display messages, overwrite page etc..
		$bypass_credaction = apply_filters( 'cred_bypass_credaction_' . $form_id, false, $form_id, $post_id, $thisform );
		$bypass_credaction = apply_filters( 'cred_bypass_credaction', $bypass_credaction, $form_id, $post_id, $thisform );

		$form_slug = $form->getForm()->post_name;
		do_action( 'cred_submit_complete_form_' . $form_slug, $post_id, $thisform );
		do_action( 'cred_submit_complete_' . $form_id, $post_id, $thisform );
		do_action( 'cred_submit_complete', $post_id, $thisform );

		// no redirect url
		$url = false;
		// do success action
		if ( $bypass_credaction ) {
			$credaction = 'form';
		} else {
			$credaction = $_fields['form_settings']->form['action'];
		}

		$prg_id = CRED_StaticClass::$out['prg_id'];
		// do default or custom actions
		switch ( $credaction ) {
			case 'post':
				$url = $formHelper->getLocalisedPermalink( $post_id, $_fields['form_settings']->post['post_type'] ); //get_permalink($post_id);
				break;
			case 'custom_post':
				$url = false;
				$action_post_id = (int) toolset_getarr( $_fields['form_settings']->form, 'action_post', 0 );
				if( 0 != $action_post_id ) {
					$action_post_type = get_post_type( $action_post_id );
					$url = $formHelper->getLocalisedPermalink( $action_post_id, $action_post_type );
				}
				break;
			case 'page':
				$url = ( ! empty( $_fields['form_settings']->form['action_page'] ) ) ? $formHelper->getLocalisedPermalink( $_fields['form_settings']->form['action_page'], 'page' )/* get_permalink($_fields['form_settings']->form['action_page']) */ : false;
				break;
			case 'message':
			case 'form':
				// custom 3rd-party action
			default:
				if ( 'form' != $credaction && 'message' != $credaction ) {
					// add hooks here, to do custom action when custom cred action has been selected
					do_action( 'cred_custom_success_action_' . $form_id, $credaction, $post_id, $thisform, $is_ajax );
					do_action( 'cred_custom_success_action', $credaction, $post_id, $thisform, $is_ajax );
				}

				// if previous did not do anything, default to display form
				if ( 'form' != $credaction && 'message' != $credaction ) {
					$credaction = 'form';
				}

				// no redirect url
				$url = false;

				// PRG (POST-REDIRECT-GET) pattern,
				// to avoid resubmit on browser refresh issue, and also keep defaults on new form !! :)
				if ( 'message' == $credaction ) {
					$url = $formHelper->currentURI( array(
						'_tt' => time(),
						'_success_message' => $prg_id,
						'_target' => $post_id,
					) );
				} else {
					if ( $is_ajax && $credaction == 'form' ) {
						//do nothing
					} else {
						$url = $formHelper->currentURI( array(
							'_tt' => time(),
							'_success' => $prg_id,
							'_target' => $post_id,
						) );
					}
				}
				if ( $is_ajax && $credaction == 'form' ) {
					//do nothing
				} else {
					$url = $url . '#cred_form_' . $prg_id;
					// do PRG, redirect now
					$cred_response = new CRED_Generic_Response( CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_REDIRECT, $url, $is_ajax, $thisform, $formHelper );

					return $cred_response->show();
					exit;  // just in case
				}
				break;
		}

		// do redirect action here
		if ( false !== $url ) {
			if ( 'form' != $credaction && 'message' != $credaction ) {
				$url = apply_filters( 'cred_success_redirect_form_' . $form_slug, $url, $post_id, $thisform );
				$url = apply_filters( 'cred_success_redirect_' . $form_id, $url, $post_id, $thisform );
				$url = apply_filters( 'cred_success_redirect', $url, $post_id, $thisform );
			}

			if ( false !== $url ) {
				$url = add_query_arg( 'cred_referrer_form_id', $form_id, $url );
				$redirect_delay = $_fields['form_settings']->form['redirect_delay'];
				$cred_response = new CRED_Generic_Response( CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_REDIRECT, $url, $is_ajax, $thisform, $formHelper, $redirect_delay );

				return $cred_response->show();
				exit;
			}
		}

		$saved_message = $formHelper->getLocalisedMessage( 'post_saved' );
		$saved_message = apply_filters( 'cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $this->_preview );
		$saved_message = apply_filters( 'cred_data_saved_message', $saved_message, $form_id, $post_id, $this->_preview );
		// add success message
		//$zebraForm->add_form_message('data-saved', $formHelper->getLocalisedMessage('post_saved'));
		$this->_cred_form_rendering->add_success_message( $saved_message );

		//is not a redirection
		return false;
	}

	/**
	 * check_form_access
	 *
	 * @param string $form_type
	 * @param int $form_id
	 * @param int|bool $post
	 * @param CRED_Form_Builder_Helper $fbHelper
	 *
	 * @return mixed
	 */
	public function check_form_access( $form_type, $form_id, $post, &$fbHelper ) {
		$post_to_check = ( 
				is_object( $post )
				&& isset( $post->post )
			)
			? $post->post
			: false;
        return apply_filters( 'toolset_forms_current_user_can_use_post_form', false, $form_id, $post_to_check );
	}

	public function set_authordata() {
		global $authordata;
		if ( is_int( $this->get_post_id() ) && $this->get_post_id() > 0 ) {
			$current_post = get_post( $this->get_post_id() );
			// As we modify the global $post, we need to also set the global $authordata and set the Toolset post relationships
			// This will bring compatibility with third party plugins and with shortcodes getting related posts data
			if ( is_object( $current_post ) && property_exists( $current_post, 'post_author' ) ) {
				$authordata = new WP_User( $current_post->post_author );
				do_action( 'toolset_action_record_post_relationship_belongs', $current_post );
			}
		}
	}

	/**
	 * _create_new_post
	 *
	 * @param int $form_id
	 * @param string $form_type
	 * @param int|bool $post_id
	 * @param string $post_type
	 *
	 * @return array|bool|int|null|object|WP_Error
	 */
	public function create_new_post( $form_id, $form_type, $post_id, $post_type ) {
		global $_post_to_create, $_current_post;

		// get post inputs
		if ( $form_type == "edit" ) {
			$post_id = $this->get_post_id();
		} elseif ( isset( $post_id )
			&& ! empty( $post_id )
			&& null !== $post_id
			&& false !== $post_id
			&& ! $this->_preview ) {
			$post_id = intval( $post_id );
		} else {
			$post_id = false;
		}

		$formHelper = $this->_formHelper;
		$form = $this->_formData;
		$this->_post_id = $post_id;

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		//TODO: get recaptcha settings
		CRED_StaticClass::$_staticGlobal['RECAPTCHA'] = $formHelper->getRecaptchaSettings( CRED_StaticClass::$_staticGlobal['RECAPTCHA'] );

		$user_id = get_current_user_id();

		$this->_postData = array();
		if ( ! $this->_preview ) {
			$post_data = new CRED_Post_Data();
			$this->_postData = $post_data->get_post_data( $post_id );
		}

		$post_to_check = ( 
				is_object( $this->_postData )
				&& isset( $this->_postData->post )
			)
			? $this->_postData->post
			: false;

		if (
			! $this->_preview
			&& ! apply_filters( 'toolset_forms_current_user_can_use_post_form', false, $form_id, $post_to_check )
		) {
			return new WP_Error( 'cred-permission', 'Permission denied' );
		}

		//Added by Ahmed Hussein, for issue where multiple cred forms in same page get same post ids
		if ( $form_type == "new" ) {
			$_post_to_create = null;
		}

		// if this is an edit form and no post id given
		if ( ( ( 'edit' == $form_type && false === $post_id && ! $this->_preview ) ||
				( isset( $_GET['action'] ) && $_GET['action'] == 'edit_translation' && 'translation' == $form_type ) ) &&
			false === $post_id && ! $this->_preview
		) {
			return $formHelper->error( __( 'No post specified', 'wp-cred' ) );
		}

		// if this is a new form or preview
		if ( 'new' == $form_type || $this->_preview ||
			( isset( $_GET['action'] ) && $_GET['action'] == 'create_translation' && 'translation' == $form_type ) || $this->_preview
		) {

			if ( ! isset( $_post_to_create ) || empty( $_post_to_create ) ) {
				//Adding auto-draft with each form render
				$unique_value = md5( CRED_StaticClass::getIP() );
				$unique_post_title = "CRED Auto Draft {$unique_value}";

				global $wpdb;
				$querystr = $wpdb->prepare(
					"SELECT $wpdb->posts.ID
					FROM $wpdb->posts
					WHERE $wpdb->posts.post_status = 'auto-draft'
					AND $wpdb->posts.post_type = %s
					AND $wpdb->posts.post_title LIKE %s
					AND $wpdb->posts.post_author = %d
					ORDER by ID desc
					LIMIT 1",
					array( $post_type, $unique_post_title, $user_id )
				);
				$_myposts = $wpdb->get_results( $querystr, OBJECT );

				//if exists
				if ( ! empty( $_myposts ) ) {
					$mypost = get_post( $_myposts[0]->ID );
					$mypost_id = $mypost->ID;
					unset( $_myposts );
				} else {
					$mypost_id = cred__create_auto_draft( $unique_post_title, $post_type, $user_id );
				}

				$_post_to_create = $mypost_id;
				$this->_post_id = $mypost_id;

				//If post_id is not null and is not auto-draft means that the user used
				//back arrow of the browser
				//So i set post_id with a new one
				if ( $this->_post_id != null ) {
					$mycheckpost = get_post( $post_id );
					if ( isset( $mycheckpost ) && $mycheckpost->post_status != 'auto-draft' ) {
						$this->_post_id = $_post_to_create;
					}
				}
				//#########################################################################
			} else {
				$this->_post_id = $_post_to_create;
			}
		}

		// get existing post data if edit form and post given
		if ( ( ( 'edit' == $form_type && ! $this->_preview ) ||
				( isset( $_GET['action'] ) &&
					$_GET['action'] == 'edit_translation' &&
					'translation' == $form_type ) ) &&
			! $this->_preview
		) {

			if ( $_current_post == 0 ) {
				$_current_post = $post_id;
			}

			$is_current_edit = false;
			if ( $_current_post > 0 ) {
				$is_current_edit = true;
			}

			if ( is_wp_error( $this->_postData ) ) {
				return $this->_postData;
			}

			if ( $is_current_edit
				&& $this->_postData->post->post_type != $post_type
			) {
				return new WP_Error( 'cred-error', __( 'Form type and post type do not match', 'wp-cred' ) );
			}
		}

		return $post_id;
	}


	/**
	 * build_form
	 */
	public function build_form() {
		// get refs here
		$formHelper = $this->_formHelper;
		$shortcodeParser = $this->_shortcodeParser;

		$zebraForm = $this->_cred_form_rendering;
		$zebraForm->_shortcodeParser = $shortcodeParser;
		$zebraForm->_formHelper = $formHelper;
		$zebraForm->_formData = $this->_formData;
		$zebraForm->_post_id = $this->_post_id;

		if ( $zebraForm->preview ) {
			$preview_content = $this->_content;
		}

		// remove any HTML comments before parsing, allow to comment-out parts
		$this->_content = $shortcodeParser->removeHtmlComments( $this->_content );
		// do WP shortcode here for final output, moved here to avoid replacing post_content
		// call wpv_do_shortcode instead to fix render wpv shortcodes inside other shortcodes
		$this->_content = apply_filters( 'cred_content_before_do_shortcode', $this->_content );

		//New CRED shortcode to retrieve current container post_id
		if ( isset( CRED_StaticClass::$_cred_container_id ) ) {
			$this->_content = str_replace( "[cred-container-id]", CRED_StaticClass::$_cred_container_id, $this->_content );
		}

		if ( function_exists( 'wpv_do_shortcode' ) ) {
			$this->_content = wpv_do_shortcode( $this->_content );
		} else {
			$this->_content = do_shortcode( $this->_content );
		}

		// parse all shortcodes internally
		$shortcodeParser->remove_all_shortcodes();
		$shortcodeParser->add_shortcode( 'credform', array( &$zebraForm, 'cred_form_shortcode' ) );
		$this->_content = $shortcodeParser->do_shortcode( $this->_content );
		$shortcodeParser->remove_shortcode( 'credform', array( &$zebraForm, 'cred_form_shortcode' ) );

		CRED_StaticClass::fix_cred_field_shortcode_value_attribute_by_single_quote( $zebraForm->_form_content );

		// render any external third-party shortcodes first (enables using shortcodes as values to cred shortcodes)
		$zebraForm->_form_content = do_shortcode( $zebraForm->_form_content );

		// build shortcodes, (backwards compatibility, render first old shortcode format with dashes)
		$shortcodeParser->add_shortcode( 'cred-field', array( &$zebraForm, 'cred_field_shortcodes' ) );
		$shortcodeParser->add_shortcode( 'cred-generic-field', array( &$zebraForm, 'cred_generic_field_shortcodes' ) );
		$shortcodeParser->add_shortcode( 'cred-show-group', array( &$zebraForm, 'cred_conditional_shortcodes' ) );

		// build shortcodes, render new shortcode format with underscores
		$shortcodeParser->add_shortcode( 'cred_field', array( &$zebraForm, 'cred_field_shortcodes' ) );
		$shortcodeParser->add_shortcode( 'cred_generic_field', array( &$zebraForm, 'cred_generic_field_shortcodes' ) );
		$shortcodeParser->add_shortcode( 'cred_show_group', array( &$zebraForm, 'cred_conditional_shortcodes' ) );
		CRED_StaticClass::$out['child_groups'] = array();
		//$this->_form_content=$shortcodeParser->do_recursive_shortcode('cred-show-group', $this->_form_content);
		$zebraForm->_form_content = $shortcodeParser->do_recursive_shortcode( 'cred_show_group', $zebraForm->_form_content );
		CRED_StaticClass::$out['child_groups'] = array();

		$zebraForm->_form_content = $shortcodeParser->do_shortcode( $zebraForm->_form_content );

		$shortcodeParser->remove_shortcode( 'cred_show_group', array( &$zebraForm, 'cred_conditional_shortcodes' ) );
		$shortcodeParser->remove_shortcode( 'cred_generic_field', array(
			&$zebraForm,
			'cred_generic_field_shortcodes',
		) );
		$shortcodeParser->remove_shortcode( 'cred_field', array( &$zebraForm, 'cred_field_shortcodes' ) );

		$shortcodeParser->remove_shortcode( 'cred-show-group', array( &$zebraForm, 'cred_conditional_shortcodes' ) );
		$shortcodeParser->remove_shortcode( 'cred-generic-field', array(
			&$zebraForm,
			'cred_generic_field_shortcodes',
		) );
		$shortcodeParser->remove_shortcode( 'cred-field', array( &$zebraForm, 'cred_field_shortcodes' ) );

		// add nonce hidden field
		if ( is_user_logged_in() ) {
			$nonce_id = substr( $zebraForm->form_properties['name'], 0, strrpos( $zebraForm->form_properties['name'], '_' ) );
			$nonceobj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::NONCE . "_" . $nonce_id, wp_create_nonce( $nonce_id ), array( 'style' => 'display:none;' ) );
		}
		// add post_id hidden field
		if ( $this->_post_id ) {
			$post_id_obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'post_id', $this->_post_id, array( 'style' => 'display:none;' ) );
		}

		if ( isset( CRED_StaticClass::$_cred_container_id ) ) {
			$cred_container_id_obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'cred_container_id', CRED_StaticClass::$_cred_container_id, array( 'style' => 'display:none;' ) );
		}

		// add to form
		$_fields = $this->_formData->getFields();
		$form_type = $_fields['form_settings']->form['type'];
		$form_id = $this->_formData->getForm()->ID;
		$form_count = CRED_Form_Count_Handler::get_instance()->get_main_count();
		$post_type = $_fields['form_settings']->post['post_type'];

		if ( $zebraForm->preview ) {
			// add temporary content for form preview
			//$obj=$zebraForm->add('textarea', CRED_StaticClass::PREFIX.'form_preview_content', $preview_content, array('style'=>'display:none;'));
			$zebraForm->add2form_content( 'textarea', CRED_StaticClass::PREFIX . 'form_preview_content', $preview_content, array( 'style' => 'display:none;' ) );
			// add temporary content for form preview (not added automatically as there is no shortcode to render this)
			//$this->_form_content.=$obj->toHTML();
			// hidden fields are rendered automatically
			//$obj=$zebraForm->add('hidden',CRED_StaticClass::PREFIX.'form_preview_post_type', $post_type, array('style'=>'display:none;'));
			$obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'form_preview_post_type', $post_type, array( 'style' => 'display:none;' ) );
			//$obj=$zebraForm->add('hidden',CRED_StaticClass::PREFIX.'form_preview_form_type', $form_type, array('style'=>'display:none;'));
			$obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'form_preview_form_type', $form_type, array( 'style' => 'display:none;' ) );

			if ( $_fields['form_settings']->form['has_media_button'] ) {
				//$zebraForm->add_form_error('preview_media', __('Media Upload will not work with form preview','wp-cred'));
				$zebraForm->add_field_message( __( 'Media Upload will not work with form preview', 'wp-cred' ) );
			}

			//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/195892843/comments#309778558
			//Created a separated preview messages
			//$zebraForm->add_form_message('preview_mode', __('Form Preview Mode','wp-cred'));
			$zebraForm->add_preview_message( __( 'Form Preview Mode', 'wp-cred' ) );
		}
		// hidden fields are rendered automatically
		// add form id
		//$obj=$zebraForm->add('hidden', CRED_StaticClass::PREFIX.'form_id', $form_id, array('style'=>'display:none;'));
		$obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'form_id', $form_id, array( 'style' => 'display:none;' ) );
		// add form count
		//$obj=$zebraForm->add('hidden', CRED_StaticClass::PREFIX.'form_count', $form_count, array('style'=>'display:none;'));
		$obj = $zebraForm->add2form_content( 'hidden', CRED_StaticClass::PREFIX . 'form_count', $form_count, array( 'style' => 'display:none;' ) );
		// check conditional expressions for javascript

		if ( ! empty( CRED_StaticClass::$_mail_error ) ) {
			echo '<label id="lbl_generic" class="wpt-form-error">' . CRED_StaticClass::$_mail_error . "</label>";
			CRED_StaticClass::$_mail_error = "";
			delete_option( '_' . $form_id . '_last_mail_error' );
		}

		$this->cache_css_and_js_assets($_fields['extra']);
	}

	/**
	 * save_form
	 *
	 * @param null $post_id
	 * @param string $_post_type
	 *
	 * @return bool|WP_Error
	 */
	public function save_form( $post_id = null, $_post_type = "" ) {
		$formHelper = $this->_formHelper;
		$zebraForm = $this->_cred_form_rendering;

		$form = $this->_formData;
		$form_id = $form->getForm()->ID;
		$_fields = $form->getFields();
		$form_type = $_fields['form_settings']->form['type'];
		$post_type = $this->_post_type;

		$thisform = array(
			'id' => $form_id,
			'post_type' => $post_type,
			'form_type' => $form_type,
			'container_id' => CRED_StaticClass::$_cred_container_id,
		);

		// do custom actions before post save
		do_action( 'cred_before_save_data_' . $form_id, $thisform );
		do_action( 'cred_before_save_data', $thisform );

		// track form data for notification mail
		$trackNotification = false;
		if (
			isset( $_fields['notification']->enable ) &&
			$_fields['notification']->enable &&
			! empty( $_fields['notification']->notifications )
		) {
			$trackNotification = true;
		}

		// save result (on success this is post ID)
		$new_post_id = false;

		// Check if we are posting nothing, in which case we are dealing with uploads greater than the size limit
		if ( empty( $_POST ) && isset( $_GET['_tt'] ) ) {
			return $new_post_id;
		}

		// default post fields
		$post = $formHelper->CRED_extractPostFields( $post_id, $trackNotification );

		// custom fields, taxonomies and file uploads; also, catch error_files for sizes lower than the server maximum but higher than the form/site maximum
		list( $fields, $fieldsInfo, $taxonomies, $files, $removed_fields, $error_files ) = $formHelper->CRED_extractCustomFields( $post_id, $trackNotification );

		// upload attachments
		$extra_files = array();
		if ( count( $error_files ) > 0 ) {
			$all_ok = false;
		} else {
			$all_ok = true;
			if ( $this->_disable_progress_bar ) {
				$all_ok = $formHelper->CRED_uploadAttachments( $post_id, $fields, $files, $extra_files, $trackNotification );
			} else {
				$formHelper->CRED_uploadFeaturedImage( $post_id );
			}
		}

		if ( $all_ok ) {
			add_filter( 'terms_clauses', array( &$this, 'terms_clauses' ) );
			add_filter( 'wpml_save_post_lang', array( &$this, 'wpml_save_post_lang' ) );
			//add_filter('wpml_save_post_trid_value',array(&$this,'wpml_save_post_trid_value'),10,2);
			// save everything
			$model = CRED_Loader::get( 'MODEL/Forms' );

			if ( ! isset( $post->post_type ) || empty( $post->post_type ) ) {
				$post->post_type = $post_type;
			}

			//cred-131#
			$fields = CRED_StaticClass::cf_sanitize_values_on_save( $fields );

			if ( empty( $post->ID ) ) {
				// TODO this seems never reached,
				// even new post forms start with an autodraft
				// so the post ID is set. I suspect we can shave a
				// quitebbig chunk of codebase here...
				// ... because none of the linked methods are ever called:
				// - addPost
				// - addFields
				// - addTaxonomies
				$new_post_id = $model->addPost( $post );
				if ( !is_wp_error( $new_post_id ) ) {
					$this->add_form_data( $new_post_id );
					$model->addFields( $new_post_id, array(
						'fields' => $fields,
						'info' => $fieldsInfo,
						'removed' => $removed_fields,
					) );
					$model->addTaxonomies( $new_post_id, $taxonomies );
				}
			} else {
				$new_post_id = $model->updatePost( $post );
				if ( !is_wp_error( $new_post_id ) ) {
					$this->add_form_data( $new_post_id );
					$model->updateFields( $new_post_id, array(
						'fields' => $fields,
						'info' => $fieldsInfo,
						'removed' => $removed_fields,
					) );
					$model->updateTaxonomies( $new_post_id, $post, $taxonomies );
				}
			}

			if ( is_int( $new_post_id ) && $new_post_id > 0 ) {
				if ( $this->_disable_progress_bar ) {
					$formHelper->attachUploads( $new_post_id, $fields, $files, $extra_files );
				}
				// save notification data (pre-formatted)
				if ( $trackNotification ) {
					CRED_StaticClass::$out['notification_data'] = $formHelper->trackData( null, true );
				}
				// for WooCommerce products only (update prices in products)
				if ( class_exists( 'Woocommerce' ) && 'product' == get_post_type( $new_post_id ) ) {
					if ( isset( $fields['_regular_price'] ) && ! isset( $fields['_price'] ) ) {
						$regular_price = $fields['_regular_price'];
						update_post_meta( $new_post_id, '_price', $regular_price );
						$sale_price = get_post_meta( $new_post_id, '_sale_price', true );
						// Update price if on sale
						if ( $sale_price != '' ) {
							$sale_price_dates_from = get_post_meta( $new_post_id, '_sale_price_dates_from', true );
							$sale_price_dates_to = get_post_meta( $new_post_id, '_sale_price_dates_to', true );
							if ( $sale_price_dates_to == '' && $sale_price_dates_to == '' ) {
								update_post_meta( $new_post_id, '_price', $sale_price );
							} elseif ( $sale_price_dates_from && strtotime( $sale_price_dates_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
								update_post_meta( $new_post_id, '_price', $sale_price );
							}
							if ( $sale_price_dates_to && strtotime( $sale_price_dates_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
								update_post_meta( $new_post_id, '_price', $regular_price );
							}
						}
					} elseif ( isset( $fields['_price'] ) && ! isset( $fields['_regular_price'] ) ) {
						update_post_meta( $new_post_id, '_regular_price', $fields['_price'] );
					}
				}

				// do custom actions on successful post save
				/* EMERSON: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185624661/comments
				  /*Add cred_save_data_form_ hook on CRED 1.3 */
				$form_slug = $form->getForm()->post_name;
				do_action( 'cred_save_data_form_' . $form_slug, $new_post_id, $thisform );
				do_action( 'cred_save_data_' . $form_id, $new_post_id, $thisform );
				do_action( 'cred_save_data', $new_post_id, $thisform );
			}
		} else {
			$WP_Error = new WP_Error();
			$WP_Error->add( 'upload', 'Error some required upload field failed.' );
			$new_post_id = $WP_Error;
		}

		// return saved post_id as result
		return $new_post_id;
	}

	/**
	 * @param $post_id
	 * @param null $attachedData
	 *
	 * @return mixed|void
	 */
	public function notify($post_id, $attachedData = null) {
		$form = &$this->_formData;
		$fields = $form->getFields();

		// init notification manager if needed
		if (
			isset( $fields['notification']->enable )
			&& $fields['notification']->enable
			&& ! empty( $fields['notification']->notifications )
		) {
			// add extra placeholder codes
			add_filter( 'cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 10, 3 );
			add_filter( 'cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 10, 3 );

			// add the post/user to notification management
			$this->add_form_data( $post_id );
			// send any notifications now if needed
			CRED_Notification_Manager_Post::get_instance()->trigger_notifications( $post_id,
				array(
					'event' => 'form_submit',
					'form_id' => $form->getForm()->ID,
					'notification' => $fields['notification'],
				), $attachedData );

			// remove extra placeholder codes
			remove_filter( 'cred_subject_notification_codes', array(&$this, 'extraSubjectNotificationCodes'), 10, 3 );
			remove_filter( 'cred_body_notification_codes', array(&$this, 'extraBodyNotificationCodes'), 10, 3 );
		}
	}


	/**
	 * Adds form data to the post
	 *
	 * @param int $post_id Post ID.
	 * @since 2.0.1
	 */
	public function add_form_data( $post_id ) {
		$form = &$this->_formData;
		$fields = $form->getFields();
		// add the post/user to notification management
		CRED_Notification_Manager_Post::get_instance()->add( $post_id, $form->getForm()->ID, $fields['notification']->notifications );
	}


	/**
	 * @param $form
	 * @param $fields
	 * @param $model
	 *
	 * @return mixed
	 */
	protected function get_attached_data( $form, $fields, $model ) {
		CRED_Notification_Manager_Post::get_instance()->set_current_attached_data( $form->getForm()->ID, $this->_post_id, $fields[ 'notification' ]->notifications );
		$attachedData = $model->getAttachedData( $this->_post_id );

		return $attachedData;
	}

	/**
	 * Save if exist any parents, post_references and relationships association by post_id if exists and validate the form
	 *
	 * @param int $post_id
	 * @param CRED_Rendering_Form $cred_form_rendering
	 *
	 * @return array
	 * @since m2m
	 */
	public function save_any_relationships_by_id( $post_id, &$cred_form_rendering ) {
		$results = array();
		if ( ! CRED_Form_Relationship::get_instance()->is_m2m_enabled() ) {
			return $results;
		}

		//Relationship Handling
		if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'relationships' ] )
			&& ! empty( CRED_StaticClass::$out[ 'fields' ][ 'relationships' ] )
		) {
			$relationship_fields = CRED_StaticClass::$out[ 'fields' ][ 'relationships' ];
			foreach ( $relationship_fields as $relationship_field ) {
				//IF the relationship is really an element present in the cred form
				if ( array_key_exists( $relationship_field[ 'key' ], CRED_StaticClass::$out[ 'form_fields' ] ) ) {
					$results[ $relationship_field[ 'slug' ] ] = CRED_Form_Relationship::get_instance()->connect_to_post( $post_id, $relationship_field );
				}
			}
		}

		//Prf Relationship Handling
		if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'post_reference_fields' ] )
			&& ! empty( CRED_StaticClass::$out[ 'fields' ][ 'post_reference_fields' ] )
		) {
			$post_reference_relationship_fields = CRED_StaticClass::$out[ 'fields' ][ 'post_reference_fields' ];
			foreach ( $post_reference_relationship_fields as $post_reference_relationship_field ) {
				//IF the Prf relationship is really an element present in the cred form
				if ( array_key_exists( $post_reference_relationship_field[ 'key' ], CRED_StaticClass::$out[ 'form_fields' ] ) ) {
					$results[ $post_reference_relationship_field[ 'slug' ] ] = CRED_Form_Relationship::get_instance()->connect_to_post( $post_id, $post_reference_relationship_field[ 'relationship' ] );
				}
			}
		}

		//Check for Legacy parent fields definition
		if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'parents' ] )
			&& ! empty( CRED_StaticClass::$out[ 'fields' ][ 'parents' ] )
		) {
			$child_post_type = get_post_type( $post_id );
			$parent_fields = CRED_StaticClass::$out[ 'fields' ][ 'parents' ];
			foreach ( $parent_fields as $parent_field ) {
				//IF the parents field is really an element present in the cred form
				if ( array_key_exists( $parent_field[ 'slug' ], CRED_StaticClass::$out[ 'form_fields' ] ) ) {
					$results[ $parent_field[ 'slug' ] ] = CRED_Form_Relationship::get_instance()->connect_to_post( $post_id, $parent_field[ 'relationship' ] );
				}
			}
		}
		if ( ! empty( $results ) ) {
			foreach ( $results as $relationship_slug => $result ) {
				if ( is_bool( $result )
					&& $result === true
				) {
					continue;
				}
				// This is not going to actually render the error messages, but kep it for reference.
				$cred_form_rendering->add_top_message( $result, $relationship_slug );
			}
		}
		
		return $results;
	}
}
