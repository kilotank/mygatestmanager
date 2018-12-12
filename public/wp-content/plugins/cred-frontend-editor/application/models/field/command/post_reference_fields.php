<?php

/**
 * Class that transform "Relationships Fields" shortcode attributes to field object for rendering fields
 *
 * @since m2m
 */
class CRED_Field_Command_Post_Reference_Fields extends CRED_Field_Command_Base {
	
	/**
	 * @var boolean
	 */
	private $disabled_for_non_default_wpml_language = false;

	public function execute() {
		$field = CRED_StaticClass::$out['fields']['post_reference_fields'][ $this->field_name ];

		$field['type'] = 'select';
		$field['post_id'] = $this->form_rendering->_post_id;
		$field['form_html_id'] = $this->translate_field_factory->get_html_form_field_id( $field );

		$field_name = $this->field_name;
		$max_results = ( isset( $this->filtered_attributes[ 'max_results' ] ) ) ? $this->filtered_attributes[ 'max_results' ] : null;
		$use_select2 = ( isset( $this->filtered_attributes[ 'use_select2' ] ) ) ? $this->filtered_attributes[ 'use_select2' ] : null;
		$force_author = ( isset( $this->filtered_attributes[ 'author' ] ) ) ? $this->filtered_attributes[ 'author' ] : '';

		//Adding Order by and Ordering attributes to Field in order to be available directly
		//on select2 JS in "fieldSettings" variable, thanks to try_register_relationship_parent_as_select2 function
		$field[ 'order_by' ] = ( isset( $this->filtered_attributes[ 'order' ] ) ) ? $this->filtered_attributes[ 'order' ] : 'ID';
		$field[ 'order' ] = ( isset( $this->filtered_attributes[ 'ordering' ] ) ) ? $this->filtered_attributes[ 'ordering' ] : 'DESC';

		/*
		 * Try to get placeholder by shortcode select_text attribute
		 * or by description if exists
		 */
		$field['placeholder'] = ( isset( $this->filtered_attributes[ 'select_text' ] ) )
			? $this->filtered_attributes[ 'select_text' ]
			: (
				isset( $field['description'] )
				? $field['description']
				: ""
			);
		
		$field['wpml_context'] = $this->form->getForm()->post_type 
			. '-' . $this->form->getForm()->post_title 
			. '-' . $this->form->getForm()->ID;
		
		$field['data']['validate'] = array();
		if ( $this->required ) {
			$field['data']['validate'] = array(
				'required' => array( 
					'message' => $this->filtered_attributes[ 'validate_text' ], 
					'active' => 1 
				),
			);
		}

		$field['data']['options'] = array();
		$default_option = null;

		/*
		 * set current post as parent if value attribute is $current
		 */
		$current_as_parent = false;
		if ( '$current' === $this->filtered_attributes['value'] ){
			global $post;
			$default_option = $post->ID;
			$current_as_parent = true;
			$field['readonly'] = true;
		}

		//Manage parent select field with select2
		$potential_parents = CRED_Select2_Utils::get_instance()->try_register_relationship_parent_as_select2( 
			$this->form_rendering->html_form_id, $field_name, $field, $max_results, $use_select2 
		);

		foreach ( $potential_parents as $ii => $option ) {
			$option_id = (string) ( $option->ID );
			$field['data']['options'][ $option_id ] = array(
				'title' => $option->post_title,
				'value' => $option_id,
				'display_value' => $option_id,
			);
		}
		$field['data']['options']['default'] = $default_option;
		
		$force_author = $this->maybe_set_ancestor_filter_by_author( $force_author, $field_name );
		
		$this->maybe_disable_for_non_default_wpml_language();

		$additional_attributes = array(
			'preset_value' => $this->value,
			'urlparam' => $this->filtered_attributes[ 'urlparam' ],
			'make_readonly' => $this->readonly,
			'max_width' => $this->filtered_attributes[ 'max_width' ],
			'max_height' => $this->filtered_attributes[ 'max_height' ],
			'class' => $this->filtered_attributes[ 'class' ],
			'output' => $this->filtered_attributes[ 'output' ],
			'select_text' => $this->filtered_attributes[ 'select_text' ],
			'data-orderby' => $field['order_by'],
			'data-order' => $field['order'],
			'data-author' => $force_author
		);

		$field_object = $this->translate_field_factory->cred_translate_field( $field_name, $field, $additional_attributes );

		// Get Prf Relationship definition
		$relationship_definition = CRED_Form_Relationship::get_instance()->get_definition_by_relationship_slug( $field[ 'relationship' ][ 'slug' ] );

		// Get possible association parent
		$results = CRED_Form_Relationship::get_instance()->get_ruled_association_by_id( $this->form_rendering->_post_id, $relationship_definition, $field[ 'role' ] );
		
		if ( count( $results ) > 0
			&& isset( $results[ 0 ][ 'post' ] )
		) {
			//TODO: maybe we need to improve checking here
			$current_post_id = $results[ 0 ][ 'post' ]->get_id();
			$field_object[ 'value' ] = $current_post_id;
			if ( ! isset( $field_object[ 'attr' ] ) ) {
				$field_object[ 'attr' ] = array();
			}
			$field_object[ 'attr' ][ 'actual_value' ] = $current_post_id;
		}

		/*
		 * We need to register the current value of the select field
		 * that could have a default value from $_GET post_[parent]_id
		 */
		if ( null != $default_option
			&& get_post_type( $default_option ) !== $field['data']['post_type']
		) {
			$default_option = null;
			$html_form_id = $this->form_rendering->html_form_id;
			
			$this->expected_parent_post_type = $field['data']['post_type'];

			add_action( 'cred_before_html_form_' . $html_form_id, array( $this, 'add_default_parent_post_type_top_error_message' ) );
		}

		$current_value = isset( $default_option ) && ( ! isset( $field_object['value'] ) || empty( $field_object['value'] ) || $current_as_parent === true ) 
			? $default_option 
			: $field_object['value'];
		
		CRED_Select2_Utils::get_instance()->set_current_value_to_registered_select2_field( 
			$this->form_rendering->html_form_id, $field_name, $current_value, $field['data']['post_type'] 
		);

		/*
		 * check which fields are actually used in form
		 */
		CRED_StaticClass::$out['form_fields'][ $field_name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out['form_fields_info'][ $field_name ] = array(
			'type' => $field['type'],
			'repetitive' => ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] ),
			'plugin_type' => ( isset( $field['plugin_type'] ) ) ? $field['plugin_type'] : '',
			'name' => $field_name,
		);
		
		if (
			$this->disabled_for_non_default_wpml_language
			&& current_user_can( 'manage_options' )
		) {
			$field_object['description'] = __( 'Post reference fields can only be managed in the default language.', 'wp-cred' );
		}
		
		return $field_object;
	}
	
	/**
	 * Disable post reference fields displayed on a form to create posts in a language different than default.
	 *
	 * Post reference fields behave like associations, and you can only create an association
	 * for a post that has a translation into the default language.
	 * When creating new posts, this does not happen automatically.
	 *
	 * @since 2.1
	 */
	private function maybe_disable_for_non_default_wpml_language() {
		if ( 'new' != $this->form_type ) {
			return;
		}
		if ( apply_filters( 'wpml_default_language', '' ) != apply_filters( 'wpml_current_language', '' ) ) {
			$this->disabled_for_non_default_wpml_language = true;
			$this->readonly = true;
		}
	}

	/**
	 * @param $force_author
	 * @param $field_name
	 *
	 * @return int
	 */
	private function maybe_set_ancestor_filter_by_author( $force_author, $field_name ) {
		if ( '$current' === $force_author ) {
			$force_author = get_current_user_id();
			$force_author = (int) $force_author;
			return $force_author;
		}
		
		if ( ! empty( $force_author ) ) {
			return $force_author;
		}
		
		$form_id = $this->form_rendering->form_id;
		
		$query_arguments = new Toolset_Potential_Association_Query_Arguments();
		
		$query_arguments->addFilter( 
			new CRED_Potential_Association_Query_Filter_Posts_Author_For_Post_Ancestor( $form_id, $field_name ) 
		);
		
		$additional_query_arguments = $query_arguments->get();
		$query_args = toolset_ensarr( toolset_getarr( $additional_query_arguments, 'wp_query_override' ) );
		
		if ( array_key_exists( 'author', $query_args ) ) {
			$force_author = (int) $query_args['author'];
			return $force_author;
		}
		
		if ( array( '0' ) === toolset_getarr( $query_args, 'post__in' ) ) {
			$force_author = 0;
			return $force_author;
		}
		
		return $force_author;
	}
}