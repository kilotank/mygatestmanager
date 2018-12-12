<?php

/**
 * Toolset_Condition_Plugin_Gutenberg_Active
 *
 * @since 3.4.0
 */
class Toolset_Condition_Plugin_Gutenberg_Active implements Toolset_Condition_Interface {

	public function is_met() {
		return function_exists( 'register_block_type' );
	}
}
