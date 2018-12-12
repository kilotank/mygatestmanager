<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\PageExtension;

/**
 * Post form content metabox extension.
 * 
 * @since 2.1
 */
class Content {

    /**
     * Generate the user form content editor, and populate its toolbar.
     *
     * @param object $form
     * @param array $callback_args
     * 
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        // The $form WP_Post gets its content with an 'edit' filter on its content: it is HTML encoded
        $form = $form->filter( 'raw' );
        $args = toolset_getarr( $callback_args, 'args', array() );
        $extra = toolset_getarr( $args, 'extra', array() );
        $extra_js = $extra->js;
        $extra_css = $extra->css;
       ?>
       <div class="cred-editor-toolbar code-editor-toolbar js-cred-editor-toolbar js-toolset-editor-toolbar js-code-editor-toolbar" data-target="content">
       <?php
           do_action( 'cred_content_editor_print_toolbar_buttons' );
       ?>
       </div>
       <textarea style="width:100%" id="content" name="content" autocomplete="off"><?php echo esc_textarea( $form->post_content ); ?></textarea>

       <div id='cred-editor-container-css' class='cred-editor-container js-cred-editor-container js-cred-editor-container-css'>
            <h2 class="code-editor-toolbar cred-editor-toggler js-cred-editor-toggler" data-target="css">
                <i class="fa fa-caret-down icon-large fa-lg" style="margin-right:5px"></i>
                <i class="cred-icon-pushpin fa fa-thumb-tack js-editor-nonempty-flag"></i>
                <?php _e( 'CSS editor', 'wp-cred' ); ?>
            </h2>
            <div class="cred-editor-wrap js-cred-editor-wrap-css" style="display:none">
            <textarea id='cred-extra-css-editor' name='_cred[extra][css]' style="position:relative;overflow-y:auto;" class="cred-extra-css-editor" autocomplete="off"><?php echo $extra_css; ?></textarea>
            </div>    
        </div>

        <div id='cred-editor-container-js' style="border-bottom:solid 1px #ccc" class='cred-editor-container js-cred-editor-container js-cred-editor-container-js'>    
            <h2 class="code-editor-toolbar cred-editor-toggler js-cred-editor-toggler" data-target="js">
                <i class="fa fa-caret-down icon-large fa-lg" style="margin-right:5px"></i>
                <i class="cred-icon-pushpin fa fa-thumb-tack js-editor-nonempty-flag"></i>
                <?php _e( 'JS editor', 'wp-cred' ); ?>
            </h2>
            <div class="cred-editor-wrap js-cred-editor-wrap-js" style="display:none">
            <textarea id='cred-extra-js-editor' name='_cred[extra][js]' style="position:relative;overflow-y:auto;" class="cred-extra-js-editor" autocomplete="off"><?php echo $extra_js; ?></textarea>
            </div>
        </div>

       <?php
   }
}