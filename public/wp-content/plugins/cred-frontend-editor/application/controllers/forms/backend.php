<?php

namespace OTGS\Toolset\CRED\Controller\Forms;

use OTGS\Toolset\CRED\Controller\Forms\Base;
use OTGS\Toolset\CRED\Controller\PageExtension\Factory;
use OTGS\Toolset\CRED\Model\Factory as ModelFactory;


/**
 * Forms main backend controller.
 * 
 * @since 2.1
 */
class Backend extends Base {

    const DOMAIN = 'shared';

    protected $assets_to_load_js = array();
    protected $assets_to_load_css = array();

    /**
     * @var OTGS\Toolset\CRED\Controller\PageExtension\Factory
     * 
     * @since 2.1
     */
    protected $page_extension_factory = null;

    protected $metaboxes = array();

    public function __construct( ModelFactory $model_factory ) {
        parent::__construct( $model_factory );
        $this->page_extension_factory = new Factory();
    }

    /**
     * Initialize backend.
     * 
     * @since 2.1
     */
    public function initialize() {
        parent::initialize();
        if ( $this->is_edit_page() ) {
            // Disable the Toolset Views conditional output quicktag from editors.
            add_filter( 'wpv_filter_wpv_disable_conditional_output_quicktag', '__return_true' );
            // Force include the Quicktag link template.
            add_action( 'admin_footer', array( $this, 'force_quicktag_link_template' ) );
        }
    }

    /**
     * Force include the Quicktag link template so it works.
     *
     * @since 2.1
     */
    public function force_quicktag_link_template() {
        if ( ! class_exists( '_WP_Editors' ) ) {
			require( ABSPATH . WPINC . '/class-wp-editor.php' );
		}
		\_WP_Editors::wp_link_dialog();
    }

    /**
     * Add frontend hooks.
     * 
     * @since 2.1
     */
    public function add_hooks() {}

    /**
     * Initialize assets.
     *
     * @since 2.1
     */
    protected function init_scripts_and_styles() {
        $this->load_backend_assets();
    }

    /**
     * Load assets.
     * 
     * @since 2.1
     */
    protected function load_backend_assets() {
        $this->register_assets();
        $this->define_assets( $this->assets_to_load_js, $this->assets_to_load_css );
        $this->load_assets();
    }

    /**
     * Register necessary scripts and styles.
     * 
     * @since 2.1
     */
    protected function register_assets() {

        if ( $this->is_listing_page() ) {

        }

        if ( $this->is_edit_page() ) {
            $this->assets_manager->register_script(
                static::JS_EDITOR_HANDLE,
                CRED_ABSURL . static::JS_EDITOR_REL_PATH,
                array( \CRED_Asset_Manager::SCRIPT_EDITOR_PROTOTYPE ),
                CRED_FE_VERSION
            );
            
            $this->assets_to_load_js['editor_main'] = static::JS_EDITOR_HANDLE;
            
            $this->assets_to_load_css[ 'editor_shared' ] = \CRED_Asset_Manager::STYLE_EDITOR;
        }
    }

    protected function register_settings_metabox( $form_fields ) {
        $callback = '__return_false';
        switch ( static::DOMAIN ) {
            case 'post':
                $form_settings_meta_box = \CRED_Page_Extension_Post_Form_Settings_Meta_Box::get_instance();
                $callback = array( $form_settings_meta_box, 'execute' );
                break;
            case 'user':
                $form_settings_meta_box = \CRED_Page_Extension_User_Form_Settings_Meta_Box::get_instance();
                $callback = array( $form_settings_meta_box, 'execute' );
                break;
        }

        $this->metaboxes['credformtypediv'] = array(
            'title' => __( 'Settings', 'wp-cred' ),
            'callback' => $callback,
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    protected function register_access_metabox( $form_fields ) {
        $this->metaboxes['accessmessagesdiv'] = array(
            'title' => __( 'Access Control', 'wp-cred' ),
            'callback' => $this->page_extension_factory->get_callback( static::DOMAIN, 'access' ),
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    protected function register_content_metabox( $form_fields ) {
        $this->metaboxes['credformcontentdiv'] = array(
            'title' => __( 'Content', 'wp-cred' ),
            'callback' => $this->page_extension_factory->get_callback( static::DOMAIN, 'content' ),
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    protected function register_notifications_metabox( $form_fields ) {
        $this->metaboxes['crednotificationdiv'] = array(
            'title' => __( 'E-mail Notifications', 'wp-cred' ),
            'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'notifications' ),
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    protected function register_messages_metabox( $form_fields ) {
        $callback = '__return_false';
        switch ( static::DOMAIN ) {
            case 'post':
                $callback = array( 'CRED_Admin_Helper', 'addMessagesMetaBox' );
                break;
            case 'user':
                $callback = array( 'CRED_Admin_Helper', 'addMessagesMetaBox2' );
                break;
        }

        $this->metaboxes['credmessagesdiv'] = array(
            'title' => __( 'Messages', 'wp-cred' ),
            'callback' => $callback,
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    protected function register_save_metabox( $form_fields ) {
        $this->metaboxes['topbardiv'] = array(
            'title' => __( 'Top bar', 'wp-cred' ),
            'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'save' ),
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'high',
            'callback_args' => $form_fields
        );
    }

    // Just refactored the page extension factory to take module_manager and get ModuleManager
    protected function maybe_register_module_manager_metabox() {
        if ( ! defined( 'MODMAN_PLUGIN_NAME' ) ) {
            return;
        }

        $this->metaboxes['modulemanagerdiv'] = array(
            'title' => __( 'Module Manager', 'wp-cred' ),
            'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'module_manager' ),
            'post_type' => NULL,
            'context' => 'normal',
            'priority' => 'default',
            'callback_args' => array()
        );
    }

}