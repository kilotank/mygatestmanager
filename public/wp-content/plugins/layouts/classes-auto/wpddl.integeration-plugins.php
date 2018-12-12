<?php

/**
 *
 * @description has info about Toolset made theme integration plugins.
 * @since 1.9
 */

class WPDD_Layouts_IntegrationPlugins{
	private $current_theme;
	private $integration_plugins = array(
		'Avada' => array('theme_name'  => 'Avada',
		                 'plugin_name' => 'Toolset Avada Integration',
		                 'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-avada-integration/?utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-Avada-sites-with-Layouts&utm_content=layouts-Avada'),

		'Cornerstone, for WordPress' => array('theme_name'  => 'Cornerstone',
		                                      'plugin_name' => 'Toolset Cornerstone Integration',
		                                      'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-cornerstone-integration/??utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-Cornerstone-sites-with-Layouts&utm_content=layouts-Cornerstone'),

		'Divi' => array('theme_name'  => 'Divi',
		                'plugin_name' => 'Toolset Divi Integration',
		                'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-divi-integration/??utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-Divi-sites-with-Layouts&utm_content=layouts-Divi'),

		'Genesis' => array('theme_name'  => 'Genesis',
		                   'plugin_name' => 'Toolset Genesis Integration',
		                   'doc_link'    => 'https://toolset.com/documentation/user-guides/layouts-genesis-integration/??utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-Genesis-sites-with-Layouts&utm_content=layouts-Genesis'),

		'Customizr' => array('theme_name'  => 'Customizr',
		                     'plugin_name' => 'Toolset Customizr Integration',
		                     'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-customizr-integration/??utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-Customizr-sites-with-Layouts&utm_content=layouts-Customizr'),

		'Twenty Sixteen' => array('theme_name'  => 'Twenty Sixteen',
		                          'plugin_name' => 'Toolset Twenty Sixteen Integration',
		                          'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-twenty-sixteen-integration/??utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-TwentySixteen-sites-with-Layouts&utm_content=layouts-TwentySixteen'),

		'Twenty Fifteen' => array('theme_name'  => 'Twenty Fifteen',
		                          'plugin_name' => 'Toolset Twenty Fifteen Integration',
		                          'doc_link'    => 'https://toolset.com/documentation/user-guides/toolset-twenty-fifteen-integration/?utm_source=typesplugin&utm_campaign=types&utm_medium=theme-integration-message&utm_term=how-to-design-TwentyFifteen-sites-with-Layouts&utm_content=layouts-TwentyFifteen')
	);
	public $toolset_downloads_link = "https://toolset.com/account/downloads/";

	public function __construct() {
		$this->current_theme = wp_get_theme();
	}

	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}

	public function get_integration_info($theme_name = null){
		$theme_name = trim($theme_name);

		if($theme_name == null){
			$theme_name = $this->current_theme->name;
		}

		if($this->is_theme_integrated($theme_name)){
			return $this->integration_plugins[$theme_name];
		}
		return null;
	}

	public function is_theme_integrated($theme_name = null){
		if($theme_name == null){
			$theme_name = $this->current_theme->name;
		}
		return array_key_exists($theme_name, $this->integration_plugins);
	}

}

WPDD_Layouts_IntegrationPlugins::get_instance();

?>