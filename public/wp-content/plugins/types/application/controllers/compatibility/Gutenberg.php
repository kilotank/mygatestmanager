<?php

namespace OTGS\Toolset\Types\Controller\Compatibility;

use OTGS\Toolset\Types\Compatibility\Gutenberg\View\PostEdit as GutenbergPostEdit;

/**
 * Class Gutenberg
 *
 * @package OTGS\Toolset\Types\Controller\Compatibility
 *
 * @since 3.2
 */
class Gutenberg {
	/**
	 * @return bool
	 */
	public function is_active_for_current_post_type() {
		if ( ! $current_screen = get_current_screen() ) {
			// called to early
			return false;
		}

		// Check Gutenberg
		if ( function_exists( 'use_block_editor_for_post_type' ) ) {
			// >= WP 5.0
			if ( ! use_block_editor_for_post_type( $current_screen->post_type ) ) {
				// no block editor active for this post type
				return false;
			}
		} else {
			// < WP 5.0
			if ( ! function_exists( 'gutenberg_can_edit_post_type' )
				 || ! gutenberg_can_edit_post_type( $current_screen->post_type ) ) {
				// no gutenberg at all or not active for this post type
				return false;
			}
		}

		// gutenberg active
		return true;
	}

	/**
	 * Load Gutenberg compatibility on post edit screen
	 *
	 * @hook load-post.php (Post Edit Page)
	 *
	 * @param GutenbergPostEdit $view
	 */
	public function post_edit_screen( GutenbergPostEdit $view ) {
		// hook frontend scripts loading to admin_enqueue_scripts
		add_action( 'admin_enqueue_scripts', function () use ( $view ) {
			// gutenberg active for the current post type
			$view->enqueueScripts();
		}, 11 );
	}
}
