/**
 * Manage the user form edit page.
 * 
 * @see Toolset.CRED.EditorPagePrototype
 * 
 * @since 2.1
 * @package CRED
 */
var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.UserFormsEditor = function( $ ) {
	Toolset.CRED.EditorPagePrototype.call( this );

	var self = this;

	self.init();
};

Toolset.CRED.UserFormsEditor.prototype = Object.create( Toolset.CRED.EditorPagePrototype.prototype );

jQuery( document ).ready( function( $ ) {
	new Toolset.CRED.UserFormsEditor( $ );
});