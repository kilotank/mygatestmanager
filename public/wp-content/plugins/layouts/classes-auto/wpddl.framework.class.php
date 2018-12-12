<?php

/**
 * Class WPDDL_Framework
 *
 * Framework API Basic elements
 */
class WPDDL_Framework {

	private static $instance = null;
	protected $column_prefix = 'col-sm-';
	protected $column_prefix_small = 'col-xs-';
	protected $column_prefix_large = 'col-lg-';
	protected $column_prefix_medium = 'col-md-';

	private function __construct() {
		add_filter( 'ddl-get_default_column_prefix', array( $this, 'get_default_column_prefix' ), 10, 1 );
	}

	function get_container_class( $mode, $renderer ) {
		return apply_filters( 'ddl-get_container_class', 'container', $mode, $renderer );
	}

	function get_container_fluid_class( $mode ) {
		return apply_filters( 'ddl-get_container_fluid_class', 'container-fluid', $mode );
	}

	function get_row_class( $mode ) {
		return apply_filters( 'ddl-get_row_class', 'row', $mode );
	}

	function get_offset_prefix() {
		return apply_filters( 'ddl-get_offset_prefix', 'offset-' );
	}

	function get_image_responsive_class() {
		return apply_filters( 'ddl-get_image_responsive_class', 'img-responsive' );
	}

	function get_default_column_prefix( $prefix = '' ){
		$options = $this->get_options_manager();
		return $options->get_options( WPDDL_Options::COLUMN_PREFIX );
	}

	function get_options_manager(){
		return new WPDDL_Options_Manager( WPDDL_Options::COLUMN_PREFIX, array( WPDDL_Options::COLUMN_PREFIX => $this->get_column_prefix() ) );
	}

	function get_column_prefix( $prefix = '' /*php prevent warning*/ ) {
		return apply_filters( 'ddl-get-column-prefix', $this->column_prefix );
	}

	function get_column_prefix_small() {
		return apply_filters( 'ddl-get-column-prefix_small', $this->column_prefix_small );
	}

	function get_column_prefix_large() {
		return apply_filters( 'ddl-get-column-prefix_large', $this->column_prefix_large );
	}

	function get_column_prefix_medium() {
		return apply_filters( 'ddl-get-column-prefix_medium', $this->column_prefix_medium );
	}

	function get_additional_column_class() {
		return apply_filters( 'ddl-get_additional_column_class', '' );
	}

	function get_thumbnail_class() {
		return apply_filters( 'ddl-get_thumbnail_class', 'thumbnail' );
	}

	function framework_supports_responsive_images() {
		return apply_filters( 'ddl-framework_supports_responsive_images', true );
	}

	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * For unit testing, forces the object to be contructed again
	 */
	public static function tearDown(){
		self::$instance = null;
	}

	public function get_framework_prefixes_data( $dummy = array() ){
		return apply_filters( 'ddl-get_framework_prefixes_data', array(
			 $this->get_column_prefix_small() => array('label' => __('Extra Small', 'ddl-layouts'), 'size' => __('less than 768px', 'ddl-layouts') ),
			 $this->get_column_prefix() => array('label' => __('Small', 'ddl-layouts'), 'size' => __('768px and up', 'ddl-layouts')  ),
			 $this->get_column_prefix_medium() => array('label' => __('Medium', 'ddl-layouts'), 'size' => __('992px and up', 'ddl-layouts') ),
			 $this->get_column_prefix_large() => array('label' => __('Large', 'ddl-layouts'), 'size' => __('1200px and up', 'ddl-layouts') )
		) );
	}
}