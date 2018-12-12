<?php

/**
 * Main shortcodes controller for Toolset Forms.
 * 
 * @since m2m
 */
final class CRED_Shortcodes {

	/**
	 * @var CRED_Shortcode_Factory
	 */
	private $factory = null;

	/**
	 * @var array
	 */
	private $prerendered_shortcodes = array();

	public function __construct( $di_factory = null ) {
		if ( $di_factory instanceof CRED_Shortcode_Factory ) {
			$this->factory = $di_factory;
		} else {
			$this->set_factory();
		}
	}

	/**
	 * Instantiate the shortcodes factory on demand.
	 *
	 * @since 2.1
	 */
	private function set_factory() {
		$frontend_form_flow = new CRED_Frontend_Form_Flow();
		$relationship_service = new Toolset_Relationship_Service();
		$attr_item_chain = new Toolset_Shortcode_Attr_Item_From_Views( 
			new Toolset_Shortcode_Attr_Item_M2M(
				new Toolset_Shortcode_Attr_Item_Legacy(
					new Toolset_Shortcode_Attr_Item_Id(),
					$relationship_service
				),
				$relationship_service
			),
			$relationship_service
		);
		
		$helpers = array(
			'association' => new CRED_Shortcode_Association_Helper( $frontend_form_flow, $relationship_service, $attr_item_chain )
		);
		
		$this->factory = new CRED_Shortcode_Factory( $frontend_form_flow, $attr_item_chain, $helpers );
	}

	/**
	 * Initialize the Toolset Forms shortcodes.
	 *
	 * @since 2.0
	 */
	public function initialize() {
		$this->register_form_elements_shortcodes();
		$this->register_form_links_shortcodes();
		$this->register_association_forms_shortcodes();
		$this->register_form_message_shortcode();

		$this->manage_preprocessed_shortcodes();
	}

	/**
	 * Register a single shortcode into the WordPress API.
	 *
	 * @param string $shortcode_string
	 * 
	 * @since 2.1
	 */
	private function register_shortcode( $shortcode_string ) {
		if ( $shortcode = $this->factory->get_shortcode( $shortcode_string ) ) {
			add_shortcode( $shortcode_string, array( $shortcode, 'render' ) );
		};
	}

	/**
	 * Register a single shortcode into the WordPress API, and make sure it gets prerendered on print.
	 *
	 * @param string $shortcode_string
	 * 
	 * @since 2.1
	 */
	private function register_prerendered_shortcode( $shortcode_string ) {
		if ( $shortcode = $this->factory->get_shortcode( $shortcode_string ) ) {
			add_shortcode( $shortcode_string, array( $shortcode, 'render' ) );
			$this->prerendered_shortcodes[ $shortcode_string ] = array( $shortcode, 'render' );
		};
	}

	/**
	 * Register the shared forms elements shortcodes.
	 *
	 * @since 2.1
	 */
	private function register_form_elements_shortcodes() {
		$registering_shortcodes = array(
			CRED_Shortcode_Form_Submit::SHORTCODE_NAME,
			CRED_Shortcode_Form_Cancel::SHORTCODE_NAME,
			CRED_Shortcode_Form_Feedback::SHORTCODE_NAME
		);
		
		foreach ( $registering_shortcodes as $shortcode_string ) {
			$this->register_shortcode( $shortcode_string );
		}
	}

	/**
	 * Register the form links shortcodes.
	 *
	 * @since 2.1
	 */
	private function register_form_links_shortcodes() {
		$registering_shortcodes = array(
			OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Post::SHORTCODE_NAME,
			OTGS\Toolset\CRED\Model\Shortcode\Form\Link\User::SHORTCODE_NAME,
			OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Association::SHORTCODE_NAME
		);
		
		foreach ( $registering_shortcodes as $shortcode_string ) {
			$this->register_shortcode( $shortcode_string );
		}
	}

	/**
	 * Register the assocition forms shortcodes.
	 *
	 * @since 2.1
	 */
	private function register_association_forms_shortcodes() {
		$registering_shortcodes = array(
			CRED_Shortcode_Association_Form::SHORTCODE_NAME,
			// Association form elements
			CRED_Shortcode_Association_Form_Container::SHORTCODE_NAME,
			CRED_Shortcode_Association_Title::SHORTCODE_NAME,
			CRED_Shortcode_Association_Field::SHORTCODE_NAME,
			CRED_Shortcode_Association_Role::SHORTCODE_NAME,
			// Delete association shortcode
			OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME
		);
		
		foreach ( $registering_shortcodes as $shortcode_string ) {
			$this->register_shortcode( $shortcode_string );
		}
	}

	/**
	 * Register the forms message shortcode.
	 *
	 * @since 2.1
	 */
	private function register_form_message_shortcode() {
		$this->register_prerendered_shortcode( OTGS\Toolset\CRED\Model\Shortcode\Form\Message::SHORTCODE_NAME );
	}

	/**
	 * Manage preprocessed shortcodes by initializing the right hooks.
	 *
	 * @since 2.1
	 * @since 2.1.2 Include the \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME filter
	 */
	private function manage_preprocessed_shortcodes() {
		add_filter( 'the_content', array( $this, 'preprocessed_shortcodes' ), 5 );
		add_filter( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, array( $this, 'preprocessed_shortcodes' ), 5 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $this, 'preprocessed_shortcodes' ), 5 );
		add_filter( 'wpv-pre-do-shortcode', array( $this, 'preprocessed_shortcodes' ), 5 );
	}

	/**
	 * Expand the preprocessed shortcodes early.
	 *
	 * As  shortcodes might produce an HTML block element, the WordPress formatting mechanism 
	 * would wrap them into paragraph tags if expanded in the native point of the page rendering.
	 * Because of that, we parse and expand those shortcodes early (at the_content:5) 
	 * to prevent formatting issues.
	 *
	 * @param string $content The current post content being rendered
	 *
	 * @return string
	 *
	 * @since 2.1
	 */
	public function preprocessed_shortcodes( $content ) {
		$shortcodes_to_preprocess = array();

		foreach ( $this->prerendered_shortcodes as $shortcode_string => $shortcode_callback ) {
			if ( strpos( $content, '[' . $shortcode_string ) !== false ) {
				$shortcodes_to_preprocess[ $shortcode_string ] = $shortcode_callback;
			}
		}

		if ( empty( $shortcodes_to_preprocess ) ) {
			return $content;
		}

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		foreach ( $shortcodes_to_preprocess as $shortcode_string => $shortcode_callback ) {
			add_shortcode( $shortcode_string, $shortcode_callback );
		}
		$content = do_shortcode( $content );
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

}