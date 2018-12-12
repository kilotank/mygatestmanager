<?php

namespace OTGS\Toolset\CRED\Controller\AssociationForms\Editor\Content;

use OTGS\Toolset\CRED\Controller\FormEditorToolbar\Base;

/**
 * Association content editor toolbar controller.
 * 
 * @since 2.1
 */
class Toolbar extends Base {
	
	protected $editor_domain = 'association';
	protected $editor_target = 'cred_association_form_content';
	
	/**
	 * Print the toolbar buttons.
	 *
	 * @since 2.1
	 */
	public function print_toolbar_buttons() {
		$current_form_id = ( 
				'cred_relationship_form' == toolset_getget( 'page' )
				&& 'edit' == toolset_getget( 'action' ) 
			) 
			? (int) toolset_getget( 'id' ) 
			: 0;
		
		$this->print_default_buttons();
		$this->print_third_party_buttons();
		$this->print_media_button( $current_form_id );
	}

	public function print_notification_subject_toolbar_buttons( $editor_id ) {}
	public function print_notification_body_toolbar_buttons( $editor_id ) {}
	public function print_action_message_toolbar_buttons( $editor_id ) {}
	
	/**
	 * Register the toolbar assets.
	 *
	 * @since 2.1
	 */
	protected function init_assets() {
		// We need to adjust the script relpath 
		// since the editor target is not "content" but "cred_association_form_content"
		// and that breaks our files naming consistency.
		$this->js_toolbar_relpath = sprintf( self::JS_TOOLBAR_REL_PATH, $this->editor_domain, 'content' );
		$this->js_toolbar_i18n_name = sprintf( self::JS_TOOLBAR_I18N_NAME, $this->editor_domain, 'content' );
		
		parent::init_assets();
	}
	
	/**
	 * Complete shared data to be used in the toolbar script.
	 *
	 * @return array
	 * 
	 * @since 2.1
	 */
	protected function get_script_localization() {
		$origin = admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' )  );
		$query_args['toolset_force_one_query_arg'] = 'toolset';
		$ajaxurl = esc_url( add_query_arg(
			$query_args,
			$origin
		) );

		$cred_ajax = \CRED_Ajax::get_instance();
		$toolset_ajax = \Toolset_Ajax::get_instance();

		$i18n_shared = $this->get_shared_script_localization();

		$i18n = array(
			'messages' => array(
				'selection_missing' => __( 'You need to select a relationship first', 'wp-cred' )
			),
			'data' => array(
				'ajaxurl' => $ajaxurl,
				'requestObjectFields' => array(
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_GET_RELATIONSHIP_FIELDS ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_GET_RELATIONSHIP_FIELDS )
				),
				'requestPostsByTitle' => array(
					'action' => $toolset_ajax->get_action_js_name( \Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
					'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE )
				),
				'shortcodes' => array(
					'form_container' => \CRED_Shortcode_Association_Form_Container::SHORTCODE_NAME
				),
				'fields' => array(
					'labels' => array(
						'meta' => __( 'Relationship fields', 'wp-cred' ),
						'roles' => __( 'Related posts', 'wp-cred' )
					),
					'fields' => array(
						'formElements' => array(
							'form_container' => array(
								'label' => __( 'Form container', 'wp-cred' ),
								'shortcode' => \CRED_Shortcode_Association_Form_Container::SHORTCODE_NAME,
								'requiredItem' => true,
								'attributes' => array(),
								'options' => array()
							)
						)
					)
				),
				'scaffold' => array(
					'fields' => array(
						'formElements' => array(
							'feedback' => array(
								'label' => __( 'Form messages', 'wp-cred' ),
								'shortcode' => \CRED_Shortcode_Form_Feedback::SHORTCODE_NAME,
								'requiredItem' => true,
								'attributes' => array(),
								'options' => array(
									'type' => array(
										'label'        => __( 'Use this HTML tag', 'wp-cred' ),
										'type'         => 'select',
										'options'      => array(
											'div' => __( 'Div', 'wp-cred' ),
											'span'  => __( 'Span', 'wp-cred' )
										),
										'defaultValue' => 'div'
									),
									'stylingCombo' => array(
										'type'   => 'group',
										'fields' => array(
											'class' => array(
												'label' => __( 'Additional classnames', 'wp-cred' ),
												'type'  => 'text'
											),
											'style' => array(
												'label' => __( 'Additional inline styles', 'wp-cred' ),
												'type'  => 'text'
											)
										),
										'description' => __( 'Include specific classnames in the messages container, or add your own inline styles.', 'wp-cred' )
									)
								),
								'location' => 'bottom'
							),
							'submit' => array(
								'label' => __( 'Submit button', 'wp-cred' ),
								'shortcode' => \CRED_Shortcode_Form_Submit::SHORTCODE_NAME,
								'requiredItem' => true,
								'attributes' => array(),
								'options' => array(
									'type' => array(
										'label'        => __( 'Use this HTML tag', 'wp-cred' ),
										'type'         => 'select',
										'options'      => array(
											'button' => __( 'Button', 'wp-cred' ),
											'input'  => __( 'Input', 'wp-cred' )
										),
										'defaultValue' => 'button'
									),
									'stylingCombo' => array(
										'type'   => 'group',
										'fields' => array(
											'class' => array(
												'label' => __( 'Additional classnames', 'wp-cred' ),
												'type'        => 'text'
											),
											'style' => array(
												'label' => __( 'Additional inline styles', 'wp-cred' ),
												'type'        => 'text'
											)
										),
										'description' => __( 'Include specific classnames in the submit button, or add your own inline styles.', 'wp-cred' )
									)
								),
								'location' => 'bottom'
							),
							'cancel' => array(
								'label'             => __( 'Cancel link', 'wp-cred' ),
								'shortcode'         => \CRED_Shortcode_Form_Cancel::SHORTCODE_NAME,
								'requiredItem'      => false,
								'attributes'        => array(),
								'searchPlaceholder' => __( 'Search', 'wp-cred' ),
								'options'           => array(
									'action'       => array(
										'label'        => __( 'This link will redirect to', 'wp-cred' ),
										'type'         => 'select',
										'options'      => array(
											'same_page'         => __( 'Same page, without any forced CT', 'wp-cred' ),
											'same_page_ct'      => __( 'Same page, forcing a different CT', 'wp-cred' ),
											'different_page_ct' => __( 'Different page, forcing a given CT', 'wp-cred' )
										),
										'defaultValue' => 'same_page'
									),
									'select_page'  => array(
										'label' => __( 'User will be redirected to', 'wp-cred' ),
										'type'  => 'select'
									),
									'select_ct'    => array(
										'label'   => __( 'Force following Content template', 'wp-cred' ),
										'type'    => 'select',
										'options' => array(),
									),
									'message'      => array(
										'label'       => __( 'Redirect confirmation message', 'wp-cred' ),
										'type'        => 'text',
										'placeholder' => __( 'You will be redirected, do you want to proceed?', 'wp-cred' ),
									),
									'stylingCombo' => array(
										'type'        => 'group',
										'fields'      => array(
											'class' => array(
												'label' => __( 'Additional classnames', 'wp-cred' ),
												'type'  => 'text'
											),
											'style' => array(
												'label' => __( 'Additional inline styles', 'wp-cred' ),
												'type'  => 'text'
											)
										),
										'description' => __( 'Include specific classnames in the cancel button, or add your own inline styles.', 'wp-cred' )
									)
								),
								'location' => 'bottom'
							)
						)
					)
				)
			)
		);
		
		return array_merge( $i18n_shared, $i18n );
	}
	
}