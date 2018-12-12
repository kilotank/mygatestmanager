<?php
/*
  Plugin Name: Toolset Forms
  Plugin URI: https://toolset.com/home/toolset-components/#cred
  Description: Create Edit Delete WordPress content (ie. posts, pages, custom posts) from the front end using fully customizable forms
  Version: 2.2.1
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  License: GPLv2
 */



// Abort if called directly.
if (!function_exists('add_action')) {
    die('Toolset Forms is a WordPress plugin and can not be called directly.');
}


// Abort if the plugin is already loaded.
if (defined('CRED_FE_VERSION')) {
    return;
}


/*
 * ---------------------------------------------
 * CONSTANTS
 * ---------------------------------------------
 */

 /**
  * Plugin version
  */
define( 'CRED_FE_VERSION', '2.2.1' );

/**
 * Absolute plugin root path.
 * Everything else is legacy: no other path definitions should be necessary.
 *
 * @since 1.8.6
 */
if ( ! defined( 'CRED_ABSPATH' ) ) {
    define( 'CRED_ABSPATH', dirname( __FILE__ ) );
}

/**
 * Absolute URL root.
 */
if ( ! defined( 'CRED_ABSURL' ) ) {
    define( 'CRED_ABSURL', plugins_url() . '/' . basename( CRED_ABSPATH ) );
}

/**
 * Templates path.
 */
$cred_templates = CRED_ABSPATH . '/application/views';
define( 'CRED_TEMPLATES', $cred_templates );

/**
 * General plugin capability.
 */
define( 'CRED_CAPABILITY', 'manage_options' );

/**
 * Custom objects post types.
 *
 * @deprecated Use \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE instead.
 * @deprecated Use \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE instead.
 */
define( 'CRED_FORMS_CUSTOM_POST_NAME', 'cred-form' );
define( 'CRED_USER_FORMS_CUSTOM_POST_NAME', 'cred-user-form' );

/**
 * Module Manager
 */
define( '_CRED_MODULE_MANAGER_KEY_', 'cred' );
define( '_CRED_MODULE_MANAGER_USER_KEY_', 'cred-user' );


/*
 * Bootstrap Toolset Forms
 */
require_once CRED_ABSPATH . '/application/bootstrap.php';
