<?php

class CRED_Translate_Image_Command extends CRED_Translate_Field_Command_Base {

	function __construct( CRED_Translate_Field_Factory $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field ) {
		parent::__construct( $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field );

		$this->field_type = 'cred' . $this->field['type'];
	}

	public function execute() {
		$can_accept_post_data = isset($this->additional_args['can_accept_post_data']) ? $this->additional_args['can_accept_post_data'] : false;
		$postData = isset($this->additional_args['postData']) ? $this->additional_args['postData'] : null;

		// show previous post featured image thumbnail
		if (
			$can_accept_post_data
			&& ! isset( $_POST['_featured_image'] )
			&& '_featured_image' == $this->field_name
		) {
			$this->field_value = '';
			if ( isset( $postData->extra['featured_img_html'] ) ) {
				$this->field_attributes['display_featured_html'] = $this->field_value = $postData->extra['featured_img_html'];
			}
		}

		global $post;
		if ( isset( $post ) ) {
			$attachments = get_children(
				array(
					'post_parent' => $post->ID,
					//'post_mime_type' => 'image',
					'post_type' => 'attachment',
				)
			);
		}

		if ( isset( $attachments ) ) {
			$this->field_attributes['preview_thumbnail_url'] = array();
			foreach ( $attachments as $attachment_post_id => $attachment ) {
				//guid will help use to mantain the correct order when are repetitive images
				$full_image_url = $attachment->guid;
				$url_image_preview_thumbnail_array = wp_get_attachment_image_src( $attachment->ID );
				$url_image_preview_thumbnail = isset( $url_image_preview_thumbnail_array[0] ) ? $url_image_preview_thumbnail_array[0] : $full_image_url;
				if ( is_array( $this->field_value ) ) {
					foreach ( $this->field_value as $n => &$single_value ) {
						if (
							isset( $single_value ) &&
							! empty( $single_value ) &&
							basename( $full_image_url ) == basename( $single_value )
						) {
							$single_value = $full_image_url;
							$hash_value = md5( $single_value );
							$this->field_attributes['preview_thumbnail_url'][ $hash_value ] = $url_image_preview_thumbnail;
							break;
						}
					}
				} else {
					if (
						isset( $this->field_value )
						&& ! empty( $this->field_value )
						&& basename( $full_image_url ) === basename( $this->field_value )
					) {
						$this->field_value = $full_image_url;
						$hash_value = md5( $this->field_value );
						$this->field_attributes['preview_thumbnail_url'][ $hash_value ] = $url_image_preview_thumbnail;
					}
				}
			}
		}

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}