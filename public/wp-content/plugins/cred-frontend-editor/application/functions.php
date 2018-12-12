<?php
/**
 * This file is meant for very generic functions that should be allways available, PHP compatibility fixes and so on.
 *
 * Do not let it grow too much and make sure to wrap each function in !function_exists() condition.
 *
 * @since 1.8.6
 */
if ( ! function_exists( "cred_get_object_form" ) ) {
	/**
	 * cred_get_object_form
	 *
	 * @param mixed $form Form slug, title or ID
	 * @param string $type (CRED_FORMS_CUSTOM_POST_NAME|CRED_USER_FORMS_CUSTOM_POST_NAME)
	 *
	 * @return bool|WP_Post
	 */
	function cred_get_object_form( $form, $type ) {
		// Check whether the passed value matches a form slug
		$result = get_page_by_path( html_entity_decode( $form ), OBJECT, $type );
		if ( $result instanceof WP_Post ) {
			return $result;
		}

		// Check whether the passed value matches a form title
		$result = get_page_by_title( html_entity_decode( $form ), OBJECT, $type );
		if ( $result instanceof WP_Post ) {
			return $result;
		}

		// Check whether the passed value matches a form ID
		if ( is_numeric( $form ) ) {
			$result = get_post( $form );
			if ( $result instanceof WP_Post ) {
				return $result;
			}
		}

		return false;
	}

}

if ( ! function_exists( "cred_get_form_id_by_form" ) ) {
	/**
	 * @param $form
	 *
	 * @return bool
	 */
	function cred_get_form_id_by_form( $form ) {
		if ( isset( $form ) && ! empty( $form ) && isset( $form->ID ) ) {
			return $form->ID;
		}

		return false;
	}

}

if ( ! function_exists( 'get_cred_html_form_id' ) ) {
	/**
	 *
	 * Creates cred form html selector ID by form type, form_id and form_count
	 * 
	 * @param string $form_type
	 * @param string $form_id
	 * @param string $form_count
	 *
	 * @return string
	 *
	 * @since 1.9
	 */
	function get_cred_html_form_id( $form_type, $form_id, $form_count ) {
		$html_form_type = str_replace( "-", "_", $form_type );

		return "{$html_form_type}_{$form_id}_{$form_count}";
	}

}