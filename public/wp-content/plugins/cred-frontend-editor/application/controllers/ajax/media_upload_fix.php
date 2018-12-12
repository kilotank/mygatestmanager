<?php

/**
 * Class responsible to fix caps when user has upload_files permissions but not edit_posts in order
 * to upload media contents
 *
 * @since 1.9.4
 */
class CRED_Ajax_Media_Upload_Fix {

	public function initialize() {
		$this->add_temporary_edit_posts_cap_to_fix_media_upload_permissions();
	}

	/**
	 * When a user has only caps upload_files set during a Add Media Upload Action
	 * the user will get a post attachment error: 'Sorry, you are not allowed to attach files to this post'
	 */
	public function add_temporary_edit_posts_cap_to_fix_media_upload_permissions() {
		$current_user = wp_get_current_user();
		if ( cred_is_ajax_call()
			&& isset( $_POST['action'] )
			&& (
				$_POST['action'] == 'query-attachments'
				|| $_POST['action'] == 'upload-attachment'
			)
			&& $current_user->ID != 0
			&& $current_user->has_cap( 'upload_files' )
			&& ! $current_user->has_cap( 'edit_posts' )
		) {
			$current_user->add_cap( 'edit_posts' );
			//remove this temporary cap at the end of WP processes
			add_action( 'shutdown', array( $this, 'remove_temporary_edit_posts_cap' ) );
		}
	}

	/**
	 * Remove edit_posts cap to users that did not have it in the origin
	 * callback of shoutdown action in CRED_Helper::add_temporary_edit_posts_cap_to_fix_media_upload_permissions()
	 */
	public function remove_temporary_edit_posts_cap() {
		remove_action( 'shutdown', array( $this, 'remove_temporary_edit_posts_cap' ) );
		$current_user = wp_get_current_user();
		$current_user->remove_cap( 'edit_posts' );
	}

}