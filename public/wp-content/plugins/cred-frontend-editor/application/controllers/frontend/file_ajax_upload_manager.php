<?php

/**
 * Toolset Forms Frontend File Ajax Upload Manager handles credfile, credimage, credaudio and credvideo files
 * through wp_ajax_[nopriv_] wp hook
 *
 * @since 1.9.3
 */
class CRED_Frontend_File_Ajax_Upload_Manager implements ICRED_Frontend_File_Ajax_Upload_Manager {

	protected $php_file_upload_error_messages;
	protected $cred_file_upload_error_messages;

	private static $instance;

	public function __construct() {
		$this->init();
		$this->prevent_possible_php_post_content_length_warning();
		$this->register_hooks();
	}

	protected function init() {
		$this->php_file_upload_error_messages = array(
			0 => __( 'There is no error, the file uploaded with success', 'wp-cred' ),
			1 => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 'wp-cred' ),
			2 => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'wp-cred' ),
			3 => __( 'The uploaded file was only partially uploaded', 'wp-cred' ),
			4 => __( 'No file was uploaded', 'wp-cred' ),
			6 => __( 'Missing a temporary folder', 'wp-cred' ),
			7 => __( 'Failed to write file to disk.', 'wp-cred' ),
			8 => __( 'A PHP extension stopped the file upload.', 'wp-cred' ),
		);

		$this->cred_file_upload_error_messages = array(
			1 => __( 'The file you uploaded it too large. You can upload files up to %s', 'wp-cred' ),
			4 => __( 'There was an error uploading your file.', 'wp-cred' ),
			5 => __( "The form you're submitting doesn't seem to belong to this page. Please reload the page and try again.", 'wp-cred' ),
			6 => __( 'The form you submitted has expired. Please refresh the page and try again.', 'wp-cred' ),
		);
	}

	// We need to handle a specific case:
	// When file upload size is higher than ini_get('post_max_size')
	// PHP execution shows
	// PHP Warning: POST Content-Length of XXXXXX bytes exceeds the limit of XXXXXX
	// on the screen before the WP execution
	// that breaks the json that contains the error message
	// and the user will always receive a generic Upload Error.
	// We can clean the screen
	// just in case we have a ajax, cred file upload
	// and the file size is not valid.
	protected function prevent_possible_php_post_content_length_warning() {
		if (
			cred_is_ajax_call()
			&& isset( $_GET['action'] )
			&& $_GET['action'] == CRED_Asset_Manager::CRED_ACTION_AJAX_UPLOAD
			&& isset( $_SERVER["CONTENT_LENGTH"] )
			&& $_SERVER["CONTENT_LENGTH"] > wp_max_upload_size()
		) {
			ob_end_clean();
		}
	}

	protected function register_hooks() {
		add_action( 'wp_ajax_' . CRED_Asset_Manager::CRED_ACTION_AJAX_UPLOAD, array( $this, 'upload' ) );
		add_action( 'wp_ajax_nopriv_' . CRED_Asset_Manager::CRED_ACTION_AJAX_UPLOAD, array( $this, 'upload' ) );
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function upload() {
		$current_user = wp_get_current_user();
		$is_current_user_admin = ( user_can( $current_user, 'administrator' ) );

		$response = new CRED_Frontend_File_Ajax_Upload_Response();
		$data = array();

		//checking nonce
		if (
			isset( $_REQUEST['nonce'] )
			&& check_ajax_referer( 'ajax_nonce', 'nonce', false )
		) {
			//checking delete action
			if (
				isset( $_POST['action'] )
				&& $_POST['action'] == 'delete'
				&& isset( $_POST['file'] )
			) {
				$file = esc_url_raw( $_POST['file'] );
				$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : - 1;

				$data = array( 'result' => true );
				$local_file = cred_get_local( $file );
				$attachments = get_children(
					array(
						'post_parent' => $id,
						'post_type' => 'attachment',
					)
				);
				if ( ! empty( $attachments ) ) {
					foreach ( $attachments as $attachment ) {
						$attach_file = strtolower( basename( $attachment->guid ) );
						$my_local_file = strtolower( basename( $local_file ) );
						if ( $attach_file == $my_local_file ) {
							wp_delete_attachment( $attachment->ID );
						}
					}
				}

			} else {

				//checking parent post_id
				if ( isset( $_GET['id'] ) ) {
					$post_id = (int) $_GET['id'];
					$post_type = get_post_type( $post_id );

					//checking formid
					if ( isset( $_GET['formid'] ) ) {
						$form_id = (int) $_GET['formid'];
						$form = get_post( $form_id );
						$form_type = $form->post_type;

						$is_user_form = ( $form_type == CRED_USER_FORMS_CUSTOM_POST_NAME );
						if ( $is_user_form ) {
							//for user forms attached parent post is always -1
							$post_id = - 1;
						}

						$form_slug = $form->post_name;

						$this_form = array(
							'id' => $form_id,
							'post_type' => $post_type,
							'form_type' => $form_type,
						);

						$files = array();
						$previews = array();
						$upload_overrides = array( 'test_form' => false );
						if ( ! empty( $_FILES ) ) {

							//controls file error_codes
							foreach ( $_FILES as $uploaded_file ) {
								$error_code = is_array( $uploaded_file['error'] ) ? reset($uploaded_file['error']) : $uploaded_file['error'];
								if ( $error_code != 0 ) {
									//shows php error messages only for admin users
									$error_message = $is_current_user_admin ? $this->php_file_upload_error_messages[ $error_code ] : $this->cred_file_upload_error_messages[4];
									$data = array( 'message' => $error_message );
									break;
								}
							}

							//There are no errors
							if ( empty( $data ) ) {

								$fields = array();
								foreach ( $_FILES as $field_name => $field_value ) {
									$fields[ $field_name ]['field_data'] = $field_value;
								}

								$errors = array();

								list( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate_' . $form_slug, array( $fields, $errors ), $this_form );
								list( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate_' . $form_id, array( $fields, $errors ), $this_form );
								list( $fields, $errors ) = apply_filters( 'cred_form_ajax_upload_validate', array( $fields, $errors ), $this_form );

								if ( ! empty( $errors ) ) {
									//even data is an array of array, we can set 1 field error only at time
									foreach ( $errors as $field_name => $error_text ) {
										$data = array( 'message' => $field_name . ': ' . $error_text );
									}
									$response->json_send_ajax_error( $data );
								} else {
									foreach ( $_FILES as $file ) {
										//For repetitive
										foreach ( $file as &$f ) {
											if ( is_array( $f ) ) {
												foreach ( $f as $p ) {
													$f = $p;
													break;
												}
											}
										}

										$upload_result = wp_handle_upload( $file, $upload_overrides );
										if ( ! isset( $upload_result['error'] ) ) {

											$base_name = basename( $upload_result['file'] );
											$attachment = array(
												'post_mime_type' => $upload_result['type'],
												'post_title' => $base_name,
												'post_content' => '',
												'post_status' => 'inherit',
												'post_parent' => $post_id,
												'post_type' => 'attachment',
												'guid' => $upload_result['url'],
											);
											$attached_id = wp_insert_attachment( $attachment, $upload_result['file'] );
											$attached_data = wp_generate_attachment_metadata( $attached_id, $upload_result['file'] );
											wp_update_attachment_metadata( $attached_id, $attached_data );

											//Fixing S3 Amazon rewriting compatibility
											if ( wp_attachment_is_image( $attached_id ) ) {
												$rewrite_url = wp_get_attachment_image_src( $attached_id, 'full' );
												$rewrite_url_preview = wp_get_attachment_image_src( $attached_id );
												$attached_data = wp_generate_attachment_metadata( $attached_id, $rewrite_url );
											} else {
												$rewrite_url = wp_get_attachment_url( $attached_id );
											}

											if ( isset( $rewrite_url ) ) {
												$files[] = ( is_array( $rewrite_url ) && isset( $rewrite_url[0] ) ) ? $rewrite_url[0] : $rewrite_url; //$res['url'];
												$attaches[] = $attached_id;
												if ( isset( $rewrite_url_preview ) ) {
													$previews[] = ( is_array( $rewrite_url_preview ) && isset( $rewrite_url_preview[0] ) ) ? $rewrite_url_preview[0] : $rewrite_url_preview;
												}
											} else {
												$files[] = $upload_result['url'];
												$attaches[] = $attached_id;
											}

											//upload success
											$data = array(
												'files' => $files,
												'attaches' => $attaches,
												'previews' => $previews,
												'delete_nonce' => time(),
											);
											$response->json_send_ajax_success( $data );
										} else {
											//very last priority. This case should never happen because we already checked error_code above.
											$data = array( 'message' => $this->cred_file_upload_error_messages[4] );
										}
									}
								}
							}

						} else {
							$data = array( 'message' => sprintf( $this->cred_file_upload_error_messages[1], $this->human_readable_max_upload_size ) );
						}

					} else {
						$data = array( 'message' => $this->cred_file_upload_error_messages[5] );
					}

				} else {
					$data = array( 'message' => $this->cred_file_upload_error_messages[5] );
				}
			}

		} else {
			$data = array( 'message' => $this->cred_file_upload_error_messages[6] );
		}

		// we cannot use wp_send_ajax_success or wp_send_ajax_error
		// because of how is handling response file_upload js lib
		$response->json_send_ajax_error( $data );
	}

}