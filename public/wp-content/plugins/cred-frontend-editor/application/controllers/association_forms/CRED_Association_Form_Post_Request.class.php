<?php

class CRED_Association_Form_Post_Request extends CRED_Association_Form_Abstract {
	const MODEL_SUFFIX = "Association_Model";
	private $association_results;
	private $current_form_id;
	private $form_count = 0;
	private $fields_message = null;

	public function __construct( CRED_Association_Form_Model_Factory $model_factory, CRED_Association_Form_Relationship_API_Helper $helper ) {
		parent::__construct( $model_factory, $helper );
		add_action( 'init', array( $this, 'handle_post_request' ), PHP_INT_MAX  );
	}

	public function handle_post_request(){
		$this->association_results = $this->process_post_data();
		$this->fields_message = $this->fields_message();
	}

	public function add_hooks() {
		add_filter( 'cred_form_feedback', array( $this, 'process_post_data_feedback' ), 10, 3 );
		add_filter( 'cred_form_feedback_classnames', array( $this, 'add_style_to_feedback'), 10, 3 );
	}

	public function initialize() {
		$this->add_hooks();
	}

	/**
	 * @param $data
	 */
	private function populate_model( $data ) {
		$this->model->populate( $data );
	}

	/**
	 * @return CRED_Association_Form_Association_Model|null
	 */
	private function set_model() {
		$this->model = $this->get_model( self::MODEL_SUFFIX, $this->helper );

		return $this->model;
	}

	/**
	 * @return mixed
	 */
	private function process_data() {
		return $this->model->process_data();
	}

	private function mock_messages_get_param(){
		$_GET['cred_referrer_form_id'] = $this->get_current_form_id();
	}

	private function clean_up_messages_get_param(){
		if( isset( $_GET['cred_referrer_form_id'] ) ) unset( $_GET['cred_referrer_form_id'] );
	}

	/**
	 * @return bool
	 */
	private function process_post_data() {
		$result = null;

		if( count( $_POST ) && isset( $_POST['cred_association_form_ajax_submit_nonce'] ) && ! wp_verify_nonce( $_POST['cred_association_form_ajax_submit_nonce'], CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE ) ){
			return array( 'message' => __( "You don't have permission to perform this action!", 'wp-cred' ) );
		}

		if ( count( $_POST ) && isset( $_POST['cred_form_id'] ) ) {
			$this->set_form_id( $_POST['cred_form_id'] );
			$this->form_count = $_POST['cred_form_count'];
			$this->mock_messages_get_param();
			$this->set_model();
			$this->populate_model( $_POST );
			$result = $this->process_data();
		}

		return $result;
	}

	/**
	 * @param $id
	 */
	public function set_form_id( $id ) {
		$this->current_form_id = $id;
	}

	/**
	 * @return mixed
	 */
	public function get_current_form_id() {
		return $this->current_form_id;
	}

	/**
	 * @param $message
	 * @param $form_id
	 *
	 * @return mixed|string
	 */
	public function process_post_data_feedback( $message, $form_id, $form_count ) {

		$this->clean_up_messages_get_param();

		if ( (int) $this->current_form_id !== (int) $form_id || (int) $this->form_count !== (int) $form_count ) {
			return $message;
		}

		$association = isset( $this->association_results['association'] ) ? $this->association_results['association'] : null;

		$messages = get_post_meta( $this->current_form_id, 'messages', true);

		if ( null === $association ) {
			$message = $messages['cred_message_post_not_saved_singular'] . ' ' . __( 'The Association you try to save does not exist.', 'wp-cred' );
		} else if( is_numeric($association ) || null !== $this->fields_message ) {
			$message = $messages['cred_message_post_saved'];
		} else {
			$message = $messages['cred_message_post_not_saved_singular'] . ' ' . $this->association_results['association'];
		}

		return $message;
	}

	public function add_style_to_feedback( $classes, $form_id, $form_count ){
		if ( (int) $this->current_form_id !== (int) $form_id || (int) $this->form_count !== (int) $form_count ) {
			return $classes;
		}

		$association = isset( $this->association_results['association'] ) ? $this->association_results['association'] : null;

		if ( null === $association ) {
			$classes[] = 'alert-danger';
		} else if( is_numeric($association ) || null !== $this->fields_message ) {
			$classes[] = 'alert-success';
		} else {
			$classes[] = 'alert-warning';
		}

		return $classes;
	}
	
	private function fields_message(){
		$fields = isset( $this->association_results['fields'] ) ? $this->association_results['fields'] : null;

		if( $fields === null ) return null;

		$changed = array();

		foreach( $fields as $field => $value ){
			if( false !== $value ){
				$changed[$field] = $value;
			}
		}

		if( count( $changed ) === 0 ){
			return null;
		} elseif( count( $changed ) === 1 ){
			$keys = array_values( array_keys( $fields ) );
			return sprintf( __( 'The %s field has been successfully saved with value %s', 'wp-cred' ), $keys[0], $fields[$keys[0]] );
		} elseif( count( $changed ) > 1 ){
			return __( 'Relationship fields have been successfully saved', 'wp-cred' );
		}

		return null;
	}

}