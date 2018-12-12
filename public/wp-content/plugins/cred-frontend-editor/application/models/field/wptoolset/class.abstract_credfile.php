<?php

require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.textfield.php';

/**
 * Base Class that include the common behavior between audio/video/image/file Classes
 * @since 1.9.3
 */
class CRED_Abstract_WPToolset_Field_Credfile extends WPToolset_Field_Textfield {

	public $disable_progress_bar;

	public static function get_image_sizes( $desidered_size = '' ) {

		global $_wp_additional_image_sizes;

		$desidered_size_array = array();
		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		// Create the full array with sizes and crop info
		foreach ( $get_intermediate_image_sizes as $image_size ) {
			if ( in_array( $image_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
				$desidered_size_array[ $image_size ]['width'] = get_option( $image_size . '_size_w' );
				$desidered_size_array[ $image_size ]['height'] = get_option( $image_size . '_size_h' );
				$desidered_size_array[ $image_size ]['crop'] = (bool) get_option( $image_size . '_crop' );
			} elseif ( isset( $_wp_additional_image_sizes[ $image_size ] ) ) {
				$desidered_size_array[ $image_size ] = array(
					'width' => $_wp_additional_image_sizes[ $image_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $image_size ]['height'],
					'crop' => $_wp_additional_image_sizes[ $image_size ]['crop'],
				);
			}
		}

		// Get only 1 size if found
		if ( $desidered_size ) {
			if ( isset( $desidered_size_array[ $desidered_size ] ) ) {
				return $desidered_size_array[ $desidered_size ];
			} else {
				return false;
			}
		}

		return $desidered_size_array;
	}

	/**
	 * Determine if the file upload progress bar should be displayed on the front-end.
	 *
	 * @return bool
	 * @since 1.9
	 */
	private function is_progress_bar_disabled() {

		/**
		 * cred_file_upload_disable_progress_bar
		 *
		 * Allows to override the decision whether the file upload & progress bar should be displayed
		 * we have this new feature since version 1.3.6.3 so current filter by default should be set to false
		 *
		 * @param bool $disable true to disable, false to enable.
		 *
		 * @since 1.3.6.3
		 */
		$is_disabled = (bool) apply_filters( 'cred_file_upload_disable_progress_bar', false );

		return $is_disabled;
	}

	public function init() {
		CRED_Asset_Manager::get_instance()->enqueue_file_upload_assets();
	}

	public static function registerScripts() {

	}

	public static function registerStyles() {

	}

	public function enqueueScripts() {

	}

	public function enqueueStyles() {

	}

	public function metaform() {
		$value = $this->getValue();
		$name = $this->getName();

		if ( isset( $this->_data['title'] ) ) {
			$title = $this->_data['title'];
		} else {
			$title = $name;
		}

		$id = $this->_data['id'];
		$unique_id = str_replace( array( '[', ']' ), '', $this->_data['name'] );

		$preview_span_input_showhide = '';
		$button_extra_classnames = '';

		$has_image = false;
		$is_empty = false;
		if ( empty( $value ) ) {
			$value = ''; // NOTE we need to set it to an empty string because sometimes it is NULL on repeating fields
			$is_empty = true;
			$preview_span_input_showhide = ' style="display:none"';
		}

		if ( ! $is_empty ) {
			// TODO we should check against the allowed mime types, not file extensions
			$has_image = $this->has_image( $value );
		}

		if ( array_key_exists( 'use_bootstrap', $this->_data ) && $this->_data['use_bootstrap'] ) {
			$button_extra_classnames = ' btn btn-default btn-sm';
		}

		$preview_file = '';
		$attr_hidden = array(
			'id' => $unique_id . "_hidden",
			'class' => 'js-wpv-credfile-hidden',
			'data-wpt-type' => 'file',
		);

		$attributes = $this->getAttr();
		$preview_images = isset( $attributes['preview_thumbnail_url'] ) ? $attributes['preview_thumbnail_url'] : "";

		$output = ( isset( $attributes['output'] ) ) ? $attributes['output'] : "";
		$shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";

		$attr_file = array(
			'id' => $unique_id . "_file",
			'class' => "js-wpt-credfile-upload-file wpt-credfile-upload-file {$shortcode_class}",
			'alt' => $value,
			'res' => $value,
		);

		if ( ! $is_empty ) {
			$image_hash = md5( $value );
			$preview_file = isset( $preview_images[ $image_hash ] ) ? $preview_images[ $image_hash ] : $value;
			$attr_file['style'] = 'display:none';
			if ( ! empty( $value ) ) {
				$attr_file['disabled'] = 'disabled';
			}
		} else {
			$attr_hidden['disabled'] = 'disabled';
		}

		$form = array();
		$form = $this->get_markup_undo_html_tag( $button_extra_classnames, $form );

		//Attachment id for _featured_image if exists
		//if it does not exists file_upload.js will handle it after file is uploaded
		if ( $name == '_featured_image' ) {
			global $post;
			if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
				$post_id = $post->ID;
				$post_thumbnail_id = $this->get_post_thumbnail_id( $post_id, $has_image );

				if ( ! empty( $post_thumbnail_id ) ) {
					// here we can use $id because referred to _feature_image only and it is unique
					$form = $this->get_attached_html_tag( $id, $name, $post_thumbnail_id, $form );
				}
			}
		}

		$form = $this->get_hidden_html_tag( $name, $value, $attr_hidden, $form );
		$form = $this->get_file_html_tag( $name, $value, $title, $attr_file, $form );

		if ( ! $this->disable_progress_bar ) {
			//Progress Bar
			$form = $this->get_progressbar_html_tag( $unique_id, $form );
		}

		$delete_span_button = $this->get_delete_span_button_by_output( $output, $button_extra_classnames );
		$span_container = $this->get_span_container( $preview_span_input_showhide );
		$form = $this->get_markup_html_tag( $has_image, $unique_id, $preview_file, $span_container, $delete_span_button, $form );

		return $form;
	}

	/**
	 * @param $value
	 *
	 * @return bool
	 */
	public function has_image( $value ) {
		$pathinfo = pathinfo( $value );

		return ( ( $this->_data['type'] == 'credimage'
				|| $this->_data['type'] == 'credfile' )
			&& isset( $pathinfo['extension'] )
			&& in_array( strtolower( $pathinfo['extension'] ), array( 'png', 'gif', 'jpg', 'jpeg', 'bmp', 'tif' ) )
		);
	}

	/**
	 * @param $id
	 * @param $name
	 * @param $post_thumbnail_id
	 * @param $form
	 *
	 * @return array
	 */
	public function get_attached_html_tag( $id, $name, $post_thumbnail_id, $form ) {
		$form[] = array(
			'#type' => 'markup',
			'#markup' => "<input id='attachid_" . $id . "' name='attachid_" . $name . "' type='hidden' value='" . esc_attr( $post_thumbnail_id ) . "'>",
		);

		return $form;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $attr_hidden
	 * @param $form
	 *
	 * @return array
	 */
	public function get_hidden_html_tag( $name, $value, $attr_hidden, $form ) {
		$form[] = array(
			'#type' => 'hidden',
			'#name' => $name,
			'#value' => $value,
			'#attributes' => $attr_hidden,
		);

		return $form;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $title
	 * @param $attr_file
	 * @param $form
	 *
	 * @return array
	 */
	public function get_file_html_tag( $name, $value, $title, $attr_file, $form ) {
		$form[] = array(
			'#type' => 'file',
			'#name' => $name,
			'#value' => $value,
			'#title' => $title,
			'#before' => '',
			'#after' => '',
			'#attributes' => $attr_file,
			'#validate' => $this->getValidationData(),
			'#repetitive' => $this->isRepetitive(),
		);

		return $form;
	}

	/**
	 * @param $unique_id
	 * @param $form
	 *
	 * @return array
	 */
	public function get_progressbar_html_tag( $unique_id, $form ) {
		$form[] = array(
			'#type' => 'markup',
			'#markup' => '<div id="progress_' . $unique_id . '" class="meter" style="display:none;"><span class = "progress-bar" style="width:0;"></span></div>',
		);

		return $form;
	}

	/**
	 * @param $output
	 * @param $button_extra_classnames
	 *
	 * @return string
	 */
	public function get_delete_span_button_by_output( $output, $button_extra_classnames ) {
		if ( $output == 'bootstrap' ) {
			$delete_span_button = '<span role="button" data-action="delete" class="dashicons-before dashicons-no js-wpt-credfile-delete wpt-credfile-delete" title="' . esc_attr( __( 'delete', 'wp-cred' ) ) . '"></span>';
		} else {
			$delete_span_button = '<input type="button" data-action="delete" class="js-wpt-credfile-delete wpt-credfile-delete' . $button_extra_classnames . '" value="' . esc_attr( __( 'delete', 'wp-cred' ) ) . '" style="width:100%;margin-top:2px;margin-bottom:2px;" />';
		}

		return $delete_span_button;
	}

	/**
	 * @param $has_image
	 * @param $unique_id
	 * @param $preview_file
	 * @param $span_container
	 * @param $delete_span_button
	 * @param $form
	 *
	 * @return array
	 */
	public function get_markup_html_tag( $has_image, $unique_id, $preview_file, $span_container, $delete_span_button, $form ) {
		if ( $has_image ) {
			$preview_image = '<img id="' . $unique_id . '_image" src="' . $preview_file . '" title="' . $preview_file . '" alt="' . $preview_file . '" data-pin-nopin="true"/>';
			$form[] = array(
				'#type' => 'markup',
				'#markup' => sprintf( $span_container, $preview_image, $delete_span_button ),
			);
		} else {
			$form[] = array(
				'#type' => 'markup',
				'#markup' => sprintf( $span_container, $preview_file, $delete_span_button ),
			);
		}

		return $form;
	}

	/**
	 * @param $button_extra_classnames
	 * @param $form
	 *
	 * @return array
	 */
	public function get_markup_undo_html_tag( $button_extra_classnames, $form ) {
		$form[] = array(
			'#type' => 'markup',
			'#markup' => '<input type="button" style="display:none" data-action="undo" class="js-wpt-credfile-undo wpt-credfile-undo' . $button_extra_classnames . '" value="' . esc_attr( __( 'Restore Previous Value', 'wp-cred' ) ) . '" />',
		);

		return $form;
	}

	/**
	 * @param $preview_span_input_showhide
	 *
	 * @return string
	 */
	public function get_span_container( $preview_span_input_showhide ) {
		$span_container = '<span class="js-wpt-credfile-preview wpt-credfile-preview" ' . $preview_span_input_showhide . '>%s %s</span>';

		return $span_container;
	}

	/**
	 * @param $post_id
	 * @param $has_image
	 *
	 * @return int|string
	 */
	public function get_post_thumbnail_id( $post_id, $has_image ) {
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );

		//Covering the case of Toolset Forms for NEW content
		//when validation is failed
		//and we need to re-render the featured_image
		//with attachid__feature_image set as well
		if ( $has_image
			&& empty( $post_thumbnail_id )
			&& isset( $_POST['attachid__featured_image'] )
		) {
			$post_thumbnail_id = (int) $_POST['attachid__featured_image'];
		}

		return $post_thumbnail_id;
	}

	/**
	 * Function that populate $validation array with wp allowed extensions in a compatible format
	 * for the relative Cred upload Field
	 *
	 * @param array $validation     Validation Array used in Metaform Field
	 * @param string $field_upload_type     Type of Field Upload (video|audio|image|file)
	 * @since 1.9.3
	 */
	protected function set_allowed_extensions_validation_by_field_upload_type( &$validation, $field_upload_type = 'file' ) {
		$all_wp_allowed_mime_types = get_allowed_mime_types();

		//array that will include all type extensions used on validation frontend
		$allowed_field_upload_mime_types = array();
		$allowed_field_upload_extensions = array();
		foreach ( $all_wp_allowed_mime_types as $extension => $mime_type ) {
			//file will include all of them
			if ( 'file' == $field_upload_type
				|| false !== strpos( $mime_type, $field_upload_type . '/' )
			) {
				$allowed_field_upload_mime_types[] = $mime_type;
				$allowed_field_upload_extensions[] = $extension;
			}
		}

		/*
		 * Adding audio/mp3 exception in order to fix issue on Chrome
		 * https://bugs.chromium.org/p/chromium/issues/detail?id=227004
		 */
		if ( ( 'audio' === $field_upload_type
				|| 'file' === $field_upload_type )
			&& ! in_array( 'audio/mp3', $allowed_field_upload_mime_types ) ) {
			$allowed_field_upload_mime_types[] = "audio/mp3";
		}

		/**
		 * toolset_valid_{$field_upload_type}_extentions
		 *
		 *  This filter allows to handle field_upload_type extensions
		 *  $field_upload_type could be audio, video, image or file
		 *
		 * @param array $allowed_field_upload_mime_types   Array of allowed field upload type extensions
		 *
		 * @since 1.9
		 */
		$allowed_field_upload_mime_types = apply_filters( 'toolset_valid_' . $field_upload_type . '_extentions', $allowed_field_upload_mime_types );

		$default_message = ( 'file' == $field_upload_type )
		/** translators: This is a validation message when trying to upload a file of an unsupported format */
			? __( 'You cannot upload a file of this type', 'wp-cred' )
			: sprintf(
				/** translators: This is a validation message when trying to upload a file of an unsupported type, like an image as an audio field value */
				__( 'You can only upload a %s file', 'wp-cred' ),
				$field_upload_type
			);

		//set validation extension array
		$validation['mime_type'] = array(
			'args' => array(
				'mime_type',
				implode('|', $allowed_field_upload_mime_types),
			),
			'message' => $default_message,
		);
		$validation['extension'] = array(
			'args' => array(
				'extension',
				implode('|', $allowed_field_upload_extensions),
			),
			'message' => $default_message,
		);
	}

}
