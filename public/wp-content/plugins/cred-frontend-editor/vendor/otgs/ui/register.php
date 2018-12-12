<?php
/*
 * Register the assets from this repository.
 *
 * DO NOT LOAD this file directly: follow the instructions in loader.php
 *
 * REMEMBER to increase the loader number in loader.php after every major update.
 */

if ( ! function_exists( 'otgs_ui_register_assets' ) ) {
	
	function otgs_ui_register_assets( $assets_data, $assets_version ) {
		/*
		 * Assets handles, in constants.
		 * Note that we define them at init.
		 */
		define( 'OTGS_ASSETS_UI_STYLES', 'otgs-ui' );
		define( 'OTGS_ASSETS_UI_SCRIPT', 'otgs-ui' );
		
		wp_register_style( OTGS_ASSETS_UI_STYLES, $assets_data[ 'url' ] . '/dist/css/ui/styles.css', array(), $assets_version );
		wp_register_script( OTGS_ASSETS_UI_SCRIPT, $assets_data[ 'url' ] . '/dist/js/ui/app.js', array(), $assets_version );
	}

}