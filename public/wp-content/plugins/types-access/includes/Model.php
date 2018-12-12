<?php

/*
*   Access Model
*
*/

class Access_Model implements TAccess_Singleton
{

    private $wpdb;
    
    public function __construct()
    {
        global $wpdb;
        
        $this->wpdb=$wpdb;
    }
    
    public function getAccessVersion()
    {
        return get_option('wpcf-access-version-check', false);
    }
    
    public function updateAccessVersion($data)
    {
        return update_option('wpcf-access-version-check', $data);
    }
    
    // WATCHOUT: in some places 'wpcf-access' was used
    public function getAccessMeta($post_id)
    {
        return get_post_meta($post_id, '_types_access', true);
    }
    
    public function updateAccessMeta($post_id, $data)
    {
        return update_post_meta($post_id, '_types_access', $data);
    }
    
    public function deleteAccessMeta($post_id)
    {
        return delete_post_meta($post_id, '_types_access');
    }
    
    public function getAccessRoles()
    {
        return get_option('wpcf-access-roles', array());
    }
    
    public function updateAccessRoles($settings)
    {
		$updated = update_option( 'wpcf-access-roles', $settings );
		do_action( 'otg_access_action_access_roles_updated', $settings, $updated );
        return $updated;
    }

    /*
    * @since 2.2
     * check and convert old options form to role based
     */
    public function getAccessSettings(){
        global $wpcf_access;
        if ( empty($wpcf_access->settings) ){
            $access_settings = get_option('toolset-access-options');
            if ( empty($access_settings) ){
                $access_types = get_option('wpcf-access-types', array());
                $access_taxs = get_option('wpcf-access-taxonomies', array());
                $access_third_party = get_option('wpcf-access-3rd-party', array());
                $access_settings = Access_Helper::toolset_access_convert_options_format( $access_types, $access_taxs, $access_third_party );
                Access_Helper::toolset_access_fix_old_access_roles();
                self::updateAccessSettings($access_settings);
            }
        }else{
            $access_settings = $wpcf_access->settings;
        }

        //Set defaults


        return self::set_access_defaults( $access_settings );
    }

    /**
     * Set defaults
     * @param $settings
     *
     * @return stdClass
     * @since 2.4
     */
    public function set_access_defaults( $settings ) {

        if ( ! is_object( $settings ) ){
            $settings = new stdClass;
        }

        if ( ! isset( $settings->types ) ){
            $settings->types = array();
        }

        if ( ! isset( $settings->tax ) ){
            $settings->tax = array();
        }

        if ( ! isset( $settings->third_party ) ){
            $settings->third_party = array();
        }

        return $settings;
    }

    public function updateAccessSettings($settings)
    {
        return update_option('toolset-access-options', $settings);
    }
    
    public function getAccessTypes()
    {
       global $wpcf_access;
       if ( !isset( $wpcf_access->settings ) ){
            $wpcf_access = new stdClass;
       }
       if ( !isset( $wpcf_access->settings->types ) ){
            $wpcf_access->settings = new stdClass;
            $wpcf_access->settings = self::getAccessSettings();
       }
       if ( !isset( $wpcf_access->settings->types ) ){
           $wpcf_access->settings->types = array();
       }

       $access_types = $wpcf_access->settings->types;

       return $access_types;
    }
    
    public function updateAccessTypes($update_settings)
    {
        //TODO
        global $wpcf_access;
        $settings = $wpcf_access->settings;
        $settings->types = $update_settings;
        $settings = self::fix_settings_array($settings);
        return update_option('toolset-access-options', $settings);
    }   

    public function getAccessTaxonomies()
    {
        global $wpcf_access;
        if ( !isset( $wpcf_access->settings->tax ) ){
            $wpcf_access->settings = new stdClass;
            $wpcf_access->settings = self::getAccessSettings();
        }
        if ( !isset( $wpcf_access->settings->tax ) ){
           $wpcf_access->settings->tax = array();
        }
        $access_taxs = $wpcf_access->settings->tax;

        return $access_taxs;
    }
    
    public function updateAccessTaxonomies($update_settings)
    {
        //TODO
        global $wpcf_access;
        $settings = $wpcf_access->settings;
        $settings->tax = $update_settings;
        $settings = self::fix_settings_array($settings);
        return update_option('toolset-access-options', $settings);
    }
    
    public function getAccessThirdParty()
    {
        global $wpcf_access;
        if ( !isset( $wpcf_access->settings->third_party ) ){
            $wpcf_access->settings = new stdClass;
            $wpcf_access->settings = self::getAccessSettings();
        }
        if ( !isset( $wpcf_access->settings->third_party ) ){
           $wpcf_access->settings->third_party = array();
        }
        $third_party = $wpcf_access->settings->third_party;
        return $third_party;
    }
    
    public function updateAccessThirdParty($update_settings)
    {
        //TODO
        global $wpcf_access;
        $settings = $wpcf_access->settings;
        $settings->third_party = $update_settings;
        $settings = self::fix_settings_array($settings);
        return update_option('toolset-access-options', $settings);
    }

    public function fix_settings_array( $settings ){

        if ( !isset($settings->third_party) ){
            $settings->third_party = array();
        }

        if ( !isset($settings->types) ){
            $settings->types = array();
        }

        if ( !isset($settings->tax) ){
            $settings->tax = array();
        }
        return $settings;

    }
    
    public function getWpcfTypes()
    {
        return get_option('wpcf-custom-types', array());
    }
    
    public function updateWpcfTypes($settings)
    {
        return update_option('wpcf-custom-types', $settings);
    }
    
    public function getWpcfTaxonomies()
    {
        return get_option('wpcf-custom-taxonomies', array());
    }
    
    public function updateWpcfTaxonomies($settings)
    {
        return update_option('wpcf-custom-taxonomies', $settings);
    }
    
    public function getWpcfActiveTypes()
    {
        $types=$this->getWpcfTypes();
        foreach ($types as $type => $data) 
        {
            if (!empty($data['disabled']))
                unset($types[$type]);
        }
        return $types;
    }
    
    public function getWpcfActiveTaxonomies()
    {
        $taxonomies=$this->getWpcfTaxonomies();
        foreach ($taxonomies as $taxonomy => $data) 
        {
            if (!empty($data['disabled']))
                unset($taxonomies[$taxonomy]);
        }
        return $taxonomies;
    }
    
    public function getPostTypes($args=false)
    {
        if (false===$args)
            $args=array('show_ui' => true);
            
        return get_post_types($args, 'objects');
    }
	
	/**
	* getPostTypesNames
	*
	* @since 2.1
	*/
	
	public function getPostTypesNames( $args = false ) {
        if ( false === $args ) {
            $args = array(
				'show_ui' => true
			);
		}
        return get_post_types($args, 'names');
    }
    
    public function getTaxonomies($args=false)
    {
        if (false===$args)
            $args=array('show_ui' => true);
            
        return get_taxonomies($args, 'objects');
    }
	
	/**
	* getTaxonomiesNames
	*
	* @since 2.1
	*/
	
	public function getTaxonomiesNames( $args = false ) {
        if ( false === $args ) {
            $args = array(
				'show_ui' => true
			);
		}
        return get_taxonomies($args, 'names');
    }
}