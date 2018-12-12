<?php

/**
 * WPDD_Helper_Twig
 *
 * @since 2.0
 */
class WPDD_Helper_Twig {

	/** @var Twig_Loader_Filesystem|null */
	private $_filesystem;

	/** @var Twig_Environment|null */
	private $_twig;

	/** @var bool */
	private $is_debug_mode;

	public function __construct( $debug = false ) {
		$this->is_debug_mode = $debug;
	}

	/**
	 * This allows to use __( 'Text to translate', 'text-domain' ) in twig templates
	 *
	 * @param $text
	 * @param string $domain
	 *
	 * @return mixed
	 */
	public function translate( $text, $domain = 'ddl-layouts' ) {
		return __( $text, $domain );
	}

	public function admin_url( $path = '', $scheme = 'admin' ) {
		if ( $path ) {
			return admin_url( $path, $scheme );
		} else {
			return admin_url();
		}
	}

	public function get_permalink( $post_id = 0, $leavename = false ) {
		return get_permalink( $post_id, $leavename );
	}


	/**
	 * Make sure that $this->_twig and $this->_filesystem are loaded.
	 */
	private function prepare_twig() {
		// backwards compatibility for php5.2
		if ( ! defined( 'E_DEPRECATED' ) ) {
			define( 'E_DEPRECATED', 8192 );
		}

		if ( ! defined( 'E_USER_DEPRECATED' ) ) {
			define( 'E_USER_DEPRECATED', 16384 );
		}

		// Take the Twig library from the Toolset GUI Base instead from the composer autoloader.
		Toolset_Common_Bootstrap::get_instance()->register_gui_base();
		Toolset_Gui_Base::get_instance()->init();

		$this->_filesystem = new Twig_Loader_Filesystem();
		$this->_filesystem->addPath( WPDDL_GUI_ABSPATH . '/templates/twig' );

		$this->_twig = new Twig_Environment( $this->_filesystem, array( 'debug' => $this->is_debug_mode ) );
		$this->_twig->addFunction( new Twig_SimpleFunction( '__', array( $this, 'translate' ) ) );
		$this->_twig->addFunction( new Twig_SimpleFunction( 'admin_url', array( $this, 'admin_url' ) ) );
		$this->_twig->addFunction( new Twig_SimpleFunction( 'get_permalink', array( $this, 'get_permalink' ) ) );
		$this->_twig->addFunction( new Twig_SimpleFunction( 'get_lang', array( $this, 'get_lang' ) ) );
		$this->_twig->addFunction( new Twig_SimpleFunction( 'is_wpml', array( $this, 'is_wpml' ) ) );
	}

	public function render( $file, $data ) {
		if( null === $this->_twig ) {
			$this->prepare_twig();
		}

		if ( $this->_filesystem->exists( $file ) ) {
			return $this->_twig->render( $file, $data );
		}

		return false;
	}

	public function get_lang( $post_id ) {
		$lang = apply_filters( 'wpml_post_language_details', null, $post_id );

		if ( $lang ) {
			return $lang['display_name'];
		}

		return '';
	}

	public function is_wpml() {
		return apply_filters( 'ddl-is_wpml_active_and_configured', false );
	}


	/**
	 * @param $data
	 * @param string $items_key
	 * @param string $page
	 *
	 * @return array
	 */
	public function build_generic_twig_context( $data, $items_key = 'items', $page = '' ) {

		$admin_url = add_query_arg( array(
			'page' => $page
		), admin_url( 'admin.php' ) );

		$context = array(
			'admin_url' => $admin_url,
			$items_key => $data
		);

		return $context;
	}
}