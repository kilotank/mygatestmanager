<?php

/**
 * Class responsible of types/generic audio field creation on frontend
 */
class WPToolset_Field_Credaudio extends CRED_Abstract_WPToolset_Field_Credfile {

	protected $_settings = array( 'min_wp_version' => '3.6' );

	/**
	 * Specification of metaform that contains description array of field structure
	 *
	 * @return array|void
	 */
	public function metaform() {
		//TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
		$validation = $this->getValidationData();
		$this->set_allowed_extensions_validation_by_field_upload_type( $validation, 'audio' );
		$this->setValidationData( $validation );

		return parent::metaform();
	}
}
