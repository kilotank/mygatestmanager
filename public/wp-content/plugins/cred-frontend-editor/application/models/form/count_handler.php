<?php

/**
 * Managing of global counter forms in order to have unique form id before and after form submissions
 *
 * @since 2.0
 */
class CRED_Form_Count_Handler {

	/**
	 * @var int Main global forms Count
	 */
	private $main_count;
	/**
	 * @var int Integer controller used to avoid to have different numeration forms before and after submission
	 */
	private $_main_count_to_skip;

	private static $instance;

	/**
	 * @return CRED_Form_Count_Handler
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->initialize();
		}

		return self::$instance;
	}

	/**
	 * Init Main Counter
	 */
	public function initialize() {
		$this->init_main_count();
		$this->add_hooks();
	}

	/**
	 * Hooks init
	 */
	protected function add_hooks() {
		add_action( 'shutdown', array( $this, 'init_main_count' ) );
	}

	/**
	 * Reset Main CRED Forms Global Counter
	 */
	public function init_main_count() {
		$this->main_count = 1;
	}

	/**
	 * @return int
	 */
	public function get_main_count() {
		return $this->main_count;
	}

	/**
	 * @param $value
	 */
	public function set_main_count( $value ) {
		return $this->main_count = (int) $value;
	}

	/**
	 * Increment the global main counter forms
	 */
	public function increment() {
		$this->main_count ++;
	}

	/**
	 * @return int
	 */
	public function get_main_count_to_skip() {
		return $this->_main_count_to_skip;
	}

	/**
	 * @param $value
	 */
	public function set_main_count_to_skip( $value ) {
		$this->_main_count_to_skip = $value;
	}

	/**
	 * Avoid First increment of Main Global CRED Form Count if it is in submission
	 * in order to maintain exactly the same Forms counter
	 *
	 * @param $is_form_submitted
	 */
	public function maybe_increment( $is_form_submitted ) {
		if ( ! $is_form_submitted
			|| $this->_main_count_to_skip === $this->main_count
		) {
			$this->increment();
			$this->_main_count_to_skip = $this->main_count;
		} else {
			$this->_main_count_to_skip ++;
		}
	}

	/**
	 * Initializing class variable $_main_count_to_ship used to produce the same correct numeration
	 * of cred forms after a submission
	 *
	 * @param $is_form_submitted
	 */
	public function init_form_counter_controller( $is_form_submitted ) {
		if ( $is_form_submitted
			&& ! isset( $this->_main_count_to_skip )
		) {
			$this->_main_count_to_skip = 0;
		}
	}
}