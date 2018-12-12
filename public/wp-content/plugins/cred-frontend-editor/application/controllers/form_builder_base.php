<?php

/**
 * Class CRED_Form_Builder_Base
 */
abstract class CRED_Form_Builder_Base {

	var $_post_to_create;

	/**
	 * CRED_Form_Builder_Base constructor.
	 */
	public function __construct() {
		// load front end form assets
		add_action( 'wp_head', array( 'CRED_Asset_Manager', 'load_frontend_assets' ) );
		add_action( 'wp_footer', array( 'CRED_Asset_Manager', 'unload_frontend_assets' ) );
	}


	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count Related to the submit count form or just 1
	 * @param bool $preview
	 *
	 * @return bool
	 */
	public function get_form( $form_id, $post_id = false, $form_count = 1, $preview = false ) {
		$this->try_to_update_by_post( $form_id, $post_id, $form_count, $preview );

		global $post;
		CRED_StaticClass::$_cred_container_id = ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) : ( isset( $post ) ? $post->ID : "" );

		//Security Check
		if ( isset( CRED_StaticClass::$_cred_container_id ) && ! empty( CRED_StaticClass::$_cred_container_id ) ) {
			if ( ! is_numeric( CRED_StaticClass::$_cred_container_id ) ) {
				wp_die( 'Invalid data' );
			}
		}

		$form = $this->get_cred_form_object( $form_id, $post_id, $form_count, $preview );
		$type_form = $form->get_type_form();
		$output = $form->print_form();

		if ( is_wp_error( $output ) ) {
			$error_message = $output->get_error_message();

			return $error_message;
		}

		$html_form_id = get_cred_html_form_id( $type_form, $form_id, $form_count );

		/**
		 * cred_after_rendering_form
		 *
		 *  This action is fired after each Toolset Form rendering just before its output.
		 *
		 * @param string $form_id ID of the current cred form
		 * @param string $html_form_id ID of the current cred form
		 * @param int $form_id Toolset Form id
		 * @param string $type_form Post type of the form
		 * @param int $form_count Number of forms rendered so far
		 *
		 * @since 1.9.3
		 */
		do_action( 'cred_after_rendering_form', $form_id, $html_form_id, $form_id, $type_form, $form_count );

		/**
		 * cred_after_rendering_form_{$form_id}
		 *
		 *  This action is fired after specific Toolset Form $form_id rendering just before its output.
		 *
		 * @param string $html_form_id ID of the current cred form
		 * @param int $form_id Toolset Form id
		 * @param string $type_form Post type of the form
		 * @param int $form_count Number of forms rendered so far
		 *
		 * @since 1.9
		 */
		do_action( 'cred_after_rendering_form_' . $form_id, $html_form_id, $form_id, $type_form, $form_count );

		return $output;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post|CRED_Form_User
	 */
	protected function get_cred_form_object( $form_id, $post_id, $form_count, $preview ) {
		$type_form = get_post_type( $form_id );
		switch ( $type_form ) {
			case CRED_USER_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_user_form( $form_id, $post_id, $form_count, $preview );
				break;
			default:
			case CRED_FORMS_CUSTOM_POST_NAME:
				$form = $this->get_post_form( $form_id, $post_id, $form_count, $preview );
				break;

		}

		CRED_StaticClass::initVars();

		return $form;
	}

	/**
	 * Maybe hide comments in the current singular page.
	 *
	 * @param CRED_Form_Base $form
	 * @since 2.1.1
	 */
	private function maybe_hide_comments( $form ) {
		global $post;

		if (
			! $form->get_form_data()->hasHideComments()
			|| ! is_singular()
			|| ! isset( $post )
		) {
			return;
		}

		global $wp_query;
		remove_post_type_support( $post->post_type, 'comments' );
		remove_post_type_support( $post->post_type, 'trackbacks' );
		$post->comment_status = "closed";
		$post->ping_status = "closed";
		$post->comment_count = 0;
		$wp_query->comment_count = 0;
		$wp_query->comments = array();
		add_filter( 'comments_open', '__return_false', 1000 );
		add_filter( 'pings_open', '__return_false', 1000 );
		add_filter( 'comments_array', '__return_empty_array', 1000 );
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_User
	 */
	private function get_user_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_User( $form_id, $post_id, $form_count, $preview );
		$this->maybe_hide_comments( $form );

		return $form;
	}

	/**
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 *
	 * @return CRED_Form_Post
	 */
	private function get_post_form( $form_id, $post_id, $form_count, $preview ) {
		$form = new CRED_Form_Post( $form_id, $post_id, $form_count, $preview );
		$this->maybe_hide_comments( $form );

		return $form;
	}

	/**
	 * @param $form_id
	 * @param $post_id
	 * @param $form_count
	 * @param $preview
	 */
	public function try_to_update_by_post( &$form_id, &$post_id, &$form_count, &$preview ) {
		if ( array_key_exists( CRED_StaticClass::PREFIX . 'form_id', $_POST ) &&
			array_key_exists( CRED_StaticClass::PREFIX . 'form_count', $_POST )
		) {
			$form_id = intval( $_POST[ CRED_StaticClass::PREFIX . 'form_id' ] );
			$form_count = intval( $_POST[ CRED_StaticClass::PREFIX . 'form_count' ] );
			$post_id = ( array_key_exists( CRED_StaticClass::PREFIX . 'post_id', $_POST ) ) ? intval( $_POST[ CRED_StaticClass::PREFIX . 'post_id' ] ) : false;
			$preview = ( array_key_exists( CRED_StaticClass::PREFIX . 'form_preview_content', $_POST ) ) ? true : false;
		}
	}

}