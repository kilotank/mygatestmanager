<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

/**
 * Plugin upgrade factory for upgrade routines.
 *
 * @since 2.1.2
 */
class Factory {

	/**
	 * Get the righ routine given its signature key.
	 *
	 * @param string $routine
	 * @return \OTGS\Toolset\CRED\Controller\Upgrade\IRoutine
	 * @since 2.1.2
	 */
	public function get_routine( $routine ) {
		$dic = apply_filters( 'toolset_dic', false );
		switch ( $routine ) {
			case 'upgrade_db_to_2010200':
				$upgrade_db_to_2010200 = $dic->make( '\OTGS\Toolset\CRED\Controller\Upgrade\Routine2010200DbUpgrade' );
				return $upgrade_db_to_2010200;
				break;
			default:
				throw new \Exception( 'Unknown routine' );
		}
	}

}