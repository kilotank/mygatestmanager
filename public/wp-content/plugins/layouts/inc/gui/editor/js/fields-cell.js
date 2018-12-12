/**
 * Handles Fields cells
 *
 * @since 2.4
 */
DDLayout.FieldsCell = function( $ ) {
	var self = this,
		$markup = jQuery('.ddl-markup-controls');

	/**
	 * Object init
	 *
	 * @since 2.4
	 */
	self.init = function() {
		self.editor = self.editor || {};
		jQuery(document).on( 'fields-cell.dialog-open', self._dialog_open );
		jQuery(document).on( 'fields-cell.dialog-close', self._dialog_close );
		jQuery(document).on( 'fields-cell.get-content-from-dialog', self._get_content_from_dialog );
		jQuery(document).on( 'change', '#ddl-layout-field_type', self.select_field_type );
	};


	/**
	 * Adds field content to Backbone
	 *
	 * @param {Event} event Event.
	 * @param {Object} content Dialog content.
	 * @param {Object} dialog Dialog object.
	 * @since 2.4
	 */
	self._get_content_from_dialog = function ( event, content, dialog ) {
		var $selected = jQuery('#ddl-default-edit #ddl-layout-field_type option:selected');
		content.icon = $selected.data('icon');
		content.field = $selected.text();
		content.field_type = $selected.val();
	};


	/**
	 * Event when the dialog is opened
	 *
	 * @param {Event} event Event.
	 * @param {Object} content Dialog content.
	 * @param {Object} dialog Dialog object.
	 * @since 2.4
	 */
	self._dialog_open = function  (event, content, dialog ) {
		var $dialog = jQuery('#ddl-default-edit');
		var $select = $dialog.find('#ddl-layout-field_type');
		$select.val(content.field_type).change();
		if ( ! $select.val() ) {
			$markup.hide();
		}
		Object.keys(content).forEach(function(option) {
			var $element = $dialog.find('[name="ddl-layout-' + option + '"]');
			if ( $element.length ) {
				if ( $element.is(':radio') ) {
					var $option = $element.filter('[value="' + content[option] + '"]');
					$option.click();
				} else if ( $element.is(':text') ) {
					$element.val( content[option] );
				}
			}
		});
	}


	/**
	 * Event when the dialog is closed
	 *
	 * @param {Event} event Event.
	 * @param {Object} content Dialog content.
	 * @param {Object} dialog Dialog object.
	 * @since 2.4
	 */
	self._dialog_close = function( event, content, dialog ) {
	};


	/**
	 * Selecting Field type
	 *
	 * @param {Event} event Event.
	 * @since 2.4
	 */
	self.select_field_type = function( event ) {
		var $dialog = jQuery('#ddl-default-edit');

		var $select = jQuery( event.target );
		if ( $select.val() ) {
			$markup.show();
		} else {
			$markup.hide();
		}
		var $container = jQuery( '#ddl-fields-cell-attributes-container');
		if ( $container.data('from') === $select.val() ) {
			// Don't know why it is called twice.
			return;
		}

		var $templateHTML = jQuery( '#ddl-fields-cell-attributes-template' ).html();
		var $template = _.template( $templateHTML );
		$optionSelected = $select.find('option:selected');
		var compiled = $template(
			{
				field_type: $optionSelected.data('field_type'),
				attributes: $optionSelected.val() ? $optionSelected.data('attributes').displayOptions : {},
				options: $optionSelected.val() ? $select.find('option:selected').data('options') : []
			}
		);
		$container.data( 'from', $select.val() );
		$container.html( compiled );
		var checkDependencies = function() {
			$dialog.find('[data-depends]').each(function() {
				var $this = jQuery(this);
				var dependencies = $this.data('depends');
				var visible = true;
				dependencies.forEach(function(dependency) {
					// Values can be an array, a simple string or a list of items joined by '|' 🤦‍
					var validValues = typeof dependency.value === 'object'
						? dependency.value
						: dependency.value.split('|');
					var $option = jQuery('[name="ddl-layout-' + dependency.key + '"]:checked:visible');
					visible = visible & $option.length && validValues.includes( $option.val() );
				});
				$this.toggle( !!visible );
			});
		}
		$container.find(':radio').change(function() {
			checkDependencies();
		});
		checkDependencies();
	}

	self.init();

};

// Including this class.
jQuery(document).on('DLLayout.admin.ready', function($){
	DDLayout.fields_cell = new DDLayout.FieldsCell($);
});
