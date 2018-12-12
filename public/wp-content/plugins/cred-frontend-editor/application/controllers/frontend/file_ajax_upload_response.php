<?php

/**
 * Class used to show json response compatible with file_upload.js library
 *
 * @since 1.9.3
 */
class CRED_Frontend_File_Ajax_Upload_Response implements ICRED_Frontend_File_Ajax_Upload_Response {

	/**
	 * @param array $data
	 */
	public function json_send_ajax_success( $data ) {
		$response = array( 'success' => true );
		$response = array_merge( $response, $data );
		echo json_encode( $response );
		wp_die();
	}

	/**
	 * @param array $data
	 */
	public function json_send_ajax_error( $data ) {
		$response = array( 'success' => false );
		$response = array_merge( $response, $data );
		echo json_encode( $response );
		wp_die();
	}
}