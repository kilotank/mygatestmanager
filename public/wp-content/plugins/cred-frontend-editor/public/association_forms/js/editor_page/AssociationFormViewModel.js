Toolset.CRED.AssociationFormsEditor.viewmodels.AssociationFormViewModel = function( model, fieldActions ) {
    // private properties
    var self = this, modelPropertyToSubscribableMap = [];
    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call( self, model.toJSON(), fieldActions );

    var getModelSubObject = function(model, propertyNames) {
        // Accept a single property name as well.
        if(!_.isArray(propertyNames)) {
            propertyNames = [propertyNames];
        }

        if( propertyNames.length === 1) {
            // Same if we have an array with a single property name.
            return {
                lastModelPart: model,
                lastPropertyName: _.first(propertyNames)
            };
        } else {
            // For more than one nesting level, we'll traverse down to the last object.
            return {
                lastModelPart: _.reduce(_.initial(propertyNames), function(modelPart, propertyName) {
                    return modelPart[propertyName];
                }, model),
                lastPropertyName: _.last(propertyNames)
            };
        }
    };

    var createModelProperty = function(subscribableConstructor, model, propertyNames) {
        var modelSubObject = getModelSubObject(model, propertyNames);

        // Actually create the subscribable (observable).
        var currentValue = modelSubObject.lastModelPart[modelSubObject.lastPropertyName];

        // Beware: Sometimes, we may be passing arrays around. We need to make sure that
        // the value in subscribable and subscribable._lastPersistedValue are actually
        // two different objects. That's why JSON.parse(JSON.stringify(currentValue)).
        //
        // Details: https://stackoverflow.com/questions/597588/how-do-you-clone-an-array-of-objects-in-javascript
        var subscribable = subscribableConstructor(JSON.parse(JSON.stringify(currentValue)));

        // Make sure the subscribable will be synchronized with the model.
        Toolset.ko.synchronize(subscribable, modelSubObject.lastModelPart, modelSubObject.lastPropertyName);

        // Attach another subscribable of the same type to it, which will hold the last
        // value that was persisted to the databse.
        subscribable._lastPersistedValue = subscribableConstructor(JSON.parse(JSON.stringify(currentValue)));

        // When the subscribable changes (and only if it actually changes), update the array of changed properties
        // on this viewmodel. That will allow for sending only relevant changes to be persisted.
        subscribable.subscribe(function(newValue) {
            // We can't just use === because the value may be an array.
            if(!_.isEqual(subscribable._lastPersistedValue(), newValue)) {
                if(!_.contains(self.changedProperties(), propertyNames)) {
                    self.changedProperties.push(propertyNames);
                }
            } else {
                // If the value *became* equal again, we also need to indicate there's no need for saving anymore.
                self.changedProperties.remove(propertyNames);
            }
        });

        // When the last persisted value changes, we mirror the change in GUI (this allows the PHP part
        // to further change the stored data, e.g. generate an unique slug, etc.)
        subscribable._lastPersistedValue.subscribe(function(newPersistedValue) {
            subscribable(JSON.parse(JSON.stringify(newPersistedValue)));
            self.changedProperties.remove(propertyNames);
        });

        // This will be needed for applying the changes after persisting.
        modelPropertyToSubscribableMap.push({
            path: propertyNames,
            subscribable: subscribable
        });

        return subscribable;
    };

    var trimmedStringHasLength = function( string ){
        return jQuery.trim( string ).length > 0
    };

    var stringDontHasScript = function( string ){
        if( ! _.isString( string ) ){
            return false;
        }
        return string.match(/<script.*?>([\s\S]*?)/gmi) === null;
    };

    var stringIsValid = function( string ){
        return trimmedStringHasLength( string ) && stringDontHasScript( string );
    };

    /**
     * Initialize select2 when custom post option is selected
     * Use ajax to search for results
     */
    self.select2Init = function () {
        if( self.redirect_to() === 'custom_post' ){

            self.postSelectorRowVisiblity(true);

            jQuery('#select_post').toolset_select2({
                width: '300px',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            s: params.term, // search term
                            action: 'toolset_select2_suggest_posts_by_title',
                            wpnonce: Toolset.CRED.AssociationFormsEditor.select2nonce
                        };
                    },
                    type: 'POST',
                    processResults: function ( results ) {
                        return {
                            results: ( results.data ) ? results.data : []
                        };
                    },
                    cache: true
                },
                placeholder: 'Search for a post',
                minimumInputLength: 3,
                templateResult: function (results) {
                    if (results.loading) {
                        return results.text;
                    }
                    return results.text;
                },
                templateSelection: function (results) {
                    return results.text;
                }
            }).on('change',function () {
                self.redirect_custom_post( this.value );

                var selectedPostData = {
                    id : this.value,
                    text : jQuery(this).find("option:selected").text()
                };

                Toolset.CRED.AssociationFormsEditor.selectedPost = selectedPostData;
            });

            // In case if value is saved in DB already, append it and select
            if( Toolset.CRED.AssociationFormsEditor.selectedPost !== null ){
                var selectedOption = new Option( Toolset.CRED.AssociationFormsEditor.selectedPost.text,  Toolset.CRED.AssociationFormsEditor.selectedPost.id, true, true);
                jQuery('#select_post').append(selectedOption).trigger('change');
            }

        }

    };


    // Extend Knockout field to be required
    ko.extenders.required = function(target, overrideMessage) {
        target.hasError = ko.observable();

        function validate(newValue) {
            target.hasError( stringIsValid( newValue ) ? false : true );
        }

        validate(target());

        target.subscribe(validate);

        return target;
    };


    // Add listener for enter key when Wizard is in finish stage
    jQuery(document).on('keypress', function ( event ) {
        var keyCode = (event.which ? event.which : event.keyCode);
        if ( keyCode === 13 && self.can_submit() && self.stepFormContentVisiblity() ) {
            self.onSave();
            return false;
        }
        return true;
    });

    self.handleMessagesGet = function(){
        var $container = jQuery('div#association_form_messages'),
            $inputs = $container.find('input'),
            messages = {};

        $inputs.each(function(i){
            messages[jQuery(this).prop('name')] = jQuery(this).val();
        });

        return messages;
    };

    self.handleMessagesSet = function( messages ){

        if( null === messages ) return;

        _.each(messages, function( value, key, list ){
            if( value && jQuery('input[name="'+key+'"]').is('input') && jQuery('input[name="'+key+'"]').val() !== value ){
                jQuery('input[name="'+key+'"]').val( value );
            }
        });
    };

    // Data properties
    self.form_name = createModelProperty(ko.observable, model.toJSON(), 'form_name').extend({required : ""});
    self.relationship = createModelProperty(ko.observable, model.toJSON(), 'relationship').extend({required : ""});
    self.form_type = createModelProperty(ko.observable, model.toJSON(), 'form_type');
    self.id = createModelProperty(ko.observable, model.toJSON(), 'id');
    self.redirect_to = createModelProperty(ko.observable, model.toJSON(), 'redirect_to').extend({required : ""});
    self.ajax_submission = createModelProperty(ko.observable, model.toJSON(), 'ajax_submission');
    self.disable_comments = createModelProperty(ko.observable, model.toJSON(), 'disable_comments');
    self.slug = createModelProperty(ko.observable, model.toJSON(), 'slug');
    self.form_content = createModelProperty(ko.observable, model.toJSON(), 'form_content');
    self.post_status = createModelProperty(ko.observable, model.toJSON(), 'post_status');
    self.form_style = createModelProperty(ko.observable, model.toJSON(), 'form_style');
    self.form_script = createModelProperty(ko.observable, model.toJSON(), 'form_script');
    self.isActive = createModelProperty(ko.observable, model.toJSON(), 'isActive');
    self.changedProperties = ko.observableArray();
    self.mockOnOffSave = ko.observable(true);
    self.messages = createModelProperty(ko.observable, model.toJSON(), 'messages');
    self.currentStep = ko.observable("stepFormInstructions");

    self.handleMessagesSet( self.messages() );

    self.redirect_custom_post = createModelProperty(ko.observable, model.toJSON(), 'redirect_custom_post').extend({required : ""});
    self.redirect_posts_options = ko.observableArray();


    self.can_submit = ko.computed(function() {

        // special case when custom post is selected, we need also check is post really selected
        if( self.redirect_to() === 'custom_post'){
            return stringIsValid( self.form_name() ) && self.relationship() && self.redirect_to() && self.mockOnOffSave() &&  self.redirect_custom_post();
        } else {
            return stringIsValid( self.form_name() ) && self.relationship() && self.redirect_to() && self.mockOnOffSave();
        }
    }, self);

    // title
    self.initialPageTitle = ko.observable();
    self.pageTitle = ko.computed( function(){
        var title = '';
        if( Toolset.CRED.AssociationFormsEditor.action === 'cred_association_form_edit' ){
            title = Toolset.CRED.AssociationFormsEditor.strings.pageEditTitle;
        } else {
            title = Toolset.CRED.AssociationFormsEditor.strings.pageTitle;
        }
        self.initialPageTitle(title);
    } );

    // Hidden rows
    self.postSelectorRowVisiblity = ko.observable( false );

    // title
    self.pageTitle = ko.observable( Toolset.CRED.AssociationFormsEditor.strings.currentPageTitle );

    // messages
    self.displayedMessage = ko.observable( {text: '', type: 'info'} );
    self.messageVisibilityMode = ko.observable('remove');
    self.messageNagClass = ko.pureComputed(function () {
        switch (self.displayedMessage().type) {
            case 'error':
                return 'notice-error';
            case 'warning':
                return 'notice-warning';
            case 'info':
            default:
                return 'notice-success';
        }
    });

    self.removeDisplayedMessage = function () {
        self.messageVisibilityMode('remove');
    };

    // enable or disable wizard
    self.wizardEnabled = ko.observable( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled );

    self.extraEditors = new Toolset.CRED.AssociationFormsEditor.EditorFactory();

    self.initExtraEditor = function( data ){
        var id = data.id,
            mode = data.type;

        return self.extraEditors.setEditor( id, mode, 'cred_filter_meta_html_'+mode+'_'+id, false );
    };

    self.toggleExtraEditorsVisibility = function( object, event ){
        var $me = jQuery( event.currentTarget ),
            $open = $me.next('div.js-cred-assets-editor'),
            $caret = $me.find('i').eq(0);
            data = $me.data();

        if( !_.isObject( data ) ) return;

        if( data.open ){

            $me.data( 'open', false );

            $caret.removeClass('fa-caret-up').addClass('fa-caret-down');

            $open.slideUp(400, function(event){

            });

        } else {

            self.initExtraEditor( data );

            $me.data( 'open', true );

            $caret.removeClass('fa-caret-down').addClass('fa-caret-up');

            $open.slideDown(400, function(event){
                self.extraEditors.getEditor(data.id, data.type).refreshEditor();
            });
        }
    };


    /**
     * Redirect to options
     */

    self.checkRedirectSelection = ko.computed(function () {
        self.select2Init();
    });


    self.redirect_to.subscribe( function( selectedValue ) {
        // display post/page selector when custom_post value is selected
        if( selectedValue === 'custom_post' ){
            self.postSelectorRowVisiblity(true);
        } else {
            self.redirect_custom_post('');
            self.postSelectorRowVisiblity(false);
        }
    });



    // enable or disable wizard
    self.fullFormVisibility = ko.observable( false );
    self.wizardFormVisibility = ko.observable( false );
    self.fullFormActivate = ko.observable( false );
    self.wizardFormActivate = ko.observable( false );

    self.showCorrectTemplate = ko.computed(function(){
        if( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled ){
            self.wizardFormActivate( true );
            self.fullFormVisibility( false );
            self.wizardFormVisibility( true );
        } else {
            self.fullFormActivate( true );
            self.fullFormVisibility( true );
            self.wizardFormVisibility( false );
            _.defer( function() {
                Toolset.hooks.doAction( 'cred_editor_init_top_bar' );
            });
        }
    });

    // Wizard steps
    self.stepFormNameClass = ko.observable();
    self.stepFormSettingsClass = ko.observable();
    self.stepFormContentClass = ko.observable();
    self.stepFormMessagesClass = ko.observable();
    self.stepFormInstructionsClass = ko.observable();

    self.stepFormNameVisiblity = ko.observable();
    self.stepFormSettingsVisiblity = ko.observable();
    self.stepFormContentVisiblity = ko.observable();
    self.stepFormMessagesVisiblity = ko.observable();
    self.stepFormInstructionsVisiblity = ko.observable( true );


    self.moveToStep = function ( step, removeActiveClass ) {
        self.currentStep( step );
        // Hide all steps first
        self.stepFormNameVisiblity(false);
        self.stepFormSettingsVisiblity(false);
        self.stepFormContentVisiblity(false);
        self.stepFormMessagesVisiblity(false);
        self.stepFormInstructionsVisiblity(false);

        // show only necessary step
        setTimeout(function(){

            // make sure that functions dynamically created actually exists
            if(
                jQuery.isFunction(self[step+'Visiblity']) &&
                jQuery.isFunction(self[step+'Class'])
            ){
                self[step+'Visiblity']( true );
                self[step+'Class']('active');

                // remove active class when going back
                if( removeActiveClass !== null && jQuery.isFunction( self[removeActiveClass+'Class'] ) ){
                    self[removeActiveClass+'Class']('');
                }

            } else {
                // This is only preventing js error,
                // but if everything is fine we should not reach this point
                console.log("Function doesn't exists");
            }

        }, 700);

    };

    self.canGoToSettings = ko.computed(function(){
        if( self.currentStep( ) === 'stepFormName' ){
            return ! stringIsValid( self.form_name() );
        } else if( self.currentStep() === 'stepFormSettings' ){
            return ! self.redirect_to() && ! self.relationship()
        } else if(self.currentStep() === 'stepFormContent' ){
            return true;
        } else {
            return false;
        }

    }, self);

    self.canGoToFinish = ko.computed(function(){
        if( self.currentStep( ) === 'stepFormName' ){
            return stringIsValid( self.form_name() );
        } else if( self.currentStep() === 'stepFormSettings' ){

            // special case when custom post is selected, we need also check is post really selected
            if( self.redirect_to() === 'custom_post'){
                return self.redirect_to() && self.relationship() && self.redirect_custom_post();
            } else {
                return self.redirect_to() && self.relationship();
            }

        } else if( self.currentStep() === 'stepFormInstructions' ){
            return true;
        } else {
            return false;
        }
    }, self);

    // exit wizard
    self.showFullEditor = function ( ) {
        Toolset.CRED.AssociationFormsEditor.main.wizardEnabled = false;
        self.fullFormActivate( true );
        self.fullFormVisibility( true );
        self.wizardFormVisibility( false );
        self.updateExtraEditorsValues();
        self.updateContentFromCodeMirror('cred_association_form_content');
        Toolset.hooks.doAction( 'cred_editor_exit_wizard_mode' );
        self.select2Init();
    };


    self.updateContentFromCodeMirror = function ( editor_id ) {
        var cm_editor_content = icl_editor.codemirrorGet( editor_id ).getValue();
        self.form_content( cm_editor_content );
    };

    self.updateExtraEditorsValues = function(){
        var changed = self.extraEditors.someHasChanged();

        if( changed ){
            self.updateExtraEditorsRelatedProperties();
            self.extraEditors.resetEditors();
        }
    };

    self.updateExtraEditorsRelatedProperties = function(){
        _.each( self.extraEditors.getEditors(), function( v, i, l ){
                if( v.slug === 'css' && v.has_changed ){
                    self.form_style( v.getEditorValue() )
                } else if( v.slug === 'js' && v.has_changed ){
                    self.form_script( v.getEditorValue() );
                }
        });
    };

    /**
     * Save / Update association form
     */
    self.onSave = function(){
        var valid = true;
        self.display.isSaving(true);
        self.mockOnOffSave(false);

        if( !valid ) return;

        // update form_content manually since we are using codeMirror
        self.messages( self.handleMessagesGet() );
        self.updateContentFromCodeMirror('cred_association_form_content');
        self.updateExtraEditorsValues();
        model.updateAllProperties( JSON.parse( ko.toJSON( self ) ) );
        model.saveForm( function( updated_model, response, object, args ){

                if( updated_model.get('id') ){
                    self.id( updated_model.get('id') );
                    self.updateBrowserLocation( updated_model.get('id') );
                    self.showDeleteButton();
                }

                if( updated_model.get('slug') ){
                    var slug_for_model = (
                        _.has( response, 'data' )
                        && _.has( response.data, 'results' )
                        && _.has( response.data.results, 'slug' )
                    ) ? response.data.results.slug : updated_model.get('slug');
                    self.slug( slug_for_model );
                    self.handleMessagesSet( { slug: slug_for_model } );
                }

            self.display.isSaving(false);
            self.displayedMessage({text: response.data.results.message, type: 'info'});
            self.messageVisibilityMode('show');
            self.mockOnOffSave(true);

            // switch to full editor after saving from wizard
            if( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled === true ){
                self.showFullEditor();
            }
        }, self );
    };

    /**
     * Use pushState to update browser location to edit page after new form is created
     * @param form_id
     */
    self.updateBrowserLocation = function ( form_id ) {

        var redirectUrl = window.location.origin+window.location.pathname+'?page=cred_relationship_form&action=edit&id='+form_id;

        if( typeof (window.history.pushState) !== 'function' ){
            window.location.href = redirectUrl;
        } else {
            window.history.pushState(null, "", redirectUrl);
            self.pageTitle( Toolset.CRED.AssociationFormsEditor.strings.pageEditTitle );
        }
    };

    /**
     * Make the button to delete the form visible.
     * 
     * Note that we use the visibility property because the top bar uses show/hide on scroll
     */
    self.showDeleteButton = function() {
        jQuery( '.js-cred-delete-form' ).css( { 'visibility': 'visible' } );
    };

    /**
     * Delete
     */
    self.onDelete = function(){
        Toolset.CRED.AssociationFormsEditor.main.deleteForm(self);
    };

    self.display = {
        isActive: {
            isStatusMenuExpanded: ko.observable(false),
            lastInput: ko.observable(self.isActive()),
            applyLastInput: function() {
                self.isActive(self.display.isActive.lastInput());
                self.display.isActive.isStatusMenuExpanded(false);
                self.post_status( self.isActive() );
            },
            cancelLastInput: function() {
                self.display.isActive.lastInput(self.isActive());
                self.display.isActive.isStatusMenuExpanded(false);
            }
        },
        isSaving: ko.observable(false)
    };
};

Toolset.ko = Toolset.ko || {

    synchronize: function(subscribable, modelOrCallable, propertyName) {
        if(typeof modelOrCallable === 'function') {
            subscribable.subscribe(modelOrCallable);
        } else {
            var model = modelOrCallable;
            subscribable.subscribe(function(newValue) {
                model[propertyName] = newValue;
            });
        }
    }
};