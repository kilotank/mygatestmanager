<?php

namespace OTGS\Toolset\CRED\Model;

/**
 * General settings model.
 * 
 * @since 2.1
 */
class Settings {

    const OPTION_NAME = 'cred_cred_settings';

	/**
	 * @var OTGS\Toolset\CRED\Model\Settings
     * 
     * @since 2.1
	 */
	private static $instance = null;

	/**
     * Get an instance of this object.
     * 
	 * @return OTGS\Toolset\CRED\Model\Settings
     * 
     * @since 2.1
	 */
	public static function get_instance() {
		if ( null == Settings::$instance ) {
			Settings::$instance = new Settings();
		}
		return Settings::$instance;
	}
    
    /**
     * Clear the current instance.
     *
     * @since 2.1
     */
	public static function clear_instance() {
		if ( Settings::$instance ) {
			Settings::$instance = null;
		}
    }

    /**
     * Get the stored settings.
     *
     * @return array
     * 
     * @since 2.1
     */
    public function get_settings() {
        return get_option( self::OPTION_NAME, array() );
    }

    /**
     * Save settings.
     *
     * @param array $settings
     * 
     * @return boolean
     * 
     * @since 2.1
     */
    public function set_settings( $settings ) {
        return update_option( self::OPTION_NAME, $settings );
    }

    /**
     * Set an individual setting.
     *
     * @param string $key
     * @param mixed $value
     * 
     * @return boolean
     * 
     * @since 2.1
     */
    public function set_setting( $key, $value ) {
        $settings = $this->get_settings();
        $settings[ $key ] = $value;
        return $this->set_settings( $settings );
    }

    /**
     * Set the initial settings when there is no stored set.
     *
     * @since 2.1
     */
    public function set_initial_settings() {
        $settings = $this->get_settings();

        if ( ! empty( $settings ) ) {
            return;
        }
        $defaults = array(
            'wizard' => 1,
            'cache_notice' => 1,
            'export_settings' => 1,
            'export_custom_fields' => 1,
            'recaptcha' => array(
                'public_key' => '',
                'private_key' => ''
            ),
            'autogeneration_email' => array(
                'subject' => 'Welcome new user',
                'body' => '[username]Your username is: %cuf_username%[/username]\\n[nickname]Your nickname is: %cuf_nickname%[/nickname]\\n[password]Your password is: %cuf_password%[/password]'
            ),
            'dont_load_cred_css' => 1
        );

        $current_settings = $this->get_settings();
        if ( !isset($settings[ 'dont_load_cred_css' ]) ) {
            $defaults[ 'dont_load_cred_css' ] = 1;
        }

        // CRED_PostExpiration
        $defaults = apply_filters('cred_ext_general_settings_options', $defaults);

        $settings = $this->get_settings();

        if ( empty( $settings ) ) {
            $this->set_settings( $defaults );
        }
    }
    
}