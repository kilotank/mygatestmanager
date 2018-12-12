<?php

/**
 * Class CRED_Shortcode_Association_Field
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Field extends CRED_Shortcode_Association_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-relationship-field';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'name'  => '', // field name
		'class' => '', // classnames
		'style' => '' // extra inline styles
	);

	/**
	 * @var string|null
	 */
	private $user_content;
	
	/**
	 * @var array
	 */
	private $user_atts;

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since m2m
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;
		
		if ( empty( $this->user_atts['name'] ) ) {
			return;
		}
		
		$current_association = $this->helper->get_current_association();
		
		$definition_factory = Toolset_Field_Definition_Factory_Post::get_instance();
		$field_definition = $definition_factory->load_field_definition( $this->user_atts['name'] );
		$field = $field_definition->get_definition_array();
		
		if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
			// repeatable field group container, skip
			return;
		}
		
		if ( $field['type'] == 'post' ) {
			// post reference field, manage separatedly, skip
			return;
		}
		
		if ( $field['type'] == 'wysiwyg' ) {
			// wysiwyg field, manage separatedly, skip
			return;
		}
		
		if ( in_array( $field['type'], array( 'audio', 'file', 'image', 'video' ) ) ) {
			$field['type'] = 'cred' . $field['type'];
		}
		
		if ( $current_association instanceof Toolset_Post ) {
			$meta   = get_post_meta( $current_association->get_id(), $field['meta_key'] );
			$config = wptoolset_form_filter_types_field( $field, $current_association->get_id() );
		} else {
			$meta = array();
			$config = wptoolset_form_filter_types_field( $field );
		}
		
		return wptoolset_form_field( 'post', $config, $meta );
		
	}
	
	
}