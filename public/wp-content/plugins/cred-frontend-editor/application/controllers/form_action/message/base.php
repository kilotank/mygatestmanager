<?php

namespace OTGS\Toolset\CRED\Controller\FormAction\Message;

/**
 * Forms action as message base controller.
 * 
 * @since 2.1.2
 */
class Base {

    /**
     * Get the message to display after submitting a form.
     *
	 * @param \CRED_Form_Data $form
	 *
	 * @return string
     * 
     * @since 2.1.2
	 */
    public function get_action_message( \CRED_Form_Data $form_data ) {
        $form_fields = $form_data->getFields();
        $form_object = $form_data->getForm();//->post_type

        $rendered_message = cred_translate(
            'Display Message: ' . $form_object->post_title,
            $form_fields['form_settings']->form['action_message'],
            'cred-form-' . $form_object->post_title . '-' . $form_object->ID
        );

		// Apply filters and set context
		$rendered_message = $this->apply_content_to_action_message(
			$rendered_message
		);

        $success_message_id = sanitize_text_field( toolset_getget( '_success_message' ) );
        
        $message = sprintf( '<div id="cred_form_%s">%s</div>', esc_attr( $success_message_id ), $rendered_message );
        return $message;
    }

    protected function apply_content_to_action_message( $message ) {
        return $message;
    }

}