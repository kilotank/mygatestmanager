<?php

class CRED_Validator_Post extends CRED_Validator_Base implements ICRED_Validator {

    public function validate() {
        $result = true;
        if (empty($_POST)) {
            // This happens when the form is submitted but no data was posted
            // We are trying to upload a file greater then the maximum allowed size
            // So we should display a custom error
            //$zebraForm->add_form_error('security', $formHelper->getLocalisedMessage('no_data_submitted'));
            $this->_zebraForm->add_top_message($this->_formHelper->getLocalisedMessage('no_data_submitted'));
            $this->_zebraForm->add_field_message($this->_formHelper->getLocalisedMessage('no_data_submitted'));
            $result = false;
        }
        return $result;
    }

}
