<?php

// All we have now is CRED_ABSPATH.

// Get constants
require_once CRED_ABSPATH . '/application/constants.php';

/*
 * Toolset Common Library paths
 */
if ( ! defined( 'WPTOOLSET_COMMON_PATH' ) ) {
	define( 'WPTOOLSET_COMMON_PATH', CRED_ABSPATH . '/vendor/toolset/toolset-common' );
}


/*
 * Loading sequence
 */


// Load common resources
require_once CRED_ABSPATH . '/vendor/toolset/onthego-resources/loader.php';
onthego_initialize( CRED_ABSPATH . '/vendor/toolset/onthego-resources/', CRED_ABSURL . '/vendor/toolset/onthego-resources/');


// Load Toolset Common Library
require_once CRED_ABSPATH . '/vendor/toolset/toolset-common/loader.php';
toolset_common_initialize( CRED_ABSPATH . '/vendor/toolset/toolset-common/', CRED_ABSURL . '/vendor/toolset/toolset-common/');


// Load legacy Toolset Forms
require_once CRED_ABSPATH . '/library/toolset/cred/plugin.php';


// Get new functions.php
require_once CRED_ABSPATH . '/application/functions.php';


// Jumpstart new Toolset Forms
require_once CRED_ABSPATH . '/application/controllers/main.php';
$cred_main = new CRED_Main();
$cred_main->initialize();