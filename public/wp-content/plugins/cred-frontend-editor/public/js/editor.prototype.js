/**
 * Manage the form editor page.
 *
 * @since 2.1
 * @package CRED
 */

var Toolset = Toolset || {};
var WPV_Toolset = WPV_Toolset || {};

Toolset.CRED = Toolset.CRED || {};

if ( typeof WPV_Toolset.CodeMirror_instance === "undefined" ) {
    WPV_Toolset.CodeMirror_instance = {};
}

Toolset.CRED.EditorPagePrototype = function( $ ) {

    this.editorSelector = 'content';
    this.editorMode = 'myshortcodes';
    this.editorInstance = {};

    this.editorJsSelector = 'cred-extra-js-editor';
    this.editorCssSelector = 'cred-extra-css-editor';
    this.editorActionMessageSelector = 'credformactionmessage';
    this.editorExtra = {
        js: {},
        css: {},
        actionMessage: {}
    };

};

/**
 * Init the main editor:
 * - Define the Codemirror mode.
 * - Init the Codemirror editors.
 * - Add Quicktags.
 * - Add Bootstrap Grid buttons.
 * - Add hooks.
 * - Add events.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initIclEditor = function() {
    CodeMirror.defineMode( this.editorMode, codemirror_shortcodes_overlay );

    this.initMainEditor();
    this.initExtraEditors();
};

/**
 * Init the fixed top bar for title plus save button.
 * 
 * @since 2.2
 */
Toolset.CRED.EditorPagePrototype.prototype.initTopBar = function() {
    if ( jQuery( 'body' ).hasClass( 'cred-top-bar' ) ) {
        return;
    }

    jQuery( 'body' ).addClass( 'cred-top-bar' );

    jQuery( 'div#topbardiv > h2.hndle' ).remove();
    jQuery( 'div#topbardiv > button.handlediv' ).remove();
    jQuery( 'div#titlediv > div.inside' ).remove();
    jQuery( 'a.page-title-action' ).remove();

    // When exiting the wizard, we force a display:block here with .show()
    jQuery( 'div#titlediv' ).css( {'display': 'flex' } );

    jQuery( 'div#titlediv' ).prependTo( 'div#topbardiv > .inside' );

    jQuery( 'div#post-body-content' ).remove();
    
    jQuery( 'h1.wp-heading-inline' ).prependTo( 'div#titlediv' );

    jQuery( '#js-cred-save-form' ).appendTo( 'div#titlewrap' );

    // When loading a form without title (ie, when existing the wizard without setting a title)
    // hide the delete button: you cannot delete a fom that does not exist yet.
    if ( '' == jQuery( '#title' ).val() ) {
        jQuery( '.js-cred-delete-form' ).remove();
    } else {
        jQuery( '#title' ).hide();
        jQuery( '<span id="title-alt">' + jQuery( '#title' ).val() + '<i class="fa fa-pencil"></i></span>' ).prependTo( 'div#titlewrap' );
    }

    jQuery( 'div#topbardiv > *').show();
    jQuery( 'div#save-form-actions' ).show();

    var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width(),
        adminBarHeight = jQuery( 'div#topbardiv' ).height(),
        adminBarTopOffset = 0,
        adjustControls = function() {
            if ( jQuery( window ).scrollTop() > 5 ) {
                jQuery( '#save-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeOut( 'fast', function() {
                    jQuery( 'body' ).addClass( 'cred-top-bar-scroll' );
                });
            }
            else {
                jQuery( 'body' ).removeClass( 'cred-top-bar-scroll' );
                jQuery( '#save-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeIn( 'fast', function() {
                    
                });
            }
        };
    
    if ( jQuery( '#wpadminbar' ).length !== 0 ) {
        adminBarTopOffset = jQuery('#wpadminbar').height();
    }

    jQuery( 'div#topbardiv' ).css({
        'top':adminBarTopOffset,
        'width':adminBarWidth
    });

    jQuery( 'div#wpbody-content' ).css({
        'padding-top':( adminBarHeight )
    });

    jQuery( window ).on( 'scroll', adjustControls );

    jQuery( window ).on( 'resize', function() {
        var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width();
        jQuery( 'div#topbardiv' ).width( adminBarWidth );
    });

    jQuery( document ).on( 'click', '#title-alt', function( e ) {
        e.preventDefault();
        jQuery( this ).hide();
        jQuery( '#title' ).show();
    });

    adjustControls();
};

/**
 * Init a Codemirror editor on demand
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initCodemirror = function( editorId, editorSettings ) {
    var defaultSettings = {
        editorMode: this.editorMode,
        addQuicktags: true,
        addBootstrap: true
    };

    editorSettings = _.extend( defaultSettings, editorSettings );

    WPV_Toolset.CodeMirror_instance[ editorId ] = icl_editor.codemirror(
        editorId,
        true,
        editorSettings.editorMode
    );

    if ( editorSettings.addQuicktags ) {
        var editorQt = quicktags( { id: editorId, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
        WPV_Toolset.add_qt_editor_buttons( editorQt, WPV_Toolset.CodeMirror_instance[ editorId ] );
    }

    if ( editorSettings.addBootstrap ) {
        _.defer( function() {
            Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', editorId );
        });
    }
};

/**
 * Destroy a Codemirror editor on demand.
 * 
 * @param {string} editorId
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.destroyCodemirror = function( editorId ) {
    WPV_Toolset.CodeMirror_instance[ editorId ] = null;
    window.iclCodemirror[ editorId ] = null;
};

/**
 * Refresh a Codemirror editor on demand.
 * 
 * @param {string} editorId
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.refreshCodemirror = function( editorId ) {
    try {
        WPV_Toolset.CodeMirror_instance[ editorId ].refresh();
    } catch( e ) {
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Get the content of a Codemirror editor on demand.
 * 
 * @param {string} editorId
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getCodemirrorContent = function( editorId ) {
    var content = '';
    try {
        content = WPV_Toolset.CodeMirror_instance[ editorId ].getValue();
    } catch( e ) {
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
    return content;
};

/**
 * Callback for the Toolset.hooks filter 
 * to get the content of a Codemirror editor on demand.
 * 
 * @param {string} content
 * @param {string} editorId
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.filterGetCodemirrorContent = function( content, editorId ) {
    return this.getCodemirrorContent( editorId );;
};

/**
 * Init the main Codemirror editor plus its JS/CSS extra editors.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initMainEditor = function() {

    // Init main editor, with Quicktags and Bootstrap
    this.initCodemirror( this.editorSelector );
    this.editorInstance = WPV_Toolset.CodeMirror_instance[ this.editorSelector ];    

    // Init JS editor
    this.initCodemirror( this.editorJsSelector, { editorMode: 'javascript', addQuicktags: false, addBootstrap: false } );
    this.editorExtra.js = WPV_Toolset.CodeMirror_instance[ this.editorJsSelector ];
    
    // Init CSS editor
    this.initCodemirror( this.editorCssSelector, { editorMode: 'css', addQuicktags: false, addBootstrap: false } );
    this.editorExtra.css = WPV_Toolset.CodeMirror_instance[ this.editorCssSelector ];

};

/**
 * Init other Codemirror editors in the page, like:
 * - the message to display after submitting the form.
 * - the notifications body editor.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initExtraEditors = function() {

    var currentInstance = this;

    this.initCodemirror( this.editorActionMessageSelector );
    this.editorExtra.actionMessage = WPV_Toolset.CodeMirror_instance[ this.editorActionMessageSelector ];

    $( '.js-cred-notification-body' ).each( function() {
        var $notificationBodyEditor = $( this ),
            notificationBodyId = $notificationBodyEditor.attr( 'id' );

            currentInstance.initCodemirror( notificationBodyId );
    });
};

/**
 * Init API hooks for the main editor.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.addHooks = function() {
    Toolset.hooks.addAction( 'cred_editor_refresh_content_editor', this.refreshContentEditor, 10, this );
    Toolset.hooks.addAction( 'cred_editor_focus_content_editor', this.focusContentEditor, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_get_content_editor', this.getContentEditor, 10, this );

    Toolset.hooks.addAction( 'cred_editor_init_codemirror', this.initCodemirror, 10, this );
    Toolset.hooks.addAction( 'cred_editor_destroy_codemirror', this.destroyCodemirror, 10, this );
    Toolset.hooks.addAction( 'cred_editor_refresh_codemirror', this.refreshCodemirror, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_get_codemirror_content', this.filterGetCodemirrorContent, 10, this );

    Toolset.hooks.addAction( 'cred_editor_init_top_bar', this.initTopBar, 10, this );
    Toolset.hooks.addAction( 'cred_editor_wizard_finished', this.initTopBar, 10, this );
};

/**
 * Refresh the main editor.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.refreshContentEditor = function() {
    try{
        this.editorInstance.refresh();
        this.editorInstance.focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Focus on the main editor.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.focusContentEditor = function() {
    try{
        this.editorInstance.focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Get the main editor.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getContentEditor = function() {
    return this.editorInstance;
};

/**
 * Manage the flag for the extra editors emptyness.
 * 
 * @param {string} editorSlug 
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.nonEmptyEditorFlag = function( editorSlug ) {
    if ( '' == this.editorExtra[ editorSlug ].getValue() ) {
        $( '.js-cred-editor-container-' + editorSlug + ' .js-editor-nonempty-flag' ).fadeOut();
    } else {
        $( '.js-cred-editor-container-' + editorSlug + ' .js-editor-nonempty-flag' ).fadeIn();
    }
};


/**
 * Init main editor events.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.addEvents = function() {

    var currentInstance = this;

    /**
     * Toggle JS and CSS editors below the main content editor.
     * 
     * @since 2.1
     */
    $( document ).on( 'click', '.js-cred-editor-toggler', function() {
        var $toggler = $( this ),
            target = $( this ).data( 'target' );
        
        $toggler
            .find( '.fa.fa-caret-down, .fa.fa-caret-up' )
                .toggleClass( 'fa-caret-down fa-caret-up' );
        
        $( '.js-cred-editor-wrap-' + target ).slideToggle( 'fast', function() {
            currentInstance.editorExtra[ target ].refresh();
            currentInstance.editorExtra[ target ].focus();
        });
    });

    /**
     * Track changes in the JS and CSS editors.
     * 
     * @since 2.1
     */
    this.editorExtra.js.on( 'change', function() {
        currentInstance.nonEmptyEditorFlag( 'js' );
    });
    this.nonEmptyEditorFlag( 'js' );

    this.editorExtra.css.on( 'change', function() {
        currentInstance.nonEmptyEditorFlag( 'css' );
    });
    this.nonEmptyEditorFlag( 'css' );

    /**
     * Toggle open a notification.
     * 
     * @since 2.1
     */
    $( document ).on( 'click', '.js-cred-notification-edit', function( e ) {
        e.preventDefault();

        var $button = $( this ),
            editorIndex = $button.data( 'index' ),
            $editorRow = $( '#cred_notification_settings_row-' + editorIndex ),
            $editorPanel = $(  "#cred_notification_settings_panel-" + editorIndex );
        
        $button.hide();
        $editorRow
            .addClass( 'cred-notification-settings-row-open' )
            .find( '.js-cred-notification-close' ).show();
        $editorPanel
            .fadeIn( 'fast', function() {
                var editorId = $editorPanel
                    .find( '.js-cred-notification-body' )
                        .attr( 'id' );
                Toolset.hooks.doAction( 'cred_editor_refresh_codemirror', editorId );
            });
    });

    /**
     * Toggle close a notification.
     * 
     * @since 2.1
     */
    $( document ).on( 'click', '.js-cred-notification-close', function( e ) {
        e.preventDefault();
        
        var $button = $( this ),
            editorIndex = $button.data( 'index' ),
            $editorRow = $( '#cred_notification_settings_row-' + editorIndex ),
            $editorPanel = $(  "#cred_notification_settings_panel-" + editorIndex );

        $button.hide();
        $editorRow
            .removeClass( 'cred-notification-settings-row-open' )
            .find( '.js-cred-notification-edit' ).show();
        $editorPanel.fadeOut( 'fast', function() {});
    });

    /**
     * Refresh the editor for the message after submitting the form when opening it.
     * 
     * @since 2.1
     */
    $( document ).on( 'change', '#cred_form_success_action', function() {
        Toolset.hooks.doAction( 'cred_editor_refresh_codemirror', currentInstance.editorActionMessageSelector );
    });

};

/**
 * Init the editor page.
 * 
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.init = function() {
    this.initIclEditor();
    this.addHooks();
    this.addEvents();
};