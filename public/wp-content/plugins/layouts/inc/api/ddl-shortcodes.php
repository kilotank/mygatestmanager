<?php
/*
 * Class for Layouts shortcodes
 * 
 * LAYOUTS SHORTCODES:
 * 
 * [toolset-plugin-active]
 * For now we have only shortcode for checking is some plugin active or not. We can easily register new plugins for that
 * Just check function register_plugins, there is also filter that we can use to add new plugins to the list outside this class
 */

Class LayoutsShortcodes{
    
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        
        // add filter for list of plugins that we can check using [toolset-plugin-active] shortcode
        add_filter('layouts_shortcodes_register_list_of_plugins', array($this,'register_plugins') );
    }
    
    function init(){
        $this->register_shortcodes();
    }
    

    function register_shortcodes(){
        // add new shortcodes here
        add_shortcode('toolset-plugin-active', array(&$this,'is_plugin_active'));
        add_shortcode('toolset-user-role-condition', array(&$this,'check_user_role'));
    }
    
    /*
     * Show some content depended on user role
     */
    
    function get_current_user_role() {
        global $wp_roles;
        global $current_user;
        $current_user = wp_get_current_user();
        $roles = $current_user->roles;
        $role = array_shift($roles);
        return isset($wp_roles->role_names[$role]) ? trim(strtolower(translate_user_role($wp_roles->role_names[$role]))) : 'guest';
    }

    function check_user_role($atts, $content=''){
        
        $return_status = true;
        
        extract(shortcode_atts(array('roles' => null, 'status'=>true), $atts));
        
        $role_status = ($status === 'true' || $status === 'yes') ? true : false;
        
        $get_roles = array();
        $get_roles = explode(",",$roles);
        $get_roles = array_map('trim',$get_roles);
            
        if(!empty($get_roles)){

            $current_user_role = $this->get_current_user_role();
            
            foreach($get_roles as $one_role){
                if($current_user_role === trim(strtolower($one_role))){
                    $return_status = true;
                    break;
                } else {
                    $return_status = false;
                }
            }
            
            if($role_status === $return_status){
                return do_shortcode($content);
            } else {
                return false;
            }
        }
        
        return false;

    }
    
    
    /*
     * Shortcode for checking is plugin active
     */
    function register_plugins(){
        $plugins = array(
            'access'=> 'TAccess_Loader' ,
            'types'=> 'wpcf_bootstrap' ,
            'views'=> 'WP_Views',
            'cred' => 'CRED_Loader',
            'maps' => 'Toolset_Addon_Maps_Types',
        );
        return $plugins;
    }
    
    function is_plugin_active($atts, $content='') {
        
        $active_status = false;
        $registered_plugins = apply_filters('layouts_shortcodes_register_list_of_plugins',array());
        extract(shortcode_atts(array('active' => '', 'plugins'=>''), $atts));
        
        // set check status
        $active = strtolower($active);
        $plugin_should_be_active = ($active === 'true' || $active === 'yes') ? true : false;

        $get_plugins = explode(",",$plugins);
        $get_plugins = array_map('trim',$get_plugins);
        
        if($get_plugins){
            foreach($get_plugins as $one_plugin){
                if( isset($registered_plugins[$one_plugin]) && ( class_exists( $registered_plugins[$one_plugin] ) || function_exists( $registered_plugins[$one_plugin] ) ) ){
                    $active_status = true;
                } else {
                    $active_status = false;
                    break;
                }
            }

            if($plugin_should_be_active === $active_status){
                return do_shortcode($content);
            } else {
                return false;
            }
        }
        return do_shortcode($content);
    }
            
    
    
}
new LayoutsShortcodes();
