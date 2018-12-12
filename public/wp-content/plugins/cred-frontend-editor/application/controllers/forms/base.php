<?php

namespace OTGS\Toolset\CRED\Controller\Forms;

use OTGS\Toolset\CRED\Model\Factory as ModelFactory;

/**
 * Abstract post form, inherited by individual controllers.
 * 
 * @since 2.1
 */
abstract class Base {

    /**
     * @var ModelFactory
     * 
     * @since 2.1
     */
    private $model_factory = null;
    
    /**
     * @var array
     * 
     * @since 2.1
     */
    private $assets_to_load;
    
    /**
     * @var \Toolset_Assets_Manager
     * 
     * @since 2.1
     */
    public $assets_manager = null;

    public function __construct( ModelFactory $model_factory ) {
        $this->model_factory = $model_factory;
        add_action( 'init', array( $this, 'initialize' ), 12 );
    }

    /**
     * Initialize this controller.
     *
     * @since 2.1
     */
    public function initialize(){
        $this->add_hooks();
        $this->assets_manager = \Toolset_Assets_Manager::get_instance();
    }
    
    /**
     * Add hooks.
     * 
     * @since 2.1
     */
    abstract public function add_hooks();

    /**
     * Define the assets to load by this controller.
     *
     * @param array $js
     * @param array $styles
     * 
     * @return array
     * 
     * @since 2.1
     */
    public function define_assets( $js, $styles ) {
        $this->assets_to_load['styles'] = $styles;
        $this->assets_to_load['js'] = $js;
        return $this->assets_to_load ;
    }

    /**
     * Load the assets for this controller.
     *
     * @since 2.1
     */
    public function load_assets() {
        do_action( 'toolset_enqueue_scripts', $this->assets_to_load['js'] );
        do_action( 'toolset_enqueue_styles', $this->assets_to_load['styles'] );
    }

}