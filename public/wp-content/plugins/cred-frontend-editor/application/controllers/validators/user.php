<?php

/**
 * fixme add description
 *
 * @since unknown
 */
class CRED_Validator_User extends CRED_Validator_Base implements ICRED_Validator {

	/** @var CRED_User_Factory */
	private $user_factory;


	/**
	 * CRED_Validator_User constructor.
	 *
	 * @param CRED_Form_Base $base_form
	 * @param CRED_User_Factory|null $user_factory_di Allows for dependency injection.
	 */
	public function __construct( $base_form, CRED_User_Factory $user_factory_di = null ) {
		parent::__construct( $base_form );

		$this->user_factory = ( null === $user_factory_di ? new CRED_User_Factory() : $user_factory_di );
	}


	public function validate() {

		$result = true;

		$zebra_form = $this->get_form_rendering();

		$form = $this->get_form_data();
		$form_fields = $form->getFields();
		$form_type = $form_fields['form_settings']->form['type'];
		$is_edit_form = ( 'edit' === $form_type );

		$is_user_form = ( $form->getForm()->post_type === CRED_USER_FORMS_CUSTOM_POST_NAME );

		// No validation if it is not a user.
		if ( ! $is_user_form ) {
			return true;
		}

		if ( isset( $_POST['user_pass'] ) ) {
			if ( $is_edit_form
				&& empty( $_POST['user_pass'] )
				&& empty( $_POST['user_pass2'] )
			) {
				unset( $_POST['user_pass'] );
				unset( $_POST['user_pass2'] );
			}
		}

		if ( ( isset( $_POST['user_pass'] ) && empty( $_POST['user_pass'] ) )
			|| ( isset( $_POST['user_pass2'] ) && empty( $_POST['user_pass2'] ) )
		) {
			$zebra_form->add_top_message( __( 'Password fields are required', 'wp-cred' ) );
			$result = false;
		} else {

			if ( isset( $_POST['user_pass'] )
				&& isset( $_POST['user_pass2'] )
				&& $_POST['user_pass'] != $_POST['user_pass2']
			) {
				$zebra_form->add_top_message( $this->_formHelper->getLocalisedMessage( 'passwords_do_not_match' ) );
				$zebra_form->add_field_message( $this->_formHelper->getLocalisedMessage( 'passwords_do_not_match' ), 'user_pass2' );
				$result = false;
			}
		}

		if ( $is_edit_form ) {
			$user_id_to_edit = isset($_POST[ CRED_StaticClass::PREFIX . 'post_id' ]) ? $_POST[ CRED_StaticClass::PREFIX . 'post_id' ] : 0;
			$_user = $this->user_factory->get_user( $user_id_to_edit );

			if ( isset( $_POST['user_email'] )
				&& $_POST['user_email'] != $this->user_factory->get( $_user, 'user_email' )
				&& email_exists( $_POST['user_email'] )
			) {
				$zebra_form->add_top_message( $this->_formHelper->getLocalisedMessage( 'email_already_exists' ) );
				$zebra_form->add_field_message( $this->_formHelper->getLocalisedMessage( 'email_already_exists' ), 'user_email' );
				$result = false;
			}

			$is_multisite_error = false;
			if ( is_multisite() ) {
				$current_user = wp_get_current_user();

				$user_login = $this->user_factory->get( $_user, 'user_login' );

				$super_admins = get_super_admins();
				$is_user_edited_super_admin = ( is_array( $super_admins ) && in_array( $user_login, $super_admins ) );
				$is_user_editing_super_admin = ( is_array( $super_admins ) && in_array( $user_login, $super_admins ) );
				if ( $is_user_edited_super_admin
					&& ! $is_user_editing_super_admin
				) {
					$is_multisite_error = false;
				}
			}

			$user_role_to_edit = isset( $_user->roles[0] ) ? strtolower( $_user->roles[0] ) : "";
			$user_role_can_edit = json_decode( $form_fields['form_settings']->form['user_role'], true );

			if ( ! empty( $user_role_can_edit )
				&& ! in_array( $user_role_to_edit, $user_role_can_edit )
				&& ! $is_multisite_error
			) {
				$msg = $this->_formHelper->getLocalisedMessage( 'invalid_edit_user_role' );
				if ( false !== strpos( $msg, '%%EDITED_USER_ROLE%%' ) ) {
					$msg = str_replace( '%%EDITED_USER_ROLE%%', $user_role_to_edit, $msg );
				}
				if ( false !== strpos( $msg, '%%SUPPORTED_USER_ROLE%%' ) ) {
					$supported_roles = implode( ", ", $user_role_can_edit );
					$msg = str_replace( '%%SUPPORTED_USER_ROLE%%', $supported_roles, $msg );
				}
				$zebra_form->add_top_message( $msg );
				$result = false;
			}
		} else {
			if ( isset( $_POST['user_email'] )
				&& email_exists( $_POST['user_email'] )
			) {
				$zebra_form->add_top_message( $this->_formHelper->getLocalisedMessage( 'email_already_exists' ) );
				$zebra_form->add_field_message( $this->_formHelper->getLocalisedMessage( 'email_already_exists' ), 'user_email' );
				$result = false;
			}

			if ( isset( $_POST['user_login'] ) ) {
				if ( ! validate_username( $_POST['user_login'] ) ) {
					$zebra_form->add_field_message( $this->_formHelper->getLocalisedMessage( 'invalid_username' ), 'user_login' );
					$result = false;
				} else if ( username_exists( $_POST['user_login'] ) ) {
					$zebra_form->add_top_message( $this->_formHelper->getLocalisedMessage( 'username_already_exists' ) );
					$zebra_form->add_field_message( $this->_formHelper->getLocalisedMessage( 'username_already_exists' ), 'user_login' );
					$result = false;
				}
			}
		}

		return $result;
	}

}
