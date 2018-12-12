<?php

/**
 * Class CRED_Shortcode_Form_Container_Base
 *
 * @since m2m
 */
class CRED_Shortcode_Form_Container_Base implements CRED_Shortcode_Interface {

	const REDIRECT_KEY = 'redirect_to';
	const AJAX_SUBMIT_KEY = 'ajax_submission';
	const CUSTOM_POST_KEY = 'redirect_custom_post';

	const REDIRECT_REFERRER_FORM_ID_KEY = 'cred_referrer_form_id';

	/**
	 * @var array
	 */
	protected $shortcode_atts = array();

	/**
	 * @var string|null
	 */
	protected $user_content;

	/**
	 * @var array
	 */
	protected $user_atts;

	/**
	 * @var array
	 */
	protected $permanent_query_args = array();

	/**
	 * @var CRED_Shortcode_Association_Helper
	 */
	protected $helper;

	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	protected $frontend_form_flow;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var string
	 */
	protected $redirect_to;

	/**
	 * @var string
	 */
	protected $redirect_url;


	/**
	 * @param CRED_Shortcode_Association_Helper $helper
	 */
	public function __construct( CRED_Shortcode_Helper_Interface $helper ) {
		$this->helper = $helper;
		$this->frontend_form_flow = $helper->get_frontend_form_flow();
	}

	/**
	 * @return CRED_Frontend_Form_Flow
	 *
	 * @since m2m
	 */
	protected function get_frontend_form_flow() {
		return $this->frontend_form_flow;
	}

	/**
	 * @return int|null
	 */
	protected function get_current_form_id(){
		return $this->get_frontend_form_flow()->get_current_form_id();
	}

	protected function get_current_form_type(){
		return $this->get_frontend_form_flow()->get_current_form_type();
	}

    protected function get_current_form_count(){
        return $this->get_frontend_form_flow()->get_current_form_count();
    }

	/**
	 * @param $form_id
	 * @param $meta_key
	 *
	 * @return mixed
	 */
	protected function get_form_setting( $form_id, $meta_key ){
		return get_post_meta( $form_id, $meta_key, true );
	}

	/**
	 * Get the form action attribute value.
	 *
	 * @since m2m
	 */
	protected function get_method() {
		return 'post';
	}

	protected function set_action_query_args( $url) {
		$query_args = array();
		switch ( $this->redirect_to ) {
			case 'form':
				foreach ( $this->permanent_query_args as $parameter ) {
					if ( toolset_getget( $parameter ) ) {
						$query_args[ $parameter ] = toolset_getget( $parameter );
					}
				}
				break;
			default:
				$query_args[ self::REDIRECT_REFERRER_FORM_ID_KEY ] = $this->form_id;
				break;
		}
		return add_query_arg( $query_args, $url );
	}

	/**
	 * Get the form action attribute value.
	 *
	 * @since m2m
	 */
	protected function get_action() {
		$url = $this->build_action();
		$url = $this->set_action_query_args( $url );
		return $url;
	}

	/**
	 * @return false|mixed|string
	 *
	 * @since m2m
	 */
	protected function build_action() {
		$this->form_id = $this->get_current_form_id();
		$this->redirect_to = $this->get_form_setting( $this->form_id, self::REDIRECT_KEY );
		$this->redirect_to = $this->validate_redirect_to( $this->redirect_to );

		$help_redirect = $this->get_redirect_helper( $this->form_id, $this->redirect_to );
		return $help_redirect->get_redirect_option();
	}

	/**
	 * Validate the action URL to redirect to.
	 *
	 * @param string $redirect_to
	 * @return string
	 *
	 * @since 2.0.1
	 * @todo This should validate if the target action belongs to the same site, and default to 'form'  otherwise.
	 */
	protected function validate_redirect_to( $redirect_to = '' ) {
		if ( 'redirect_back' != $redirect_to ) {
			return $redirect_to;
		}
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return 'form';
		}

		return $redirect_to;
	}

	/**
	 * @param $form_id
	 * @param $redirect_to
	 *
	 * @return Cred_Redirect_To_Helper
	 */
	protected function get_redirect_helper( $form_id, $redirect_to ){
		return new Cred_Redirect_To_Helper( $form_id, $redirect_to );
	}

	/**
	 * @return void|string
	 *
	 * @since m2m
	 */
	protected function get_hidden_fields() { return; }

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
		$this->redirect_url = esc_url( $this->get_action() );

		$form_type = $this->get_current_form_type();
		$form_type_class = $form_type.'_class';


		$output = '<form class="cred-form ' . $form_type_class . '" method="' . esc_attr( $this->get_method() ) .'" action="' . $this->redirect_url . '">';
		$output .= $this->get_hidden_fields();
		$output .= do_shortcode( $this->user_content );
		$output .= '</form>';

		return apply_filters( 'cred_form_shortcode_get_output_value', $output, $atts, $content, $this );
	}
}

/**
 * Class Cred_Redirect_To_Helper
 * small helper class to get the redirect_to $url from $form_id and $redirect_to value
 */
class Cred_Redirect_To_Helper{
	/**
	 * @var string
	 */
	private $redirect_option;
	/**
	 * @var int
	 */
	private $form_id;
	/**
	 * @var string
	 */
	private $redirect_custom_post = CRED_Shortcode_Form_Container_Base::CUSTOM_POST_KEY;
	/**
	 * @var array
	 */
	protected $options = array(
	     'custom_post' => 'custom_post',
		 'form' => 'form',
		 'redirect_back' => 'redirect_back'
	);
	/**
	 * Cred_Redirect_To_Helper constructor.
	 *
	 * @param $form_id
	 * @param $redirect_option
	 */
	public function __construct( $form_id, $redirect_option ) {
		$this->form_id = $form_id;
		$this->redirect_option = $redirect_option;
	}
	/**
	 * @return mixed
	 */
	private function redirect_back(){
		return $_SERVER['HTTP_REFERER'];
	}
	/**
	 * @return false|string
	 */
	private function form(){
		return get_permalink();
	}
	/**
	 * @return false|string
	 */
	private function custom_post( ){
		$post_id = (int) $this->get_custom_post();
		return get_permalink( $post_id );
	}
	/**
	 * @return mixed
	 */
	private function get_custom_post(){
		return get_post_meta( $this->form_id, $this->redirect_custom_post, true );
	}
	/**
	 * @return false|mixed|string
	 */
	public function get_redirect_option(){
		if ( ! $this->redirect_option ) {
			return $this->form();
		}
		$option = array_key_exists( $this->redirect_option, $this->options ) ? $this->options[$this->redirect_option] : null;

		if( $option && method_exists( $this, $option ) ){
			return call_user_func( array( $this, $option) );
		} else {
			return $this->form();
		}
	}
}
