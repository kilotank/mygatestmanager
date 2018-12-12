<?php

namespace OTGS\Toolset\CRED\Model\Field\Generic;

class Gui {

	const SHORTCODE_NAME_FORM_GENERIC_FIELD = 'cred_generic_field';
	
	/**
	 * Generate the attribute options to fill a set of fields values
	 * from a shortcode outcome.
	 *
	 * @return array
	 * @since 2.1.1
	 */
	private function get_shortcode_source_options() {
		return array(
			'type'  => 'text',
			'placeholder' => __( 'Add your shortcode here', 'wp-cred' ),
			'description' => __( 'Note that this shortcode needs to generate a valid list of JSON objects with the right format:', 'wp-cred' )
				. '<br />'
				. '<code>'
				. '{"value": "value1", "label": "Label 1"}, {"value": "value2", "label": "Label 2"}'
				. '</code>'
		);
	}

    /**
	 * Gather a list of generic fields available togther with their attributes.
     * 
     * Used to add generic fields to a form editor, and also to add or edit options
     * for non Toolset fields under Forms control.
     * 
     * @note Do not modify grouped attributes, since the fields controls GUI needs to
     *       filter out some of the top-level items.
	 *
	 * @return array
	 * 
	 * @since 2.1
	 */
	public function get_generic_fields() {
		return array(
			'audio' => array(
				'label' => __( 'Audio', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'audio'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'checkboxes' => array(
				'label' => __( 'Checkboxes', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'checkboxes'
				),
				'options' => array(
					'field' => array(
						'label' => __( 'Field slug', 'wp-cred' ),
						'type'  => 'text',
						'required' => true
					),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					)
				)
			),
			'checkbox' => array(
				'label' => __( 'Checkbox', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'checkbox'
				),
				'options' => array(
                    'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
					'label' => array(
                        'label' => __( 'Field label', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
                    'required' => array(
                        'label' => __( 'Should this field be required?', 'wp-cred' ),
                        'type'  => 'radio',
                        'options' => array(
                            'no' => __( 'No, do not make this field required', 'wp-cred' ),
                            'yes' => __( 'Yes, make this field required', 'wp-cred' )
                        ),
                        'defaultValue' => 'no'
                    )
				)
			),
			'colorpicker' => array(
				'label' => __( 'Colorpicker', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'colorpicker'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'date' => array(
				'label' => __( 'Date', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'date'
				),
				'options' => array(
					'field' => array(
                        'label' => __( 'Field slug', 'wp-cred' ),
                        'type'  => 'text',
                        'required' => true
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'email' => array(
				'label' => __( 'Email', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'email'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'embed' => array(
				'label' => __( 'Embedded Media', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD, 
				'attributes' => array(
					'type' => 'embed'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'file' => array(
				'label' => __( 'File', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'file'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'hidden' => array(
				'label' => __( 'Hidden', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'hidden'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
					),
					'generic_type' => array(
						'label' => __( 'Notifications recipient source', 'wp-cred' ),
						'type'  => 'radio',
						'options' => array(
							'' => __( 'Do not use this field on notifications', 'wp-cred' ),
							'user_id' => __( 'This field value is an user ID, and should be included in the list of available recipients for notifications', 'wp-cred' ),
						),
						'defaultValue' => '',
						'description' => __( 'Toolset Forms can send notifications to an user whose ID is saved in a generic field, if you select it in the notification settings.', 'wp-cred' )
					)
				)
			),
			'image' => array(
				'label' => __( 'Image', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'image'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'integer' => array(
				'label' => __( 'Integer', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'integer'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'multiselect' => array(
				'label' => __( 'Multiselect', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'multiselect'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					)
				)
			),
			'numeric' => array(
				'label' => __( 'Numeric', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'numeric'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'password' => array(
				'label' => __( 'Password', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'password'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'phone' => array(
				'label' => __( 'Phone', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'phone'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'required' => array(
                        'label' => __( 'Should this field be required?', 'wp-cred' ),
                        'type'  => 'radio',
                        'options' => array(
                            'no' => __( 'No, do not make this field required', 'wp-cred' ),
                            'yes' => __( 'Yes, make this field required', 'wp-cred' )
                        ),
                        'defaultValue' => 'no'
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'radio' => array(
				'label' => __( 'Radio', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'radio'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					),
					'generic_type' => array(
						'label' => __( 'Notifications recipient source', 'wp-cred' ),
						'type'  => 'radio',
						'options' => array(
							'' => __( 'Do not use this field on notifications', 'wp-cred' ),
							'user_id' => __( 'This field value is an user ID, and should be included in the list of available recipients for notifications', 'wp-cred' ),
						),
						'defaultValue' => '',
						'description' => __( 'Toolset Forms can send notifications to an user whose ID is saved in a generic field, if you select it in the notification settings.', 'wp-cred' )
					)
				)
			),
			'select' => array(
				'label' => __( 'Select', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'select'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					),
					'generic_type' => array(
						'label' => __( 'Notifications recipient source', 'wp-cred' ),
						'type'  => 'radio',
						'options' => array(
							'' => __( 'Do not use this field on notifications', 'wp-cred' ),
							'user_id' => __( 'This field value is an user ID, and should be included in the list of available recipients for notifications', 'wp-cred' ),
						),
						'defaultValue' => '',
						'description' => __( 'Toolset Forms can send notifications to an user whose ID is saved in a generic field, if you select it in the notification settings.', 'wp-cred' )
					)
				)
			),
			'skype' => array(
				'label' => __( 'Skype', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'skype'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'required' => array(
                        'label' => __( 'Should this field be required?', 'wp-cred' ),
                        'type'  => 'radio',
                        'options' => array(
                            'no' => __( 'No, do not make this field required', 'wp-cred' ),
                            'yes' => __( 'Yes, make this field required', 'wp-cred' )
                        ),
                        'defaultValue' => 'no'
                    )
				)
			),
			'textarea' => array(
				'label' => __( 'Multiple Lines', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'textarea'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'default' => array(
						'label' => __( 'Default field value', 'wp-cred' ),
						'type'  => 'textarea',
					)
				)
			),
			'textfield' => array(
				'label' => __( 'Single Line', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'textfield'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'required' => array(
                        'label' => __( 'Should this field be required?', 'wp-cred' ),
                        'type'  => 'radio',
                        'options' => array(
                            'no' => __( 'No, do not make this field required', 'wp-cred' ),
                            'yes' => __( 'Yes, make this field required', 'wp-cred' )
                        ),
                        'defaultValue' => 'no'
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'url' => array(
				'label' => __( 'URL', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'url'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'default' => array(
								'label' => __( 'Default field value', 'wp-cred' ),
								'type'  => 'text',
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							),
							'validate_format' => array(
								'label' => __( 'Validate Format', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'Do not validate this field', 'wp-cred' ),
									'yes' => __( 'Validate this field', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
					)
				)
			),
			'video' => array(
				'label' => __( 'Video', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'video'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => array(
								'label' => __( 'Field slug', 'wp-cred' ),
								'type'  => 'text',
								'required' => true
							),
							'required' => array(
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'no' => __( 'No, do not make this field required', 'wp-cred' ),
									'yes' => __( 'Yes, make this field required', 'wp-cred' )
								),
								'defaultValue' => 'no'
							)
						)
                    ),
                    'class' => array(
                        'label' => __( 'Additional CSS classnames', 'wp-cred' ),
                        'type'  => 'text'
                    )
				)
			),
			'wysiwyg' => array(
				'label' => __( 'WYSIWYG', 'wp-cred' ), 
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'wysiwyg'
				),
				'options' => array(
					'field' => array(
						'label' => __( 'Field slug', 'wp-cred' ),
						'type'  => 'text',
						'required' => true
					),
					'default' => array(
						'label' => __( 'Default field value', 'wp-cred' ),
						'type'  => 'textarea',
					)
				)
			)
		);
    }

    public function get_generic_fields_labels() {
        $labels = array();
        
        $generic_fields = $this->get_generic_fields();
        foreach ( $generic_fields as $field_slug => $field_data ) {
            $labels[ $field_slug ] = $field_data['label'];
        }

        return $labels;
    }
    
}