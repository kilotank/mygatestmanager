<?php

class Toolset_User_Editors_Editor_Screen_Visual_Composer_Frontend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	public function initialize() {
		add_action( 'init', array( $this, 'map_all_vc_shortcodes' ) );

		add_action( 'the_content', array( $this, 'render_custom_css' ) );
		
		add_filter( 'vc_basic_grid_find_post_shortcode', array( $this, 'maybe_get_shortcode_from_assigned_ct_id' ), 10, 3 );

		// this adds the [Fields and Views] to editor of WPBakery Page Builder (former Visual Composer) text element
		if( array_key_exists( 'action', $_POST ) && $_POST['action'] == 'vc_edit_form' ) {
			add_filter( 'wpv_filter_dialog_for_editors_requires_post', '__return_false' );
		}
	}

	/**
	 * We need to force the registration of all the WPBakery Page Builder (former Visual Composer) shortcodes in order to be rendered properly upon CT
	 * rendering.
	 */
	public function map_all_vc_shortcodes() {
		// make sure all vc shortcodes are loaded (needed for ajax pagination)
		if ( method_exists( 'WPBMap', 'addAllMappedShortcodes' ) ) {
			WPBMap::addAllMappedShortcodes();
		}
	}

	/**
	 * WPBakery Page Builder (former Visual Composer) stores custom css as postmeta.
	 * We need to check if current post has content_template and if so apply the custom css.
	 * Hooked to the_content
	 *
	 * @param $content
	 * @return mixed
	 */
	public function render_custom_css( $content ) {
		if(
			method_exists( 'Vc_Base', 'addPageCustomCss' )
			&& method_exists( 'Vc_Base', 'addShortcodesCustomCss' )
		) {
			$content_template = get_post_meta( get_the_ID(), '_views_template', true );

			if( $content_template && ! isset( $this->log_rendered_css[$content_template] ) ) {
				$vcbase = new Vc_Base();
				$vcbase->addPageCustomCss( $content_template );
				$vcbase->addShortcodesCustomCss( $content_template );
				$this->log_rendered_css[$content_template] = true;
			}
		}
		return $content;
	}

	/**
	 * Some of the WPBakery Page Builder (former Visual Composer) shortcodes are accessing the post meta of the post
	 * currently displayed to get the setting of the shortcodes settings. For the case where a Content Template is built
	 * with WPBakery Page Builder (former Visual Composer), in order for the shortcode settings to be retrieved correctly
	 * we need to access the Content Template post.
	 *
	 * @note Part of the method's code is copied by "findPostShortcodeById" method of WPBakery Page Builder.
	 *
	 * @param $shortcode The shortcode currently being rendered.
	 * @param $page_id   The page ID currently displayed.
	 * @param $grid_id   The grid ID saved inside the post meta.
	 *
	 * @return array     The new shortcode fetched from the Content Template
	 *
	 * @since 2.5.7
	 */
	public function maybe_get_shortcode_from_assigned_ct_id( $shortcode, $page_id, $grid_id ) {
		$page_id = intval( $page_id );
		$template_selected = get_post_meta( $page_id, '_views_template', true );
		if (
			! empty( $template_selected )
			&& intval( $template_selected ) > 0
		) {
			$page_id = $template_selected;
		}

		$post_meta = get_post_meta( (int) $page_id, '_vc_post_settings' );

		$shortcode = '';

		if ( is_array( $post_meta ) ) {
			foreach ( $post_meta as $meta ) {
				if ( isset( $meta['vc_grid_id'] ) && ! empty( $meta['vc_grid_id']['shortcodes'] ) && isset( $meta['vc_grid_id']['shortcodes'][ $grid_id ] ) ) {
					$shortcode = $meta['vc_grid_id']['shortcodes'][ $grid_id ];
					break;
				}
			}
		}

		return $shortcode;
	}
}
