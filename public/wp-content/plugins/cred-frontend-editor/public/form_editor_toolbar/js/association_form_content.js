/**
 * Manage the association form editor toolbar.
 * 
 * @see Toolset.CRED.EditorToolbarPrototype
 *
 * @since m2m
 * @package CRED
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationFormsContentEditorToolbar = function( $ ) {
	Toolset.CRED.EditorToolbarPrototype.call( this );

	var self = this;

	/**
	 * Initialize localization strings.
	 * 
	 * @since 2.1
	 */
	self.initI18n = function() {
		this.i18n = cred_association_form_content_editor_toolbar_i18n;
		return this;
	};

	/**
	 * Init Toolset hooks.
	 *
	 * @uses Toolset.hooks
	 * @since 2.1
	 */
	self.initHooks = function() {
		self.constructor.prototype.initHooks.call( self );
		Toolset.hooks.addAction( 'cred-action-toolbar-scaffold-dialog-loaded', self.manageScaffoldSettings, 10 );
		Toolset.hooks.addAction( 'cred-action-toolbar-shortcode-dialog-loaded', self.manageCancelFieldSettings, 10 );
		return self;
	};

	/**
	 * Get the current form slug.
	 *
	 * @return string
	 * @since 2.2.1
	 */
	self.getFormSlug = function() {
		return $( '#slug' ).val();
	};

	/**
	 * Get the object key to manipulate fields for.
	 *
	 * @return string
	 * @since 2.1
	 */
	self.getObjectKey = function() {
		return $( '#relationship' ).val();
	};

	self.manageScaffoldSettings = function() {
		var $context = $( '.js-cred-editor-scaffold-dialog-container' );
		self.manageCancelField( $context );
	};

	self.manageCancelFieldSettings = function( shortcode ) {
		if ( 'cred-form-cancel' !== shortcode ) {
			return;
		}
		var $context = $( '.js-cred-editor-shortcode-dialog-container');
		self.manageCancelField( $context );
	};

	self.manageCancelField = function( $context ) {
		var $pageSelector = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-select_page select', $context );
		var $ctSelector = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-select_ct select', $context );

		// Hide fields by default
		$pageSelector.parent().hide();
		$ctSelector.parent().hide();
		self.initOnChangeForCancelButtonSelector( $pageSelector, $ctSelector );
	};

	/**
	 * Control page and CT selector based on selected value
	 *
	 * @since m2m
	 */
	self.initOnChangeForCancelButtonSelector = function( $pageSelector, $ctSelector ) {
		$( '.js-toolset-shortcode-gui-attribute-wrapper-for-action select' ).change( function() {
			var currentSelectorValue = $( this ).val();

			if ( 'different_page_ct' === currentSelectorValue ) {
				$pageSelector.parent().show();
				$ctSelector.parent().show();
				self.initSelect2ForSelector( $pageSelector, '', '');
				self.initSelect2ForSelector( $ctSelector, 'view-template', 'post_name' );
			} else if ( 'same_page_ct' === currentSelectorValue ) {
				$pageSelector.parent().hide();
				$ctSelector.parent().show();
				self.initSelect2ForSelector( $ctSelector, 'view-template', 'post_name' );
			} else {
				$pageSelector.parent().hide();
				$ctSelector.parent().hide();
			}
		});
	};

	/**
	 * Init Select2 for page or CT selectors
	 *
	 * @param $selector
	 * @param postType
	 * @param valueType
	 *
	 * @since m2m
	 */
	self.initSelect2ForSelector = function( $selector, postType, valueType ) {
		
		var $selectorParent = $selector.closest( '.js-toolset-shortcode-gui-dialog-container' );
		var currentInstance = this;

		$selector.toolset_select2({
			width: '300px',
			dropdownParent:	$selectorParent,
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 300,
				data: function (params) {
					return {
						s: params.term, // search term
						loadRecent: true,
						action: currentInstance.i18n.data.requestPostsByTitle.action,
						postType:  postType,
						valueType: valueType,
						wpnonce: currentInstance.i18n.data.requestPostsByTitle.nonce
					};
				},
				type: 'POST',
				processResults: function( results ) {
					return {
						results: ( results.data ) ? results.data : []
					};
				},
				cache: true
			},
			placeholder: currentInstance.i18n.data.scaffold.fields.formElements.cancel.searchPlaceholder,
			minimumInputLength: 0,
			templateResult: function( results ) {
				if ( results.loading ) {
					return results.text;
				}
				return results.text;
			},
			templateSelection: function( results ) {
				return results.text;
			}
		});
	};

	self.init();

};

Toolset.CRED.AssociationFormsContentEditorToolbar.prototype = Object.create( Toolset.CRED.EditorToolbarPrototype.prototype );

jQuery( document ).ready( function( $ ) {
	new Toolset.CRED.AssociationFormsContentEditorToolbar( $ );
});