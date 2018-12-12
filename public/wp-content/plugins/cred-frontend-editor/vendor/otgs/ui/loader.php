<?php
/*
 * Load the shared OTGS icons library, on demand.
 *
 * =================
 * Usage
 * =================
 * $vendor_root_url = [ URL of the root of your relative vendor directory housing this repository ]
 * require_once( [ path to the root of your relative vendor directory housing this repository ] .  '/otgs/ui/loader.php' );
 *
 * =================
 * Restrictions
 * =================
 * - Assets are registered at init:10
 * - Their handles are stored in constants that you can use as dependencies, on assets registered after init:10.
 */

if ( ! isset( $vendor_root_url ) ) {
	return;
}

/*
 * OTGS UI version - increase after every major update.
 */
$otg_ui_version = 100;

/*
 * =================
 * ||   WARNING   ||
 * =================
 *
 * DO NOT EDIT below this line.
 */

global $otg_ui_versions;
if ( ! isset( $otg_ui_versions ) ) {
    $otg_ui_versions = array();
}
$otg_ui_versions[ $otg_ui_version ] = array(
	'url' => $vendor_root_url . '/otgs/ui',
	'path' => dirname( __FILE__ )
);

if ( ! has_action( 'init', 'otgs_ui_register' ) ) {
	add_action( 'init', 'otgs_ui_register' );
}

if ( ! function_exists( 'otgs_ui_register' ) ) {
	function otgs_ui_register() {
		global $otg_ui_versions;
		$latest = 0;
		
		foreach ( $otg_ui_versions as $version => $version_data ) {
			if ( $version > $latest ) {
				$latest = $version;
			}
		}
		
		require_once( $otg_ui_versions[ $latest ]['path'] . '/register.php' );
        otgs_ui_register_assets( $otg_ui_versions[ $latest ], $latest );
	}
}