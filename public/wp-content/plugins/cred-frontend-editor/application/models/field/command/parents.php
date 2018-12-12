<?php

/**
 * Class that transform "Parent Fields" shortcode attributes to field object for rendering fields
 *
 * - _wpcf_belongs_post_id
 *
 * @since 2.0
 */
class CRED_Field_Command_Parents extends CRED_Field_Command_Base {

	public function execute() {
		$field = CRED_StaticClass::$out[ 'fields' ][ 'parents' ][ $this->field_name ];

		$field[ 'form_html_id' ] = $this->translate_field_factory->get_html_form_field_id( $field );

		$name = $this->field_name;
		
		$force_author = ( isset( $this->filtered_attributes[ 'author' ] ) ) ? $this->filtered_attributes[ 'author' ] : '';
		$force_author = $this->maybe_set_ancestor_filter_by_author( $force_author, $this->field_name );
		
		//Adding Order by and Ordering attributes to Field in order to be available directly
		//on select2 JS in "fieldSettings" variable, thanks to try_register_relationship_parent_as_select2 function
		$field[ 'order_by' ] = ( isset( $this->filtered_attributes[ 'order' ] ) ) ? $this->filtered_attributes[ 'order' ] : 'ID';
		$field[ 'order' ] = ( isset( $this->filtered_attributes[ 'ordering' ] ) ) ? $this->filtered_attributes[ 'ordering' ] : 'DESC';

		/*
		 * Try to get placeholder by shortcode select_text attribute
		 * or by description if exists
		 */
		$field[ 'placeholder' ] = ( isset( $this->filtered_attributes[ 'select_text' ] ) ) ? $this->filtered_attributes[ 'select_text' ] : ( isset( $field[ 'description' ] ) ? $field[ 'description' ] : "" );
		$field[ 'wpml_context' ] = $this->form->getForm()->post_type . '-' . $this->form->getForm()->post_title . '-' . $this->form->getForm()->ID;

		$field[ 'data' ][ 'validate' ] = array();
		if ( $this->required ) {
			$field[ 'data' ][ 'validate' ] = array(
				'required' => array( 'message' => $this->filtered_attributes[ 'validate_text' ], 'active' => 1 ),
			);
		}
		
		$forced_args = array();
		$forced_args['orderby'] = $field[ 'order_by' ];
		$forced_args['order'] = $field[ 'order' ];
		if ( '' !== $force_author ) {
			$force_author = (int) $force_author;
			if ( $force_author > 0 ) {
				$forced_args['author'] = $force_author;
			} else {
				$forced_args['post__in'] = array( '0' );
			}
		}

		//Manage parent select field with select2
		$potential_parents = CRED_Select2_Utils::get_instance()->try_register_parent_as_select2( $this->filtered_attributes[ 'html_form_id' ], $this->field_name, $field, $this->filtered_attributes[ 'max_results' ], $this->filtered_attributes[ 'use_select2' ], $forced_args );

		$field[ 'data' ][ 'options' ] = array();
		$default_option = null;
		/*
		 * enable setting parent form url param ([cred_child_link_form])
		 */
		if ( array_key_exists( 'parent_' . $field[ 'data' ][ 'post_type' ] . '_id', $_GET ) ) {
			$default_option = (int) $_GET[ 'parent_' . $field[ 'data' ][ 'post_type' ] . '_id' ];
		}

		foreach ( $potential_parents as $ii => $option ) {
			$option_id = (string) ( $option->ID );
			$field[ 'data' ][ 'options' ][ $option_id ] = array(
				'title' => $option->post_title,
				'value' => $option_id,
				'display_value' => $option_id,
			);
		}
		$field[ 'data' ][ 'options' ][ 'default' ] = $default_option;

		$additional_attributes = array(
			'preset_value' => $this->value,
			'urlparam' => $this->filtered_attributes[ 'urlparam' ],
			'make_readonly' => $this->readonly,
			'max_width' => $this->filtered_attributes[ 'max_width' ],
			'max_height' => $this->filtered_attributes[ 'max_height' ],
			'class' => $this->filtered_attributes[ 'class' ],
			'output' => $this->filtered_attributes[ 'output' ],
			'select_text' => $this->filtered_attributes[ 'select_text' ],
			'data-orderby' => $field[ 'order_by' ],
			'data-order' => $field[ 'order' ],
			'data-author' => $force_author,
		);

		$field_object = $this->translate_field_factory->cred_translate_field( $name, $field, $additional_attributes );

		/*
		 * We need to register the current value of the select field
		 * that could have a default value from $_GET post_[parent]_id
		 */
		if ( null != $default_option
			&& get_post_type( $default_option ) !== $field[ 'data' ][ 'post_type' ]
		) {
			$default_option = null;
			$html_form_id = $this->filtered_attributes[ 'html_form_id' ];
			$this->expected_parent_post_type = $field[ 'data' ][ 'post_type' ];

			add_action( 'cred_before_html_form_' . $html_form_id, array(
				$this,
				'add_default_parent_post_type_top_error_message',
			) );
		}

		$current_value = isset( $default_option ) && ( ! isset( $field_object[ 'value' ] ) || empty( $field_object[ 'value' ] ) ) ? $default_option : $field_object[ 'value' ];
		CRED_Select2_Utils::get_instance()->set_current_value_to_registered_select2_field( $this->filtered_attributes[ 'html_form_id' ], $this->field_name, $current_value, $field[ 'data' ][ 'post_type' ] );

		/*
		 * check which fields are actually used in form
		 */
		CRED_StaticClass::$out[ 'form_fields' ][ $name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out[ 'form_fields_info' ][ $name ] = array(
			'type' => $field[ 'type' ],
			'repetitive' => ( isset( $field[ 'data' ][ 'repetitive' ] ) && $field[ 'data' ][ 'repetitive' ] ),
			'plugin_type' => ( isset( $field[ 'plugin_type' ] ) ) ? $field[ 'plugin_type' ] : '',
			'name' => $name,
		);
		
		return $field_object;
	}
	
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

		/**
		 * Force a post author on a specific post form parent selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_parent_in_post_form_' . $form_id,
			$force_author,
			$field_name
		);
		/**
		 * Force a post author on a specific post form and a specific parent selector.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_' . $field_name . '_parent_in_post_form_' . $form_id,
			$force_author
		);
		/**
		 * Force a post author on all post forms parent selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_parent_in_post_form',
			$force_author,
			$form_id,
			$field_name
		);
		/**
		 * Force a post author on all CRED interfaces to set a related post.
		 *
		 * This is also used in the frontend post forms when setting a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_related_post',
			$force_author
		);
		
		/**
		 * Force a post author on all Toolset interfaces to set a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'toolset_force_author_in_related_post',
			$force_author
		);
		
		if ( '$current' === $force_author ) {
			$force_author = get_current_user_id();
			$force_author = (int) $force_author;
		}

		return $force_author;
	}
}