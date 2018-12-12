<?php

namespace OTGS\Toolset\CRED\Controller\Permissions;

/**
 * Toolset Forms permissions compatibility with Toolset Access.
 * 
 * @since 2.1.1
 */
class ToolsetAccess {

    const POST_FORMS_AREA_ID = '__CRED_CRED';
    const USER_FORMS_AREA_ID = '__CRED_CRED_USER';

    const POST_FORMS_GROUP_ID = '__CRED_CRED_GROUP';
    const USER_FORMS_GROUP_ID = '__CRED_CRED_USER_GROUP';

    /**
     * @var \OTGS\Toolset\CRED\Controller\Permissions
     */
    private $permissions_manager = null;

    public function __construct( \OTGS\Toolset\CRED\Controller\Permissions $permissions_manager ) {
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * Initilize the compatibility layer with Toolset Access.
     *
     * @since 2.1.1
     */
    public function initialize() {
        // Register the right tabs in the Toolset Access settings GUI
        add_filter( 'types-access-tab', array( $this, 'register_access_cred_tab' ) );
        add_filter( 'types-access-area-for-cred-forms', array( $this, 'register_access_cred_areas' ) );
        // Register groups in the Toolset Access GUI
        add_filter( 'types-access-group', array( $this, 'register_access_cred_groups' ), 10, 2 );
        // Register the right capabilities within Toolset Access
        add_filter( 'types-access-cap', array( $this, 'register_access_cred_post_caps' ), 10, 3 );
        add_filter( 'types-access-cap', array( $this, 'register_access_cred_user_caps' ), 10, 3 );
        // Include Forms capabilities in the Toolset Access import/export
        add_filter(
            'access_export_custom_capabilities_' . self::POST_FORMS_AREA_ID,
            array( $this, 'export_access_cred_post_caps' ),
            1, 2
        );
        add_filter(
            'access_import_custom_capabilities_' . self::POST_FORMS_AREA_ID,
            array( $this, 'import_access_cred_post_caps' ),
            1, 2
        );
        add_filter(
            'access_export_custom_capabilities_' . self::USER_FORMS_AREA_ID,
            array( $this, 'export_access_cred_user_caps' ),
            1, 2
        );
        add_filter(
            'access_import_custom_capabilities_' . self::USER_FORMS_AREA_ID,
            array( $this, 'import_access_cred_user_caps' ),
            1, 2
        );
    }

    /**
     * Register the Toolset Forms tab in the Toolset Access settings page.
     *
     * @param array $tabs
     * @return array
     * @since 2.1.1
     */
    public function register_access_cred_tab( $tabs ) {
        $tabs['cred-forms'] = __( 'Toolset Forms', 'wp-cred' );
        return $tabs;
    }
    
    /**
     * Register the Toolset Forms areas in the Toolset Forms tab
     * in the Toolset Access settings page.
     *
     * @param array $areas
     * @return array
     * @since 2.1.1
     */
    public function register_access_cred_areas( $areas ) {
        $areas[] = array(
            'id' => self::POST_FORMS_AREA_ID,
            'name' => __( 'Toolset Post Forms Frontend Access', 'wp-cred' ),
        );
        $areas[] = array(
            'id' => self::USER_FORMS_AREA_ID,
            'name' => __( 'Toolset User Forms Frontend Access', 'wp-cred' ),
        );
        return $areas;
    }

    /**
     * Register the Toolset Forms groups in the Toolset Forms areas
     * in the Toolset Forms tab in the Toolset Access settings page.
     *
     * @param array $groups The list of groups in this area.
     * @param string $id The area ID.
     * @return array
     * @since 2.1.1
     */
    public function register_access_cred_groups( $groups, $id ) {
        if ( self::POST_FORMS_AREA_ID === $id ) {
            $groups[] = array(
                'id' => self::POST_FORMS_GROUP_ID,
                'name' => __( 'Post Forms Front-end Access Group', 'wp-cred' ),
            );
        }
        if ( self::USER_FORMS_AREA_ID === $id ) {
            $groups[] = array(
                'id' => self::USER_FORMS_GROUP_ID,
                'name' => __( 'User Forms Front-end Access Group', 'wp-cred' ),
            );
        }

        return $groups;
    }

    /**
     * Define the default capability for forms in each domain.
     *
     * @param string $domain
     * @return string|void
     * @since 2.1.1
     */
    private function get_cred_caps_default_role_by_domain( $domain ) {
        switch ( $domain ) {
            case \CRED_Form_Domain::POSTS:
                return 'author';
                break;
            case \CRED_Form_Domain::USERS:
                return 'administrator';
                break;
        }
        return;
    }

    /**
     * Define capabilities for Toolset Access based on capabilities prefixes, forms IDs
     * and the default role that should get it granted by default.
     *
     * @param array $caps List of already existing Toolset Access capabilities.
     * @param array $existing_forms List of forms, as objects with properties ID, post_title, post_name.
     * @param array $cap_prefixes List of prefixes for capabilities.
     * @param string $default_role Default role to grant the resulting capabilities to.
     * @return array
     * @since 2.1.1
     */
    private function register_access_cred_caps_by_form_and_prefix( $caps, $existing_forms, $cap_prefixes, $default_role ) {
        foreach ( $existing_forms as $form ) {
            foreach ( $cap_prefixes as $cap_prefix ) {
                $cred_cap = $cap_prefix . $form->ID;
                $caps[ $cred_cap ] = array(
                    'cap_id' => $cred_cap,
                    'title' => $this->permissions_manager->get_custom_capability_title( $cap_prefix, $form->post_title ),
                    'default_role' => $default_role,
                );
            }
        }
        return $caps;
    }

    /**
     * Define capabilities for Toolset Access based on their domain.
     *
     * @param array $caps List of already existing Toolset Access capabilities.
     * @param string $domain
     * @return array
     * @since 2.1.1
     */
    private function register_access_cred_caps_by_domain( $caps, $domain ) {
        if ( ! in_array( $domain, array( \CRED_Form_Domain::POSTS, \CRED_Form_Domain::USERS ) ) ) {
            return $caps;
        }

        $default_role = $this->get_cred_caps_default_role_by_domain( $domain );

        $existing_forms = apply_filters( 'cred_get_available_forms', array(), $domain );
        $existing_forms_new = toolset_getarr( $existing_forms, 'new', array() );
        $existing_forms_edit = toolset_getarr( $existing_forms, 'edit', array() );

        $custom_capabilities_by_form = $this->permissions_manager->get_custom_capabilities_by_form();
        $custom_capabilities_by_domain_form = toolset_getarr( $custom_capabilities_by_form, $domain, array() );

        $caps = $this->register_access_cred_caps_by_form_and_prefix( $caps, $existing_forms_new, $custom_capabilities_by_domain_form['new'], $default_role );
        $caps = $this->register_access_cred_caps_by_form_and_prefix( $caps, $existing_forms_edit, $custom_capabilities_by_domain_form['edit'], $default_role );

        return $caps;
    }
    
    /**
     * Register capabilities for Toolset Access managing post forms in the Toolset Access GUI.
     *
     * @param array $caps List of already existing Toolset Access capabilities.
     * @param string $area_id
     * @param string $area_id
     * @return array
     * @since 2.1.1
     */
    public function register_access_cred_post_caps( $caps, $area_id, $group_id ) {
        if (
            self::POST_FORMS_AREA_ID === $area_id 
            && self::POST_FORMS_GROUP_ID === $group_id
        ) {
            $caps = $this->register_access_cred_caps_by_domain( $caps, \CRED_Form_Domain::POSTS );

            $default_role = $this->get_cred_caps_default_role_by_domain( \CRED_Form_Domain::POSTS );
            $custom_capabilities = $this->permissions_manager->get_custom_capabilities();
            foreach( $custom_capabilities[ \CRED_Form_Domain::POSTS ] as $cap ) {
                $caps[ $cap ] = array(
                    'cap_id' => $cap,
                    'title' => $this->permissions_manager->get_custom_capability_title( $cap ),
                    'default_role' => $default_role,
                );
            }
        }

        return $caps;
    }
    
    /**
     * Register capabilities for Toolset Access managing user forms in the Toolset Access GUI.
     *
     * @param array $caps List of already existing Toolset Access capabilities.
     * @param string $area_id
     * @param string $area_id
     * @return array
     * @since 2.1.1
     */
    public function register_access_cred_user_caps( $caps, $area_id, $group_id ) {
        if (
            self::USER_FORMS_AREA_ID === $area_id 
            && self::USER_FORMS_GROUP_ID === $group_id
        ) {
            $caps = $this->register_access_cred_caps_by_domain( $caps, \CRED_Form_Domain::USERS );
            // For user capabilities we do not register in Access those about deleting users
        }

        return $caps;
    }

    /**
     * Transform a stored capability key depending on a form property to make it
     * depend on other form property.
     * 
     * Used on export/import mechanism to transform ID-dependent data into
     * post_name-dependent data, and viceversa, for portability.
     *
     * @param array $cred_capabilities
     * @param array $existing_forms
     * @param array $cap_prefixes
     * @param string $prop_from
     * @param string $prop_to
     * @return array
     * @since 2.1.1
     */
    private function swap_stored_prefix( $cred_capabilities, $existing_forms, $cap_prefixes, $prop_from, $prop_to ) {
        foreach ( $existing_forms as $form ) {
            foreach ( $cap_prefixes as $cap_prefix ) {
                if ( array_key_exists( $cap_prefix . $form->$prop_from, $cred_capabilities ) ) {
                    $cred_capabilities[ $cap_prefix . $form->$prop_to ] = $cred_capabilities[ $cap_prefix . $form->$prop_from ];
                    unset( $cred_capabilities[ $cap_prefix . $form->$prop_from ] );
                }
            }
        }
        return $cred_capabilities;
    }

    /**
     * Get the Toolset Access registered group ID by its domain.
     *
     * @param string $domain
     * @return string|void
     * @since 2.1.1
     */
    private function get_forms_group_id_by_domain( $domain ) {
        if ( ! in_array( $domain, array( \CRED_Form_Domain::POSTS, \CRED_Form_Domain::USERS ) ) ) {
            return;
        }

        switch ( $domain ) {
            case \CRED_Form_Domain::POSTS:
                return self::POST_FORMS_GROUP_ID;
                break;
            case \CRED_Form_Domain::USERS:
                return self::USER_FORMS_GROUP_ID;
                break;
        }

        return;
    }

    /**
     * Transform a stored capability key depending on a form property to make it
     * depend on other form property, by domain.
     *
     * @param array $caps
     * @param string $prop_from
     * @param string $prop_to
     * @param string $domain
     * @return array
     * @since 2.1.1
     */
    private function swap_access_cred_caps_prefix( $caps, $prop_from, $prop_to, $domain ) {
        if ( ! in_array( $prop_from, array( 'ID', 'post_name' ) ) ) {
            return $caps;
        }
        if ( ! in_array( $prop_to, array( 'ID', 'post_name' ) ) ) {
            return $caps;
        }
        if ( ! in_array( $domain, array( \CRED_Form_Domain::POSTS, \CRED_Form_Domain::USERS ) ) ) {
            return $caps;
        }

        $group_id = $this->get_forms_group_id_by_domain( $domain );

        if ( empty( $group_id ) ) {
            return $caps;
        }

        $cred_capabilities = toolset_getnest(
            $caps,
            array( $group_id, 'permissions' ),
            array()
        );

        if ( empty( $cred_capabilities ) ) {
            return $caps;
        }

        $existing_forms = apply_filters( 'cred_get_available_forms', array(), $domain );
        $existing_forms_new = toolset_getarr( $existing_forms, 'new', array() );
        $existing_forms_edit = toolset_getarr( $existing_forms, 'edit', array() );

        $custom_capabilities_by_form = $this->permissions_manager->get_custom_capabilities_by_form();
        $custom_capabilities_by_domain_form = toolset_getarr( $custom_capabilities_by_form, $domain, array() );

        $cred_capabilities = $this->swap_stored_prefix( $cred_capabilities, $existing_forms_new, $custom_capabilities_by_domain_form['new'], $prop_from, $prop_to );
        $cred_capabilities = $this->swap_stored_prefix( $cred_capabilities, $existing_forms_edit, $custom_capabilities_by_domain_form['edit'], $prop_from, $prop_to );

        $caps[ $group_id ]['permissions'] = $cred_capabilities;

        return $caps;
    }

    /**
     * Include post forms capabilities in the Toolset Access export package.
     * 
     * @param array $caps
     * @param string $area
     * @since 2.1.1
     */
    public function export_access_cred_post_caps( $caps, $area ) {
        $caps = $this->swap_access_cred_caps_prefix( $caps, 'ID', 'post_name', \CRED_Form_Domain::POSTS );
        return $caps;
    }

    /**
     * Adjust post forms capabilities when importing a Toolset Access package.
     * 
     * @param array $caps
     * @param string $area
     * @since 2.1.1
     */
    public function import_access_cred_post_caps( $caps, $area ) {
        $caps = $this->swap_access_cred_caps_prefix( $caps, 'post_name', 'ID', \CRED_Form_Domain::POSTS );
        return $caps;
    }
    
    /**
     * Include user forms capabilities in the Toolset Access export package.
     * 
     * @param array $caps
     * @param string $area
     * @since 2.1.1
     */
    public function export_access_cred_user_caps( $caps, $area ) {
        $caps = $this->swap_access_cred_caps_prefix( $caps, 'ID', 'post_name', \CRED_Form_Domain::USERS );
        return $caps;
    }
    
    /**
     * Adjust user forms capabilities when importing a Toolset Access package.
     * 
     * @param array $caps
     * @param string $area
     * @since 2.1.1
     */
    public function import_access_cred_user_caps( $caps, $area ) {
        $caps = $this->swap_access_cred_caps_prefix( $caps, 'post_name', 'ID', \CRED_Form_Domain::USERS );
        return $caps;
    }

}