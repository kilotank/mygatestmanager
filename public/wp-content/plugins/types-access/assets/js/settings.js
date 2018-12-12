var wpcfAccess = wpcfAccess || {};
var OTGAccess = OTGAccess || {};
/**
* OTGAccess.AccessSettings
*
* @since 2.2.2
*/

OTGAccess.AccessCleanup = function( $ ) {
	
	// @todo add proper mesage management

	var self = this;
	var myHistory = [];
	self.spinner = '<span class="wpcf-loading ajax-loader js-otg-access-spinner"></span>';
	self.section_status = '';
	
	self.spinner_placeholder = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="otg-access-spinner"><i class="fa fa-refresh fa-spin"></i></div>' +
		'</div>' +
		'</div>'
	);

	$( document ).on( 'click', '.js-toolset-access-erase-message-before-start', function( e ) {
		$('.js-toolset-access-erase_database').show();
		$('.js-toolset-access-misc-start').prop('disabled', true);
		$('.js-toolset-access-erase-message-before-start').hide();
	});

	$( document ).on( 'change', '#js-toolset-access-misc-remove-settings, #js-toolset-access-misc-remove-roles', function( e ) {
		$('.js-error-container').html('');
		if ( $('#js-toolset-access-misc-remove-roles').prop('checked') || $('#js-toolset-access-misc-remove-settings').prop('checked') ){
			$('.js-toolset-access-misc-start').prop('disabled', false);
		}else{
			$('.js-toolset-access-misc-start').prop('disabled', true);
		}

	});

	/**
	 * Misc
	 * clean up Access settings
	 */
	$( document ).on( 'change', '#js-toolset-access-misc-remove-roles', function( e ) {
		e.preventDefault();
		if ( $(this).prop('checked') ){
			$('.js-toolset-access-misc-existing-users, .js-toolset-access-misc-reasign-users').show();
		}else{
			$('.js-toolset-access-misc-existing-users, .js-toolset-access-misc-reasign-users').hide();
		}
	});

	$( document ).on( 'click', '.js-toolset-access-misc-start', function( e ) {
		e.preventDefault();

		var remove_settings = $('#js-toolset-access-misc-remove-settings').prop('checked'),
			remove_roles = $('#js-toolset-access-misc-remove-roles').prop('checked'),
			is_agree = $('#js-toolset-access-misc-agree-clean-database').prop('checked'),
			disable_plugin = $('#js-toolset-access-misc-disable-plugin').prop('checked');

		$('.js-error-container').html('');

		if ( !remove_settings && !remove_roles ){
			$('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.toolset_access_misc_select_action + '</p>');
			return;
		}

		if ( remove_roles && $('.js-toolset-access-misc-reasign-users select').val() == '' ){
			$('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.toolset_access_misc_select_role + '</p>');
			return;
		}

		$('.toolset-access-misc-form-process').hide();
		$( self.spinner ).appendTo( '.js-toolset-access-misc-spiner' ).show();

		var data = {
			action : 'wpcf_access_clean_up_database',
			remove_settings : remove_settings,
			remove_roles : remove_roles,
			role_to_assign : $('.js-toolset-access-misc-reasign-users select').val(),
			disable_plugin : disable_plugin,
			wpnonce : jQuery('#wpcf-access-edit').val()
		};

		toolset_access_bulk_role_remove(data);

		return false;
	});

	var toolset_access_bulk_role_remove = function( data ){
		jQuery.ajax({
			url:		ajaxurl,
			type:		'POST',
			dataType:	"json",
			data:		data,
			success: 	function( response ) {},
			complete:	function( object, status ) {
				var text = jQuery.parseJSON(object.responseText);

				if ( text.status == 1 ){
					var disable_plugin = $('#js-toolset-access-misc-disable-plugin').prop('checked');
					if ( !disable_plugin ){
						$('.toolset-access-misc-form-process').show();
						$( '.js-toolset-access-misc-spiner' ).html(text.message);
						$('.toolset-access-misc-form-process').hide();
					}else{
						document.location.href = 'plugins.php';
					}
				}else{
					var message = text.message;
					var total_parsed = parseInt($('.js-toolset-access-misc-total-users-processed').val()) + parseInt(text.assigned_users);
					$('.js-toolset-access-misc-total-users-processed').val(total_parsed);
					message = message.replace("%n", total_parsed);
					message = message.replace("%t", $('.js-toolset-access-misc-total-users').val());
					$( '.js-toolset-access-misc-spiner' ).find('.toolset-access-misc-progress-message').remove();
					$( '.js-toolset-access-misc-spiner' ).html(self.spinner +'<div class="toolset-access-misc-progress-message">'+ message +'</div>');
					toolset_access_bulk_role_remove(data);
				}
			}
		});
	}


};

jQuery( document ).ready( function( $ ) {
    OTGAccess.access_cleanup = new OTGAccess.AccessCleanup( $ );
});

