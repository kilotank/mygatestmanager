<?php

/**
 * Class responsible to register the entry point when an AJAXed Form is submitted
 *
 * @since 1.9.4
 */
class CRED_Form_Ajax_Init {

	/**
	 * Check whether an entry point is needed for frontend AJAX forms.
	 *
	 * @return bool
	 * @since 2.1.2
	 */
	public function condition_is_met() {
		return ( 
			isset( $_POST )
			&& 'cred_ajax_form' == toolset_getpost( 'action' )
			&& array_key_exists( CRED_StaticClass::PREFIX . 'form_id', $_POST )
			&& array_key_exists( CRED_StaticClass::PREFIX . 'form_count', $_POST )
		);
	}

	public function initialize() {
		add_action( 'template_redirect', array( $this, 'register_entry_point' ), 10 );
	}

	/**
	 * When Forms are AJAX-submitted we need to register a dedicated entry point in order to
	 * re-create the saved form. We need to have at least a Form submition.
	 *
	 * @return bool
	 */
	public function register_entry_point() {
		if ( ! is_admin() ) {
			CRED_Form_Count_Handler::get_instance()->set_main_count( $_POST[ CRED_StaticClass::PREFIX . 'form_count' ] );
			return CRED_Form_Builder::initialize()->get_form( false, false );
		}
	}
}