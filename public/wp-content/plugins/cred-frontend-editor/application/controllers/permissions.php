<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Controller\Condition\ClassExists;
use OTGS\Toolset\CRED\Controller\Condition\FunctionExists;

use OTGS\Toolset\CRED\Controller\Permissions\Factory as permissionsFactory;

/**
 * Toolset Forms permissions manager.
 * 
 * @since 2.1.1
 */
class Permissions {

    /**
     * @var \Toolset_Condition_Plugin_Access_Active
     */
    private $toolset_access_condition = null;
    
    /**
     * @var ClassExists
     */
    private $di_class_exists = null;
    
    /**
     * @var FunctionExists
     */
    private $di_function_exists = null;

    /**
     * @var permissionsFactory
     */
    private $di_factory = null;

    /**
     * @var array
     */
    private $custom_capabilities = array(
        \CRED_Form_Domain::POSTS => array(
            'delete_own_posts_with_cred',
            'delete_other_posts_with_cred',
        ),
        \CRED_Form_Domain::USERS => array(
            'delete_own_user_with_cred',
            'delete_other_users_with_cred',
        )
    );

    /**
     * @var array
     */
    private $custom_capabilities_by_form = array(
        \CRED_Form_Domain::POSTS => array(
            'new' => array(
                'create_posts_with_cred_',
            ),
            'edit' => array(
                'edit_own_posts_with_cred_',
                'edit_other_posts_with_cred_',
            )
        ),
        \CRED_Form_Domain::USERS => array(
            'new' => array(
                'create_users_with_cred_',
            ),
            'edit' => array(
                'edit_own_user_with_cred_',
                'edit_other_users_with_cred_',
            )
        )
    );

    /**
     * @var array|null
     */
    private $built_custom_capabilities = null;

    public function __construct(
        \Toolset_Condition_Plugin_Access_Active $di_toolset_access_condition = null,
        ClassExists $di_class_exists = null,
        FunctionExists $di_function_exists = null,
        permissionsFactory $di_factory = null

    ) {
        $this->toolset_access_condition = ( $di_toolset_access_condition instanceof \Toolset_Condition_Plugin_Access_Active )
            ? $di_toolset_access_condition
            : new \Toolset_Condition_Plugin_Access_Active();
        
        $this->di_class_exists = ( null === $di_class_exists )
            ? new ClassExists()
            : $di_class_exists;
        
        $this->di_function_exists = ( null === $di_function_exists )
            ? new FunctionExists()
            : $di_function_exists;

        $this->di_factory = ( null === $di_factory )
            ? new permissionsFactory()
            : $di_factory;
    }

    /**
     * Generate a valid title for the Toolset Forms custom capabilities.
     *
     * @param string $capability
     * @param string $arg Optional variable particle on the title.
     * @return string
     * @since 2.1.1
     */
    public function get_custom_capability_title( $capability, $arg = '' ) {
        $title = '';
        switch ( $capability ) {
            case 'delete_own_posts_with_cred':// Checked, in delete post shortcode and action
                return __( 'Delete Own Posts using Toolset Forms', 'wp-cred' );
                break;
            case 'delete_other_posts_with_cred':// Checked, in delete post shortcode and action
                return __( 'Delete Others Posts using Toolset Forms', 'wp-cred' );
                break;
            case 'delete_own_user_with_cred':// Checked - never used
                return __( 'Delete Own User using Toolset Forms', 'wp-cred' );
                break;
            case 'delete_other_users_with_cred':// Checked - never used
                return __( 'Delete Other Users using Toolset Forms', 'wp-cred' );
                break;
            case 'create_posts_with_cred_':// Checked, in checkFormAccess
                return sprintf( __( 'Create Custom Post with the Form "%s"', 'wp-cred' ), $arg );
                break;
            case 'edit_own_posts_with_cred_':// Checked, in checkFormAccess and the deprecated cred-link-form/cred_link_form shortcode
                return sprintf( __( 'Edit Own Custom Post with the Form "%s"', 'wp-cred' ), $arg );
                break;
            case 'edit_other_posts_with_cred_':// Checked, in checkFormAccess and the deprecated cred-link-form/cred_link_form shortcode
                return sprintf( __( 'Edit Others Custom Post with the Form "%s"', 'wp-cred' ), $arg );
                break;
            case 'create_users_with_cred_':// Checked, in checkUserFormAccess
                return sprintf( __( 'Create User with the Form "%s"', 'wp-cred' ), $arg );
                break;
            case 'edit_own_user_with_cred_':// Checked, in checkUserFormAccess
                return sprintf( __( 'Edit Own User with the Form "%s"', 'wp-cred' ), $arg );
                break;
            case 'edit_other_users_with_cred_':// Checked, in checkUserFormAccess
                return sprintf( __( 'Edit Other User with the Form "%s"', 'wp-cred' ), $arg );
                break;
        }
        return $title;
    }

    /**
     * Initialize the permissions management for Toolset Forms, in a cascading decision tree.
     * 
     * - First, check whether Toolset Access is instaled, and eventually initialize its
     *   compatibility layer.
     * - Otherwise, check whether support for tird parties is needed, and
     *   eventually initialize it.
     * - Otherwise, setup our custom capabilities and grant them to the right users.
     *
     * @since 2.1.1
     */
    public function initialize() {
        if ( $this->toolset_access_condition->is_met() ) {
            $toolset_access_compatibility = $this->di_factory->toolset_access( $this );
            $toolset_access_compatibility->initialize();
            return;
        }
        if ( $this->is_third_party_support_needed() ) {
            $third_party_compatibility = $this->di_factory->third_party( $this );
            $third_party_compatibility->initialize();
            return;
        }
        $this->setup_custom_capabilities();
    }

    /**
     * Check whether third party support for capabilitis is needed.
     *
     * @return boolean
     * @since 2.1.1
     */
    private function is_third_party_support_needed() {
        return (
            //User Role Editor plugin
            $this->di_function_exists->is_met( 'ure_not_edit_admin' )
            // Members plugin
            || $this->di_class_exists->is_met( 'Members_Load' )
        );
    }

    /**
     * Setup our custom capabilities in a generic way.
     *
     * @since 2.1.1
     */
    private function setup_custom_capabilities() {
        add_filter( 'user_has_cap', array( $this, 'grant_built_capabilities' ), 5, 3 );
    }

    /**
     * Grant custom capabilities to the right users.
     * 
     * Although we grant those capabilities here, we do not really support them later in all cases.
     * User forms have additional checks besides capabilities.
     * 
     * @todo Finetune this here instead, or too.
     *
     * @since 2.1.1
     */
    public function grant_built_capabilities( $allcaps, $caps, $args ) {
        if ( empty( $caps ) ) {
            return $allcaps;
        }

        if ( strpos( $caps[0], 'with_cred' ) === false ) {
            return $allcaps;
        }

        $custom_capabilities = $this->get_built_custom_capabilities();
        foreach ( $custom_capabilities[ \CRED_Form_Domain::POSTS ] as $custom_cap ) {
            $allcaps[ $custom_cap ] = true;
        }
        foreach ( $custom_capabilities[ \CRED_Form_Domain::USERS ] as $custom_cap ) {
            $allcaps[ $custom_cap ] = true;
        }

        return $allcaps;
    }
    
    /**
     * Get, or generate and return, the whole set of custom capabilities managed by Toolset Forms.
     * 
     * Toolset Forms registers a series of static capabilities, to delete own and other posts,
     * and to delete own and other users.
     * 
     * It also generates a number of capabilities that depend on form IDs and the form domain and usage
     * (create new content or edit eisting content).
     *
     * @return array
     * @since 2.1.1
     */
    public function get_built_custom_capabilities() {
        if ( ! is_null( $this->built_custom_capabilities ) ) {
            return $this->built_custom_capabilities;
        }

        $this->built_custom_capabilities = array(
            \CRED_Form_Domain::POSTS => $this->generate_custom_capabilities_by_domain( \CRED_Form_Domain::POSTS ),
            \CRED_Form_Domain::USERS => $this->generate_custom_capabilities_by_domain( \CRED_Form_Domain::USERS ),
        );

        return $this->built_custom_capabilities;
    }

    /**
     * Get the list of static capabilities not depending on form IDs, but still classified by domain.
     *
     * @return array
     * @since 2.1.1
     */
    public function get_custom_capabilities() {
        return $this->custom_capabilities;
    }

    /**
     * Get the list of prefixes for dynamic capabilities that depend on domain, usage and form IDs.
     *
     * @return array
     * @since 2.1.1
     */
    public function get_custom_capabilities_by_form() {
        return $this->custom_capabilities_by_form;
    }

    /**
     * Generate custom capabilities given a list of forms and capability prefixes.
     *
     * @param array $built Already built capabilities.
     * @param array $forms List of forms, as objects with properties ID, post_title, post_name.
     * @param array $cap_prefixes List of prefixes for capabilities.
     * @return array
     * @since 2.1.1
     */
    private function generate_custom_capabilities_by_form_and_prefix( $built, $forms, $cap_prefixes ) {
        foreach ( $forms as $form ) {
            foreach( $cap_prefixes as $cap_prefix ) {
                $built[] = $cap_prefix . $form->ID;
            }
        }

        return $built;
    }

    /**
     * Generate custom capabilities given a domain.
     *
     * @param string $domain
     * @return array
     * @since 2.1.1
     */
    private function generate_custom_capabilities_by_domain( $domain ) {
        if ( ! in_array( $domain, array( \CRED_Form_Domain::POSTS, \CRED_Form_Domain::USERS ) ) ) {
            return array();
        }

        $custom_capabilities = $this->get_custom_capabilities();
        $built = $custom_capabilities[ $domain ];

        $existing_forms = apply_filters( 'cred_get_available_forms', array(), $domain );
        $existing_forms_new = toolset_getarr( $existing_forms, 'new', array() );
        $existing_forms_edit = toolset_getarr( $existing_forms, 'edit', array() );

        $custom_capabilities_by_form = $this->get_custom_capabilities_by_form();
        $custom_capabilities_by_form_and_domain = toolset_getarr( $custom_capabilities_by_form, $domain, array() );

        $built = $this->generate_custom_capabilities_by_form_and_prefix( $built, $existing_forms_new, $custom_capabilities_by_form_and_domain['new'] );
        $built = $this->generate_custom_capabilities_by_form_and_prefix( $built, $existing_forms_edit, $custom_capabilities_by_form_and_domain['edit'] );

        return $built;
    }

}
