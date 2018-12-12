<?php
/**
 * Fields cell dialog
 *
 * @since 2.4
 */
?>

<div class="padding-5 ddl-form">
	<h4><?php esc_html_e( 'Fields Groups', 'ddl-layouts' ); ?></h4>
	<legend><?php esc_html_e( 'Field type', 'ddl-layouts' ); ?></legend>
	<p>
		<select id="<?php the_ddl_name_attr( 'field_type' ); ?>">
			<option value=""><?php esc_html_e( 'Select a Custom Field', 'ddl-layouts' ); ?></option>
			<?php foreach ( $groups as $group ) { ?>
				<optgroup label="<?php echo esc_html( $group['title'] ); ?>">
					<?php foreach ( $group['fields'] as $field ) { ?>
						<option
							value="<?php echo esc_html( $group['id'] . '#' . $field['slug'] ); ?>"
							data-icon="<?php echo esc_html( $field['icon'] ); ?>"
							data-attributes="<?php echo esc_attr( wp_json_encode( $field['attributes'] ) ); ?>"
							data-options="<?php echo esc_attr( wp_json_encode( $field['field_options'] ) ); ?>"
							data-field_type="<?php echo esc_attr( $field['type'] ); ?>"
						><?php echo esc_html( $field['name'] ); ?></option>
					<?php } ?>
				</optgroup>
			<?php } ?>
		</select>
	</p>
	<div id="ddl-fields-cell-attributes-container" class="ddl-repeat-field">
	</div>
</div>
<script type="text/html" id="ddl-fields-cell-attributes-template">
	<# if ( typeof attributes === 'object' && !!attributes.fields && Object.keys( attributes.fields ).length ) { #>
		<h3>{{ attributes.header }}</h3>
		<#
			var renderGroup = function( key, item, i, fieldItem, option, replaceText ) {
				var label = !! fieldItem.pseudolabel
					? fieldItem.pseudolabel
					: ( !! fieldItem.label ? fieldItem.label : '' );
				if ( [ 'checkbox', 'checkboxes' ].includes( field_type ) ) {
					var optionName = 'selectedValue' === item
						? 'display_value_selected'
						: 'display_value_not_selected';
					var inputName = 'options[' + i + '][' + optionName + ']';
				} else {
					inputName = i;
				}
				#>
					<p>
						<label for="ddl-fields-cell-{{ key }}-{{ item }}-{{ i }}">{{ label.replace( '%%OPTION%%', replaceText ) }}</label>
						<input type="text" id="ddl-fields-cell-{{ key }}-{{ item }}-{{ i }}" name="ddl-layout-{{ inputName }}" />
					</p>
				<#
			}

			var fields = attributes.fields;
			Object.keys( fields ).forEach( function( key ) {
				var field = fields[key];
				var dependsOn = !!field.dependsOn;
				var visible = ! dependsOn
					? true
					: true; // TODO when saved values selected then calculate
				var dependsObAttribute = dependsOn
					? ' data-depends=' + JSON.stringify( field.dependsOn ) + ' ' // Last condition is enough
					: '';
				var defaultValue = !!field.defaultValue
					? field.defaultValue
					: ( !!field.defaultForceValue ? field.defaultForceValue : false );

		#>
			<div class="js-ddl-fields-cell-option"  id="ddl-fields-cell-{{ key }}" {{ dependsObAttribute }}>
				<fieldset class="dll-form from-top-15">
				<# if ( !! field.label ) { #>
					<legend>{{ field.label }}</legend>
				<# } #>
				<div class="fields-group">
				<#
					if ( field.type === 'radio' ) {
						Object.keys( field.options ).forEach( function( value ) {
				#>
						<p>
							<label for="ddl-fields-cell-{{ key }}-{{ value }}">
								<input type="radio" value="{{ value }}" id="ddl-fields-cell-{{ key }}-{{ value }}" name="ddl-layout-{{ key }}" {{ defaultValue === value ? ' checked="checked" ' : '' }} />
								{{{ field.options[value] }}}
							</label>
						</p>
					<# } ); // options foreach #>
				<# } // is radio #>
				<#
					if ( field.type === 'text' ) {
				#>
					<p>
						<input type="text" id="ddl-fields-cell-{{ key }}" name="ddl-layout-{{ key }}"  {{ defaultValue ? ' value=' + defaultValue + ' ' : '' }} />
					</p>
				<# } // is text #>

				<#
					if ( field.type === 'group' ) {
						if ( options.length ) {
							options.forEach( function(option, i) {
								Object.keys( field.fields ).forEach( function( item ) {
									renderGroup( key, item, i, field.fields[item], option, option.label );
								} ); // options fields
							} ); // options
						} else {
							Object.keys( field.fields ).forEach( function( item, i ) {
								renderGroup( key, item, item, field.fields[item], {}, '' );
							} ); // options fields
						}
					} // is group
				#>
					</div>
				<# if ( !!field.description) { #>
					<p class="desc">{{ field.description }}</p>
				<# } #>
				</fieldset>
			</div>
		<# } ); // fields foreach #>
	<# } #>
</script>
