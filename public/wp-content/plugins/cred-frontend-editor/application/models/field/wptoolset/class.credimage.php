<?php

/**
 * Class responsible of types/generic image field creation on frontend
 */
class WPToolset_Field_Credimage extends CRED_Abstract_WPToolset_Field_Credfile {

	/**
	 * Specification of metaform that contains description array of field structure
	 *
	 * @return array|void
	 */
	public function metaform() {
		$validation = $this->getValidationData();
		$this->set_allowed_extensions_validation_by_field_upload_type( $validation, 'image' );
		$this->setValidationData( $validation );

		return parent::metaform();
	}
}
