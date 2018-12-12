<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post;

use OTGS\Toolset\CRED\Controller\Forms\Backend as BackendBase;
use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostFormMain;
use OTGS\Toolset\CRED\Controller\Forms\Post\Editor\Content\Toolbar;

/**
 * Post forms backend controller.
 * 
 * @since 2.1
 */
class Backend extends BackendBase {

    const DOMAIN = 'post';
    
    const JS_EDITOR_HANDLE = 'toolset_cred_post_forms_back_end_editor_main_js';
    const JS_EDITOR_REL_PATH = '/public/post_forms/js/editor_page/main.js';

    /**
     * Initialize backend.
     * 
     * Creates metaboxes, initializes the toolbar on the edit page, 
     * initializes scripts in edit and listing pages.
     * 
     * @since 2.1
     */
    public function initialize() {
        parent::initialize();
        
        

        if ( $this->is_edit_page() ) {
            add_filter( 'screen_options_show_screen', '__return_false' );
            add_filter( 'screen_layout_columns', array( $this, 'screen_layout_columns' ) );
            add_filter( 'get_user_option_screen_layout_' . PostFormMain::POST_TYPE, array( $this, 'screen_layout' ) );

            add_action( 'add_meta_boxes_' . PostFormMain::POST_TYPE, array( $this , 'add_meta_boxes'), 20, 1 );
            add_action( 'do_meta_boxes', array( $this, 'remove_meta_boxes' ) );
            add_filter( 'hidden_meta_boxes', '__return_empty_array' );
            
            $content_editor_toolbar = new Toolbar();
            $content_editor_toolbar->initialize();
        }

        if ( 
            $this->is_edit_page()
            || $this->is_listing_page() 
        ) {
            $this->init_scripts_and_styles();
        }
    }
    
    /**
     * Whether we are on a bckend listing page.
     *
     * @return boolean
     * 
     * @since 2.1
     */
    protected function is_listing_page() {
        global $pagenow;

        if (
            'edit.php' === $pagenow
            && PostFormMain::POST_TYPE === toolset_getget( 'post_type' )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Whether we are on a backend edit page.
     *
     * @return boolean
     * 
     * @since 2.1
     */
    protected function is_edit_page() {
        global $pagenow;

        if ( 
            'post.php' === $pagenow
            && PostFormMain::POST_TYPE === get_post_type( toolset_getget( 'post' ) )
        ) {
            return true;
        }

        if (
            'post-new.php' === $pagenow
            && PostFormMain::POST_TYPE === toolset_getget( 'post_type' )
        ) {
            return true;
        }

        return false;
    }

    public function screen_layout_columns( $columns ) {
        $columns[ PostFormMain::POST_TYPE ] = 1;
        return $columns;
    }

    public function screen_layout( $dummy ) {
        return 1;
    }

    public function remove_meta_boxes() {
        remove_meta_box( 'submitdiv', PostFormMain::POST_TYPE, 'side' );
        remove_meta_box( 'slugdiv', PostFormMain::POST_TYPE, 'normal' );
    }
    
    /**
     * Register metaboxs for the backend edit page.
     *
     * @param object $form
     * 
     * @since 2.1
     */
    public function add_meta_boxes( $form ) {
        $model = \CRED_Loader::get( 'MODEL/Forms' );
        $form_fields = $model->getFormCustomFields(
            $form->ID, 
            array( 'form_settings', 'notification', 'extra' )
        );

        $this->register_save_metabox( $form_fields );
        $this->register_settings_metabox( $form_fields );
        $this->register_access_metabox( $form_fields );
        $this->register_content_metabox( $form_fields );
        $this->register_notifications_metabox( $form_fields );
        $this->register_messages_metabox( $form_fields );

        // CRED_PostExpiration is not registered here anymore
        // Keep for backwards compatibility, although I think we should remove
        $this->metaboxes = apply_filters( 'cred_ext_meta_boxes', $this->metaboxes, $form_fields );

        $this->maybe_register_module_manager_metabox();

        // do same for any 3rd-party metaboxes added to CRED forms screens
        $extra_metaboxes = apply_filters( 'cred_admin_register_meta_boxes', array() );
        if ( ! empty( $extra_metaboxes ) ) {
            foreach ( $extra_metaboxes as $mt )
                add_filter( 'postbox_classes_' . PostFormMain::POST_TYPE . "_$mt", array( 'CRED_Admin_Helper', 'addMetaboxClasses' ) );
        }

        // add defined meta boxes
        foreach ( $this->metaboxes as $mt => $mt_definition ) {
            add_filter( 'postbox_classes_' . PostFormMain::POST_TYPE . "_$mt", array( 'CRED_Admin_Helper', 'addMetaboxClasses' ) );
            add_meta_box( $mt, $mt_definition['title'], $mt_definition['callback'], $mt_definition['post_type'], $mt_definition['context'], $mt_definition['priority'], $mt_definition['callback_args'] );
        }

        // allow 3rd-party to add meta boxes to CRED form admin screen
        do_action( 'cred_admin_add_meta_boxes', $form );

    }
}