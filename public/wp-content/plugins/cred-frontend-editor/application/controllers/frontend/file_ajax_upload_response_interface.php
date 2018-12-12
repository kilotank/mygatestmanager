<?php

/**
 * Interface for CRED_Frontend_File_Ajax_Upload_Manager
 *
 * @since 1.9.3
 */
interface ICRED_Frontend_File_Ajax_Upload_Response {

	public function json_send_ajax_success( $data );

	public function json_send_ajax_error( $data );
}