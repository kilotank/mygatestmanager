<?php

/**
 * Group of classes for Types Fields cells.
 *
 * @since 2.4
 */

if ( ddl_has_feature( 'fields-cell' ) === false ) {
	return;
}

/**
 * Genereic fields cells
 * TODO add an extended comment
 *
 * @since 2.4
 */
class WPDD_layout_cell_fields extends WPDD_layout_cell {


	/**
	 * WPDD_layout_cell_fields constructor
	 *
	 * @param string $id Cell ID.
	 * @param string $name Cell name.
	 * @param int    $width Cell width.
	 * @param string $css_class_name Cell class name.
	 * @param string $content Content.
	 * @param string $css_id CSS ID.
	 * @param string $tag Tag.
	 * @param string $unique_id Unique ID.
	 * @since 2.4
	 */
	function __construct( $id, $name, $width, $css_class_name = '', $content = null, $css_id, $tag, $unique_id ) {
		parent::__construct( $id, $name, $width, $css_class_name, 'fields-cell', $content, $css_id, $tag, $unique_id );
		$this->set_cell_type( 'fields-cell' );
	}


	/**
	 * Renders the frontend
	 *
	 * @param WPDD_layout_render $target Layout renderer.
	 * @since 2.4
	 */
	function frontend_render_cell_content( $target ) {
		$field_options = $this->get_content();
		// [group ID]#[field slug] just in case it is needed.
		$field_slug = explode( '#', $field_options['field_type'] );

		if ( $target->get_layout()->is_private() ) {
			// If private layout it has to be rendered using shortcodes because it can't be pre-rendered because post's fields can be modifiend.
			$content = $this->generate_shortcode( $field_slug[1], $this->sanitize_options( $field_options ) );
		} else {
			$content = types_render_field( $field_slug[1], $this->sanitize_options( $field_options ) );
		}

		return $target->cell_content_callback( $content, $this );
	}


	/**
	 * Generates a shortcode string
	 *
	 * @param string $field_slug Field slug.
	 * @param array $attributes List of attributes.
	 * @return string
	 * @since 2.4
	 */
	private function generate_shortcode( $field_slug, $attributes ) {
		unset( $attributes['unique_id'] );
		unset( $attributes['icon'] );
		unset( $attributes['field'] );
		unset( $attributes['field_type'] );
		$attributes_joined = array_map( function( $v, $k ) {
			return $k . '="' . $v . '"';
		}, array_values( $attributes ), array_keys( $attributes ) );
		$shortcode = '[types field="' . $field_slug . '" ' . implode( ' ', $attributes_joined ) . '][/types]';
		return $shortcode;
	}


	/**
	 * Some options need to be sanitized in order to work with `types_render_field`
	 *
	 * @param array $options List of Field Cell options
	 * @return array
	 * @since 2.4
	 */
	function sanitize_options( $options ) {
		$data = array();

		// Converts option[id][attribute] string in nested arrays.
		$params_joined = array_map( function( $v, $k ) { return $k.'='.$v; }, array_values( $options ), array_keys( $options ) );
		parse_str( implode( '&', $params_joined ), $data );

		// Sets `toolsetCombo:` options
		foreach ( $data as $option => $value ) {
			if ( strpos( $option, 'toolsetCombo:' ) === 0 ) {
				$data[ str_replace( 'toolsetCombo:', '', $option ) ] = $value;
				unset( $data[ $option ] );
			}
		}

		if ( isset( $data['output'] ) && 'url' === $data['output'] ) {
			$data['url'] = 'true';
		}

		if ( isset( $data['proportional'] ) && 'true' === $data['proportional'] ) {
			$data['resize'] = 'proportional';
			unset( $data['proportional'] );
		}

		return $data;
	}
}


/**
 * Factory class for Fields cell
 *
 * @since 2.4
 */
class WPDD_layout_cell_fields_factory extends WPDD_layout_cell_factory {



	/**
	 * Builder
	 *
	 * @param string $name Cell name.
	 * @param int    $width Cell width.
	 * @param string $css_class_name Cell class name.
	 * @param string $content Content.
	 * @param string $css_id CSS ID.
	 * @param string $tag Tag.
	 * @param string $unique_id Unique ID.
	 * @since 2.4
	 */
	public function build( $name, $width, $css_class_name = '', $content = null, $css_id, $tag, $unique_id ) {
		return new WPDD_layout_cell_fields( $unique_id, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id );
	}


	/**
	 * Gets cell info
	 *
	 * @param array $template Template array.
	 * @return array
	 * @since 2.4
	 */
	public function get_cell_info( $template ) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH . 'fields-cell.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'field_expand-image.png';
		$template['name'] =	__( 'Types Fields', 'ddl-layouts' );
		$template['description'] = __( 'Display content of a post field. You need to configure the Field Group and the value in the post.', 'ddl-layouts' );
		$template['button-text'] = __( 'Assign Fields cell', 'ddl-layouts' );
		$template['dialog-title-create'] = __( 'Create new Fields cell', 'ddl-layouts' );
		$template['dialog-title-edit'] = __( 'Edit Fields cell', 'ddl-layouts' );
		$template['dialog-template'] = $this->_dialog_template();
		$template['category'] = __( 'Fields, text and media', 'ddl-layouts' );
		$template['has_settings'] = true;

		return $template;
	}

	public function get_editor_cell_template() {
		$template_path = WPDDL_GUI_ABSPATH . 'editor/templates/cell_types/fields/cell_preview.tpl.php';
		$context = array();
		$files = new Toolset_Files();
		if ( $files->is_file( $template_path ) ) {
			$output = $files->get_include_file_output( $template_path, $context );
		}
		return $output;
	}


	/**
	 * Returns the output of the dialog
	 *
	 * @return string
	 * @since 2.4
	 */
	private function _dialog_template() {
		$template_path = WPDDL_GUI_ABSPATH . 'editor/templates/cell_types/fields/main.tpl.php';
		$context = array(
			'groups' => toolset_get_custom_fields_by_field_group(),
		);
		$files = new Toolset_Files();
		if ( $files->is_file( $template_path ) ) {
			$output = $files->get_include_file_output( $template_path, $context );
		}
		return $output;
	}


	/**
	 * Enqueues editor scripts
	 */
	public function enqueue_editor_scripts() {
		$script = new WPDDL_script( 'layouts-fields-cell', WPDDL_GUI_RELPATH . 'editor/js/fields-cell.js' );
		$script->enqueue();
	}
}


/**
 * Making fields cell available
 */
add_filter( 'dd_layouts_register_cell_factory', 'dd_layouts_register_cell_fields_factory' );
/**
 * Register a new factory for the fields cells
 *
 * @param array $factories Previous factories registered.
 * @return array
 * @since 2.4
 */
function dd_layouts_register_cell_fields_factory( $factories ) {
	if ( defined( 'TYPES_VERSION' ) ) {
		$factories['fields-cell'] = new WPDD_layout_cell_fields_factory;
	}
	return $factories;
}

//add_action( 'ddl-before_init_layouts_plugin', array('WPDD_layouts_layout_accordion', 'get_instance') );
