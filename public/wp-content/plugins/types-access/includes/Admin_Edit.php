<?php

class Access_Admin_Edit
{
/*
 * Edit access page.
 */


/**
 * Admin page form.
 *
 * We are doing lots of things here we do not need at all
 */
public static function wpcf_access_admin_edit_access( $enabled = true ) {
	$output = '';
	
	$tabs = array(
		'post-type'		=> __( 'Post Types', 'wpcf-access' ),
		'taxonomy'		=> __( 'Taxonomies', 'wpcf-access' ),
		'custom-group'	=> __( 'Posts Groups', 'wpcf-access' ),
	);
	
	$extra_tabs = apply_filters( 'types-access-tab', array() );
	
	foreach ( $extra_tabs as $tab_slug => $tab_name ) {
		$tabs[ $tab_slug ] = $tab_name;
	}
	
	$custom_areas = apply_filters( 'types-access-area', array() );
	if ( count( $custom_areas ) > 0 ) {
		$tabs['third-party'] = __( 'Custom Areas', 'wpcf-access' );
	}
		
	if ( apply_filters( 'otg_access_filter_is_wpml_installed', false ) ) {
		$tabs['wpml-group'] = __( 'WPML Groups', 'wpcf-access' );
	}
	
	$tabs['custom-roles'] = __( 'Custom Roles', 'wpcf-access' );
	
	$current_tab = 'post-type';
	if ( isset( $_GET['tab'] ) ) {
		$current_tab_candidate = sanitize_text_field( $_GET['tab'] );
		if ( isset( $tabs[ $current_tab_candidate ] ) ) {
			$current_tab = $current_tab_candidate;
		}
	}
	
	$output .= wp_nonce_field( 'otg-access-edit-sections', 'otg-access-edit-sections', true, false );

	$output .= wp_nonce_field( 'wpcf-access-error-pages', 'wpcf-access-error-pages', true, false );
	
	$output .= '<p class="otg-access-new-nav">';
	foreach ( $tabs as $tab_section => $tab_title ) {
		$section_classname = array( 'nav-tab' );
		if ( $current_tab == $tab_section ) {
			$section_classname[] = 'nav-tab-active';
		}
		$section_classname[] = 'js-wpcf-access-shortcuts js-otg-access-nav-tab';
		$title = '';
		if ( $tab_section == 'post-type' ){
			$title = __( 'Manage access control to posts, pages and custom post types', 'wpcf-access' );
		}elseif ( $tab_section == 'types-fields' ){
			$title = __( 'Control who can view and edit custom fields  ', 'wpcf-access' );
		}elseif ( $tab_section == 'cred-forms' ){
			$title = __( 'Choose who can use Toolset Forms on the front-end ', 'wpcf-access' );
		}elseif ( $tab_section == 'taxonomy' ){
			$title = __( 'Manage access control to tags, categories and custom taxonomies ', 'wpcf-access' );
		}elseif ( $tab_section == 'custom-group' ){
			$title = __( 'Manage read access to front-end pages ', 'wpcf-access' );
		}elseif ( $tab_section == 'wpml-group' ){
			$title = __( 'Set up access control to content according to language ', 'wpcf-access' );
		}elseif ( $tab_section == 'custom-roles' ){
			$title = __( 'Define custom user roles and set up their access to admin functions ', 'wpcf-access' );
		}


		$output .= '<a title="'.$title.'" href="' . admin_url( 'admin.php?page=types_access&tab=' . $tab_section ) . '" data-target="' . esc_attr( $tab_section ) . '" class="' .  implode( ' ', $section_classname ) . '">' . esc_html( $tab_title ) . '</a>';
	}
	$output .= '</p>';
	
	$output .= '<div class="otg-access-settings-container">';
	$output .= '<form id="wpcf_access_admin_form" method="post" action="">';
    $output .= '<div class="js-otg-access-content">';
	
	$output .= '<div class="otg-access-settings-section-loading js-otg-access-settings-section-loading" style="display:none;">'
		. '<i class="fa fa-refresh fa-spin"></i>  '
		. __( 'Loading...', 'wpcf-access' )
		. '</div>';
	
	switch ( $current_tab ) {
		case 'post-type';
			$output .= self::otg_access_get_permission_table_for_posts();
			break;
		case 'taxonomy';
			$output .= self::otg_access_get_permission_table_for_taxonomies();
			break;
		case 'third-party';
			$output .= self::otg_access_get_permission_table_for_third_party();
			break;
		case 'custom-group';
			$output .= self::otg_access_get_permission_table_for_custom_groups();
			break;
		case 'wpml-group';
			$output .= self::otg_access_get_permission_table_for_wpml();
			break;
		case 'custom-roles';
			$output .= self::otg_access_get_permission_table_for_custom_roles();
			break;		
		default;
			if ( isset( $extra_tabs[ $current_tab ] ) ) {
				$output .= self::otg_access_get_permission_table_for_third_party( $current_tab );
			}
			break;
	}
		
	$output .= '</div>';

    $output .= '</form></div>';

    echo $output;

}

public static function otg_access_get_permission_table_for_posts() {
	$output = '';

	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-post-type" data-tab="post-type">';
	
	$model					= TAccess_Loader::get('MODEL/Access');
	$post_types_settings	= $model->getAccessTypes();
	$roles					= Access_Helper::wpcf_get_editable_roles();
	$post_types_available	= $model->getPostTypes();
	$post_types_available 	= Access_Helper::wpcf_object_to_array( $post_types_available );
	$section_statuses = self::get_section_statuses();
	$container_class = 'is-enabled';
	$enabled = true;
	
	$access_bypass_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses the same word for singular name and plural name. Access can\'t control access to this object. Please use a different word for the singular and plural names.', 'wpcf-access') . '</p>'
		. '</div>';
    $access_conflict_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default WordPress capabilities. Access can not manage this entity, try changing its name and / or slug', 'wpcf-access') . '</p>'
		. '</div>';
    $access_notices='';
	
	
	
	foreach ( $post_types_available as $type_slug => $type_data ) {
		// filter types, excluding types that do not have different plural and singular names
		if ( 
			isset( $type_data['__accessIsNameValid'] ) 
			&& ! $type_data['__accessIsNameValid']
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}
		if ( 
			isset( $type_data['__accessIsCapValid'] ) 
			&& ! $type_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}

		if ( isset( $post_types_settings[ $type_slug ] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_capabilities'] = $post_types_settings[ $type_slug ];
		}

		if ( ! empty( $type_data['_wpcf_access_inherits_post_cap'] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_inherits_post_cap'] = 1;
		}
	}

	// Put Posts and Pages in front
	// @todo do this on a proper way
	$native_post_types = array( 'page', 'post' );
	foreach ( $native_post_types as $npt ) {
		if ( isset( $post_types_available[ $npt ] ) ) {
			$clone = array( $npt => $post_types_available[ $npt ] );
			unset( $post_types_available[ $npt ] );
			$post_types_available = $clone + $post_types_available;
		}
	}
	$output .= '<p class="toolset-access-tab-info">
		'. __('Expand each of these post types to set their access control. Select the \'Managed by Access\' checkbox to customize the access rules. ', 'wpcf-access') .'<br>
		<a href="https://toolset.com/documentation/user-guides/setting-access-control/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Post%20types&utm_campaign=access" title="'. __('Access Control for Standard and Custom Content Types', 'wpcf-access') .'" target="_blank">'. __('Access Control for Standard and Custom Content Types', 'wpcf-access') .'</a>
	</p>';

	if ( ! empty( $post_types_available ) ) {
		$permission_array = Access_Helper::wpcf_access_types_caps_predefined();

		$post_types_with_custom_group = Access_Helper::otg_access_get_post_types_with_custom_groups();

		foreach ( $post_types_available as $type_slug => $type_data ) {
			if ( $type_data['public'] === 'hidden' ) {
				continue;
			}
			if (
				$type_slug == 'view-template' 
				|| $type_slug == 'view' 
				|| $type_slug == 'cred-form' 
				|| $type_slug == 'cred-user-form'
				|| $type_slug == 'widget-area'
			) {
				continue;
			}
			// Set data
			$mode = isset( $type_data['_wpcf_access_capabilities']['mode'] ) ? $type_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			$is_managed = ( $mode == 'permissions' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}
			$is_section_opened = false;
			if ( isset($section_statuses[$type_slug]) && $section_statuses[$type_slug] == 1 ){
				$is_section_opened = true;
			}

			$output .= '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item '. $container_class .' wpcf-access-post-type-name-' . $type_slug . ' js-wpcf-access-type-item">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $type_slug ) . '">' 
				. $type_data['labels']['name'] 
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed" style="display:'. ( !$is_section_opened?'block':'none') .'">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' ) : __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';
			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $type_slug ) . '" 
				style="display:'. ( !$is_section_opened?'none':'block') .'">';
			
			if ( $type_slug == 'attachment' ) {
				$output .= '<p class="otg-access-settings-section-description">' .
				__( 'This section controls access to media-element pages and not to media that is included in posts and pages.', 'wpcf-access' )
				. '</p>';
			}
			
			$output .= '<p class="wpcf-access-mode-control">';
            if ( $type_slug == 'post' || $type_slug == 'page'){
                $output .= '<label><input type="checkbox" name="wpcf-enable-access[' . $type_slug . ']" class="js-wpcf-enable-access" value="permissions"' . checked( $is_managed, true, false ) . '>'
                     . __( 'Managed by Access', 'wpcf-access' ) . '</label>';
            }else {
                $output .= '<label><input type="radio" name="wpcf-enable-access[' . $type_slug . ']" class="js-wpcf-enable-access" value="permissions"'
                         . checked( $is_managed, true, false ) . '>'
                         . __( 'Managed by Access', 'wpcf-access' ) . '</label>';

                $output .= '<br><label><input type="radio" name="wpcf-enable-access[' . $type_slug . ']" class="js-wpcf-enable-access" value="follow"'
                        . checked( $mode, 'follow', false ) . '>'
                        . __( 'Same read permission as posts', 'wpcf-access' ) . '</label>';

                $output .= '<br><label><input type="radio" name="wpcf-enable-access[' . $type_slug . ']" class="js-wpcf-enable-access" value="not_managed"'
                        . checked( $mode, 'not_managed', false ) . '>'
                        . __( 'Use the default WordPress read permissions', 'wpcf-access' ) . '</label>';
            }

			$output .= '<input type="hidden" class="js-wpcf-enable-set" '
					. 'name="types_access[types]['
					. $type_slug . '][mode]" value="'
					. $mode . '" />';
			$output .= '</p>';

			if ( $type_slug != 'post' && $type_slug != 'page' ){
				/*$output .= '<p class="otg-access-settings-section-description js-otg-access-settings-section-is-mamanged"'. (!$is_managed ? '' : 'style="display:none;"') .'>'
				. sprintf( __( 'Since %s are not managed by Access plugin, %s will have the same access rules as blog posts.', 'wpcf-access' ), $type_data['label'], $type_data['label']) . '</p>';*/
			}


			$permissions = ! empty( $type_data['_wpcf_access_capabilities']['permissions'] ) ? $type_data['_wpcf_access_capabilities']['permissions'] : array();

			$output .= self::wpcf_access_permissions_table(
						$roles,
						$permissions,
						$permission_array,
						'types',
						$type_slug,
						$enabled,
						$is_managed,
						$post_types_settings,
						$type_data
					);
			if ( in_array( $type_slug, $post_types_with_custom_group ) ) {
				$message = sprintf(
					__( 'Some %1$s may have different read settings because they belong to a Custom Group. %2$sEdit Custom Groups%3$s', 'wpcf-access' ),
						$type_data['labels']['name'],
						'<a class="js-otg-access-manual-tab" data-target="custom-group" href="' . admin_url( 'admin.php?page=types_access&tab=custom-group' ) . '">',
						'</a>'
				);
				$output .= '<div class="toolset-alert toolset-alert-info js-toolset-alert toolset-access-post-groups-info">' . $message . '</div>';
			}
			$output .= '<p class="wpcf-access-buttons-wrap">';

				$output .= self::wpcf_access_submit_button( $enabled, $is_managed, $type_data['labels']['name'] );
			$output .= '</p>';
			

			
			$output .= '</div><!-- wpcf-access-mode -->';
			$output .= '</div><!-- wpcf-access-type-item -->';

		}
	} else {
		$output .= '<p>'
			. __( 'There are no post types registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_taxonomies() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-taxonomy" data-tab="taxonomy">';
	
	$model					= TAccess_Loader::get('MODEL/Access');
	$roles					= Access_Helper::wpcf_get_editable_roles();
	$post_types_settings	= $model->getAccessTypes();
	$post_types_available	= $model->getPostTypes();
	$post_types_available	= Access_Helper::wpcf_object_to_array( $post_types_available );
	$taxonomies_settings	= $model->getAccessTaxonomies();
	$taxonomies_available	= $model->getTaxonomies();
	$taxonomies_available	= Access_Helper::wpcf_object_to_array( $taxonomies_available );
	$section_statuses = self::get_section_statuses();
	
	$container_class = 'is-enabled';
	$enabled = true;
	
	$access_bypass_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses the same word for singular name and plural name. Access can\'t control access to this object. Please use a different word for the singular and plural names.', 'wpcf-access') . '</p>'
		. '</div>';
    $access_conflict_template = '<div class="error">'
		. '<p>' . __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default WordPress capabilities. Access can not manage this entity, try changing its name and / or slug', 'wpcf-access') . '</p>'
		. '</div>';
    $access_notices='';
	
	$supports_check = array();
	
	foreach ( $post_types_available as $type_slug => $type_data ) {
		// filter types, excluding types that do not have different plural and singular names
		if ( 
			isset( $type_data['__accessIsNameValid'] ) 
			&& ! $type_data['__accessIsNameValid']
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}
		if ( 
			isset( $type_data['__accessIsCapValid'] ) 
			&& ! $type_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Post Type','wpcf-access'), $type_data['labels']['singular_name'] );
			unset( $post_types_available[ $type_slug ] );
			continue;
		}

		if ( isset( $post_types_settings[ $type_slug ] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_capabilities'] = $post_types_settings[ $type_slug ];
		}

		if ( ! empty( $type_data['_wpcf_access_inherits_post_cap'] ) ) {
			$post_types_available[ $type_slug ]['_wpcf_access_inherits_post_cap'] = 1;
		}
	}

	// Put Posts and Pages in front
	$native_post_types = array( 'page', 'post' );
	foreach ( $native_post_types as $npt ) {
		if ( isset( $post_types_available[ $npt ] ) ) {
			$clone = array( $npt => $post_types_available[ $npt ] );
			unset( $post_types_available[ $npt ] );
			$post_types_available = $clone + $post_types_available;
		}
	}

	foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
		// filter taxonomies, excluding tax that do not have different plural and singular names
		if (
			isset( $tax_data['__accessIsNameValid'] ) 
			&& ! $tax_data['__accessIsNameValid'] 
		) {
			$access_notices .= sprintf( $access_bypass_template, __('Taxonomy','wpcf-access'), $tax_data['labels']['singular_name'] );
			unset( $taxonomies_available[ $tax_slug ] );
			continue;
		}
		if ( 
			isset( $tax_data['__accessIsCapValid'] ) 
			&& ! $tax_data['__accessIsCapValid'] 
		) {
			$access_notices .= sprintf( $access_conflict_template, __('Taxonomy','wpcf-access'), $tax_data['labels']['singular_name'] );
			unset( $taxonomies_available[ $tax_slug ] );
			continue;
		}

		$taxonomies_available[ $tax_slug ]['supports'] = array_flip( $tax_data['object_type'] );
		if ( isset( $taxonomies_settings[ $tax_slug ] ) ) {
			$taxonomies_available[ $tax_slug ]['_wpcf_access_capabilities'] = $taxonomies_settings[ $tax_slug ];
		}

		if ( $enabled ) {
			$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ? $tax_data['_wpcf_access_capabilities']['mode'] : 'follow';
			if ( empty( $tax_data['supports'] ) ) {
				continue;
			}

			foreach ( $tax_data['supports'] as $supports_type => $true ) {
				if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'] ) ) {
					continue;
				}

				$mode = $post_types_available[ $supports_type ]['_wpcf_access_capabilities']['mode'];

				if ( ! isset( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) {
					continue;
				}

				$supports_check[ $tax_slug ][ md5( $mode . serialize( $post_types_available[ $supports_type ]['_wpcf_access_capabilities'][ $mode ] ) ) ][] = $post_types_available[ $supports_type ]['labels']['name'];
			}
		}
	}

	// Put Categories and Tags in front
	$native_taxonomies = array( 'post_tag', 'category' );
	foreach ( $native_taxonomies as $nt ){
		if ( isset( $taxonomies_available[ $nt ] ) ) {
			$clone = array( $nt => $taxonomies_available[ $nt ] );
			unset( $taxonomies_available[ $nt ] );
			$taxonomies_available = $clone + $taxonomies_available;
		}
	}
	
	$custom_data = Access_Helper::wpcf_access_tax_caps();
	$output .= '<p class="toolset-access-tab-info">
		'. __('Expand each of these taxonomies to set their access control. Select the \'Managed by Access\' checkbox to customize the access rules. ', 'wpcf-access') .'<br>
		<a href="https://toolset.com/documentation/user-guides/setting-access-control/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Taxonomies&utm_campaign=access" title="'. __('Access Control for Taxonomy ', 'wpcf-access') .'" target="_blank">'. __('Access Control for Taxonomy', 'wpcf-access') .'</a>
	</p>';
	if ( ! empty( $taxonomies_available ) ) {
		foreach ( $taxonomies_available as $tax_slug => $tax_data ) {
			$mode = 'not_managed';
			if ( $tax_data['public'] === 'hidden' ) {
				continue;
			}
			// Set data
			if ( isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ) {
				$mode = $tax_data['_wpcf_access_capabilities']['mode'];
			} elseif ( $enabled ) {
				$mode = Access_Helper::wpcf_access_get_taxonomy_mode( $tax_slug, $mode );
			} else {
				$mode = 'not_managed';
			}

			// For built-in set default to 'not_managed'
			if ( in_array( $tax_slug, $native_taxonomies ) ) {
				$mode = isset( $tax_data['_wpcf_access_capabilities']['mode'] ) ? $tax_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			}
			
			if ( isset( $tax_data['_wpcf_access_capabilities']['permissions'] ) ) {
				foreach ( $tax_data['_wpcf_access_capabilities']['permissions'] as $cap_slug => $cap_data ) {
					$custom_data[$cap_slug]['roles'] = $cap_data['roles'];
					$custom_data[$cap_slug]['users'] = isset( $cap_data['users'] ) ? $cap_data['users'] : array();
				}
			}
			
			$is_managed = ( $mode != 'not_managed' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}
			$is_section_opened = false;
			if ( isset($section_statuses[$tax_slug]) && $section_statuses[$tax_slug] == 1 ){
				$is_section_opened = true;
			}

			$output .= '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item ' . $container_class . '">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $tax_slug ) .'">'
				. $tax_data['labels']['name'] 
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed" style="display:'. ( !$is_section_opened?'block':'none') .'">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' ) : __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';

			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $tax_slug ) . '" style="display:'. ( !$is_section_opened?'none':'block') .'">';
			
			// Add warning if shared and settings are different
			$disable_same_as_parent = false;
			if (
				$enabled 
				&& isset( $supports_check[ $tax_slug ] ) 
				&& count( $supports_check[ $tax_slug ] ) > 1
			) {
				$txt = array();
				foreach ($supports_check[$tax_slug] as $sc_tax_md5 => $sc_tax_md5_data){
					$txt = array_merge($txt, $sc_tax_md5_data);
				}
				$last_element = array_pop($txt);
				$warning = '<br /><img src="' . TACCESS_ASSETS_URL . '/images/warning.png" style="position:relative;top:2px;" />' . sprintf(__('You need to manually set the access rules for taxonomy %s. That taxonomy is shared between several post types that have different access rules.'),
								$tax_data['labels']['name'],
								implode(', ', $txt), $last_element);
				$output .= $warning;
				$disable_same_as_parent = true;
			}
			
			// Managed checkbox - Custom taxonomies section
			$output .= '<p>';
			$output .= '<label><input type="checkbox" class="not-managed js-wpcf-enable-access" name="types_access[tax][' . $tax_slug . '][not_managed]" value="1"';
			if ( ! $enabled ) {
				$output .= ' disabled="disabled" readonly="readonly"';
			}
			$output .= $is_managed ? ' checked="checked"' : '';
			$output .= '/>' . __('Managed by Access', 'wpcf-access') . '</label>';
			$output .= '</p>';

			if ( $tax_slug != 'category' ){
				// 'Same as parent' checkbox
				$output .= '<p>';
				$output .= '<label><input type="checkbox" class="follow js-wpcf-follow-parent" name="types_access[tax][' . $tax_slug . '][mode]" value="follow"';
				if ( ! $enabled ) {
					$output .= ' disabled="disabled" readonly="readonly" checked="checked"';
				} else if ( $disable_same_as_parent ) {
					$output .= ' disabled="disabled" readonly="readonly"';
				} else {
					$output .= $mode == 'follow' ? ' checked="checked"' : '';
				}
				$output .= ' />' . __('Same as Category', 'wpcf-access') . '</label>';
				$output .= '</p>';
			}

			$output .= '<div class="wpcf-access-mode-custom">';
			$output .= self::wpcf_access_permissions_table(
				$roles, 
				$custom_data,
				$custom_data, 
				'tax', 
				$tax_slug, 
				$enabled,
				$is_managed, 
				$taxonomies_settings,
				array(),
				'tax'
			);
			$output .= '</div>	<!-- .wpcf-access-mode-custom -->';
			
			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= self::wpcf_access_submit_button( $enabled, $is_managed, $tax_data['labels']['name'] );
			$output .= '</p>';
			
			$output .= '</div>	<!-- wpcf-access-mode -->';
			$output .= '</div>	<!-- wpcf-access-type-item -->';
		}
	} else {
		$output .= '<p>'
			. __( 'There are no taxonomies registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_third_party( $current_tab = 'third-party' ) {
	$output = '';
	
	$model				= TAccess_Loader::get('MODEL/Access');
	$roles				= Access_Helper::wpcf_get_editable_roles();
	$settings_access	= $model->getAccessTypes();
	$third_party		= $model->getAccessThirdParty();
	$section_statuses = self::get_section_statuses();

	$enabled = true;
	$current_tab = esc_attr( $current_tab );
	
	if ( $current_tab == 'third-party' ) {
		$areas			= apply_filters( 'types-access-area', array() );
	} else {
		$areas			= apply_filters( 'types-access-area-for-' . $current_tab, array() );
	}

	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-' . $current_tab . '" data-tab="' . $current_tab . '">';
	$has_output = false;

	if ( $current_tab == 'types-fields' ){
		$output .= '<p class="toolset-access-tab-info">
			'. __('Expand each of these field groups to set their access control.', 'wpcf-access') .'<br>
			<a href="https://toolset.com/documentation/user-guides/access-control-for-user-fields/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Fields&utm_campaign=access" title="'. __('Access Control for Fields', 'wpcf-access') .'" target="_blank">'. __('Access Control for Fields', 'wpcf-access') .'</a>
		</p>';
	}
	if ( $current_tab == 'cred-forms' ){
		$output .= '<p class="toolset-access-tab-info">
			'. __('Expand each of these groups of forms (for posts and for users) to set their access control on the front-end.', 'wpcf-access') .'<br>
			<a href="https://toolset.com/documentation/user-guides/access-control-for-cred-forms/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Toolset%20Forms&utm_campaign=access" title="' . esc_attr( __('Access Control for Toolset Forms', 'wpcf-access') ) .'" target="_blank">'. __('Access Control for Toolset Forms', 'wpcf-access') .'</a>
		</p>';
	}
	foreach ( $areas as $area ) {
		// Do not allow Types IDs for post types or taxonomies
		if ( in_array( $area['id'], array( 'types', 'tax' ) ) )
			continue;

		// make all groups of same area appear on same line in shortcuts
		$groups = apply_filters( 'types-access-group', array(), $area['id'] );

		if ( 
			! is_array( $groups ) 
			|| empty( $groups ) 
		) {
			continue;
		}
		$output .= '<h3>' . $area['name'] . '</h3>';
		$has_output = true;
		
		foreach ( $groups as $group ) {
			$is_section_opened = false;
			if ( isset($section_statuses[$group['id']]) && $section_statuses[$group['id']] == 1 ){
				$is_section_opened = true;
			}

			$output .= '<div class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item">';
			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group['id'] ) .'">' . $group['name'] . '</h4>';
			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group['id'] ) . '" style="display:'. ( !$is_section_opened?'none':'block') .'">';

			$caps = array();
			$caps_filter = apply_filters( 'types-access-cap', array(), $area['id'], $group['id'] );
			$saved_data = array();
			foreach ( $caps_filter as $cap_slug => $cap ) {
				$caps[ $cap['cap_id'] ] = $cap;
				if ( isset( $cap['default_role'] ) ) {
					// @since 2.2, convert minimal role to minimal capability
					if ( $cap['default_role'] == 'guest' ){
						$cap['default_role'] = 'read';
					}
					elseif ( $cap['default_role'] == 'administrator' ){
						$cap['default_role'] = 'delete_users';
					}
					else{
						$cap['default_role'] = 'edit_posts';
					}
					$caps[ $cap['cap_id'] ]['role'] = $cap['role'] = $cap['default_role'];
				}
				$saved_data[ $cap['cap_id'] ] =
						isset( $third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ] ) ?
						$third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ] : array( 'roles' => Access_Helper::toolset_access_get_roles_by_role( '', $cap['default_role'] ) );
			}
			// Add registered via other hook
			if ( ! empty( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] ) ) {
				foreach ( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] as $cap_slug => $cap ) {
					// Don't allow duplicates
					if ( isset( $caps[ $cap['cap_id'] ] ) ) {
						unset( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap_slug ] );
						continue;
					}
					$saved_data[ $cap['cap_id'] ] = $cap['saved_data'];
					$caps[ $cap['cap_id'] ] = $cap;
				}
			}
			if ( 
				isset( $cap['style'] ) 
				&& $cap['style'] == 'dropdown'
			) {

			} else {
				$output .= self::wpcf_access_permissions_table(
					$roles, 
					$saved_data,
					$caps, 
					$area['id'], 
					$group['id'],
					true, 
					$settings_access,
					array(),
					array(),
					'third_party'
				);
			}


			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= self::wpcf_access_submit_button( $enabled, true, $group['name'] );
			$output .= '</p>';

			$output .= '</div>	<!-- .wpcf-access-mode -->';
			$output .= '</div>	<!-- .wpcf-access-type-item -->';
		}
	}
	
	if ( ! $has_output ) {
		$output .= '<p>'
			. __( 'There are no third party areas registered.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_custom_groups() {
	$output = '';
	$section_statuses = self::get_section_statuses();
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-custom-group" data-tab="custom-group">';
	
	$model = TAccess_Loader::get('MODEL/Access');
	$roles = Access_Helper::wpcf_get_editable_roles();
	$enabled = true;
	$group_output = '';
	
	$settings_access = $model->getAccessTypes();
	$show_section_header = true;
	$group_count = 0;
	if ( is_array($settings_access) && !empty($settings_access) ){
		foreach ( $settings_access as $group_slug => $group_data) {
			if ( strpos($group_slug, 'wpcf-custom-group-') !== 0 ) {
				continue;
			}
			if ( !isset($group_data['title']) ){
					$new_settings_access = $model->getAccessTypes();
					unset($new_settings_access[$group_slug]);
					$model->updateAccessTypes($new_settings_access);
					continue;
			}
			if ( $show_section_header ){
					$show_section_header = false;
			}
			$group_count++;
			$group_div_id = str_replace('%','',$group_slug);
			$group['id'] = $group_slug;
			$group['name']= $group_data['title'];
			$is_section_opened = false;
			if ( isset($section_statuses[$group_div_id]) && $section_statuses[$group_div_id] == 1 ){
				$is_section_opened = true;
			}

			$group_output .= '<div id="js-box-' . $group_div_id . '" class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
			$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group_div_id ) .'">' . $group['name'] . '</h4>';
			$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group_div_id ) . '" style="display:'. ( !$is_section_opened?'none':'block') .'">';

			$group_output .= '<div class="toolset-access-posts-group-info">
				<div class="toolset-access-posts-group-assigned-posts-list">';
			$_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
			$post_types_array = array();
		    foreach ( $_post_types  as $post_type ) {
		        $post_types_array[] = $post_type['name'];
            }
            $args = array( 'post_type' => $post_types_array, 'posts_per_page' => 0, 'meta_key' => '_wpcf_access_group', 'meta_value' => $group['id']);
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				$group_output .= '<strong>'. __('Posts in this Post Group', 'wpcf-access') .':</strong> ';
				$posts_list = '';
				$show_assigned_posts = 4;
				while ( $the_query->have_posts() && $show_assigned_posts != 0  ) {
					$the_query->the_post();
					$posts_list .= get_the_title().', ';
					$show_assigned_posts --;
				}
				$group_output .= substr($posts_list, 0, -2);
				if ( $the_query->found_posts > 4 ){
					$group_output .= sprintf( __( ' and %d more', 'wpcf-access' ), ($the_query->found_posts - 2));
				}
			}
			$group_output .= '</div>
				<div class="toolset-access-posts-group-moddify-button">
					<input data-group="' . $group_slug . '" data-groupdiv="' . $group_div_id . '" type="button" value="' . __('Modify Group', 'wpcf-access') . '"  class="js-wpcf-modify-group button-secondary" />
				</div>
			</div>';

			$caps = array();
			$saved_data = array();

			// Add registered via other hook
			if ( !empty($group_data['permissions']) ) {
				  $saved_data['read'] = $group_data['permissions']['read'];
			}

			$def = array(
				'read' => array(
					'title' => __('Read', 'wpcf-access'),
					'role' => 'read',
					'predefined' => 'read',
					'cap_id' => 'group')
				);

			$group_output .= self::wpcf_access_permissions_table(
					$roles, $saved_data,
					$def, 'types', $group['id'],
					$enabled, 'permissions',
					$settings_access );

			$group_output .= '<p class="wpcf-access-buttons-wrap">';
			$group_output .= '<span class="ajax-loading spinner"></span>';
			$group_output .= self::wpcf_access_submit_button($enabled, true, $group['name']);
			$group_output .= '</p>';
			$group_output .= '<input type="hidden" name="groupvalue-' . $group_slug . '" value="' . $group_data['title'] .'">';
			$group_output .= '<div class="toolset-access-post-group-remove-group">
				<a href="#" data-group="' . $group_slug . '" data-target="custom-group" data-section="'. base64_encode('custom-group') .'" data-groupdiv="' . $group_div_id . '"  class="js-wpcf-remove-group"><i class="fa fa-trash"></i> ' . __('Remove Group', 'wpcf-access') . '</a></div>';
			$group_output .= '</div>	<!-- .wpcf-access-mode  -->';

			$group_output .= '</div>	<!-- .wpcf-access-custom-group -->';

		}
	}
	$output .= '<p class="toolset-access-tab-info">
		'. __('Create \'post groups\', which hold together content that will share the same front-end access control. Then, set the access control for each group', 'wpcf-access') .'<br>
		<a href="https://toolset.com/documentation/user-guides/limiting-read-access-specific-content/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Post%20groups&utm_campaign=access" title="'. __('Limiting read access to specific content', 'wpcf-access') .'" target="_blank">'. __('Limiting read access to specific content', 'wpcf-access') .'</a>
	</p>';
	if ( $group_count > 0 ){
		$output .= '<p class="toolset-access-align-right">'
			. '<button data-label="' . esc_attr( __('Add Group','wpcf-access') ) . '" value="' . esc_attr( __('Add Post Group', 'wpcf-access') ) .'" class="button button-large button-secondary wpcf-add-new-access-group js-wpcf-add-new-access-group">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. esc_html( __('Add Post Group', 'wpcf-access') )
			. '</button>'
			. '</p>';
		$output .= $group_output;
	} else {
		  $output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups"><p>'. __('No Post Groups found.', 'wpcf-access') .'</p><p>'
			.'<a href="" data-label="'.__('Add Group','wpcf-access').'"
			class="button button-secondary js-wpcf-add-new-access-group">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. __('Add your first Post Group', 'wpcf-access') .'</a></p></div>';
	}
	
	$output .= '</div>';
	
	return $output;
}

public static function otg_access_get_permission_table_for_wpml() {
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-wpml-group" data-tab="wpml-group">';

	$model = TAccess_Loader::get('MODEL/Access');
	if ( apply_filters( 'otg_access_filter_is_wpml_installed', false ) ) {
		$section_statuses = self::get_section_statuses();
		$group_count = 0;
		$group_output = '<p class="toolset-access-tab-info">
		'. __('Create \'permission per language\', where you select the post types and languages that you want to control. Then, choose who can access it.', 'wpcf-access') .'<br>
		<a href="https://wpml.org/documentation/translating-your-contents/how-to-use-access-plugin-to-create-editors-for-specific-language/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20WPML%20groups&utm_campaign=access" title="'. __('Creating Editors for Specific Language', 'wpcf-access') .'" target="_blank">'. __('Creating Editors for Specific Language', 'wpcf-access') .'</a>
		</p>';
		$group_output .= '<p class="toolset-access-align-right">
				<button " style="background-image: url('.ICL_PLUGIN_URL . '/res/img/icon.png'.')" class="button button-large button-secondary wpcf-add-new-access-group wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
			. __('Create permission for languages', 'wpcf-access') .'</button></p>';
		//WPML groups
		$settings_access = $model->getAccessTypes();
		$roles = Access_Helper::wpcf_get_editable_roles();
		$show_section_header = true;
		$enabled = true;
		if ( 
			is_array( $settings_access ) 
			&& ! empty( $settings_access )
		) {
			$_post_types = Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
			foreach ( $settings_access as $group_slug => $group_data) {
				if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
					continue;
				}
				if ( ! isset( $_post_types[ $group_data['post_type'] ] ) ) {
					continue;
				}

				if ( ! apply_filters( 'wpml_is_translated_post_type', null, $group_data['post_type'] ) ) {
					self::otg_access_remove_wrong_wpml_group( $group_slug );
					continue;
				}

				if ( $show_section_header ) {
					$show_section_header = false;
				}
				$group_count++;
				$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );

				$languages = array();
				if ( isset( $group_data['languages'] ) ) {
					foreach( $group_data['languages'] as $lang => $lang_data ) {
						if ( isset( $wpml_active_languages[ $lang ] ) ) {
							$languages[] = $wpml_active_languages[ $lang ]['native_name'];
						} else {
							$group_data['title'] = self::otg_access_rename_wpml_group( $group_slug );
						}
					}
				}

				if ( count( $languages ) == 0 ) {
					//self::otg_access_remove_wrong_wpml_group( $group_slug );
					//continue;
				}

				$group_div_id = str_replace('%','',$group_slug);
				$group['id'] = $group_slug;
				$group['name']= $group_data['title'];
				$is_section_opened = false;
				if ( isset($section_statuses[$group_div_id]) && $section_statuses[$group_div_id] == 1 ){
					$is_section_opened = true;
				}
				$disabled_message = '';
				$is_group_active = true;
				if ( ! isset( $settings_access[ $group_data['post_type'] ] ) || $settings_access[ $group_data['post_type'] ]['mode'] == 'not_managed' ){
                    $is_group_active = false;
                    $disabled_message = ' (' . sprintf( __( 'This WPML Group is inactive because "%s" post type is not managed by Access', 'wpcf-access' ), $_post_types[ $group_data['post_type'] ]['label'] ) . ')';
                    $is_section_opened = false;
                }


				$group_output .= '<div id="js-box-' . $group_div_id . '" class="' . ( ! $is_group_active ? 'otg-access-settings-section-item-not-managed ' : '' ) . 'otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
				$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="' . esc_attr( $group_div_id ) . '">' . $group['name'] . $disabled_message . '</h4>';
				$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-' . esc_attr( $group_div_id ) . '" style="display:'. ( !$is_section_opened?'none':'block') .'">';

				$group_output .= '<div class="toolset-access-posts-group-info">					
					<div class="toolset-access-posts-group-moddify-button">
						<input data-group="' . $group_slug . '" data-groupdiv="' . $group_div_id . '" type="button" value="' . __('Modify WPML Group', 'wpcf-access') . '"  class="js-wpcf-add-new-wpml-group button-secondary" />
					</div>
				</div>';
				$caps = array();
				$saved_data = array();

				if ( ! empty( $group_data['permissions'] ) ) {
					  $saved_data = array(
						'read'			=> $group_data['permissions']['read'],
						'edit_own'		=> $group_data['permissions']['edit_own'],
						'delete_own'	=> $group_data['permissions']['delete_own'],
						'edit_any'		=> $group_data['permissions']['edit_any'],
						'delete_any'	=> $group_data['permissions']['delete_any'],
						'publish'	=> $group_data['permissions']['publish'],
					 );
				}

				$def = array(
					'read' => array(
						'title'			=> __('Read', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'read',
						'cap_id'		=> 'group'
					),
					'edit_own' => array(
						'title'			=> __('Edit and translate own', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'edit_own',
						'cap_id'		=> 'group'
					),
					'delete_own' => array(
						'title'			=> __('Delete own', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'delete_own',
						'cap_id'		=> 'group'
					),
					'edit_any' => array(
						'title'			=> __('Edit and translate any', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'edit_any',
						'cap_id'		=> 'group'
					),
					'delete_any' => array(
						'title'			=> __('Delete any', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'delete_any',
						'cap_id'		=> 'group'
					),
					'publish' => array(
						'title'			=> __('Publish', 'wpcf-access'),
						'role'			=> 'edit_posts',
						'predefined'	=> 'publish',
						'cap_id'		=> 'group'
					)
				);

				$group_output .= self::wpcf_access_permissions_table(
					$roles,
					$saved_data,
					$def,
					'types',
					$group['id'],
					$enabled,
					'permissions',
					$settings_access
				);

				$group_output .= '<p class="wpcf-access-buttons-wrap">';
				$group_output .= '<span class="ajax-loading spinner"></span>';
				$group_output .= self::wpcf_access_submit_button($enabled, true, '');
				$group_output .= '</p>';
				$group_output .= '<div class="toolset-access-post-group-remove-group">
				<a href="#" data-group="' . $group_slug . '" data-target="wpml-group" data-section="'. base64_encode('wpml-group') .'" data-groupdiv="' . $group_div_id . '"  class="js-wpcf-remove-group"><i class="fa fa-trash"></i> ' . __('Remove Group', 'wpcf-access') . '</a></div>';
				$group_output .= '</div>	<!-- .wpcf-access-mode  -->';
				$group_output .= '</div>	<!-- .wpcf-access-wpml-group -->';
			}
		}
		if ( $group_count > 0 ) {
			$output .= $group_output;
		} else {
			  $output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups"><p class="toolset-access-tab-info">
				'. __('Create \'permission per language\', where you select the post types and languages that you want to control. Then, choose who can access it.', 'wpcf-access') .'<br>
				<a href="https://wpml.org/documentation/translating-your-contents/how-to-use-access-plugin-to-create-editors-for-specific-language/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20WPML%20group&utm_campaign=access" title="'. __('Creating Editors for Specific Language', 'wpcf-access') .'" target="_blank">'. __('Creating Editors for Specific Language', 'wpcf-access') .'</a></p>
				<p>'. __('No permission for languages found.', 'wpcf-access')
			.'</p><p><a href="#" data-label="'.__('Add Group','wpcf-access').'"
			class="button button-secondary js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
			. '<i class="icon-plus fa fa-plus"></i>'
			. __('Create your first permission for languages', 'wpcf-access') .'</a></p></div>';
		}

	} else {
		$output .= '<p>'
			. __( 'WPML is not installed.', 'wpcf-access' )
			. '</p>';
	}
	
	$output .= '</div>';
	
	return $output;
}

/*
 * Generate Custom roles tab content
 */
public static function otg_access_get_permission_table_for_custom_roles() {
	$roles = Access_Helper::wpcf_get_editable_roles();
	$output = '';
	
	$output .= '<div class="js-otg-access-settings-tab-section js-otg-access-settings-section-for-custom-roles" data-tab="custom-roles">';
	
    $output .= self::wpcf_access_admin_set_custom_roles_level_form( $roles );
    $output .= wp_nonce_field('wpcf-access-edit', '_wpnonce', true, false);

	$output .= '</div>';
	
	return $output;
}


/*
 * Rename WPML group when one of languages was deactivated
 * @paran $group_slug
 */
public static function otg_access_rename_wpml_group( $group_slug ){
    $model = TAccess_Loader::get('MODEL/Access');
    $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
    $languages = array();
    $title_languages_array = array();
    $wpml_active_languages = apply_filters( 'wpml_active_languages', '', array('skip_missing' => 0) );
    $settings_access = $model->getAccessTypes();


    if ( isset($settings_access[$group_slug]['languages']) ) {
        //for ($i=0, $count_lang = count($settings_access[$group_slug]['languages']); $i<$count_lang; $i++){
        foreach( $settings_access[$group_slug]['languages'] as $lang_name => $lang_status){
            if ( isset($wpml_active_languages[$lang_name]) ){
                $languages[$lang_name] = 1;
                $title_languages_array[] = $wpml_active_languages[$lang_name]['translated_name'];
            }else{
                unset($settings_access[$group_slug]['languages'][$lang_name]);
            }
        }
    }
    if(count($title_languages_array)>1)
    {
        $title_languages = implode(', ' , array_slice($title_languages_array,0,count($title_languages_array)-1)) . ' and ' . end($title_languages_array);
    }
    else
    {
            $title_languages = implode(', ' , $title_languages_array);
    }
    $group_name = $title_languages .' '. $_post_types[$settings_access[$group_slug]['post_type']]['labels']['name'];
    $settings_access[$group_slug]['title'] = $group_name;
    $model->updateAccessTypes( $settings_access );

    return $group_name;
}

/*
 * Remove WPML group
 * @param $group_slug
 */
public static function otg_access_remove_wrong_wpml_group( $group_slug ){
    $model = TAccess_Loader::get('MODEL/Access');
	$settings_access = $model->getAccessTypes();

	if ( isset($settings_access[$group_slug]) ) {
			unset($settings_access[$group_slug]);
    }
    if ( isset($settings_access['_custom_read_errors'][$group_slug]) ) {
		unset($settings_access['_custom_read_errors'][$group_slug]);
    }
    if ( isset($settings_access['_custom_read_errors_value'][$group_slug]) ) {
		unset($settings_access['_custom_read_errors_value'][$group_slug]);
    }

    $model->updateAccessTypes( $settings_access );
}
/**
 * Renders dropdown with editable roles.
 *
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_roles_dropdown( $roles, $name, $data = array(), $dummy = false, $enabled = true, $exclude = array() ) {
    $default_roles = Access_Helper::wpcf_get_default_roles();
    $output = '';
    $output .= '<select name="' . $name . '"';
    $output .= isset($data['predefined']) ? 'class="js-wpcf-reassign-role wpcf-access-predefied-' . $data['predefined'] . '">' : '>';

	if ($dummy) {
        $output .= "\n\t<option";
        if (empty($data)) {
            $output .= ' selected="selected" disabled="disabled"';
        }
        $output .= ' value="0">' . $dummy . '</option>';
    }
    foreach ($roles as $role => $details)
    {
        if (in_array($role, $exclude)) {
            continue;
        }
        if (in_array($role, $default_roles))
            $title = translate_user_role($details['name']);
        else
            $title = taccess_t($details['name'], $details['name']);

        $output .= "\n\t<option";
        if (isset($data['role']) && $data['role'] == $role) {
            $output .= ' selected="selected"';
        }
        if (!$enabled) {
            $output .= ' disabled="disabled"';
        }
        $output .= ' value="' . esc_attr($role) . "\">$title</option>";
    }

    // For now, let's add Guest only for read-only
    if (isset($data['predefined']) && $data['predefined'] == 'read-only')
    {
        $output .= "\n\t<option";
        if (isset($data['role']) && $data['role'] == 'guest') {
            $output .= ' selected="selected"';
        }
        if (!$enabled) {
            $output .= ' disabled="disabled"';
        }
        $output .= ' value="guest">' . __('Guest', 'wpcf-access') . '</option>';
    }
    $output .= '</select>';
    return $output;
}

/**
 * Auto-suggest users search.
 *
 * @param type $data
 * @param type $name
 * @return string
 */
public static function wpcf_access_admin_users_form( $data, $name, $enabled = true, $managed = true ) {
    $output = ''; 
    $output .= self::wpcf_access_suggest_user($enabled, $managed, $name);
    $output .= '<div class="wpcf-access-user-list">';
		if ( $enabled && isset($data['users']) && is_array($data['users']) )
		{
			foreach ($data['users'] as $user_id)
			{
				$user = get_userdata($user_id);
				if ( !empty($user) )
				{
					$output .= '
							<div class="wpcf-access-remove-user-wrapper">
								<a href="javascript:;" class="wpcf-access-remove-user"></a>
								<input type="hidden"
										name="' . $name . '[users][]"
										value="' . $user -> ID . '" />'
								. $user->display_name . ' (' . $user->user_login . ')
							</div>';
				}
			}
		}
    $output .= '</div>	<!-- .wpcf-access-user-list -->';
    return $output;
}

/**
 * Renders pre-defined table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_predefined($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= '<table class="wpcf-access-predefined-table">';
    foreach ($data as $mode => $mode_data)
    {
        if (!isset($mode_data['title']) || !isset($mode_data['role']))
            continue;

        $output .= '<tr><td >' . $mode_data['title'] . '</td><td>';
        $output .= '<input
						type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $mode . '"
						value="' . $name . '[' . $mode . ']" />';
        $output .= self::wpcf_access_admin_roles_dropdown($roles, $name . '[' . $mode . '][role]', $mode_data, false, $enabled);
        $output .= '</td><td>';
        $output .= self::wpcf_access_admin_users_form($mode_data, $name . '[' . $mode . ']', $enabled);
        $output .= '</td></tr>';
    }
    $output .= '</table>	<!-- .wpcf-access-predefined-table -->';
    return $output;
}

/**
 * Renders custom caps types table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_edit_access_types_item($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= __('Set all capabilities to users of type:') . ''
            . self::wpcf_access_admin_roles_dropdown($roles,
                    'wpcf_access_bulk_set[' . $type_slug . ']', array(),
                    '-- ' . __('Choose user type', 'wpcf-access') . ' --', $enabled);
    $output .= '<table class="wpcf-access-caps-wrapper">';
    foreach ($data as $cap_slug => $cap_data)
    {
        $output .= '<tr>
						<td style="text-align:right;">';
        $output .= $cap_data['title'] . '<td/>
						<td>';
        $output .= self::wpcf_access_admin_roles_dropdown($roles,
                $name . '[' . $cap_slug . '][role]',
				$cap_data, false, $enabled);
        $output .= '<input
						type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $cap_slug . '"
						data-wpcfaccesscap="' . $cap_slug . '"
						data-wpcfaccessname="' . $name . '[' . $cap_slug . ']"
						value="' . $name . '[' . $cap_slug . ']" />';
        $output .= '</td>
					<td>';
        $output .= self::wpcf_access_admin_users_form(
				$cap_data,
                $name . '[' . $cap_slug . ']',
				$enabled);
        $output .= '</td>
				</tr>';
    }
    $output .= '</td></tr></table>';
    return $output;
}

/**
 * Renders custom caps tax table.
 *
 * @param type $type_slug
 * @param type $roles
 * @param type $name
 * @param type $data
 * @return string
 */
public static function wpcf_access_admin_edit_access_tax_item($type_slug, $roles, $name, $data, $enabled = true) {
    $output = '';
    $output .= '<table class="wpcf-access-caps-wrapper">';
    foreach ($data as $cap_slug => $cap_data)
    {
        $output .= '<tr><td>';
        $output .= $cap_data['title'] . '<td/><td>';
        $output .= self::wpcf_access_admin_roles_dropdown($roles,
                $name . '[' . $cap_slug . '][role]', $cap_data, false, $enabled);
        $output .= '<input type="hidden"
						class="wpcf-access-name-holder"
						name="wpcf_access_' . $type_slug . '_' . $cap_slug . '"
						value="' . $name . '[' . $cap_slug . ']" />';
        $output .= '</td><td>';
        $output .= self::wpcf_access_admin_users_form($cap_data, $name . '[' . $cap_slug . ']', $enabled);
        $output .= '</td></tr>';
    }
    $output .= '</table>';
    return $output;
}

/**
 * Submit button.
 *
 * @param type $enabled
 * @param type $managed
 * @return type
 */
public static function wpcf_access_submit_button( $enabled = true, $managed = true, $id = '' ) {
	ob_start();
	?>
	<button class="wpcf-access-submit-section otg-access-settings-section-save button-primary js-wpcf-access-submit-section js-otg-access-settings-section-save">
	<?php echo esc_html( __( 'Save ', 'wpcf-access' ) ); ?>
	</button>
	<?php
    return ob_get_clean();

}

/**
 * Custom roles form.
 *
 * @param type $roles
 * @return string
 */
public static function wpcf_access_admin_set_custom_roles_level_form($roles, $enabled = true)
{
    $output = '';

    $advanced_mode = get_option('otg_access_advaced_mode', 'false');
    if ( $advanced_mode != 'true' ){
        $advanced_mode_text = __('Enable advanced mode', 'wpcf-access');
    }else{
        $advanced_mode_text = __('Disable advanced mode', 'wpcf-access');
    }
       
    $output .= '<div id="wpcf-access-custom-roles-wrapper">';
	$output .= '<p class="toolset-access-tab-info">
		'. __('Create custom user roles. Then, grant privileges for them.', 'wpcf-access') .'<br>
		<a href="https://toolset.com/documentation/user-guides/managing-wordpress-admin-capabilities-access/?utm_source=access&utm_medium=admin-tabs&utm_term=Access%20Control%20for%20Custom%20roles&utm_campaign=access" title="'. __('Managing WordPress Admin Capabilities', 'wpcf-access') .'" target="_blank">'. __('Managing WordPress Admin Capabilities', 'wpcf-access') .'</a>
	</p>';
	$output .= '<p class="toolset-access-align-right">
			<button class="button button-large button-secondary js-otg-access-add-new-role otg-access-add-new-role"><i class="icon-plus fa fa-plus"></i>' .
		 __('Add a new role', 'wpcf-access') . '</button></p>';

    $output .= '<div id="wpcf-access-custom-roles-table-wrapper">';
    $output .= '<table class="wpcf-access-custom-roles-table wp-list-table widefat fixed striped">
				<thead>
					<tr><th class="manage-column column-title column-primary">'. __('Role', 'wpcf-access') .'</th></tr>
				</thead>
				<tbody>';
	$ordered_roles = self::toolset_access_order_wp_roles();


	//TODO: monitor this, check if this part do not overload big database
	$users_count = count_users();
	$default_roles = array( 'administrator' => 1, 'editor' => 1, 'author' => 1, 'contributor' => 1, 'subscriber' => 1 );
	foreach ( $ordered_roles as $role => $role_info ){
		if ( $role == 'guest') continue;

		$output .= '<tr>';

		$role_link_class = 'wpcf-access-view-caps';
		if ( (isset($role_info['capabilities']['wpcf_access_role']) || $advanced_mode == 'true') && !isset($default_roles[$role])  ) {
			$role_link_class = 'wpcf-access-change-caps';
		}

		$output .= '<td class="title column-title has-row-actions column-primary page-title">
						<div class="wpcf-access-roles wpcf-access-roles-custom">
						     <span><a href="#" class="'. $role_link_class .'" data-role="' . sanitize_title($role) . '">' .
						     taccess_t($role_info['name'], $role_info['name']) . '</a>' .
						     (isset($users_count['avail_roles'][$role])? ' ('.$users_count['avail_roles'][$role].')':' (0)')
						     . '</span>
					 	</div>
					 	<div class="row-actions"><span class="edit">';

		if ( (isset($role_info['capabilities']['wpcf_access_role']) || $advanced_mode == 'true') && !isset($default_roles[$role]) ) {
			//Change Caps link
			$output .= ' <span><a href="#" class="wpcf-access-change-caps" data-role="' . sanitize_title($role) . '">' . __('Change permissions', 'wpcf-access') . '</a></span> ';
			$output .= ' <span> | <a href="#" data-role="' . sanitize_title($role) . '" class="wpcf-access-delete-role js-wpcf-access-delete-role">' .
			__('Delete role', 'wpcf-access') . '</a></span> ';
		} elseif ( $advanced_mode == 'true' && isset( $default_roles[ $role ] ) ) {
			$output .= ' <span><a href="#" class="wpcf-access-change-caps" data-role="' . esc_attr( $role ) . '">'
			           . esc_html( __( 'Change permissions', 'wpcf-access' ) ) . '</a></span> ';
		} else {
			$output .= ' <span><a href="#" class="wpcf-access-view-caps" data-role="' . sanitize_title($role) . '">' . __('View permissions', 'wpcf-access') . '</a></span> ';
		}
		$output .= ' <span> | <a href="users.php?role='. $role .'">' . __('View users', 'wpcf-access') . '</a></span> ';
		$output .= '</div></td></tr>';
	}
	
    $output .= '</tbody>';
    $output .= '<tfoot>
					<tr class="manage-column column-title column-primary sortable desc"><td>'. __('Role', 'wpcf-access') .'</td></tr>
				</tfoot></table>';
    $output .= '</div>';
    $output .= '<p>'. __('Advanced mode', 'wpcf-access') .': <button data-status="'. ( $advanced_mode == 'true' ? 'true':'false') .'" value="'. $advanced_mode_text .
    '" class="button button-large button-secondary js-otg_access_enable_advanced_mode"><i class="fa icon-'. ( $advanced_mode != 'true' ? 'lock fa-lock':'unlock fa-unlock') .'"></i>'. $advanced_mode_text .'</button></p>';
    $output .= '</div>';
    $output .= '<div id="wpcf-access-new-role" class="wpcf-access-new-role-wrap js-otg-access-new-role-wrap">
		<table class="otg-access-new-role-extra js-otg-access-new-role-extra"  style="display:none">';
			$output .= '<tr>
						<td width="50%"><label for="otg-access-new-role-name">'. __( 'Role name (at least 5 characters)', 'wpcf-access' ) .'</label></td>
						<td><input type="text" name="types_access[new_role]" class="js-otg-access-new-role-name" id="otg-access-new-role-name" value="" /></td>
						</tr>
						<tr>
						<td><label for="toolset-access-copy-caps-from">'. __( 'Copy privileges from', 'wpcf-access' ) .':</label></td>
						<td>
							<select id="toolset-access-copy-caps-from" class="js-toolset-access-copy-caps-from toolset-access-copy-caps-from">
							<option value="">' . __('None', 'wpcf-access') . '</options>';
							$ordered_roles = self::toolset_access_order_wp_roles();
							foreach ( $ordered_roles as $role => $role_info ){
								$output .= '<option value="' . $role . '">' . ( isset( $role_info['name'] ) ? $role_info['name'] : ucwords( $role ) ) . '</option>';
							}
							$output .= '</select>
						</td>
		</table>
		<div class="ajax-response js-otg-access-message-container"></div>
   		</div>	<!-- #wpcf-access-new-role -->';
    return $output;
}

/**
 * Get and cache list of available content templates
 * @return bool|mixed
 * @since 2.2.4
 * @todo check whether we should restrct to, mmm, published CTs...
 */
public static function toolset_access_get_content_template_list ( ){
	global $wpdb;
	$cached_content_template_list = Access_Cacher::get( 'content_templates_available' );

	if ( false === $cached_content_template_list ) {
		$available_content_template_list = $wpdb->get_results( "SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'view-template' AND post_status = 'publish'" );
		$cached_content_template_list = array();
		foreach ( $available_content_template_list as $template_to_cache ) {
			$cached_content_template_list[ $template_to_cache->ID ] = array(
				'post_title' => $template_to_cache->post_title,
				'post_name' => $template_to_cache->post_name
			);
		}
		Access_Cacher::set( 'content_templates_available', $cached_content_template_list);
	}
	return $cached_content_template_list;
}

/**
 * Get Content Template title
 * @param $id
 */
public static function toolset_access_get_content_template_name( $id ) {
	$cached_content_template_list = self::toolset_access_get_content_template_list( );
	if ( isset( $cached_content_template_list[ $id ] ) ) {
		return $cached_content_template_list[ $id ]['post_title'];
	}
	return '';
}

/**
 * @param $check
 * @param string $type
 *
 * @return string
 */
public static function toolset_access_get_content_template_slug( $check, $type = '' ) {
	$cached_content_template_list = self::toolset_access_get_content_template_list( );	
	if ( $type == 1 ){
		foreach ( $cached_content_template_list as $ct_key => $ct_data ){
            if ( $ct_data['post_name'] == $check ){
                return $ct_key;
            }
        }
		return '';
	}
	if ( isset( $cached_content_template_list[ $check ] ) ) {
		return $cached_content_template_list[ $check ]['post_name'];
	}
	return '';
}

/**
 * @param $check
 * @param string $type
 *
 * @return string
 */
public static function toolset_access_get_views_archive_slug( $check, $type = '' ) {

	$cached_views_archives_list = Access_Cacher::get( 'views_archives_available' );
	if ( false === $cached_views_archives_list ) {
		$wpv_args = array(
		    'post_type' => 'view',
		    'posts_per_page' => -1,
		    'order' => 'ASC',
		    'orderby' => 'title',
		    'post_status' => 'publish'
		);
		$wpv_query = new WP_Query( $wpv_args );
		$wpv_count_posts = $wpv_query->post_count;
		$caching_views_archives_list = array();

		if ( $wpv_count_posts > 0 ) {
			while ( $wpv_query->have_posts() ) :
				$wpv_query->the_post();
				$post_id = get_the_id();
				$post = get_post( $post_id );
				$caching_views_archives_list[ $post->ID ] = $post->post_name;
			endwhile;
            Access_Cacher::set( 'views_archives_available', $caching_views_archives_list );
			if ( $type == 1 ){
				foreach ( $caching_views_archives_list as $archive_key => $archive_data ){
                    if ( $archive_data == $check ){
                        return $archive_key;
                    }
                }
                return '';
			}
			if ( isset( $caching_views_archives_list[ $check ] ) ) {
				return $caching_views_archives_list[ $check ];
			}
		}
		else{
			return '';
		}
	} else {
		if ( $type == 1 ){
			foreach ( $cached_views_archives_list as $archive_key => $archive_data ){
                if ( $archive_data == $check ){
                    return $archive_key;
                }
            }
		    return '';
		}
		if ( isset( $cached_views_archives_list[ $check ] ) ) {
			return $cached_views_archives_list[ $check ];
		}
	}
	return '';
}

/**
 * Get and cache list of Layouts
 * @return array|bool
 */
public static function toolset_access_get_layouts_list ( ){
	if ( ! class_exists('WPDD_Layouts') ) {
		return array();
	}
	$cached_layouts_list = Access_Cacher::get( 'layouts_available' );
	if ( false === $cached_layouts_list ) {
		$layouts_settings = WPDD_Utils::get_all_published_settings_as_array();

		$cached_layouts_list = array();

		for ( $i = 0, $total_layouts = count( $layouts_settings ); $i < $total_layouts; $i ++ ) {
			$layout = $layouts_settings[ $i ];
			if ( isset($layout->has_child) && $layout->has_child === true ) {
				continue;
			}
			$cached_layouts_list[ $layout->id ] = array( 'post_name' => $layout->slug, 'post_title' => $layout->name );
		}
		Access_Cacher::set( 'layouts_available', $cached_layouts_list );
	}
	return $cached_layouts_list;
}

/**
 * @param $check|string
 * @param $type|int
 * @return mixed|string
 */
public static function toolset_access_get_layout_slug( $check, $type = '' ) {
	if ( ! class_exists('WPDD_Layouts') ) {
		return;
	}
	$cached_layouts_list = self::toolset_access_get_layouts_list();

	if ( $type == 1 ){
        foreach ( $cached_layouts_list as $layout_key => $layout_data ){
            if ( $layout_data['post_name'] == $check ){
                return $layout_key;
            }
        }
		return '';
	}
	if ( isset( $cached_layouts_list[ $check ] ) ) {
		return $cached_layouts_list[ $check ]['post_name'];
	}

	return '';
}


/**
 * @param $id|int
 *
 * @return string
 */
public static function toolset_access_get_layout_name( $id ){
	$cached_layouts_list = self::toolset_access_get_layouts_list();

	if ( isset( $cached_layouts_list[ $id ] ) ) {
		return $cached_layouts_list[ $id ]['post_title'];
	}

	return '';
}

/**
 * Get Content Template title
 * @param $id
 *
 */
public static function toolset_access_get_view_name( $id ) {
	$view = get_post($id);
	if ( is_object($view) ){
		return $view->post_title;
	}
}

public static function toolset_access_check_for_cap( $cap, $role ){
	$output = false;
	if ( isset($role['capabilities'][$cap]) ){
		$output = true;
	}
	return $output;
}

/**
 * HTML formatted permissions table.
 *
 * @param type $roles
 * @param type $permissions
 * @param type $name
 * @return string
 */
public static function wpcf_access_permissions_table($roles, $permissions, $settings,
        $group_id, $id, $enabled = true, $managed = true, $custom_errors = array(), $type_data = array(), $area = 'types' ) {
	$output = '';
	global $wpcf_access;

	$ordered_roles = self::toolset_access_order_wp_roles();
	$ordered_roles['guest'] = array( 'name' => __('Guest', 'wpcf-access'), 'permissions_group' => 6, 'capabilities' => array( 'read' => 1) );

	$settings = array_reverse($settings);
	$default_roles = Access_Helper::wpcf_get_default_roles();

	$output .= '<table class="wpcf-access-table js-access-table  widefat fixed striped">';

	// Table Header
	$output .= '<tr>';
		$role_column_class = '';
		if ( count($settings) <= 2 ){
			$role_column_class = ' class="toolset-access-roles-column-fixed-width"';
		}
		$output .= '<th'.$role_column_class.'>&nbsp;</th>';
		foreach ($settings as $permission_slug => $data){
			if ( isset($custom_errors['_custom_read_errors'][$id]) && $permission_slug == 'read' ) {
				$current_custom_errors = $custom_errors['_custom_read_errors'][$id]['permissions']['read'];
				$current_custom_errors_value = ( isset( $custom_errors['_custom_read_errors_value'][$id]['permissions']['read'] ) ? $custom_errors['_custom_read_errors_value'][$id]['permissions']['read'] : '' );
			}
			if ( isset($custom_errors['_archive_custom_read_errors'][$id]) && $permission_slug == 'read' ) {
				$current_archive_custom_errors = $custom_errors['_archive_custom_read_errors'][$id]['permissions']['read'];
				$current_archive_custom_errors_value = isset( $custom_errors['_archive_custom_read_errors_value'][$id]['permissions']['read'] ) ? $custom_errors['_archive_custom_read_errors_value'][$id]['permissions']['read'] : '';
			}
			$title = $data['title'];

			if ( $group_id == 'types' && $id != 'attachment' && $permission_slug == 'read') {

				$link_title = '';
				$error_value_value = $error_type_value = $archive_error_value_value = $archive_error_type_value = $text = $archive_text =  '';
				if ( isset($current_custom_errors['everyone']) && !empty($current_custom_errors['everyone']) ) {
					$error_type_value = $current_custom_errors['everyone'];
					$error_value_value = ( isset( $current_custom_errors_value['everyone'] ) ? $current_custom_errors_value['everyone'] : '' );
					if ( $error_type_value == 'error_404' ) {
						$text = '404';
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$text = __( 'Template', 'wpcf-access' ) . ': ' . self::toolset_access_get_content_template_name( $error_value_value );
						$link_title = __( 'Show Content Template', 'wpcf-access' ) . ' - ' . self::toolset_access_get_content_template_name( $error_value_value );
					} elseif ( $error_type_value == 'error_layouts' ) {
						if ( class_exists('WPDD_Layouts') ) {
							$text = __( 'Template Layout: ', 'wpcf-access' ) . ': ' . self::toolset_access_get_layout_name( $error_value_value );
							$link_title = __( 'Show Template Layout', 'wpcf-access' ) . ' - ' . self::toolset_access_get_layout_name( $error_value_value );
						} else {
							$text = $link_title = '';
						}
					} else {
						$text = __('PHP Template', 'wpcf-access').': '. $error_value_value;
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}

				//Set Archive Errors
				if ( isset($current_archive_custom_errors['everyone']) && !empty($current_archive_custom_errors['everyone']) && isset($type_data['has_archive']) && $type_data['has_archive'] == 1  ) {
					$archive_error_type_value = $current_archive_custom_errors['everyone'];
					$archive_error_value_value = ( isset( $current_archive_custom_errors_value['everyone'] ) ? $current_archive_custom_errors_value['everyone'] : '' );
					if ( $archive_error_type_value == 'default_error' ) {
						$archive_text = __('Display: \'No posts found\'','wpcf-access');
					} elseif ( $archive_error_type_value == 'error_ct' ) {
						$archive_text = __('View Archive', 'wpcf-access').': '. self::toolset_access_get_view_name( $archive_error_value_value );
					} elseif ( $archive_error_type_value == 'error_layouts' ) {
						if ( class_exists('WPDD_Layouts') ) {
							$archive_text = __( 'Layout Template Archive', 'wpcf-access' ) . ': ' . self::toolset_access_get_layout_name( $archive_error_value_value );
						} else {
							$archive_text = '';
						}
					} elseif ( $archive_error_type_value == 'error_php' ) {
						$archive_text = __('PHP Archive', 'wpcf-access').': '. preg_replace("/.*(\/.*\/)/","$1",$archive_error_value_value);
					}else{
						$archive_text = '';
					}

				}


				$is_archive = '';
				$archive_vars = '';

				if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
					$is_archive = 1;
				}
				$link_title = ' title="'. sprintf( __('Choose what to display to people who don’t have read permission for %s','wpcf-access'), $id) .'" ';
				$error_type = 'types_access_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$error_value = 'types_access_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$archive_error_type = 'types_access_archive_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$archive_error_value = 'types_access_archive_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][everyone]';
				$custom_error_json_array = wp_json_encode(
					array(
						'typename' => $error_type,
						'role' => '',
						'valuename' => $error_value,
						'curtype' => $error_type_value,
						'curvalue' => $error_value_value,
						'archivetypename' => $archive_error_type,
						'archivevaluename' => $archive_error_value,
						'archivecurtype' => $archive_error_type_value,
						'archivecurvalue' => $archive_error_value_value,
						'posttype' => $id,
						'archive' => $is_archive,
						'forall' => 1
					)
				);
				$addon = ' <a ' . $link_title . 'class="wpcf-add-error-page js-wpcf-add-error-page"'
				         . ' data-custom_error="' . esc_attr( $custom_error_json_array ) . '" href="">'
				         . '<i class="icon-edit fa fa-pencil-square-o"></i></a>';
						//Labels
						$addon .= '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">' . $text . '</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">' . $archive_text . '</span></p>'
						//Errors inputs
						. '<input type="hidden" name="' . $error_type . '" value="' . $error_type_value.'">
						<input type="hidden" name="' . $error_value . '" value="' . $error_value_value .'">';
						if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
							$addon .= '<input type="hidden" name="' . $archive_error_type . '" value="' . $archive_error_type_value.'">
							<input type="hidden" name="' . $archive_error_value . '" value="' . $archive_error_value_value .'">';
						}
				//Custom error, when disabled for role
				$title .= $addon;

			}
			$output .= '<th>' . $title . '</th>';
		}
	$output .= '</tr>';

	foreach( $ordered_roles as $role => $roles_data ){
		$output .= '<tr>';
		$output .= '<td class="wpcf-access-table-action-title">';
			if (in_array($role, $default_roles))
				$output .= translate_user_role($roles_data['name']);
			else
				$output .= taccess_t($roles_data['name'], $roles_data['name']);
		$output .= '</td>';

		foreach ( $settings as $permission_slug => $data ){

			if ( isset($custom_errors['_custom_read_errors'][$id]) && $permission_slug == 'read' ) {
				$current_custom_errors = $custom_errors['_custom_read_errors'][$id]['permissions']['read'];
				$current_custom_errors_value = isset( $custom_errors['_custom_read_errors_value'][$id]['permissions']['read'] ) ? $custom_errors['_custom_read_errors_value'][$id]['permissions']['read'] : '';
			}
			if ( isset($custom_errors['_archive_custom_read_errors'][$id]) && $permission_slug == 'read' ) {

				$current_archive_custom_errors = $custom_errors['_archive_custom_read_errors'][$id]['permissions']['read'];
				$current_archive_custom_errors_value = isset( $custom_errors['_archive_custom_read_errors_value'][$id]['permissions']['read'] ) ? $custom_errors['_archive_custom_read_errors_value'][$id]['permissions']['read'] : '';
			}
			// Change slug for 3rd party
			if (!in_array($group_id, array('types', 'tax'))) {
				$permission_slug = $data['cap_id'];
				$managed = true;
			}
			$option_enabled = false;
			if ( isset( $permissions[$permission_slug]['roles'] ) ){
				if ( is_string($permissions[$permission_slug]['roles']) ){
					$permissions[$permission_slug]['roles'] = Access_Helper::toolset_access_get_roles_by_role($permissions[$permission_slug]['roles']);
				}
				if ( in_array($role, $permissions[$permission_slug]['roles']) !== false ){
					$option_enabled = true;
				}
			}elseif( isset($settings[$permission_slug]['roles']) ){
				if ( in_array($role, $settings[$permission_slug]['roles']) !== false ){
					$option_enabled = true;
				}
			}else{
				//Set permissions by predefined role capabilities
				if( isset($settings[$permission_slug]['role']) ){
					$option_enabled = self::toolset_access_check_for_cap( $settings[$permission_slug]['role'], $roles_data );
				}
			}


			$name = 'types_access[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][roles][]';

			$addon = '';
			if ( $permission_slug == 'read'  && $role != 'administrator' && $id != 'attachment' ) {
				$addon_id = $group_id . '_' . $id . '_error_page_' . $permission_slug . '_' . $role . '_role';
				$error_value_value = $error_type_value = $archive_error_value_value = $archive_error_type_value = $text = $archive_text =  '';

				$link_title = '';

				if ( isset($current_custom_errors[$role]) && !empty($current_custom_errors[$role]) ) {
					$error_type_value = $current_custom_errors[$role];
					$error_value_value = ( isset( $current_custom_errors_value[ $role ] ) ? $current_custom_errors_value[ $role ] : '');
					if ( $error_type_value == 'error_404' ) {
						$text = '404';
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$text = __( 'Template', 'wpcf-access' ) . ': ' . self::toolset_access_get_content_template_name( $error_value_value );
						$link_title = __( 'Show Content Template','wpcf-access' ) . ' - ' . self::toolset_access_get_content_template_name( $error_value_value );
					} elseif ( $error_type_value == 'error_layouts' ) {
						if ( class_exists('WPDD_Layouts') ) {
							$text = __( 'Template Layout: ', 'wpcf-access' ) . ': ' . self::toolset_access_get_layout_name( $error_value_value );
							$link_title = __( 'Show Template Layout', 'wpcf-access' ) . ' - ' . self::toolset_access_get_layout_name( $error_value_value );
						} else {
							$text = $link_title = '';
						}
					} else {
						$text = __('PHP Template', 'wpcf-access').': '. $error_value_value;
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}
				elseif ( isset($current_custom_errors['everyone']) && !empty($current_custom_errors['everyone']) ) {
					if ( $error_type_value == 'error_404' ) {
						$link_title = __('Show 404 - page not found','wpcf-access');
					} elseif ( $error_type_value == 'error_ct' ) {
						$link_title = __( 'Show Content Template','wpcf-access' ) . ' - ' . self::toolset_access_get_content_template_name( $error_value_value );
					} else {
						$link_title = __('Show Page template','wpcf-access').' - '. $error_value_value;
					}
				}

				//Set Archive Errors
				if ( isset($current_archive_custom_errors[$role]) && !empty($current_archive_custom_errors[$role]) && isset($type_data['has_archive']) && $type_data['has_archive'] == 1  ) {
					$archive_error_type_value = $current_archive_custom_errors[$role];
					$archive_error_value_value = $current_archive_custom_errors_value[$role];
					if ( $archive_error_type_value == 'default_error' ) {
						$archive_text = __('Display: \'No posts found\'','wpcf-access');
					} elseif ( $archive_error_type_value == 'error_ct' ) {
						$archive_text = __('View Archive', 'wpcf-access').': '. self::toolset_access_get_view_name($archive_error_value_value);
					} elseif ( $archive_error_type_value == 'error_layouts' ) {
						if ( class_exists('WPDD_Layouts') ) {
							$archive_text = __( 'Layout Template', 'wpcf-access' ) . ': ' . self::toolset_access_get_layout_name( $archive_error_value_value );
						} else {
							$archive_text = '';
						}
					} elseif ( $archive_error_type_value == 'error_php' ) {
						$archive_text = __('PHP Archive', 'wpcf-access').': '. preg_replace("/.*(\/.*\/)/","$1",$archive_error_value_value);
					}else{
						$archive_text = '';
					}

				}


				$is_archive = '';
				$archive_vars = '';
				if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
					$is_archive = 1;
				}
				$link_title = ' title="'. sprintf( __('Choose what to display to people who don’t have read permission for %s','wpcf-access'), $id) .'" ';

				$error_type = 'types_access_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$error_value = 'types_access_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$archive_error_type = 'types_access_archive_error_type[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$archive_error_value = 'types_access_archive_error_value[' . $group_id . '][' . $id . '][permissions]' . '[' . $permission_slug . '][' . $role . ']';
				$custom_error_json_array = wp_json_encode(
					array(
						'typename' => $error_type,
						'role' => $role,
						'valuename' => $error_value,
						'curtype' => $error_type_value,
						'curvalue' => $error_value_value,
						'archivetypename' => $archive_error_type,
						'archivevaluename' => $archive_error_value,
						'archivecurtype' => $archive_error_type_value,
						'archivecurvalue' => $archive_error_value_value,
						'posttype' => $id,
						'archive' => $is_archive,
						'forall' => 0
					)
				);
		        $addon = '<a ' . $link_title . 'class="wpcf-add-error-page js-wpcf-add-error-page"'
		        	     . ' data-custom_error="' . esc_attr( $custom_error_json_array ) . '" href="">'
		                 . '<i class="icon-edit fa fa-pencil-square-o"></i></a>';

						//Labels
						$addon .= '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">' . $text . '</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">' . $archive_text . '</span></p>'
						//Errors inputs
						. '<input type="hidden" name="' . $error_type . '" value="' . $error_type_value.'">
						<input type="hidden" name="' . $error_value . '" value="' . $error_value_value .'">';
						if( isset($type_data['has_archive']) && $type_data['has_archive'] == 1 ){
							$addon .= '<input type="hidden" name="' . $archive_error_type . '" value="' . $archive_error_type_value.'">
							<input type="hidden" name="' . $archive_error_value . '" value="' . $archive_error_value_value .'">';
						}

			}
			$is_disabled_cred_checkbox = ( $id == '__CRED_CRED_USER_GROUP' && $role == 'guest' && strpos( $name, 'create_users_with_cred' ) === FALSE );
			$att_id = $group_id . '_' . $id . '_permissions_' . $permission_slug . '_' . $role . '_role';
            $attributes = $option_enabled && ! $is_disabled_cred_checkbox ? ' checked="checked" ' : '';
            $attributes .= !$managed ? ' readonly="readonly" disabled="disabled" ' : '';
            $tooltip = '';
            if ( $managed && $role == 'guest' && $permission_slug != 'read' && ( $group_id == 'types' || $group_id == 'tax' ) || $is_disabled_cred_checkbox ){
            	$attributes .= ' readonly="readonly" disabled="disabled" ';
            	$tooltip = ' title="'. __('This option doesn\'t work for Guests', 'wpcf-access') .'"';
			}
			$output .= '<td class="wpcf-access-table-option-cell"' . $tooltip . '><div class="error-page-set-wrap"><input type="checkbox" name="' . $name . '" id="' . $att_id . '" value="' . $role . '"'
                        . $attributes . ' class="wpcf-access-check-left wpcf-access-'
                        . $permission_slug . '" data-wpcfaccesscap="'
                        . $permission_slug . '" data-wpcfaccessname="'
                        . $name . '" ' . 'onclick="wpcfAccess.AutoThick( jQuery(this), \''
                        . $permission_slug . '\', \''
                        . $name . '\');"';
            if ( ! $enabled ) {
                $output .= ' disabled="disabled" readonly="readonly"';
            }
            $output .= '>';
            if ( $role == 'administrator' ){
                $output .= '<input type="hidden" name="' . $name . '" id="' . $att_id . '" value="' . $role . '"'
                        . $attributes . ' class="wpcf-access-check-left wpcf-access-'
                        . $permission_slug . '" data-wpcfaccesscap="'
                        . $permission_slug . '" data-wpcfaccessname="'
                        . $name . '>';
            }
            $output .= $addon . '<span class="toolset-access-disabled-detector" data-parent="js-otg-access-settings-section-item-toggle-target-' . esc_attr( $id ) . '"></span></div></td>';


		}
		$output .= '</tr>';
	}

	//Specific users row
	$output .= '<tr class="toolset-access-specific-users-row">';
		$output .= '<th>&nbsp;</th>';
		foreach ($settings as $permission_slug => $data){
			$users_list = '';

			//Fix users array
			if ( isset($permissions[$permission_slug]['users']) && !empty($permissions[$permission_slug]['users']) && is_string($permissions[$permission_slug]['users']) && !empty($area) ){
				$model = TAccess_Loader::get('MODEL/Access');
				$permissions[$permission_slug]['users'] = explode(',', $permissions[$permission_slug]['users']);
				$_temp_settings_global = $model->getAccessSettings();
				$_temp_settings = $_temp_settings_global->$area;
				$_temp_settings[$group_id][$id]['permissions'][$permission_slug]['users'] = $permissions[$permission_slug]['users'];
				$_temp_settings_global->$area = $_temp_settings;
				$model->updateAccessSettings($_temp_settings_global);
			}

			if ( isset($permissions[$permission_slug]['users']) && is_array($permissions[$permission_slug]['users']) && count($permissions[$permission_slug]['users']) > 0 ){
				$args = array(
					'orderby' => 'user_login',
					'include' => array_slice($permissions[$permission_slug]['users'], 0, 2)
				);
				$user_query = new WP_User_Query( $args );
				foreach ( $user_query->results as $user ) {
					$users_list .= $user->data->user_login.'<br>';
				}
				$users_list .= ( (count($permissions[$permission_slug]['users']) > 2)? 'and '.(count($permissions[$permission_slug]['users'])-2).' more':'');
			}
			$link_disabled = !$managed ? ' js-toolset-access-specific-user-disabled' : '';

			$output .= '<td>
				<a href="#" title="'.__('Specific users', 'wpcf-access').'" class="js-toolset-access-specific-user-link'.$link_disabled.'"  data-parent="js-otg-access-settings-section-item-toggle-target-' . esc_attr( $id ) . '" data-slugtitle="'.$data['title'].'" data-option="'. $permission_slug .'" data-id="'. $id .'" data-groupid="'. $group_id .'">
					<i class="icon-user-plus fa fa-user-plus"></i>
				</a>
				<span class="js-access-toolset-specific-users-list js-access-toolset-specific-users-list-'. $id.'-'. $group_id.'-'. $permission_slug.' access-toolset-specific-users-list">'. $users_list .'</span>
				</td>';
		}
	$output .= '</td>';


	$output .= '</table>';

    return $output;
}

/**
 * Suggest user form.
 *
 * @global type $wpdb
 * @return string
 */
public static function wpcf_access_suggest_user( $enabled = true, $managed = false, $name = '' )
{
    static $_id=0;
    global $wpdb;

    // Select first 5 users
    $users = $wpdb -> get_results("SELECT ID, user_login, display_name FROM $wpdb->users LIMIT 5");
    $output = '';
    $_id++;
	$first_class = ' dropdown_bottom';	
	//if ( strpos($name, '[read]') > 0 || strpos($name, '[view_fields_in_edit_page') > 0 || strpos($name, '[__CRED_CRED]') > 0 ){
	//	$first_class = ' dropdown_bottom';	
	//}
    $output = '<div class="types-suggest-user types-suggest" id="types-suggest-user-' . $_id . '">';
		$output .= '<input type="text" class="input" placeholder="' . esc_attr__('search', 'wpcf-access') . '"';
		if (!$enabled || !$managed) {
			$output .= ' readonly="readonly" disabled="disabled"';
		}
		$output .= ' />';
		$output .= '<img src="' . esc_url(admin_url('images/wpspin_light.gif')) . '" class="img-waiting" alt="" />';
		$output .= '<div class="button-group js-suggest-user-controls"><button class="cancel toggle button button-small button-secondary">' . __('Cancel', 'wpcf-access') . '</button> ';
		$output .= '<button class="confirm toggle button button-small button-primary">' . __('OK', 'wpcf-access') . '</button></div>';
		$output .= '<select size="' . count($users) . '" class="dropdown'.$first_class.'">';
		foreach ($users as $u) {
			$output .= '<option value="' . $u->ID . '">' . $u->display_name . ' (' . $u->user_login . ')' . '</option>';
		}
		$output .= '</select>';
    $output .= '</div>';
    return $output;
}

	/**
	 * @sinse 2.2
	 * return array of opened sections
	 * @return mixed
	 */
public static function get_section_statuses(){
	global $current_user;
	$user_id = $current_user->ID;
	$sections_array = get_user_meta( $user_id, 'wpcf_access_section_status', true );
	return $sections_array;
}

/**
 * @since 2.2
 * Order wp_roles array
 * order: administrator, super users, admins, editors, all other users
*/
public static function toolset_access_order_wp_roles( $invalidate = false ){

	$ordered_roles = Access_Cacher::get( 'toolset_access_ordered_roles' );
	if ( false === $ordered_roles || $invalidate = true  ) {
		global $wp_roles;
		if ( ! isset( $wp_roles ) || empty($wp_roles) ) {
            $wp_roles = new WP_Roles();
		}

		$roles = $wp_roles->roles;
		$group1 = $group2 = $group3 = $group4 = $group5 = $group6 = array();
		ksort($roles);
		foreach ( $roles as $role => $role_info ){
			if ( $role == 'administrator' ){
				$group1[$role] = $role_info;
				$group1[$role]['permissions_group'] = 1;
			}
			elseif ( isset( $role_info['capabilities']['manage_network']) || isset( $role_info['capabilities']['manage_sites'])
				|| isset( $role_info['capabilities']['manage_network_users'] ) || isset( $role_info['capabilities']['manage_network_plugins'] )
				|| isset( $role_info['capabilities']['manage_network_themes'] ) || isset( $role_info['capabilities']['manage_network_options'] ) ){
				$group2[$role] = $role_info;
				$group2[$role]['permissions_group'] = 2;
			}
			elseif ( isset( $role_info['capabilities']['delete_users'] ) ){
				$group3[$role] = $role_info;
				$group3[$role]['permissions_group'] = 3;
			}
			elseif ( isset( $role_info['capabilities']['edit_others_pages'] ) || isset( $role_info['capabilities']['edit_others_posts'] ) ){
				$group4[$role] = $role_info;
				$group4[$role]['permissions_group'] = 4;
			}
			elseif ( isset( $role_info['capabilities']['edit_pages'] ) || isset( $role_info['capabilities']['edit_posts'] ) ){
				$group5[$role] = $role_info;
				$group5[$role]['permissions_group'] = 5;
			}
			else{
				$group6[$role] = $role_info;
				$group6[$role]['permissions_group'] = 6;
			}
		}
		$ordered_roles = array_merge($group1, $group2, $group3, $group4, $group5, $group6);
		$ordered_roles['guest'] = array( 'name' => __('Guest', 'wpcf-access'), 'permissions_group' => 6, 'capabilities' => array('read'=>1) );
		Access_Cacher::set( 'toolset_access_ordered_roles', $ordered_roles  );
	}

	return $ordered_roles;
}


}
