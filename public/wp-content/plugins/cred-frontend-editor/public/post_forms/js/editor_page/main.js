/**
 * Manage the post form edit page.
 * 
 * @see Toolset.CRED.EditorPagePrototype
 * 
 * @since 2.1
 * @package CRED
 */
var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.PostFormsEditor = function( $ ) {
	Toolset.CRED.EditorPagePrototype.call( this );

	var self = this;

	self.init();
};

Toolset.CRED.PostFormsEditor.prototype = Object.create( Toolset.CRED.EditorPagePrototype.prototype );

jQuery( document ).ready( function( $ ) {
	new Toolset.CRED.PostFormsEditor( $ );
});