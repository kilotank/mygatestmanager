<?php

class CRED_Validator_Nonce extends CRED_Validator_Base implements ICRED_Validator {

    public function validate() {
        $result = true;
        if (is_user_logged_in()) {
            $nonce_id = substr($this->_zebraForm->form_properties['name'], 0, strrpos($this->_zebraForm->form_properties['name'], '_'));
            if (!array_key_exists(CRED_StaticClass::NONCE . "_" . $nonce_id, $_POST) ||
                    !wp_verify_nonce($_POST[CRED_StaticClass::NONCE . "_" . $nonce_id], $nonce_id)) {
                $this->_zebraForm->add_top_message($this->_formHelper->getLocalisedMessage('invalid_form_submission'));
                $this->_zebraForm->add_field_message($this->_formHelper->getLocalisedMessage('invalid_form_submission'));
                $result = false;
            }
        }
        return $result;
    }

}
